<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MassRecurrent extends Model
{
    use SoftDeletes;
    protected $table = 'debtors_mass_recurrents';

    protected $fillable = [
        'task_id',
        'sum_indebt',
        'debtor_id',
        'status_id',
    ];
}
