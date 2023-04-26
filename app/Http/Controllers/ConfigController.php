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
//        \PC::debug($result);
//return $this->backWithSuc();
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
//        \PC::debug([$req->all(),$start_date,$end_date]);
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
        
        $timezones = \App\DebtorRegionTimezone::get();
        
        foreach ($timezones as $tz) {
            if ($tz->id == 65) {
                \App\Passport::where('fact_address_region', 'like', $tz->root_word . '%')->whereNull('fact_timezone')->update(['fact_timezone' => $tz->timezone]);
                continue;
            }
            \App\Passport::where('fact_address_region', 'like', '%' . $tz->root_word . '%')->whereNull('fact_timezone')->update(['fact_timezone' => $tz->timezone]);
        }

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
