<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class MassRecurrentTask extends Model {

    protected $table = 'debtors_mass_recurrents_tasks';

    protected $fillable = [
        'user_id',
        'debtors_count',
        'str_podr',
        'timezone',
        'completed'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
