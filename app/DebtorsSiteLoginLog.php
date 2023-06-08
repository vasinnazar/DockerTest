<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class DebtorsSiteLoginLog extends Model
{
    protected $table = 'debtors_site_login_log';
    protected $fillable = [
        'customer_id_1c',
        'str_podr',
        'sum_loans_debt',
        'debt_loans_count',
        'debt_group_id'
    ];
    
    
}
