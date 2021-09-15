<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Config;

class DebtorsInfo extends Model {

    protected $table = 'debtors.debtors_info';
    protected $fillable = ['debtor_id', 'last_open'];
    
    /**
     * Проверяет существует ли запись для должника и если существует - возвращает дату последнего открытия должника в АРМ
     * @param string/int $debtor_id
     * @return mixed
     */
    public static function rowExists($debtor_id) {
        $row = DebtorsInfo::where('debtor_id', $debtor_id)->first();
        if (is_null($row)) {
            return false;
        }
        
        return $row->last_open;
    }

}
