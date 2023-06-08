<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorEventPromisePay extends Model
{
    protected $table = 'debtors_events_promise_pays';
    protected $fillable = [
        'debtor_id',
        'event_id',
        'amount',
        'promise_date',
    ];
    
    
}
