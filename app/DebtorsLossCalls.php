<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Debtor;
use App\DebtorEvent;
use Auth;

class DebtorsLossCalls extends Model
{
    protected $table = 'debtors_loss_calls';
    protected $fillable = ['debtor_id_1c', 'customer_telephone', 'internal_phone'];
    
    /**
     * Добавляет запись о пропущенном звонке в инфинити
     * @return void
     */
    public static function addRecord($customer_telephone) {
        $customer = Customer::where('telephone', $customer_telephone)->first();
        if (is_null($customer)) {
            return 0;
        }
        
        $debtor = Debtor::where('customer_id_1c', $customer->id_1c)->where('is_debtor', 1)->first();
        if (is_null($debtor)) {
            return 0;
        }
        
        $rowExists = DebtorsLossCalls::where('debtor_id_1c', $debtor->debtor_id_1c)->first();
        if (!is_null($rowExists)) {
            return 0;
        }
        
        $passport = Passport::where('series', $debtor->passport_series)->where('number', $debtor->passport_number)->first();
        
        $fio = (!is_null($passport)) ? $passport->fio : 'Не Определено Имя';
        
        $user = User::where('id_1c', $debtor->responsible_user_id_1c)->first();
        if ($user) {
            $user_id = $user->id;
        } else {
            $user_id = 69;
        }
        
        $text = 'Пропущен звонок от должника <a href="/debtors/debtorcard/' . $debtor->id . '" target="_blank">' . $fio . '</a>';
        
        $row = new DebtorsLossCalls();
        $row->customer_telephone = $customer_telephone;
        $row->responsible_user_id = $user_id;
        $row->text = $text;
        //$row->internal_phone = $internal_phone;
        $row->debtor_id_1c = $debtor->debtor_id_1c;
        
        $row->save();
        
        $current_time = date('Y-m-d H:i:s', time());
        
        $event = new DebtorEvent();
        $event->date = $current_time;
        $event->customer_id_1c = $debtor->customer_id_1c;
        $event->loan_id_1c = $debtor->loan_id_1c;
        $event->event_type_id = 4;
        $event->debt_group_id = $debtor->debt_group_id;
        $event->event_result_id = 17;
        $event->report = 'Пропущен звонок от должника ' . $fio . ' ' . date('d.m.Y H:i', time()) . ', отв: ' . $user->name;
        $event->debtor_id = $debtor->id;
        $event->user_id = $user->id;
        $event->completed = 0;
        $event->debtor_id_1c = $debtor->debtor_id_1c;
        $event->user_id_1c = $user->id_1c;
        $event->refresh_date = $current_time;
        
        $event->save();
        
        return 1;
    }
}
