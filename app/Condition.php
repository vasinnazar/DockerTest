<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Condition extends Model {

    protected $table = 'conditions';
    protected $guarded = ['id'];
    protected $fillable = ['name', 'condition'];

    public function loantypes() {
        return $this->belongsToMany('App\LoanType', 'loantypes_conditions', 'condition_id');
    }

}
