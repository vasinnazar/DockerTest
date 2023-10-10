<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class MassRecurrent extends Model {

    protected $table = 'debtors_mass_recurrents';

    protected $fillable = [
        'task_id',
        'debtor_id',
        'status_id'
    ];
}
