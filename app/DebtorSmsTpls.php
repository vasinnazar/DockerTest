<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

//group_id: 0-admin, 1-user,-1 - superadmin
class DebtorSmsTpls extends Model {

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
     * Возвращает шаблоны для SMS-сообщений
     * @param string $recovery_type
     * @return array
     */
    static function getSmsTpls($recovery_type, $is_ubytki = false) {
        $q = self::where('recovery_type', $recovery_type);
        if ($recovery_type == 'remote' && $is_ubytki) {
            $q->where('sort', 1);
        } else {
            $q->where('sort', null);
        }
        return $q->get();
    }
}
