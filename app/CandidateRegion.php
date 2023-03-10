<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CandidateRegion extends Model
{

    protected $table = 'candidate_regions';
    protected $fillable = ['name', 'visible'];


    public static function getCandidateRegions()
    {
        return CandidateRegion::pluck('name', 'id', 'visible')->toArray();
    }

}
