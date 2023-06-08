<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\StrUtils;

class DebtorGeocode extends Model {

    protected $table = 'debtors_geocodes';
    protected $fillable = ['debtor_id', 'geocode'];
    
    /**
     * Проверяет записан ли геокод для должника в базу данных
     * @param string $debtor_id
     * @return boolean
     */
    static function geocodeExists($debtor_id) {
        $row = DebtorGeocode::where('debtor_id', $debtor_id)->first();
        if (is_null($row)) {
            return false;
        }
        
        return true;
    }
    
    static function addGeocode($debtor_id, $geocode) {
        $row = DebtorGeocode::create();
        $row->debtor_id = $debtor_id;
        $row->geocode = $geocode;
        
        $row->save();
    }
}
