<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NpfContract extends BasicModel {

    protected $table = 'npf_contracts';
    protected $fillable = ['passport_id', 'npf_fond_id', 'old_fio'];

    public function passport() {
        return $this->belongsTo('App\Passport');
    }

    public function npf_fond() {
        return $this->belongsTo('App\NpfFond', 'npf_fond_id');
    }

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function subdivision() {
        return $this->belongsTo('App\Subdivision');
    }

}
