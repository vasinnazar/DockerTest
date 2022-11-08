<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailMessage extends Model
{
    protected $table = 'emails_messages';
    protected $fillable = [
        'name',
        'role_id',
        'template_message',
    ];
}
