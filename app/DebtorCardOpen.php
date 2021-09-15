<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorCardOpen extends Model {

    protected $table = 'debtor_card_open';
    protected $fillable = ['debtor_id', 'date_opened'];

    static public function checkOpenCard($debtor_id) {
        $now = time();        
        $open_time = DebtorCardOpen::where('debtor_id', $debtor_id)->first();
        
        if (is_null($open_time)) {
            $open_time = new DebtorCardOpen();
            $open_time->debtor_id = $debtor_id;
            $open_time->date_opened = date('Y-m-d H:i:s', $now);
            $open_time->save();
            
            return false;
        }
        
        $last_open_date = date('Y-m-d', strtotime($open_time->date_opened));
        $now_date = date('Y-m-d', $now);
        
        $open_time->date_opened = date('Y-m-d H:i:s', $now);
        $open_time->save();
        
        if ($last_open_date == $now_date) {
            return true;
        }
        
        return false;
    }
}
