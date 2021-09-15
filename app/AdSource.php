<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdSource extends Model {
    protected $table = 'adsources';
    protected $id;
    protected $name;
    
    public function about_client(){
        return $this->belongsTo('App\about_client','adsource');
    }
}
