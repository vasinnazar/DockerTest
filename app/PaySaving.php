<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaySaving extends BasicModel {

    protected $table = 'pay_savings';
    protected $fillable = ['pc', 'od', 'exp_pc', 'fine', 'created_at', 'customer_id', 'loan_id', 'repayment_id', 'plus'];

    public function loan() {
        return $this->belongsTo('App\Loan');
    }

    public function repayment() {
        return $this->belongsTo('App\Repayment');
    }

}
