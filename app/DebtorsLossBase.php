<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Debtor;
use Auth;

class DebtorsLossBase extends Model
{
    protected $table = 'debtors_loss_base';
    protected $fillable = ['debtor_id_1c', 'is_loaded'];
    
    /**
     * Ставит флаг загрузки ордеров для должника в Убытках
     * @return void
     */
    public static function addRecord($debtor_id_1c) {
        $row = new DebtorsLossBase();
        
        $row->debtor_id_1c = $debtor_id_1c;
        $row->is_loaded = 1;
        
        $row->save();
    }
    
    
}
