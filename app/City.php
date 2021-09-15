<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends BasicModel {

    protected $table = 'cities';
    protected $fillable = ['name', 'region_id'];

    public function region() {
        return $this->belongsTo('\App\Region');
    }

}
