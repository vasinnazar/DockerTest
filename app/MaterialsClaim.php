<?php

namespace App;

class MaterialsClaim extends BasicModel {

    protected $table = 'materials_claims';
    protected $fillable = ['data','comment','claim_date','sfp_new','sfp_old','sfp_claim','status'];
    
    public function user() {
        return $this->belongsTo('App\User');
    }
    public function subdivision() {
        return $this->belongsTo('App\Subdivision');
    }

}
