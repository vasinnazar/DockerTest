<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Loan;
use App\Synchronizer;
use App\Debtor;
use App\User;
use Carbon\Carbon;
use App\Message;
use App\Passport;
use Log;

class FromSellingARMController extends Controller {

    /**
     * Создает сообщение для специалиста и начальников, если должник пришел на точку
     * @param Request $req
     */
    public function alertDebtorOnSubdivision(Request $req) {
        $input = $req->input();
        Log::info('alertDebtorOnSubdivision ', ['data' => $input]);

        $user = DB::Table('armf.users')->where('id', $input['user_id'])->first();
        if (!is_null($user)) {
            $spec_name = $user->login;
            $subdivision = DB::Table('armf.subdivisions')->find($user->subdivision_id);

            $subdivision_name = (!is_null($subdivision)) ? $subdivision->name : 'не определена';
            $subdivision_name_id = (!is_null($subdivision)) ? $subdivision->name_id : '-';
        } else {
            $spec_name = 'не определен';
            $subdivision_name = 'не определена';
            $subdivision_name_id = '-';
        }

        $debtor = Debtor::where('customer_id_1c', $input['customer_id_1c'])->where('loan_id_1c', $input['loan_id_1c'])->first();

        if (!is_null($debtor)) {
            // префикс в зависимости от отдела взыскания: 1 -личное/0 - удаленное
            $pfx_loan = ($input['is_debtor_personal'] == 1) ? 'pn' : 'ln';

            $msgExists = Message::where('type', $pfx_loan . $input['loan_id_1c'])->where('created_at', '>=', Carbon::now()->subMinutes(60))->first();
            if (is_null($msgExists)) {
                $passport = Passport::where('series', $debtor->passport_series)->where('number', $debtor->passport_number)->first();
                $userTo = User::where('id_1c', $debtor->responsible_user_id_1c)->first();

                $fio = (!is_null($passport)) ? $passport->fio : 'Имя Не Найдено';

                $msg = new Message();
                $msg->text = 'Должник <a href="/debtors/debtorcard/' . $debtor->id . '" target="_blank">' . $fio . '</a> пришел на точку: ' . $subdivision_name . '<br>Код подразделения: ' . $subdivision_name_id . '<br>Специалист: ' . $spec_name;
                $msg->recepient_id = $userTo->id;
                $msg->type = $pfx_loan . $input['loan_id_1c'];
                $msg->message_type = $pfx_loan;

                $msg->save();
            }
        }
    }

    public function isDebtorOnSite(Request $req) {
        $customer_id_1c = $req->get('customer_id_1c');

        if (is_null($customer_id_1c) || !mb_strlen($customer_id_1c)) {
            return 0;
        }

        $debtor = Debtor::where('customer_id_1c', $customer_id_1c)->where('is_debtor', 1)->first();

        if (!is_null($debtor)) {
            if ($debtor->str_podr == '000000000006') {
                $pfx_loan = 'sn';
            } else if ($debtor->str_podr == '000000000007') {
                $pfx_loan = 'vn';
            } else {
                return 0;
            }


            $msgExists = Message::where('type', $pfx_loan . $debtor->loan_id_1c)->where('created_at', '>=', Carbon::now()->subMinutes(1440))->first();
            if (is_null($msgExists)) {
                $passport = Passport::where('series', $debtor->passport_series)->where('number', $debtor->passport_number)->first();
                $userTo = User::where('id_1c', $debtor->responsible_user_id_1c)->first();

                $fio = (!is_null($passport)) ? $passport->fio : 'Имя Не Найдено';

                $msg = new Message();
                $msg->text = 'Должник <a href="/debtors/debtorcard/' . $debtor->id . '" target="_blank">' . $fio . '</a> зашел на сайт.';
                $msg->recepient_id = $userTo->id;
                $msg->type = $pfx_loan . $debtor->loan_id_1c;
                $msg->message_type = $pfx_loan;
                

                $msg->save();

                // добавляем мероприятие
                if ($pfx_loan == 'sn') {
                    $current_time = date('Y-m-d H:i:s', time());

                    $event = new \App\DebtorEvent();
                    $event->date = '0000-00-00 00:00:00';
                    $event->created_at = $current_time;
                    $event->customer_id_1c = $debtor->customer_id_1c;
                    $event->loan_id_1c = $debtor->loan_id_1c;
                    $event->event_type_id = 9;
                    if ($debtor->base != 'Архив компании' && $debtor->base != 'Архив убытки') {
                        $event->debt_group_id = 2;
                    }
                    $event->overdue_reason_id = 0;
                    $event->event_result_id = 17;
                    $event->report = 'Должник ' . $fio . ' зашел на сайт. ' . date('d.m.Y H:i', strtotime($msg->created_at)) . ', отв: ' . $userTo->name;
                    $event->debtor_id = $debtor->id;
                    $event->user_id = $userTo->id;
                    $event->completed = 1;
                    $event->debtor_id_1c = $debtor->debtor_id_1c;
                    $event->user_id_1c = $userTo->id_1c;
                    $event->refresh_date = $current_time;
                    $event->save();

                    if ($debtor->base != 'Архив компании' && $debtor->base != 'Архив убытки') {
                        $debtors = Debtor::where('customer_id_1c', $customer_id_1c)->where('is_debtor', 1)->get();
                        foreach ($debtors as $d) {
                            $d->debt_group_id = 2;
                            $d->save();
                        }
                        $debtor->debt_group_id = 2;
                    }
                    $debtor->refresh_date = $current_time;
                    $debtor->save();

                    $plan_event = new \App\DebtorEvent();
                    $plan_event->date = $current_time;
                    $plan_event->created_at = $current_time;
                    $plan_event->event_type_id = 6;
                    $plan_event->debtor_id = $debtor->id;
                    $plan_event->user_id = $userTo->id;
                    $plan_event->completed = 0;
                    $plan_event->debtor_id_1c = $debtor->debtor_id_1c;
                    $plan_event->user_id_1c = $userTo->id_1c;
                    $plan_event->refresh_date = $current_time;
                    $plan_event->save();
                }
            }

            return 1;
        }

        return 0;
    }

    public function withoutAcceptEvent(Request $req) {
        $customer_id_1c = $req->get('customer_id_1c', false);
        $loan_id_1c = $req->get('loan_id_1c', false);
        $amount = $req->get('amount', false);
        $card_number = $req->get('card_number', false);
        $payment_date = $req->get('payment_date', false);
        
        Log::info('FromSellingARMController withoutAcceptEvent: input ', [$req->input()]);
        
        if (!$payment_date) {
            $payment_date = date('Y-m-d H:i:s', time());
        }

        if ($customer_id_1c && $loan_id_1c && $amount) {
            $debtor = Debtor::where('customer_id_1c', $customer_id_1c)->where('loan_id_1c', $loan_id_1c)->first();

            if (!is_null($debtor)) {
                $current_time = date('Y-m-d H:i:s', time());

                $event = new \App\DebtorEvent();

                $event->date = '0000-00-00 00:00:00';
                $event->created_at = $payment_date;
                $event->customer_id_1c = $debtor->customer_id_1c;
                $event->loan_id_1c = $debtor->loan_id_1c;
                $event->event_type_id = 21;
                $event->overdue_reason_id = 0;
                $event->event_result_id = 28;

                $event->report = 'Безакцептное списание по договору ' . $loan_id_1c . ' на сумму ' . number_format($amount / 100, 2, '.', '') . ' руб. c карты ' . $card_number;

                $event->debtor_id = $debtor->id;
                $event->user_id = 1545;
                $event->completed = 1;
                $event->debtor_id_1c = $debtor->debtor_id_1c;
                $event->user_id_1c = 'Офис                                              ';
                $event->refresh_date = $current_time;
                
                $event->save();
            }
        }
        
        return 1;
    }

}
