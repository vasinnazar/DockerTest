<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\OmicronTask;
use App\UploadSqlFile;
use App\Utils\DebtorsInfoUploader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CronController extends Controller {

    public function __construct() {
        
    }

    public function getOmicronTask() {
        $today = date('Y-m-d', time());

        $omicron_task_today = OmicronTask::where('created_at', '>=', $today . ' 00:00:00')
                ->where('created_at', '<=', $today . ' 23:59:59')
                ->where('result_recieved', 0)
                ->first();
        

        //$omicron_task_today = OmicronTask::orderBy('id', 'desc')->where('created_at', '>=', '2022-07-10 00:00:00')->where('created_at', '<=', '2022-07-10 23:59:59')->first();

        if (is_null($omicron_task_today)) {
            exit();
        }

        if ($omicron_task_today->result_recieved == 1) {
            exit();
        }

        $postdata = [
            'username' => 'admin@pdengi.ru',
            'password' => md5('73218696'),
            'taskid' => $omicron_task_today->omicron_task_id
                //'taskid' => 23775828
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.votbox.ru/api/autocall.check.api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

        $result = curl_exec($ch);
        curl_close($ch);

        $object = simplexml_load_string($result);

        //var_dump($object);
        //exit();

        if ((string) $object->data->task["taskstatusstr"] == 'Закончена') {
            $arEventData = [];

            $events = \App\DebtorEvent::where('date', '>=', $today . ' 00:00:00')
                    ->where('date', '<=', $today . ' 23:59:59')
                    //where('date', '>=', '2022-07-10 00:00:00')
                    //->where('date', '<=', '2022-07-10 23:59:59')
                    ->where('event_type_id', 22)
                    ->where('completed', 0)
                    ->get();

            foreach ($events as $event) {
                $debtor = \App\Debtor::where('debtor_id_1c', $event->debtor_id_1c)->first();
                if (!is_null($debtor)) {
                    $customer = \App\Customer::where('id_1c', $debtor->customer_id_1c)->first();
                    if (!is_null($customer)) {
                        $arEventData[$customer->telephone] = [
                            'event_id' => $event->id,
                            'debtor_id_1c' => $debtor->debtor_id_1c
                        ];
                    }
                }
            }

            foreach ($object->data->item as $call) {
                $phone = (string) $call['phonenumber'];
                if ($phone[0] == '8') {
                    $phone[0] = '7';
                }

                if (isset($arEventData[$phone])) {
                    $now = date('Y-m-d H:i:s', time());

                    $planned_event = \App\DebtorEvent::find($arEventData[$phone]['event_id']);
                    $planned_event->completed = 1;
                    $planned_event->refresh_date = $now;
                    $planned_event->save();

                    $debtor = \App\Debtor::where('debtor_id_1c', $arEventData[$phone]['debtor_id_1c'])->first();
                    $resp_user = \App\User::where('id_1c', $debtor->responsible_user_id_1c)->first();

                    if ((string) $call['jobstatus'] == '3') {
                        $call_result = 24;
                    } else {
                        $call_result = 23;
                    }

                    $report = (string) $call['reldescr'];
                    if ($report == 'Ошибка сети') {
                        $report .= ' или абонент сбросил вызов';
                    }

                    $newEvent = new \App\DebtorEvent();
                    $newEvent->event_type_id = 22;
                    $newEvent->event_result_id = $call_result;
                    $newEvent->debt_group_id = $debtor->debt_group_id;
                    $newEvent->report = $report;
                    $newEvent->debtor_id = $debtor->id;
                    $newEvent->user_id = (!is_null($resp_user)) ? $resp_user->id : 1029;
                    $newEvent->completed = 1;
                    $newEvent->debtor_id_1c = $debtor->debtor_id_1c;
                    $newEvent->user_id_1c = (!is_null($resp_user)) ? $resp_user->id_1c : 'Автоинформатор(Омикрон)';
                    $newEvent->refresh_date = $now;
                    $newEvent->save();
                }
            }

            $omicron_task_today->result_recieved = 1;
            $omicron_task_today->save();
        }



        //echo $object['data']['task']['taskstatusstr'];
        //var_dump($object->item[0]);
    }

    public function setEventsForOmicron() {
        $str_podr = '000000000007';

        $debtors = \App\Debtor::where('is_debtor', 1)->where('debt_group_id', 6)
                ->where('str_podr', $str_podr)
                ->where('qty_delays', '>=', 100)
                ->where('qty_delays', '<=', 136)
                ->get();

        $tomorrow = date('Y-m-d H:i:s', strtotime('+1 day', date('Y-m-d H:i:s', time())));

        foreach ($debtors as $debtor) {
            $resp_user = \App\User::where('id_1c', $debtor->responsible_user_id_1c)->first();

            $newEvent = new \App\DebtorEvent();
            $newEvent->date = $tomorrow;
            $newEvent->event_type_id = 22;
            $newEvent->debt_group_id = $debtor->debt_group_id;
            $newEvent->debtor_id = $debtor->id;
            $newEvent->user_id = (!is_null($resp_user)) ? $resp_user->id : 1029;
            $newEvent->completed = 1;
            $newEvent->debtor_id_1c = $debtor->debtor_id_1c;
            $newEvent->user_id_1c = (!is_null($resp_user)) ? $resp_user->id_1c : 'Автоинформатор(Омикрон)';
            $newEvent->refresh_date = date('Y-m-d H:i:s', time());

            $newEvent->save();
        }
    }

    public function checkSqlFileForUpdate() {
        set_time_limit(0);
        
        $processExists = UploadSqlFile::where('in_process', 1)->first();

        if (!is_null($processExists)) {
            return 0;
        }

        $newProcesses = UploadSqlFile::where('completed', 0)->orderBy('id', 'asc')->get();

        if (is_null($newProcesses) || count($newProcesses) < 1) {
            return 0;
        }

        foreach ($newProcesses as $newProcess) {
            $path = 'debtors/' . $newProcess->filename;
            if (!Storage::disk('ftp')->has($path)) {
                continue;
            }

            if ($newProcess->filetype == 1) {
                $newProcess->in_process = 1;
                $newProcess->save();

                DB::unprepared(Storage::disk('ftp')->get($path));

                $newProcess->in_process = 0;
                $newProcess->completed = 1;
                $newProcess->save();
            }

            if ($newProcess->filetype == 2) {
                $newProcess->in_process = 1;
                $newProcess->save();

                $uploader = new DebtorsInfoUploader();
                $res = $uploader->updateClientInfo($newProcess->filename);

                $newProcess->in_process = 0;
                $newProcess->completed = 1;
                $newProcess->save();
            }
        }

        return 1;
    }

}
