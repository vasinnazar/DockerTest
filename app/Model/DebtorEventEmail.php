<?php

namespace App\Model;

use App\Debtor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtorEventEmail extends Model
{
    use SoftDeletes;
    protected $table = 'debtor_event_email';
    protected $fillable = [
        'debtor_id', 'message', 'status'
    ];
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function email()
    {
        return $this->belongsTo(Debtor::class, 'id');
    }
}
