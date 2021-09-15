<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HlrLog extends Model {
    protected $connection = 'arm';
    protected $table = 'hlr_logs';
    protected $fillable = ['telephone', 'answer', 'postclient', 'available', 'user_id', 'cost','passport_series','passport_number'];

}
