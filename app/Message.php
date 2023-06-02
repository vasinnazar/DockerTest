<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends BasicModel {

    protected $table = 'messages';
    protected $fillable = [
        'text',
        'caption',
        'type',
        'user_id',
        'recepient_id',
        'message_type'
    ];

    public function user() {
        $this->belongsTo('App\User');
    }

    static function createEndMonthSfpMessage() {
        Message::create(['text' => 'Внимание! Необходимо проставить остатки по картам СФП и сделать заявку на следующий месяц по картам', 'caption' => 'Офис', 'type' => 'warning']);
    }
    /**
     * добавить сообщение о платеже
     * @param type $debtor
     * @param type $orderMoney
     * @param type $orderDate
     * @param type $user_id
     * @return type
     */
    static function addPaymentMessage($debtor, $orderNumber, $orderMoney, $orderDate, $user_id) {
        $user = User::find($user_id);
        if(is_null($user) || empty($user->password)){
            return;
        }
        $msg = new Message();
        $passport = Passport::getBySeriesAndNumber($debtor->passport_series, $debtor->passport_number);
        $msg->caption = 'Офис';
        $msg->type = 'success';
        $text = 'Поступил платеж № ' . $orderNumber . ' на ' . StrUtils::kopToRub($orderMoney) . ' рублей от '. with(new \Carbon\Carbon($orderDate))->format('d.m.Y') .'г. на должника <a ';
        if(!is_null($debtor)){
            $text .= 'href="' . config('admin.debtors_arm') . '/debtors/debtorcard/' . $debtor->id . '"';
        }
        $text.='>';
        if(!is_null($passport)){
            $text .= $passport->fio;
        } else {
            $text .= 'ФИО';
        }
        $text .= '</a>';
        $msg->text = $text;
        $msg->recepient_id = $user_id;
        return $msg->save();
    }

}
