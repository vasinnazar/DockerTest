<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

//group_id: 0-admin, 1-user,-1 - superadmin
class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{

    use Authenticatable,
        Authorizable,
        CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'login',
        'password',
        'subdivision_change',
        'subdivision_id',
        'doc',
        'begin_time',
        'end_time',
        'banned',
        'ban_at',
        'id_1c',
        'last_login',
        'phone',
        'sms_limit',
        'sms_sent',
        'position',
        'region_id',
        'user_group_id',
        'infinity_extension',
        'birth_date'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function isAdmin()
    {
        return ($this->group_id == 0 || $this->group_id == -1);
    }

    public function isSuperAdmin()
    {
        return ($this->group_id == -1);
    }

    /**
     * @return boolean
     */
    public function isDebtorsRemote()
    {
        return !is_null($this->roles()->where('role_id', Role::DEBTORS_REMOTE)->first());
    }

    /**
     * @return boolean
     */
    public function isDebtorsPersonal()
    {
        return !is_null($this->roles()->where('role_id', Role::DEBTORS_PERSONAL)->first());
    }

    public function isCC()
    {
        return ($this->subdivision_id == config('options.office_subdivision_id') && !$this->isAdmin()) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isChiefSpecialist()
    {
        return !is_null($this->roles()->where('role_id', Role::DEBTORS_CHIEF)->first());
    }

    public function subdivision()
    {
        return $this->belongsTo('App\Subdivision');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    public function subordinatedUsers()
    {
        return $this->belongsToMany(User::class, 'debtor_users_ref', 'master_user_id', 'user_id');
    }

    static function createEmptyUser($user_id_1c, $subdiv_id = null)
    {
        $user = User::where('id_1c', $user_id_1c)->first();
        if (!is_null($user)) {
            return $user;
        }
        $user = new User();
        $user->id_1c = $user_id_1c;
        $user->name = $user_id_1c;
        $user->login = $user_id_1c;
        $user->group_id = 1;
        $user->subdivision_id = $subdiv_id;
        $user->banned = 1;
        $user->last_login = '2016-01-01 00:00:00';
        if (!$user->save()) {
            return null;
        }
        return $user;
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }

    public function hasRole($name)
    {
        return ($this->roles()->where('name', $name)->count() > 0);
    }

    public function userPermissions()
    {
        return $this->hasMany(PermissionUser::class);
    }

    public function hasPermission($name)
    {
        foreach ($this->userPermissions as $userPermission) {
            /** @var PermissionUser $userPermission */
            if ($userPermission->permission->name === $name
                && (
                    $userPermission->valid_until === null
                    || $userPermission->valid_until >= Carbon::now()
                )
            ) {
                return true;
            }
        }
        foreach ($this->roles as $role) {
            if ($role->hasPermission($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Возвращает список пользователей, которые могут работать в разделе "Должники"
     * @return json
     */
    static function getDebtorRoleUsers()
    {
        $debtorUsers = User::select(array('users.id as id', 'users.login as login'))
            ->leftJoin('role_user', 'role_user.user_id', '=', 'users.id')
            ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.name', 'debtors');

        return json_encode($debtorUsers->get()->toArray());
    }

    /**
     * Проверяет не исчерпан ли лимит SMS для пользователя
     * @return boolean
     */
    public function canSendSms()
    {
        return ($this->sms_sent < $this->sms_limit);
    }

    /**
     * Инкрементирует значение отправленных SMS
     * @return void
     */
    public function increaseSentSms()
    {
        $this->sms_sent++;
        $this->save();
    }
}
