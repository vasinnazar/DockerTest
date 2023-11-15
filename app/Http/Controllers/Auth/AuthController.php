<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Spylog\Spylog;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /*
      |--------------------------------------------------------------------------
      | Registration & Login Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles the registration of new users, as well as the
      | authentication of existing users. By default, this controller uses
      | a simple trait to add these behaviors. Why don't you explore it?
      |
     */
    use  AuthenticatesUsers;

    /**
     * Create a new authentication controller instance.
     *
     * @param \Illuminate\Contracts\Auth\Guard $auth
     * @return void
     */
    public function __construct()
    {

    }

    public function login(Request $request)
    {
        return view('auth.login');
    }

    public function postLogin(Request $request)
    {
        if (Auth::attempt($request->only('login', 'password'))) {
            $user = Auth::user();

            $now = Carbon::now();
            $msg = '';
            if (with(new Carbon($user->last_login))->addDays(config('options.days_until_user_block'))->lt($now) && $user->last_login != '0000-00-00 00:00:00' && !$user->isAdmin()) {
                if (!$user->hasRole('not_blockable_user')) {
                    $user->banned = 1;
                    $msg = 'Доступ был запрещён по причине отсутствия в программе в течении ' . config('options.days_until_user_block') . ' дней.';
                }
            }
            if ($user->swapable == 1) {
                return redirect('subdivisions/swapable/change');
            }
            if ($user->banned || (!is_null($user->ban_at) && $user->ban_at != '0000-00-00' && $now->gte(new Carbon($user->ban_at)))) {
                Auth::logout();
                return redirect('/auth/login')->with('msg_err',
                    'Доступ запрещён. Обратитесь в техническую поддержку. ' . $msg)->with('class', 'alert-danger');
            }
            $begin = new Carbon($user->begin_time);
            $end = new Carbon($user->end_time);
            if ((Carbon::now()->gte($end) || Carbon::now()->lte($begin)) && !Auth::user()->id == 1) {
                Auth::logout();
                return redirect('/auth/login')->with('msg_err', 'Доступ временно запрещён')->with('class', 'alert-danger');
            }
            $user->last_login = Carbon::now();
            $user->save();

            //если спец уже сегодня сохранял учет рабочего времени и выходил, и в нем заполнена дата выхода - стирать дату выхода, 
            //чтобы в отчете по учету рабочего времени не показывало что он вышел и не зашел
            $worktime = \App\WorkTime::where('user_id', $user->id)->where('created_at', '>=',
                Carbon::today()->format('Y-m-d H:i:s'))->first();
            if (!is_null($worktime) && !empty($worktime->date_end)) {
                $worktime->date_end = null;
                $worktime->save();
            }

            Spylog::log(Spylog::ACTION_LOGIN, 'users', $user->id, $_SERVER['REMOTE_ADDR']);
            if (config('app.version_type') == 'debtors') {
                return redirect('debtors/index')
                    ->with('msg_suc', 'Добро пожаловать, ' . Auth::user()->name)
                    ->with('class', 'alert-success');
            } else {
                if (Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_SELECT,
                    \App\Utils\PermLib::SUBJ_CANDIDATE_LIST))) {
                    return redirect('candidate/index');
                }
                return redirect('home')
                    ->with('msg_suc', 'Добро пожаловать, ' . Auth::user()->name)
                    ->with('class', 'alert-success');
            }
        }
        return redirect()->back()->with('msg_err', 'Ошибка при вводе почты\\пароля')->with('class', 'alert-danger');
    }

}
