<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmsVz extends Model {

    protected $table = 'sms_vz';
    protected $fillable = ['text', 'telephone'];

}
