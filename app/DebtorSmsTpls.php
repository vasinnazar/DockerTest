<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Auth;

//group_id: 0-admin, 1-user,-1 - superadmin
class DebtorSmsTpls extends Model implements AuthenticatableContract, CanResetPasswordContract {

    use Authenticatable,
        CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'debtors.debtor_sms_tpls';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['sort'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
    
    /**
     * Возвращает шаблоны для SMS-сообщений
     * @param string $recovery_type
     * @return array
     */
    static function getSmsTpls($recovery_type, $is_ubytki = false) {
        $q = DebtorSmsTpls::where('recovery_type', $recovery_type);
        if ($recovery_type == 'remote' && $is_ubytki) {
            $q->where('sort', 1);
        } else {
            $q->where('sort', null);
        }
        $arReturn = $q->get()->toArray();
        
        return $arReturn;
    }
}
