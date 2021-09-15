<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorsMessage extends Model {

    protected $table = 'debtors_messages';
    protected $fillable = ['message', 'user_id'];

    /**
     * добавить сообщение о платеже
     * @param type $debtor
     * @param type $orderMoney
     * @param type $orderDate
     * @param type $user_id
     * @return type
     */
    static function addPaymentMessage($debtor, $orderNumber, $orderMoney, $orderDate, $user_id) {
        $msg = new DebtorsMessage();
        $passport = Passport::getBySeriesAndNumber($debtor->passport_series, $debtor->passport_number);
        $msg->message = 'Поступил платеж № ' . $orderNumber . ' на ' . StrUtils::kopToRub($orderMoney) . ' рублей от '. $orderDate->format('d.m.Y') .'г. на должника <a href="' . url('debtors/debtorcard/' . $debtor->id) . '">' . $passport->fio . '</a> ';
        $msg->user_id = $user_id;
        return $msg->save();
    }
    

}
