<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Debtor;
use Auth;

class DebtorsOtherPhones extends Model
{
    protected $table = 'debtors_other_phones';
    protected $fillable = ['debtor_id_1c', 'phone', 'type'];
    
    /**
     * Сохранение телефонов
     * @return int
     */
    public static function addRecord($debtor_id_1c, $phone, $type) {
        $row = new DebtorsOtherPhones();
        
        $row->debtor_id_1c = $debtor_id_1c;
        $row->phone = $phone;
        $row->type = $type;
        
        $row->save();
    }
}
