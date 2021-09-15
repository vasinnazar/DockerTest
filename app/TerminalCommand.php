<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TerminalCommand extends Model {

    protected $table = 'terminal_commands';
    protected $fillable = ['ANSWER','isExecuted','Success'];

}
