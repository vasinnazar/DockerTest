<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Role;
use App\Permission;
use App\Utils\StrLib;
use App\Spylog\Spylog;
use App\User;
use Illuminate\Support\Facades\DB;

class RolesController extends BasicController {

    /**
     * Главная страница
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $roles = Role::all();
        $permissions = Permission::all();
        return view('adminpanel/roles/index', ['roles' => $roles,'permissions'=>$permissions]);
    }

    /**
     * Открыть форму создания роли
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $permissions = Permission::all();
        return view('adminpanel/roles/edit', ['role' => new Role(), 'title' => 'Создание', 'permissions' => $permissions]);
    }

    /**
     * Открыть форму редактирования роли
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $role = Role::find((int) $id);
        if (is_null($role)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $permissions= Permission::all();
        return view('adminpanel/roles/edit', ['role' => $role, 'title' => 'Редактирование', 'permissions' => $permissions]);
    }

    /**
     * Обновить роль
     *
     * @param  \Illuminate\Http\Request  $req
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req) {
        $role = Role::findOrNew($req->get('id'));
        $old_model = $role->toArray();
        $role->fill($req->input());
        if ($req->has('id')) {
            Spylog::logModelChange(Spylog::TABLE_ROLES, $old_model, $role->toArray());
        } else {
            Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_ROLES, $role);
        }
        $role->permissions()->sync($req->get('permission',[]));
        if ($role->save()) {
            return redirect('/adminpanel/roles/index')->with('msg_suc', StrLib::SUC);
        } else {
            return $this->backWithErr(StrLib::ERR);
        }
    }

    public function grant(Request $req) {
        if (!$req->has('user_id')) {
            return 0;
        }
        $user = User::find($req->get('user_id'));
        if (is_null($user)) {
            return 0;
        }
        $user->roles()->sync($req->get('role', []));
        return 1;
    }
    public function getUsersByRoles(int $roleId){
        $arrIdsUsers =  DB::table('role_user')->where('role_id', $roleId)->lists('user_id');
        return User::whereIn('id',$arrIdsUsers)->where('banned',0)->whereNotNull('user_group_id')->get();
    }
}
