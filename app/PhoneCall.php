<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhoneCall extends Model {

    protected $table = 'phone_calls';
    protected $fillable = ['comment', 'birth_date', 'call_Result', 'fio', 'telephone', 'last_date_call','customer_id_1c'];

    public function user() {
        return $this->belongsTo('\App\User');
    }

    public function subdivision() {
        return $this->belongsTo('\App\Subdivision');
    }

}
