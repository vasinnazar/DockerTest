<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoanRate extends Model {

    protected $table = 'loan_rates';
    
    static function getByDate($date=null){
        if(is_null($date)){
            $date = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        }
        return LoanRate::where('start_date','<=',$date)->orderBy('start_date','desc')->first();
    }
    static function getPSK($date=null){
        $rate = LoanRate::getByDate($date);
        return number_format($rate->pc*365, 3, ',', '');
    }
}
