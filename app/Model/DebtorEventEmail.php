<?php

namespace App\Model;

use App\Customer;
use App\DebtorEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtorEventEmail extends Model
{
    use SoftDeletes;
    protected $table = 'debtor_event_email';
    protected $fillable = [
        'customer_id_1c', 'event_id', 'message', 'status'
    ];
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_1c');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(DebtorEvent::class, 'id');
    }
}
