<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubdivisionGroup extends BasicModel {

    protected $table = 'subdivision_groups';
    protected $fillable = ['name', 'director'];

}
