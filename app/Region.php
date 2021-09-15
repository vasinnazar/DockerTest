<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Region extends BasicModel {

    protected $table = 'regions';
    protected $fillable = ['name'];
    
    public function cities(){
        return $this->hasMany('\App\City');
    }

	public static function getRegions() {
        //$regions = Region::get();
		
		$regions= Region::lists('name', 'id');		
				
        return $regions->toArray();
    }

}
