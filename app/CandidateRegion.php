<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CandidateRegion extends BasicModel {

    protected $table = 'candidate_regions';
    protected $fillable = ['name', 'visible'];
    

	public static function getCandidateRegions() {
		$regions= CandidateRegion::lists('name', 'id', 'visible');
				
        return $regions->toArray();
    }

}
