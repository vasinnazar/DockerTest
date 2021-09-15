<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorUpdateHistory extends Model
{
    protected $table = 'debtors.debtor_update_history';
    protected $fillable = ['arm_loan_id'];
    
    
}
