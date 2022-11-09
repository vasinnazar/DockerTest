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
    App\Subdivision,
    Illuminate\Support\Facades\DB,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel,
    App\Utils\StrLib,
    App\NpfFond,
    App\Utils\HtmlHelper,
    Illuminate\Support\Facades\Hash;

class MassiveChangeController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        return view('adminpanel.massive_change');
    }

    public function executeChange(Request $req) {
        if (!$req->has('change_type')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        switch ($req->change_type) {
            case 0: return $this->userDocsChange($req);
            case 1: return $this->addRoleToSpecialists($req);
        }
    }

    /**
     * Загружает выбранный csv файл с фамилиями и доверенностями и меняет в базе, как в файле
     * @param Request $req
     * @return type
     */
    public function userDocsChange(Request $req) {
        $file = $req->file('file');
        $csv = array_map('str_getcsv', file($file->getPathname()));
        $found = 0;
        $changed = 0;
        $notfound = 0;
        foreach ($csv as $item) {
            if (count($item) < 2) {
                continue;
            }
            $user = User::where('name', $item[1])->first();
            if (!is_null($user)) {
                $found++;
                $was = $user->doc;
                $user->doc = $item[0] . ' от 01.12.2016 г.';
                if ($user->save()) {
                    if ($user->doc != $was) {
                        $changed++;
                    }
                }
                \PC::debug($user->name . ' ' . $user->doc);
            } else {
                $notfound++;
            }
        }
        return redirect()->back()->with('msg_suc', 'Найдено: ' . $found . '; Изменено: ' . $changed . '; Не найдено: ' . $notfound);
    }

    public function addRoleToSpecialists(Request $req) {
        if(Auth::user()->id!=5){
            return redirect()->back()->with('msg_err', StrLib::ERR_NOT_ADMIN);
        }
        $users = User::where('group_id', 1)->get();
        $granted = 0;
        foreach ($users as $user) {
            $user->roles()->sync([$req->get('role_id')]);
            $granted++;
        }
        return redirect()->back()->with('msg_suc', 'Обновлено специалистов: ' . $granted);
    }

}
