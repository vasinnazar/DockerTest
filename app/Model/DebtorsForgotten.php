<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtorsForgotten extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'debtor_id',
        'forgotten_date',
    ];

    protected $casts = [
        'forgotten_date' => 'datetime',
    ];
}
