<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    Auth,
    Illuminate\Http\Request,
    Validator,
    Storage,
    Input,
    Log,
    Carbon\Carbon,
    Illuminate\Support\Facades\Redirect,
    App\Subdivision,
    Illuminate\Support\Facades\Session;

class SubdivisionController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        return view('subdivisionchange');
    }

    public function process(Request $request) {
        $messages = array(
            'required' => 'ОШИБКА! Поле "Код подразделения" должно быть заполнено.',
        );

        $v = Validator::make($request->all(), ['sub_code' => 'required',], $messages);
        Session::put('sub_code', $request['sub_code']);
        if ($v->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($v->errors());
        }
        $subdiv = DB::table('subdivisions')->select('name', 'closed')->where('name_id', $request['sub_code'])->first();
        $red = (!is_null($subdiv)) ? $subdiv->name : NULL;

        if (empty($red)) {
            return redirect()->back()->with('error_sub', '1');
        }
        if (is_null($subdiv) || $subdiv->closed) {
            return redirect()->back()->with('error_sub', '1');
        }
        return redirect()->back()->with('Subdivision', $red);
    }

    public function update() {
        if (empty(Session::get('sub_code'))) {
            return redirect()->back()->with('error_sub', '1');
        }
        $subdivision = Subdivision::where('name_id', Session::get('sub_code'))->first();
        if (is_null($subdivision)) {
            return redirect()->back()->with('error_sub', '1');
        }
        $user = Auth::user();
        Spylog::log(Spylog::ACTION_SUBDIV_CHANGE, 'users', $user->id, ($user->subdivision_id . '->' . $subdivision->id));
        $user->subdivision_id = $subdivision->id;
        $user->subdivision_change = Carbon::now()->format('Y-m-d H:i:s');
        $user->save();
        return redirect('home');
    }

    public function getSubdivisionsFrom1c() {
//        $res1c = \App\MySoap::getSubdivisionsList();
//        Storage::put('subdivs'.(round(microtime(true) * 1000)).'.json',(string)$res1c);
//        return ['res'=>0,'msg'=>'whoa'];
//        \PC::debug($res1c);
        $file = Storage::get('subdivs.json');
        $res1c = json_decode($file, true);
        if (!array_key_exists('subdivision', $res1c)) {
            return ['res' => '0', 'msg' => ''];
        }
//        if ($res1c['res'] == 0) {
//            return 0;
//        }
        $saved = 0;
        $itemsCount = count($res1c);
        foreach ($res1c['subdivision'] as $item) {
            $subdiv = Subdivision::where('name_id', $item['name_id'])->first();
            if (is_null($subdiv)) {
                $subdiv = new Subdivision();
            }
            $subdiv->name = $item['name'];
            $subdiv->name_id = $item['name_id'];
            $subdiv->address = $item['name'];
            $subdiv->peacejudge = $item['peacejudge'];
            $subdiv->districtcourt = $item['districtcourt'];
            $subdiv->director = $item['director'];

            if (!$subdiv->save()) {
                Log::error('SubdivisionController.getSubdivisionsFrom1c Ошибка на обновлении подразделений. Обновлено ' . $saved . ' записей из ' . $itemsCount, ['subdivision' => $subdiv, 'item from 1c' => $item]);
                return ['res' => 0, 'msg' => 'Ошибка! Обновлено ' . $saved . ' записей из ' . $itemsCount];
            }
            $saved++;
        }
        return ['res' => 1, 'msg' => 'Обновлено ' . $saved . ' записей.'];
//        return ['res'=>1,'msg'=>'wow'];
    }

    public function editSubdivision($subdiv_id = null) {
        if (is_null($subdiv_id)) {
            $subdiv = new Subdivision();
        } else {
            $subdiv = Subdivision::find($subdiv_id);
            if (is_null($subdiv)) {
                return redirect()->back()->with('msg', 'Подразделение не найдено')->with('class', 'alert-danger');
            }
        }
        return view('adminpanel.subdivisionsEdit')->with('subdivision', $subdiv);
    }

    public function updateSubdivision(Request $req) {
        if ($req->has('id')) {
            $subdiv = Subdivision::find($req->id);
            if (is_null($subdiv)) {
                return redirect('adminpanel/subdivisions')->with('msg', 'Подразделение не найдено')->with('class', 'alert-danger');
            }
        } else {
            $subdiv = new Subdivision();
        }
        $subdiv->fill(Input::all());
        if ($subdiv->save()) {
            return redirect('adminpanel/subdivisions')->with('msg', 'Подразделение сохранено')->with('class', 'alert-success');
        } else {
            return redirect()->back()->with('msg', 'Подразделение не сохранено')->with('class', 'alert-danger');
        }
    }

    public function updateCities() {
//        $subdivs = Subdivision::all();
//        $num = 0;
//        foreach ($subdivs as $s) {
//            $dcrcount = \App\DailyCashReport::where('subdivision_id',$s->id)->count();
//            if($dcrcount==0){
//                $s->closed = 1;
//                $s->save();
//                $num++;
//                \PC::debug($s->toArray());
//            }
//        }
//        \PC::debug($num,'total closed');
        return redirect()->back()->with('msg_suc', 'Сохранено');
    }

    public function getAutocompleteList(Request $req) {
        $list = Subdivision::whereRaw('address like \'%' . $req->term . '%\' or name_id like \'%' . $req->term . '%\'')->distinct()->select('name as label', 'name_id', 'is_terminal', 'closed', 'id')->get();
        foreach ($list as $item) {
            $item->label = $item->label . ' (' . $item->name_id . ')';
            if($item->is_terminal){
                $item->label .= '(Т)';
            }
            if($item->closed){
                $item->label .= '(ЗАКРЫТО)';
            }
        }
        return $list;
    }

}
