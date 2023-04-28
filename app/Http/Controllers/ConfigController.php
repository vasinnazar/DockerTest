<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use App\Utils\StrLib;
use App\Utils\DebtorsInfoUploader;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use \App\Utils\HelperUtil;
use Log;
use Storage;

class ConfigController extends BasicController {

    public function index(Request $req) {
        return view('adminpanel.config', [
            'sms_test_telephone' => '79030466344',
            'sms_servers_list' => config('admin.sms_servers_list'),
            'sms_server_active' => array_search(config('admin.sms_server'), config('admin.sms_servers_list')),
            'print_servers_list' => config('admin.print_servers_list'),
            'print_server' => config('admin.print_server'),
            'server_1c' => array_search(config('admin.server_1c'), config('admin.servers_1c_list')),
            'servers_1c_list' => config('admin.servers_1c_list'),
            'auto_change_server_1c' => config('admin.auto_change_server_1c'),
            'orders_without_1c' => config('admin.orders_without_1c'),
            'maintenance_mode' => config('admin.maintenance_mode'),
            'empty_customers' => 0,
            'toggle_kladr' => \App\Option::getByName('no_kladr', 0)
        ]);
    }

    function getEmptyCustomersNum() {
        if (config('database.connections')[config('database.default')]['database'] == 'debtors') {
            return \App\Debtor::countEmptyDebtors();
        } else {
            return 0;
        }
    }

    public function smserUpdate(Request $req) {
        if (!$req->has('sms_server')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        if ($this->updateConfig('admin', ['sms_server' => config('admin.sms_servers_list')[$req->sms_server]])) {
            return $this->backWithSuc();
        } else {
            return $this->backWithErr();
        }
    }

    public function smserCheck(Request $req) {
        $sms_server = ($req->has('sms_server')) ? config('admin.sms_servers_list')[$req->sms_server] : config('admin.sms_server');
        $telephone = ($req->has('telephone')) ? \App\StrUtils::removeNonDigits($req->telephone) : '79030466344';
        $sms = 'Тест';
        \App\Utils\SMSer::send($telephone, $sms);
        return $this->backWithSuc();
    }

    public function printServerUpdate(Request $req) {
        if (!$req->has('print_server')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        if ($this->updateConfig('admin', ['print_server' => $req->print_server])) {
            return $this->backWithSuc();
        } else {
            return $this->backWithErr();
        }
    }

    public function updateConfig($configName, $data) {
        return HelperUtil::UpdateConfig($configName, $data);
//        $config = Config::get($configName);
//        foreach ($data as $k => $v) {
//            $config[$k] = $v;
//        }
//        $filedata = var_export($config, 1);
//        if (File::put(config_path() . '/' . $configName . '.php', "<?php\n return $filedata ;")) {
//            return true;
//        } else {
//            return false;
//        }
    }

    public function killAllMysql($param) {
        $myid = with(DB::select('SELECT CONNECTION_ID()'))[0]->{'CONNECTION_ID()'};
        $result = DB::select("SHOW FULL PROCESSLIST");
        foreach ($result as $r) {
            if ($r->Id != $myid) {
                switch ($param) {
                    case 'all':
                        DB::select('KILL ' . $r->Id);
                        break;
                    case 'sleep':
                        if ($r->Command == 'Sleep') {
                            DB::select('KILL ' . $r->Id);
                        }
                        break;
                }
            }
        }
        return $this->backWithSuc();
    }

    public function getMysqlThreadsCount() {
        $mysqlThreads = DB::select("show status where `variable_name` = 'Threads_connected'");
//        MySoap::checkMysql();
        return $mysqlThreads[0]->Value;
    }

    public function modeUpdate(Request $req) {
        $data = [];
        $data['orders_without_1c'] = ($req->has('orders_without_1c') && $req->orders_without_1c) ? 1 : 0;
        $data['maintenance_mode'] = ($req->has('maintenance_mode') && $req->maintenance_mode) ? 1 : 0;
        if ($this->updateConfig('admin', $data)) {
            return $this->backWithSuc();
        } else {
            return $this->backWithErr();
        }
    }

    public function server1cUpdate(Request $req) {
        if (!$req->has('server_1c')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        $data = [
            'server_1c' => config('admin.servers_1c_list')[$req->server_1c],
        ];
        $data['auto_change_server_1c'] = ($req->has('auto_change_server_1c') && $req->auto_change_server_1c) ? 1 : 0;
        if ($this->updateConfig('admin', $data)) {
            return $this->backWithSuc();
        } else {
            return $this->backWithErr();
        }
    }

    public function syncOrders() {
        $result = \App\Order::syncOrders();
        return $result;
    }

    public function mysqlThreadsChart() {
        return view('adminpanel.mysql_stat');
    }

    public function getMysqlThreadsData(Request $req) {
        $start_date = ($req->has('start_date')) ? (new Carbon($req->start_date)) : Carbon::now()->setTime(0, 0, 0);
        if ($req->has('last_update_datetime')) {
            $start_date = new Carbon($req->last_update_datetime);
        }
        $end_date = ($req->has('end_date')) ? (new Carbon($req->end_date)) : Carbon::now()->setTime(23, 59, 59);
        if ($req->has('start_time')) {
            $start_time = explode(':', $req->start_time);
            $start_date->setTime($start_time[0], $start_time[1], 0);
        }
        if ($req->has('end_time')) {
            $end_time = explode(':', $req->end_time);
            $end_date->setTime($end_time[0], $end_time[1], 0);
        }
//        \App\MysqlThread::selectRaw()
        $res = \App\MysqlThread::where('created_at', '>', $start_date->format('Y-m-d H:i:s'))
                ->where('created_at', '<', $end_date->format('Y-m-d H:i:s'))
                ->orderBy('created_at', 'asc')
                ->groupBy(DB::raw('DATE_FORMAT (`created_at`, "%y-%m-%d-%H-%i")'))
                ->limit(5000);
//        if($req->has('showmethetruth')){
        $res->where('amount', '<', '43');
//        }
        return $res->get();
    }

    public function uploadEmptyDebtorsToArm($iteration = 0) {

        set_time_limit(0);
        
        /*$dh = \App\DebtorTransferHistory::where('row', 59310)
                ->where('responsible_user_id_1c_after', 'Еричев Сергей                                ')
                ->where('responsible_user_id_1c_before', '<>', 'Еричев Сергей                                ')
                ->get();
        
        foreach ($dh as $dhi) {
            $debtor = \App\Debtor::where('debtor_id_1c', $dhi->debtor_id_1c)->first();
            
            if ($debtor) {
                $debtor->responsible_user_id_1c = $dhi->responsible_user_id_1c_before;
                $debtor->str_podr = $dhi->str_podr_before;
                $debtor->base = $dhi->base_before;
                $debtor->fixation_date = $dhi->fixation_date_before;
                //$debtor->refresh_date = '2022-07-07 10:10:10';
                $debtor->save();
            }
        }*/
        
        /*$debtorsLog = \App\DebtorsSiteLoginLog::whereNull('responsible_user_id')->get();
        
        foreach ($debtorsLog as $d) {
            $debtor = \App\Debtor::where('customer_id_1c', $d->customer_id_1c)->where('is_debtor', 1)->first();
            if (!is_null($debtor)) {
                $u = \App\User::where('id_1c', $debtor->responsible_user_id_1c)->first();
                if (!is_null($u)) {
                    $d->responsible_user_id = $u->id;
                    
                    $d->save();
                }
            }
        }*/

        $rDate = date('Y-m-d H:i:s', time());
        
        $timezones = \App\DebtorRegionTimezone::get();
        
        foreach ($timezones as $tz) {
            if ($tz->id == 65) {
                \App\Passport::where('fact_address_region', 'like', $tz->root_word . '%')->whereNull('fact_timezone')->update(['fact_timezone' => $tz->timezone]);
                continue;
            }
            \App\Passport::where('fact_address_region', 'like', '%' . $tz->root_word . '%')->whereNull('fact_timezone')->update(['fact_timezone' => $tz->timezone]);
        }
        
        /*$de = \App\DebtorEvent::where('created_at', '>=', '2021-07-26 14:00:00')
                ->where('event_type_id', 12)
                ->where('user_id', 1913)
                ->get();
        
        foreach ($de as $ev) {
            $new_report = str_replace('10.07', '28.07', $ev->report);
            $ev->report = $new_report;
            $ev->save();
        }*/
        
        /*$debtors = \App\DebtorTransferHistory::where('transfer_time', '>=', '2022-01-14 04:00:00')->where('transfer_time', '<=', '2022-01-14 16:00:00')
                ->where('base_before', 'Б-1')
                ->where('base_after', 'Б-3')
                ->get();
        
        foreach ($debtors as $d) {
            $debtor = \App\Debtor::where('debtor_id_1c', $d->debtor_id_1c)->where('is_debtor', 1)->first();
            
            if (is_null($debtor)) {
                continue;
            }
            
            $debtor->base = 'Б-3';
            $debtor->responsible_user_id_1c = $d->responsible_user_id_1c_after;
            $debtor->str_podr = $d->str_podr_after;
            $debtor->refresh_date = date('Y-m-d H:i:s', time());
            $debtor->save();
        }*/

        
        /*$dbh = \App\DebtorTransferHistory:://where('created_at', '>=', '2021-07-02 00:00:00')->where('created_at', '<=', '2021-07-02 12:00:00')
                where('row', 45795)
                ->where('operation_user_id', 2716)
                ->where('str_podr_before', '000000000006')
                ->get();
        
        foreach ($dbh as $dbhi) {
            $debtor = \App\Debtor::where('debtor_id_1c', $dbhi->debtor_id_1c)->first();
            
            if (is_null($debtor)) {
                continue;
            }
            
            $debtor->base = $dbhi->base_before;
            $debtor->str_podr = $dbhi->str_podr_before;
            $debtor->responsible_user_id_1c = $dbhi->responsible_user_id_1c_before;
            $debtor->save();
        }*/
        
        /*$debtor_events = \App\DebtorEvent::where('event_result_id', 22)->whereNull('debtor_id')->get();
        
        foreach ($debtor_events as $event) {
            $debtor = \App\Debtor::where('debtor_id_1c', $event->debtor_id_1c)->first();
            
            if (!is_null($debtor)) {
                $event->debtor_id = $debtor->id;
                $event->save();
            }
        }*/
        
        /*$dth = \App\DebtorTransferHistory::where('row', 41298)
        //where('transfer_time', '>=', '2020-04-29 09:00:00')
                //->where('responsible_user_id_1c_before', 'Еричев Сергей                                ')
                ->get();
        
        foreach ($dth as $th) {
            $debtor = \App\Debtor::where('debtor_id_1c', $th->debtor_id_1c)->first();
            if ($debtor) {
                $debtor->responsible_user_id_1c = $th->responsible_user_id_1c_before;
                $debtor->str_podr = $th->str_podr_before;
                $debtor->base = $th->base_before;
                
                $debtor->save();
            }
        }*/

        /*if (($handle = fopen(storage_path() . '/app/debtors/zms4.csv', 'r')) !== false) {
            $cnt = 0;

            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $debtor = \App\Debtor::where('debtor_id_1c', $data[1])->first();

                if (!is_null($debtor)) {
                    $debtor->str_podr = $data[5];
                    $debtor->save();
                }
            }
        }*/

        /* $th = \App\DebtorTransferHistory::where('responsible_user_id_1c_before', 'Ооржак Л. Н.                                 ')
          ->where('responsible_user_id_1c_after', 'Ондар А. А.                                  ')
          ->get();

          foreach ($th as $thi) {
          $debtor = \App\Debtor::where('debtor_id_1c', $thi->debtor_id_1c)->first();

          $debtor->responsible_user_id_1c = 'Ондар А. А.                                  ';
          $debtor->refresh_date = '2020-04-16 13:13:13';
          $debtor->save();
          } */

        /* $debtors = \App\Debtor::where('qty_delays', 21)->where('refresh_date', '>=', '2019-09-25 08:00:00')->get();

          foreach ($debtors as $debtor) {
          $debtor_unclosed = \App\Debtor::where('customer_id_1c', $debtor->customer_id_1c)->where('is_debtor', 1)->where('qty_delays', '<=', 21)->get();
          foreach ($debtor_unclosed as $unclosed) {
          if ($debtor->id == $unclosed->id) {
          continue;
          }
          $arStopBase = [];
          if ($unclosed->base == 'Архив ЗД') {
          continue;
          }

          $unclosed->base = 'Б-1';
          $unclosed->str_podr = '000000000006';
          $unclosed->responsible_user_id_1c = $debtor->responsible_user_id_1c;
          $unclosed->refresh_date = '2019-09-25 13:13:13';

          $unclosed->save();
          }
          } */

        /* $notices = \App\NoticeNumbers::where('created_at', '>=', '2020-02-21 00:00:00')
          ->where('created_at', '<=', '2020-02-21 23:59:59')
          //->where('str_podr', '000000000006')
          ->where('str_podr', '000000000007')
          ->whereIn('user_id_1c', ['Ведущий специалист личного взыскания', 'Медведев В.В.', 'Кузнецов Д.С.', 'Иванов Н.С.                                  '])
          ->get();

          $file = fopen(storage_path() . '/app/debtors/post_registry_lv_2020_02_21.csv', 'w');

          foreach ($notices as $notice) {
          $data = [];
          $data[0] = $notice->id . '/ЛВ';
          $data[1] = date('d.m.Y', strtotime($notice->created_at));


          $debtor = \App\Debtor::where('debtor_id_1c', $notice->debtor_id_1c)->first();
          if (!is_null($debtor)) {
          $passport = \App\Passport::where('series', $debtor->passport_series)->where('number', $debtor->passport_number)->first();
          if (!is_null($passport)) {
          $data[2] = $passport->fio;
          if ($notice->is_ur_address) {
          $data[3] = \App\Passport::getFullAddress($passport);
          } else {
          $data[3] = \App\Passport::getFullAddress($passport, true);
          }
          } else {
          $data[2] = '-';
          $data[3] = '-';
          }
          }

          $data[4] = trim($notice->user_id_1c);

          fputcsv($file, $data);
          }

          fclose($file); */

//        if (($handle = fopen(storage_path() . '/app/debtors/calls_15022019.csv', 'r')) !== false) {
//            $cnt = 0;
//            
//            $file = fopen(storage_path() . '/app/debtors/calls_res_15022019.csv', 'w');
//            $arEventResults = config('debtors.event_results');
//            
//            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
//                $count_customers = \App\Customer::where('telephone', $data[10])->count();
//                if ($count_customers > 1) {
//                    $data[22] = 'дубль_номера';
//                    $data[23] = '';
//                    continue;
//                }
//                
//                $customer = \App\Customer::where('telephone', $data[10])->first();
//                if (is_null($customer)) {
//                    $data[22] = 'нет_контрагента';
//                    $data[23] = '';
//                    continue;
//                }
//                
//                $debtors = \App\Debtor::where('customer_id_1c', $customer->id_1c)->get();
//                
//                $arDebtorIds = [];
//                foreach ($debtors as $debtor) {
//                    $arDebtorIds[] = $debtor->id;
//                }
//                
//                $call_date = date('Y-m-d', strtotime($data[0]));
//                
//                $event = \App\DebtorEvent::whereIn('debtor_id', $arDebtorIds)
//                        ->where('created_at', '>=', $call_date . ' 00:00:00')
//                        ->where('created_at', '<=', $call_date . ' 23:59:59')
//                        ->where('date', '0000-00-00 00:00:00')
//                        ->orderBy('id', 'desc')
//                        ->first();
//                
//                if (is_null($event)) {
//                    $data[22] = '';
//                    $data[23] = '';
//                    continue;
//                }
//                
//                $event_result = (isset($arEventResults[$event->event_result_id])) ? $arEventResults[$event->event_result_id] : 'неизвестный_результат';
//                
//                $data[22] = $event_result;
//                $data[23] = $event->report;
//                
//                fputcsv($file, $data);
//            }
//            
//            fclose($file);
//        }
//        if (($handle = fopen(storage_path() . '/app/debtors/10-02-debtors.csv', 'r')) !== false) {
//            $cnt = 0;
//            
//            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
//                $debtor = \App\Debtor::where('id', $data[0])->first();
//                if (!is_null($debtor)) {
//
//                    $debtor->str_podr = $data[24];
//                    $debtor->responsible_user_id_1c = $data[13];
//                    $debtor->base = $data[12];
//                    
//                    $debtor->fixation_date = $data[14];
//                    $debtor->refresh_date = $data[15];
//                    
//                    $debtor->debt_group_id = $data[19];
//                    
//                    $debtor->save();
//                }
//            }
//        }
//        $debtors = \App\Debtor::whereIn('qty_delays', [19, 20, 23, 24])->where('responsible_user_id_1c', 'Яблонцев В. В.                               ')->get();
//        
//        foreach ($debtors as $debtor) {
//            $last_event = \App\DebtorEvent::where('debtor_id', $debtor->id)
//                    ->where('created_at', '>=', '2018-12-31 00:00:00')
//                    ->where('created_at', '<=', '2018-12-31 22:30:00')
//                    ->orderBy('id', 'desc')
//                    ->limit(1)
//                    ->first();
//            
//            if (is_null($last_event)) {
//                continue;
//            }
//
//            $last_resp = $debtor->responsible_user_id_1c;
//            
//            if ($debtor->responsible_user_id_1c != $last_event->user_id_1c) {
//                $debtor->responsible_user_id_1c = $last_event->user_id_1c;
//                $debtor->refresh_date = $rDate;
//                $debtor->save();
//            }
//            
//
//            $debtor_log = \App\DebtorLog::where('debtor_id', $debtor->id)->where('created_at', '>=', '2018-12-31 09:00:00')->where('created_at', '<=', '2018-12-31 23:00:00')->orderBy('id', 'desc')->limit(1)->first();
//            if (is_null($debtor_log)) {
//                continue;
//            }
//
//            //if ($debtor_log->before->responsible_user_id_1c != $debtor_log->after->responsible_user_id_1c) {
//                //$debtor->responsible_user_id_1c = $debtor_log->before->responsible_user_id_1c;
//                $debtor->base = $debtor_log->before->base;
//                //$debtor->refresh_date = $rDate;
//                $debtor->fixation_date = $debtor_log->before->fixation_date;
//                $debtor->str_podr = $debtor_log->before->str_podr;
//                $debtor->save();
//                
//            //}
//                
//            $another_debtors = \App\Debtor::where('customer_id_1c', $debtor->customer_id_1c)->get();
//            foreach ($another_debtors as $aDebtor) {
//                if ($aDebtor->responsible_user_id_1c != $debtor->responsible_user_id_1c) {
//                    $aDebtor->responsible_user_id_1c = $debtor->responsible_user_id_1c;
//                    $aDebtor->base = 'Б-1';
//                    $aDebtor->str_podr = '000000000006';
//                    $aDebtor->save();
//                }
//            }
//            $events = \App\DebtorEvent::where('debtor_id', $debtor->id)->where('created_at', '>=', '2018-10-10 00:00:00')->where('created_at', '<=', '2018-10-10 23:59:59')->get();
//            foreach ($events as $event) {
//                $event->refresh_date = $rDate;
//                $event->save();
//            }
        //}

        /* $today = with(new Carbon())->today();
          $debtors_tmp = DB::Table('debtors.debtors_tmp')->get();

          foreach ($debtors_tmp as $debtor_tmp) {
          $debtor = \App\Debtor::where('debtor_id_1c', $debtor_tmp->debtor_id_1c)->first();
          $debtor_log = \App\DebtorLog::where('debtor_id', $debtor->id)->where('user_id', 885)
          ->whereBetween('created_at', array($today->setTime(8, 0, 0)->format('Y-m-d H:i:s'), $today->setTime(11, 38, 0)->format('Y-m-d H:i:s')))
          ->orderBy('id', 'desc')
          ->limit(1)
          ->first();

          if (is_null($debtor_log)) {
          continue;
          }
          if ($debtor_log->before->responsible_user_id_1c != $debtor_log->after->responsible_user_id_1c) {
          $debtor->responsible_user_id_1c = $debtor_log->after->responsible_user_id_1c;
          $debtor->base = 'Б-1';
          $debtor->refresh_date = date('Y-m-d H:i:s', time());
          $debtor->save();

          \PC::debug([$debtor->id, $debtor_log->before->responsible_user_id_1c, $debtor_log->after->responsible_user_id_1c]);
          }
          } */

//        if (($handle = fopen(storage_path() . '/app/debtors/1002_0209.csv', 'r')) !== false) {
//
//            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
//                $debtor = \App\Debtor::where('id', $data[0])->first();
//                
//                $debtor_log = \App\DebtorLog::where('debtor_id', $data[0])->orderBy('id', 'desc')->first();
//                
//                $debtor->responsible_user_id_1c = $debtor_log->before->responsible_user_id_1c;
//                $debtor->fixation_date = $debtor_log->before->fixation_date;
//                $debtor->base = $debtor_log->before->base;
//                $debtor->str_podr = $debtor_log->before->str_podr;
//                $debtor->debt_group_id = $debtor_log->before->debt_group_id;
//                
//                $debtor->refresh_date = $debtor_log->before->refresh_date;
//                
//                $debtor->save();
//            }
//        }
//        if (($handle = fopen(storage_path() . '/app/debtors/10_02_debtors.csv', 'r')) !== false) {
//
//            $arDebtGroups = [
//                'Бесконтактный' => 4,
//                'Перспективный1' => 1,
//                'Перспективный2' => 2,
//                'Перспективный3' => 3,
//                'Сложный' => 5
//            ];
//
//            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
//                $debtor = \App\Debtor::where('customer_id_1c', $data[0])->where('loan_id_1c', $data[1])->first();
//                if (!is_null($debtor)) {
//
//                    $user = \App\User::where('login', $data[3])->first();
//                    if (!is_null($user)) {
//
//                        $debtor->responsible_user_id_1c = $user->id_1c;
//                        $debtor->fixation_date = '2019-02-10 00:00:00';
//                        $debtor->base = 'Б-1';
//                        $debtor->str_podr = '000000000006';
//                        $debtor->refresh_date = '2019-02-10 17:42:42';
//                        
//                        if (!is_null($data[2]) && mb_strlen($data[2]) > 0) {
//                            $debtor->debt_group_id = $arDebtGroups[$data[2]];
//                        }
//                        
//                        $debtor->save();
//                        
//                        \PC::debug([$debtor->id, $user->id_1c]);
//                    }
//                }
//            }
//        }
//        $debtors = \App\Debtor::where('qty_delays', 141)->get();
//        
//        foreach ($debtors as $debtor) {
//            $debtor_log = \App\DebtorLog::where('debtor_id', $debtor->id)->where('user_id', 2343)->where('created_at', '>=', '2019-02-10 08:00:06')->orderBy('id', 'desc')->first();
//            if (!is_null($debtor_log)) {
//                $debtor->responsible_user_id_1c = $debtor_log->before->responsible_user_id_1c;
//                $debtor->base = 'Б-1';
//                $debtor->fixation_date = '2019-02-10 11:02:41';
//                $debtor->refresh_date = '2019-02-10 11:02:41';
//                $debtor->str_podr = '000000000006';
//                
//                $debtor->save();
//            }
//        }
//        
//        $loans = \App\Loan::where('created_at', '>=', '2015-07-30 00:00:00')->where('created_at', '<=', '2015-07-30 23:59:59')->get();
//        
//        $i = 1;
//        foreach ($loans as $loan) {
//            $debtor = \App\Debtor::where('loan_id_1c', $loan->id_1c)->first();
//            if (is_null($debtor)) {
//                continue;
//            }
//            $debtor->uploaded = 0;
//            $debtor->save();
//            $i++;
//        }
//        
//        echo $i;
//        $debtors = \App\Debtor::where('qty_delays', 83)->get();
//        foreach ($debtors as $debtor) {
//            $event = \App\DebtorEvent::where('debtor_id', $debtor->id)->orderBy('id', 'desc')->first();
//            if (!is_null($event)) {
//                if ($event->user_id_1c != $debtor->responsible_user_id_1c) {
//                    $debtor->responsible_user_id_1c = $event->user_id_1c;
//                    $debtor->save();
//                }
//            }
//        }
//        
//        if (($handle = fopen(storage_path() . '/app/debtors/sverka_250219.csv', 'r')) !== false) {
//            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
//                $debtor = \App\Debtor::where('debtor_id_1c', $data[0])->first();
//                if (is_null($debtor)) {
//                    continue;
//                }
//                
//                $debtor->is_debtor = $data[1];
//                if ($data[1] == 0) {
//                    $debtor->od = 0;
//                    $debtor->pc = 0;
//                    $debtor->exp_pc = 0;
//                    $debtor->fine = 0;
//                    $debtor->sum_indebt = 0;
//                }
//                
//                $base = trim($data[3]);
//                $debt_group_id = ($data[4] == 'NULL') ? null : $data[4];
//                $str_podr = trim($data[5]);
//                
//                $debtor->responsible_user_id_1c = $data[2];
//                $debtor->base = $base;
//                $debtor->debt_group_id = $debt_group_id;
//                $debtor->str_podr = $str_podr;
//                $debtor->decommissioned = $data[6];
//                
//                $debtor->save();
//            }
//        }
//        set_time_limit(0);
//        
//        if (($handle = fopen(storage_path() . '/app/debtors/13.txt', 'r')) !== false) {
//            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
//                
//            }
//        }
//        $debtors = \App\Debtor::where('debt_group_id', '680')->get();
//        foreach ($debtors as $debtor) {
//            $debtor_event = \App\DebtorEvent::where('debtor_id', $debtor->id)->where('date', '0000-00-00 00:00:00')->orderBy('id', 'desc')->first();
//            if (is_null($debtor_event)) {
//                $debtor->debt_group_id = null;
//                $debtor->save();
//                continue;
//            }
//            
//            $debtor->debt_group_id = $debtor_event->debt_group_id;
//            $debtor->save();
//        }
//        if (($handle = fopen(storage_path() . '/app/debtors/13_11_debtors.csv', 'r')) !== false) {
//            
//            $arDebtGroups = [
//                'Бесконтактный' => 4,
//                'Перспективный1' => 1,
//                'Перспективный2' => 2,
//                'Перспективный3' => 3,
//                'Сложный' => 5
//            ];
//
//            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
//                $debtor = \App\Debtor::where('loan_id_1c', $data[0])->first();
//                if (!is_null($debtor)) {
//                    $user = \App\User::where('login', $data[2])->first();
//                    
//                    if (!is_null($user)) {
//                        //$old_user = $debtor->responsible_user_id_1c;
//                        //$debtor->responsible_user_id_1c = $user->id_1c;
//                        if (mb_strlen($data[1]) > 0 && isset($arDebtGroups[$data[1]])) {
//                            //$debtor->debt_group_id = $arDebtGroups[$data[1]];
//                        }
//                        $debtor->refresh_date = $rDate;
//                        $debtor->save();
//                        
//                        \PC::debug([$debtor->id]);
//                    }
//                }
//            }
//        }
//        $u = new DebtorsInfoUploader();
//        $u->uploadByFilenames(['cred_22072017.txt', 'dopnik_22072017.txt', 'factadress_22072017.txt', 'mirovoe_22072017.txt', 'passport_22072017.txt', 'rassrochka_22072017.txt', 'uradress_22072017.txt', 'zayavka_22072017.txt', 'zayavlenie_penya_22072017.txt']);
    }

    /**
     * Сохранить настройку в базу
     * @param Request $req
     * @return boolean
     */
    public function updateOption(Request $req) {
        $opt = \App\Option::where('name', $req->get('option_name'))->first();
        if (is_null($opt)) {
            $opt = new \App\Option();
            $opt->name = $req->get('option_name');
        }
        $opt->data = $req->get('option_data');
        $opt->save();
        return $this->backWithSuc();
    }

    public function makeAction(Request $req) {
        switch ($req->get('action')) {
            case 'refreshLogFile':
                return $this->refreshLogFile($req);
        }
    }

    /**
     * Обновить права на текущий лог файл
     * @param Request $req
     * @return type
     */
    function refreshLogFile(Request $req) {
        try {
            if (!Storage::exists('../logs/laravel-' . Carbon::today()->format('Y-m-d') . '.log')) {
                Log::info('Hello from refresh. Changing owner');
            }
            \App\Utils\HelperUtil::startShellProcess('chown -R apache:apache /var/www/armff.ru/storage/logs/laravel-' . Carbon::today()->format('Y-m-d') . '.log');
        } catch (Exception $ex) {
            
        }
        return $this->backWithSuc();
    }

}
