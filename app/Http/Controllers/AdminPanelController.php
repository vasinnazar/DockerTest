<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Auth,
    Input,
    Validator,
    Session,
    Redirect,
    App\Loan,
    App\Card,
    App\User,
    App\DebtorUsersRef,
    App\Subdivision,
    App\UsersRegions,
    Illuminate\Support\Facades\DB,
    Yajra\Datatables\Facades\Datatables,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel,
    App\Utils\StrLib,
    App\NpfFond,
    App\Utils\HtmlHelper,
    Illuminate\Support\Facades\Hash;

class AdminPanelController extends Controller {

    public function __construct() {
//        $this->middleware('auth');
    }

    public function index() {
        return view('adminpanel.main');
//        return view('adminpanel.main')->with('today_orders',DB::raw());
    }

    public function getUsers() {
        return view('adminpanel.users', [
            'users_regions' => UsersRegions::getRegions(),
            'subdivisions' => \App\Subdivision::where('closed', '<>', 1)->pluck('name'),
            'roles' => \App\Role::all(),
			'permissions' => \App\Permission::where('name', 'like', 'show.%')->get()
        ]);
    }

    public function changeSubdivision(Request $req) {
        if (!$req->has('user_id') || !$req->has('subdivision_id')) {
            return 0;
        }
        $user = User::find($req->get('user_id'));
        if (is_null($user)) {
            return 0;
        }
        $user->subdivision_id = $req->get('subdivision_id');
        if ($user->save()) {
            Spylog::log(Spylog::ACTION_SUBDIV_CHANGE, 'users', $user->id, ($user->subdivision_id . '->' . $req->get('subdivision_id')));
            return 1;
        } else {
            return 0;
        }
    }

    public function getUserLog(Request $req = null, $user_id = null) {
        if (!is_null($req) && !$req->has('user_id')) {
            return null;
        }
        $user_id = (!is_null($user_id)) ? $user_id : $req->user_id;
        $from = (is_null($req) || is_null($req->from)) ? Carbon::now() : new Carbon($req->from);
        $from = $from->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $to = (is_null($req) || is_null($req->to)) ? Carbon::now() : new Carbon($req->to);
        $to = $to->setTime(23, 59, 59)->format('Y-m-d H:i:s');
//        $logs['login'] = DB::connection('spylogsDB')->table('spylogs')
//                ->select(DB::raw('MIN(created_at) as created_at'))
//                ->where('user_id', $user_id)
//                ->where('action', Spylog::ACTION_LOGIN)
//                ->whereBetween('created_at', [ $from, $to])
//                ->groupBy(DB::raw('YEAR(created_at)'))
//                ->groupBy(DB::raw('MONTH(created_at)'))
//                ->groupBy(DB::raw('DAY(created_at)'))
//                ->orderBy('created_at')
//                ->get();
//        $logs['logout'] = DB::connection('spylogsDB')->table('spylogs')
//                ->select(DB::raw('MAX(created_at) as created_at'))
//                ->where('user_id', $user_id)
//                ->where('action', Spylog::ACTION_LOGOUT)
//                ->whereBetween('created_at', [ $from, $to])
//                ->groupBy(DB::raw('YEAR(created_at)'))
//                ->groupBy(DB::raw('MONTH(created_at)'))
//                ->groupBy(DB::raw('DAY(created_at)'))
//                ->orderBy('created_at')
//                ->get();
        $logs['login'] = [];
        $logs['logout'] = [];

        return $logs;
    }

    public function getUser($user_id) {
        $user = User::find($user_id);
        $user->subdivision_name = ($user->subdivision != null) ? $user->subdivision->name : '';
        $data = [
            'user' => $user,
            'passports' => \App\Passport::where('customer_id', $user->customer_id)->select('id')->get(),
            'logs' => $this->getUserLog(null, $user_id),
            'roles' => $user->roles,
            'debtorRoleUsers' => json_encode([]),
            'debtorUserSlaves' => json_encode([]),
            'hasDebtorRole' => $user->hasRole('debtors')
        ];
        if (config('app.version_type') != 'sales') {
            $data['debtorRoleUsers'] = User::getDebtorRoleUsers();
            $data['debtorUserSlaves'] = DebtorUsersRef::getDebtorSlaveUsers($user_id);
        }
        return $data;
    }

    public function getUsersList(Request $request) {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        $cols = ['name', 'id'];
        $users = DB::table('users')->select('name', 'id')->orderBy('name');
        return Datatables::of($users)
            ->editColumn('name', function ($user) {
                return '<div style="cursor:pointer" '
                    . 'onclick="$.adminCtrl.viewUser(' . $user->id . ',this); return false;">'
                    . $user->name . '</div>';
            })
            ->removeColumn('id')
            ->filter(function ($query) use ($request) {
                if ($request->has('name')) {
                    $query->where('name', 'like', "%{$request->get('name')
                                        }%");
                }
            })
            ->setTotalRecords(1000)
            ->make();
    }

    public function updateUser(Request $request) {
        $user = User::find($request->user_id);

        if (is_null($user)) {
            return 0;
        }
        Spylog::logModelChange('users', $user, $request->all());
        $user->fill($request->all());

        return($user->save()) ? 1 : 0;
    }

    public function createCustomer($id) {
        $user = User::find($id);
        if (!is_null($user)) {
            if (is_null($user->customer_id)) {
                DB::beginTransaction();
                $customer = new \App\Customer();
                $customer->creator_id = Auth::user()->id;
                if (!$customer->save()) {
                    DB::rollback();
                    return 0;
                }
                $passport = new \App\Passport();
                $passport->fio = $user->name;
                $passport->customer_id = $customer->id;
                if (!$passport->save()) {
                    DB::rollback();
                    return 0;
                }
                $user->customer_id = $customer->id;
                if (!$user->save()) {
                    DB::rollback();
                    return 0;
                }
                DB::commit();
                return ['customer_id' => $customer->id, 'passport_id' => $passport->id];
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function changePassword(Request $req) {
        if (!$req->has('password') || !$req->has('old_password') || !$req->has('user_id')) {
            return 0;
        }
        $user = User::find($req->user_id);
        if (is_null($user)) {
            return 0;
        }
        if (!config('app.dev')) {
            if (!in_array(Auth::user()->id, [1, 5, 69])) {
                if (!is_null($user->password) && $user->password != '' && !Hash::check($req->old_password, $user->password)) {
                    return 0;
                }
            }
        }
        $user->fill(['password' => Hash::make($req->password)]);
        Spylog::log(Spylog::ACTION_UPDATE, 'users', $user->id, 'Изменён пароль');
        return($user->save()) ? 1 : 0;
    }

    public function updateUserBantime(Request $request) {
        $user = User::find($request->user_id);

        if (is_null($user)) {
            return 0;
        }
        $data = [
            'ban_at' => $request->ban_at,
            'banned' => ($request->has('banned')) ? 1 : 0,
//            'ban_http' => ($request->has('ban_http')) ? 1 : 0,
            'begin_time' => $request->begin_time,
            'end_time' => $request->end_time
        ];
        //сбрасывает дату последнего визит при разблокировке пользователя
        if ($user->banned == 1 && $data['banned'] == 0) {
            $user->last_login = Carbon::now()->format('Y-m-d H:i:s');
        }
        if ($data['banned'] == 1) {
            $user->remember_token = null;
        }
        Spylog::logModelChange('users', $user, $data);
        $user->fill($data);
        return ($user->save()) ? 1 : 0;
    }

    public function refreshUserLastLogin($user_id) {
        $user = User::find($user_id);
        if (!is_null($user)) {
            $last_login = $user->last_login;
            $user->last_login = Carbon::now()->format('Y-m-d H:i:s');
            if ($user->save()) {
                Spylog::log(Spylog::ACTION_LASTLOGIN_REFRESH, 'users', $user_id, $last_login . '->' . $user->last_login);
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function addUser() {
        $ts = Carbon::now()->timestamp;
        $user = User::create([
                    'name' => 'Новый пользователь ' . $ts,
                    'login' => $ts,
                    'password' => Hash::make('1')
        ]);
        Spylog::logModelAction(Spylog::ACTION_CREATE, 'users', $user);
        return $user;
    }

    public function getSubdivisionsList(Request $request) {
        $subdivisions = Subdivision::select('id', 'name_id', 'name', 'closed');
        return Datatables::of($subdivisions)
            ->editColumn('name', function ($subdiv) {
                return ($subdiv->closed) ? ('<s>' . $subdiv->name . '</s>') : $subdiv->name;
            })
            ->addColumn('actions', function ($s) {
                $html = '';
                $html .= HtmlHelper::Buttton(url('adminpanel/subdivisions/edit/' . $s->id),
                    ['size' => 'sm', 'glyph' => 'pencil']);
                if (!$s->closed) {
                    $html .= HtmlHelper::Buttton(url('adminpanel/subdivisions/close/' . $s->id),
                        ['text' => 'Закрыть', 'size' => 'sm']);
                } else {
                    $html .= HtmlHelper::Buttton(null, ['disabled' => true, 'size' => 'sm']);
                }
                $html .= HtmlHelper::Buttton(url('adminpanel/cashbook/' . $s->id), ['size' => 'sm', 'glyph' => 'book']);
                $html .= HtmlHelper::Buttton(url('orders/' . $s->id),
                    ['size' => 'sm', 'glyph' => 'file', 'text' => ' Ордеры']);
                return $html;
            })
            ->removeColumn('closed')
            ->removeColumn('id')
            ->filter(function ($query) use ($request) {
                if ($request->has('name_id')) {
                    $query->where('name_id', 'like', "%" . $request->get('name_id') . "%");
                }
                if ($request->has('name')) {
                    $query->where('name', 'like', "%" . $request->get('name') . "%");
                }
            })
            ->setTotalRecords(1000)
            ->make();
    }

    public function subdivisionsList() {
        return view('adminpanel.subdivisions');
    }

    public function closeSubdivision($id) {
        $subdiv = Subdivision::find($id);
        if (is_null($subdiv)) {
            return redirect()->back()->with('msg_err', StrLib::$ERR_NULL);
        }
        $subdiv->closed = true;
        if ($subdiv->save()) {
            Spylog::log(Spylog::ACTION_UPDATE, 'subdivisions', $id, 'Закрыто');
            return redirect()->back()->with('msg_suc', StrLib::$SUC);
        } else {
            return redirect()->back()->with('msg_err', StrLib::$ERR);
        }
    }

    public function getCashbook($id) {
        $cashbook = \App\Cashbook::where('subdivision_id', $id)->get();
        Spylog::log(Spylog::ACTION_OPEN, 'cashbook', null, 'subdivision_id=' . $id);
        return view('adminpanel.cashbook', ['cashbook' => $cashbook]);
    }

    public function removeOrder($id) {
        $order = \App\Order::find($id);
        if (is_null($order)) {
            return redirect()->back()->with('msg_err', StrLib::$ERR);
        }
        if ($order->delete()) {
            return redirect()->back()->with('msg_suc', StrLib::$SUC);
        } else {
            return redirect()->back()->with('msg_err', StrLib::$ERR);
        }
    }

    public function getNpfFondsList() {
        return view('adminpanel.npffonds')->with('npf_fonds', NpfFond::all());
    }

    public function editNpfFond(Request $req) {
        if ($req->has('id')) {
            $item = NpfFond::find($req->id);
            if (is_null($item)) {
                return redirect()->back()->with('msg_err', StrLib::$ERR_NULL);
            }
        } else {
            $item = new NpfFond();
        }
        return view('adminpanel.npffond_edit')->with('item', $item)->with('contract_forms', \App\ContractForm::pluck('name', 'id'));
    }

    public function updateNpfFond(Request $req) {
        $item = NpfFond::findOrNew($req->get('id'));
        $item->fill($req->all());
        if ($item->save()) {
            return redirect('adminpanel/npffonds')->with('msg_suc', StrLib::$SUC_SAVED);
        } else {
            return redirect()->back()->with('msg_err', StrLib::$ERR);
        }
    }

    public function removeNpfFond(Request $req) {
        if ($req->has('id')) {
            $fond = NpfFond::find($req->id);
            if (is_null($fond)) {
                return redirect()->back()->with('msg_err', StrLib::$ERR_NULL);
            }
            if ($fond->delete()) {
                return redirect()->back()->with('msg_suc', StrLib::$SUC);
            } else {
                return redirect()->back()->with('msg_err', StrLib::$ERR);
            }
        }
    }

    public function problemSolverMain(Request $req) {
        return view('adminpanel.problemsolver');
    }

    public function saveDebtorUserSlaves(Request $req) {
        if (DebtorUsersRef::saveRefs($req->input())) {
            return 1;
        }

        return 0;
    }

    public function createUserCertificate(Request $req) {
        $user = User::find($req->get('user_id'));
        if(is_null($user)){
            return redirect()->back()->with('msg_err', 'Ошибка! Пользователь не найден');
        }
        $clientFilePath = \App\Utils\SslUtil::createUserCertificate($user);
        if ($clientFilePath === FALSE) {
            return redirect()->back()->with('msg_err', 'Ошибка при создании сертификата.');
        }
        return response()->download($clientFilePath);
    }

}
