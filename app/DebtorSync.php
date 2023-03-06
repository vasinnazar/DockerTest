<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorSync extends Model
{
    protected $table = 'debtor_sync_sql';

    protected $fillable = [
        'sql_command',
        'file_id',
        'deleted_at',
    ];
}
