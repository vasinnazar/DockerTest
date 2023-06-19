<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subdivision extends Model
{
    protected $fillable = [
        'name_id',
        'name',
        'address',
        'peacejudge',
        'districtcourt',
        'director',
        'is_terminal',
        'city',
        'city_id',
        'allow_use_new_cards',
        'closed',
        'is_lead',
        'working_times',
    ];
    protected $table = 'subdivisions';

    public function group()
    {
        return $this->belongsTo('App\SubdivisionGroup');
    }

    public function users()
    {
        return $this->hasMany('App\User');
    }

    public function getCity()
    {
        return $this->belongsTo('App\City', 'city_id');
    }

    static function getDirectorsList($withEmpty = false)
    {
        $res = Subdivision::groupBy('director')->pluck('director')->toArray();
        if ($withEmpty) {
            array_unshift($res, '');
        }
        return $res;
    }
}
