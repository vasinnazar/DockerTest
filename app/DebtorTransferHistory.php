<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Debtor;
use Auth;

class DebtorTransferHistory extends Model
{
    protected $table = 'debtors_transfer_history';
    protected $fillable = ['operation_user_id', 'debtor_id_1c'];
    
    public function addRecord() {
        
    }
}
