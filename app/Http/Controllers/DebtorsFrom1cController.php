<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Utils\PermLib;
use App\Permission;
use App\Utils\StrLib;
use Auth;
use App\Debtor;
use App\Order;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\StrUtils;
use App\DebtorEvent;
use App\Utils\HtmlHelper;
use Carbon\Carbon;
use App\Photo;
use Illuminate\Support\Facades\Storage;
use App\User;
use App\Utils;
use Image;
use App\Loan;
use App\Claim;
use App\Repayment;
use App\MySoap;
use Config;
use App\MyResult;
use Log;
use App\DebtorsInfo;

class DebtorsFrom1cController extends Controller {

    /**
     * Принимает мероприятие из 1С и записывает, либо обновляет его
     * @param Request $req
     */
    public function eventFrom1c(Request $req) {
        $input = $req->input();

        $event = DebtorEvent::where('id_1c', $input['id_1c'])->first();
        if (is_null($event)) {
            $event = new DebtorEvent();
        }

        if (!isset($input['debtor_id_1c']) || !mb_strlen($input['debtor_id_1c'])) {
            Log::error('DebtorsController eventFrom1c: не пришел код должника');
            die();
        }
        if (!isset($input['user_id_1c']) || !mb_strlen($input['user_id_1c'])) {
            Log::error('DebtorsController eventFrom1c: не пришел код пользователя');
            die();
        }
        if (!isset($input['loan_id_1c']) || !mb_strlen($input['loan_id_1c'])) {
            Log::error('DebtorsController eventFrom1c: не пришел код кредитника');
            die();
        }
        if (!isset($input['customer_id_1c']) || !mb_strlen($input['customer_id_1c'])) {
            Log::error('DebtorsController eventFrom1c: не пришел код контрагента');
            die();
        }

        $debtor = Debtor::where('debtor_id_1c', $input['debtor_id_1c'])->first();
        if (is_null($debtor)) {
            Log::error('DebtorsController eventFrom1c: не найден должник по коду', [$input]);
            die();
        }

        $user = User::where('id_1c', $input['user_id_1c'])->first();
        if (is_null($user)) {
            $user = new User();
            $user->id_1c = $input['user_id_1c'];
            $user->name = trim($input['user_id_1c']);
            $user->login = trim($input['user_id_1c']);
            $user->customer_id = 0;
            $user->subdivision_id = 113;
            $user->save();
        }

        $customer = \App\Customer::where('id_1c', $input['customer_id_1c'])->first();
        if (is_null($customer)) {
            Log::error('DebtorsController eventFrom1c: не найден контрагент по коду');
            die();
        }

        $loan = Loan::where('id_1c', $input['loan_id_1c'])->first();
        if (is_null($loan)) {
            Log::error('DebtorsController eventFrom1c: не найден контрагент по коду');
            die();
        }

        $created_at = with(new Carbon($input['created_at']))->format('Y-m-d H:i:s');

        $event->created_at = $created_at;
        $event->customer_id_1c = $customer->id_1c;
        $event->loan_id_1c = $loan->id_1c;
        $event->event_type_id = null;
        $event->debt_group_id = null;
        $event->event_result_id = null;
        if (mb_strlen($input['event_type_id'])) {
            $event->event_type_id = $input['event_type_id'];
        }
        if (mb_strlen($input['debt_group_id'])) {
            $event->debt_group_id = $input['debt_group_id'];
        }
        if (mb_strlen($input['event_result_id'])) {
            $event->event_result_id = $input['event_result_id'];
        }
        $event->report = $input['report'];
        $event->debtor_id = $debtor->id;
        $event->user_id = $user->id;
        $event->completed = 1;
        $event->id_1c = $input['id_1c'];
        $event->debtor_id_1c = $debtor->debtor_id_1c;
        $event->user_id_1c = $user->id_1c;

        $event->save();
    }

    public function loanClosing(Request $req) {
        $input = $req->input();

        if ($input['debtor_id_1c'] == 'q666666') {
            Log::error('DebtorsController loanClosingTest: не найден должник по коду', [$input]);
            die();
        }

        if (isset($input['debtor_id_1c']) && mb_strlen($input['debtor_id_1c'])) {

            $debtor = Debtor::where('debtor_id_1c', $input['debtor_id_1c'])->first();
            
            $debtor->od_after_closing = $debtor->od;
            $debtor->closed_at = date('Y-m-d H:i:s', time());

            $debtor->is_debtor = 0;
            $debtor->od = 0;
            $debtor->pc = 0;
            $debtor->exp_pc = 0;
            $debtor->fine = 0;
            $debtor->tax = 0;
            $debtor->sum_indebt = 0;
            $debtor->overpayments = $input['overpayments'];
            $debtor->qty_delays = 0;

            $debtor->base = 'Архив ЗД';
            //$debtor->responsible_user_id_1c = 'Архив ЗД';
            //$debtor->str_podr = '00000000000010';

            $debtor->save();

            $ar = [
                'customer' => $debtor->customer_id_1c,
                'debtor_id_1c' => $debtor->debtor_id_1c
            ];

            Log::info('DebtorsController loanClosing: закрытие', [$ar]);
        }
    }

    public function omicronTask(Request $req) {
        $jsonData = $req->all();

        logger('DebtorsFrom1cController omicronTask Log', [$jsonData]);

        $arData = $jsonData;

        if (is_array($arData)) {
            logger('DebtorsFrom1cController omicronTask Log1', [$arData]);
            if (isset($arData['omicron_task_id'])) {
                logger('DebtorsFrom1cController omicronTask Log2', [$arData['omicron_task_id']]);
                $omicron_task = new \App\OmicronTask();
                $omicron_task->omicron_task_id = $arData['omicron_task_id'];
                $omicron_task->result_recieved = 0;
                $omicron_task->save();
            }

            if (isset($arData['events_refused']) && is_array($arData['events_refused'])) {
                foreach ($arData['events_refused'] as $eventData) {
                    $event = DebtorEvent::where('debtor_id_1c', $eventData['debtor_id_1c'])
                            ->where('id_1c', $eventData['event_id_1c'])
                            ->first();

                    if (!is_null($event)) {
                        $now = date('Y-m-d H:i:s', time());

                        $event->completed = 1;
                        $event->refresh_date = $now;
                        $event->save();

                        $debtor = Debtor::where('debtor_id_1c', $event->debtor_id_1c)->first();
                        
                        $arReasons[1] = 'Не был отправлен на обзвон по лимиту звонков.';
                        $arReasons[2] = 'Номер уже был отправлен в задачу на обзвон сегодня.';
                        $arReasons[3] = 'Клиент закрыл договор до звонка.';
                        $arReasons[4] = 'Некорректный ответственный в мероприятии.';

                        if (!is_null($debtor)) {
                            $resp_user = User::where('id_1c', $debtor->responsible_user_id_1c)->first();
                            
                            $newEvent = new DebtorEvent();
                            $newEvent->event_type_id = 22;
                            $newEvent->debt_group_id = $debtor->debt_group_id;
                            $newEvent->report = $arReasons[$eventData['reason']];
                            $newEvent->debtor_id = $debtor->id;
                            $newEvent->user_id = (!is_null($resp_user)) ? $resp_user->id : 1029;
                            $newEvent->completed = 1;
                            $newEvent->debtor_id_1c = $debtor->debtor_id_1c;
                            $newEvent->user_id_1c = (!is_null($resp_user)) ? $resp_user->id_1c : 'Автоинформатор(Омикрон)';
                            $newEvent->refresh_date = $now;
                            $newEvent->save();
                        }
                    }
                }
            }
        }
    }

}
