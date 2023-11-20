<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MassRecurrentTask extends Model
{
    const COMPLETED = 1;
    const NOT_COMPLETED = 0;

    protected $table = 'debtors_mass_recurrents_tasks';

    protected $fillable = [
        'user_id',
        'debtors_count',
        'debtors_processed',
        'str_podr',
        'qty_delays_from',
        'qty_delays_to',
        'timezone',
        'completed'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
