<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UpdateDebtors extends Model
{
    protected $table = 'update_debtors';

    protected $fillable = [
        'sql_command',
        'file_id',
        'deleted_at',
    ];
}
