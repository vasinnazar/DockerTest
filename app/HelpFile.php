<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HelpFile extends Model {

    const T_PDF = 0;
    const T_VIDEO = 1;
    const T_LINK = 2;
    const T_HTML = 3;

    protected $table = 'help_files';
    protected $fillable = ['url', 'name', 'data'];
    
}
