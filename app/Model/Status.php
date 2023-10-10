<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    const NEW_SEND = 1;
    const IN_PROCESS = 2;
    const SUCCESS = 3;
    const FAILED = 4;

    public $timestamps = false;
    protected $table = 'statuses';
    protected $fillable = [
        'id', 'name'
    ];

}