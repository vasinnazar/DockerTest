<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkTime extends BasicModel {

    protected $table = 'work_times';
    protected $fillable = ['comment', 'evaluation', 'review', 'reason'];

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function subdivision() {
        return $this->belongsTo('App\Subdivision');
    }

}
