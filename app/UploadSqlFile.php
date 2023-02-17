<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UploadSqlFile extends Model {

    protected $fillable = [
        'filetype','filename','in_process','completed'
    ];
    protected $table = 'upload_sql_files';

}
