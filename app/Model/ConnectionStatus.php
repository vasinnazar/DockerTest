<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ConnectionStatus extends Model
{
    const AO = 'АО';

    public $timestamps = false;

    protected $fillable = [
        'id', 'name'
    ];

}