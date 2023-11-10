<?php

namespace App\Model;

use App\Customer;
use App\Debtor;
use App\DebtorEvent;
use App\DebtorSmsTpls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtorEventSms extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'sms_id',
        'customer_id_1c',
        'debtor_id',
        'debtor_base'
    ];

    public function sms(): BelongsTo
    {
        return $this->belongsTo(DebtorSmsTpls::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(DebtorEvent::class);
    }

    public function debtor(): BelongsTo
    {
        return $this->belongsTo(Debtor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id_1c', 'id_1c');
    }
}