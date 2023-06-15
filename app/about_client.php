<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class about_client extends Model {

    protected $table = 'about_clients';
    protected $fillable = ['customer_id', 'sex', 'goal', 'zhusl', 'deti', 'fiosuprugi',
        'fioizmena', 'avto', 'telephonehome', 'innorganizacia', 'dolznost',
        'vidtruda', 'fiorukovoditel', 'adresorganiz', 'credit', 'dohod', 'dopdohod', 'stazlet',
        'adsource', 'pensionnoeudost', 'telephonerodstv', 'stepenrodstv', 'obrasovanie', 'pensioner', 'postclient',
        'armia', 'poruchitelstvo', 'zarplatcard', 'organizacia', 'telephoneorganiz',
        'drugs', 'alco', 'stupid', 'badspeak', 'pressure', 'dirty', 'smell', 'badbehaviour', 'soldier', 'watch', 'other',
        'anothertelephone', 'marital_type_id','other_mfo','other_mfo_why',
        'recomend_phone_1','recomend_fio_1',
        'recomend_phone_2','recomend_fio_2',
        'recomend_phone_3','recomend_fio_3',
        'dohod_husband','pension'
        ,'email'
        ];

    public function customer() {
        return $this->belongsTo('App\Customer', 'customer_id');
    }

    public function liveCondition() {
        return $this->belongsTo('App\LiveCondition', 'zhusl');
    }
    public function educationLevel(){
        return $this->belongsTo('App\EducationLevel', 'obrasovanie');
    }
    public function maritalType(){
        return $this->belongsTo('App\MaritalType', 'marital_type_id');
    }
    public function relationDegree(){
        return $this->belongsTo('App\Stepenrodstv', 'stepenrodstv');
    }

//    public function zhusl(){
//        return $this->morphOne('App\LiveCondition', 'zhusl');
//    }
//    public function adsource(){
//        return $this->hasOne('App\AdSource','adsource','id');
//    }
}
