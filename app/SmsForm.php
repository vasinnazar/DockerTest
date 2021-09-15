<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmsForm extends Model
{
    protected $table='sms_forms';
    protected $fillable = ['template'];
}
