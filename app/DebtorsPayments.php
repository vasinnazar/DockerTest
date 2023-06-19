<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class DebtorsPayments extends Model {
//    protected $connection = 'arm';
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'debtors_payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['date', 'responsible_user_id_1c', 'money', 'customer_id_1c'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
    
    /**
     * Проверяет есть ли уже платеж в БД
     * @param type $date
     * @param type $customer_id_1c
     * @param type $money
     * @return boolean
     */
    public static function paymentExists($date, $customer_id_1c, $money) {
        
        $debtorPayment = DebtorsPayments::where('date', $date)
                ->where('customer_id_1c', $customer_id_1c)
                ->where('money', $money)
                ->first();
        
        if (!is_null($debtorPayment)) {
            return true;
        }
        
        return false;
    }
}
