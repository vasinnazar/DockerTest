<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtorEventSms extends Model
{
    use SoftDeletes;

    protected $fillable = [
      'event_id',
      'customer_id',
      'sms_id'
    ];

    public function sms(): BelongsTo
    {
        return $this->belongsTo(DebtorSmsTpls::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(DebtorEvent::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
