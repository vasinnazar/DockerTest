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

class PermissionsController extends BasicController {

    /**
     * Главная страница
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $roles = Role::all();
        return view('adminpanel/roles/index', ['roles' => $roles]);
    }

    /**
     * 
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        return view('adminpanel/roles/edit', ['role' => new Permission(), 'title' => 'Создание']);
    }

    /**
     * Открыть форму редактирования разрешения
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $role = Role::find((int) $id);
        if (is_null($role)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        return view('adminpanel/roles/edit', ['role' => $role, 'title' => 'Редактирование']);
    }

    /**
     * Обновить разрешение
     *
     * @param  \Illuminate\Http\Request  $req
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req) {
        $permission = Permission::findOrNew($req->get('id'));
        $name = Permission::makeName($req->get('action'), $req->get('subject'), $req->get('condition'), $req->get('time'));
        $isNew = (is_null($permission->id));
        if (Permission::where('name', $name)->count() > 0 && $isNew) {
            return ($req->has('ajax')) ? ['result' => 0] : $this->backWithErr(StrLib::ERR);
        }
        $old_model = $permission->toArray();
        $permission->name = $name;
        $permission->description = $req->get('description', '');
        DB::beginTransaction();
        if ($isNew) {
            Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_PERMISSIONS, $permission);
        } else {
            Spylog::logModelChange(Spylog::TABLE_PERMISSIONS, $old_model, $permission->toArray());
        }
        if ($permission->save()) {
            //каждое новое разрешение автоматом добавлять админу
            if($isNew){
                $role = Role::where('name',Role::ADMIN)->first();
                if(!is_null($role)){
                    $role->permissions()->attach($permission->id);
                }
            }            
            DB::commit();
            return ($req->has('ajax')) ? ['result' => 1, 'permission' => $permission->toArray()] : redirect('/adminpanel/permissions/index')->with('msg_suc', StrLib::SUC);
        } else {
            DB::rollback();
            return ($req->has('ajax')) ? ['result' => 0] : $this->backWithErr(StrLib::ERR);
        }
    }

    public function grant(Request $req) {
        if (!$req->has('role_id')) {
            return 0;
        }
        $role = Role::find($req->get('role_id'));
        if (is_null($role)) {
            return 0;
        }
        $role->permissions()->sync($req->get('permission', []));
        return 1;
    }
	
	public function grantToUser(Request $request)
    {
        if (!$request->has('user_id')) {
            return 0;
        }
        $user = User::find($request->get('user_id'));
        if (is_null($user)) {
            return 0;
        }
        $permissions = $request->get('permission', []);
        $syncData = [];
        foreach ($permissions as $permission) {
            $date = $request->get('date' . $permission);
            $date = empty($date) ? null : Carbon::createFromFormat('Y-m-d\TH:i', $date);
            $syncData[$permission] = [
                'valid_until' => $date
            ];
        }
        $user->permissions()->sync($syncData);
        return 1;
    }

    public function destroy($id) {
        $perm = Permission::find($id);
        if (is_null($perm)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        if ($perm->delete()) {
            return $this->backWithSuc();
        } else {
            return $this->backWithErr();
        }
    }

    public function getPermission($id) {
        $perm = Permission::find($id);
        if (is_null($perm)) {
            return ['result' => 0];
        }
        $p = $perm->toArray();
        $p = array_merge($p, $perm->getNameArray());
        return ['result' => 1, 'permission' => $p];
    }

}
