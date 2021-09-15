<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Terminal extends BasicModel {

    protected $table = 'terminals';
    protected $fillable = ['HardwareID', 'is_locked', 'description', 'password', 'bill_count', 'bill_cash', 'dispenser_count', 'address', 'user_id',
        'DispenserStatus', 'stWebcamStatus', 'stValidatorStatus', 'stPrinterStatus', 'stScannerStatus'];

    public function user() {
        return $this->belongsTo('App\User');
    }

}
