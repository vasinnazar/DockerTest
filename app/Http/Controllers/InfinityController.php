<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Log;
use DB;

class InfinityController extends BasicController {

    public function __construct() {
        parent::__construct();
    }

    public function callbacks(Request $req) {
//        Log::info('callbacks',['req'=>$req->all()]);
        //try{
        //    file_get_contents(str_replace('192.168.1.215','192.168.1.60/armff.ru',$req->fullUrl()));
        //} catch (Exception $ex) {
        //}
    }

    public function main(Request $req) {
        $this->middleware('auth');
        $server_address = 'http://192.168.1.50/';
        $extension = auth()->user()->infinity_extension;
//        $extension = '105';
        if (empty($extension)) {
            return $this->ajaxResult(0, 'Неверный идентификатор пользователя в инфинити');
        }
        $customerTelephone = \App\StrUtils::parsePhone($req->get('telephone'));
//        $customerTelephone = '79030466344';
        if (empty($customerTelephone)) {
            return $this->ajaxResult(0, 'Неверный телефон');
        }

        switch ($req->get('type')) {
            case 'call':
//                return $this->ajaxResult(0, $customerTelephone);
                return $this->ajaxResult(1, '', json_decode(@file_get_contents($server_address . 'call/make/?Extension=' . $extension . '&Number=' . $customerTelephone . '&Tag=1'), true));
            default:
                return $this->ajaxResult(0, 'Неизвестный тип операции');
        }
    }

    public function incoming(Request $req) {
        Log::info('income call ', ['req' => $req->all()]);
        if (!$req->has('telephone') && !$req->has('user-extension')) {
            return 'no params';
        }
        $customers = \App\Customer::where('telephone', $req->get('telephone'))->get();
        if (is_null($customers) || count($customers) == 0) {
            return 'no customer';
        }
        $data = [
            'telephone' => $req->get('telephone'),
            'user_extension' => $req->get('user_extension'),
            'call_id' => $req->get('call_id'),
            'customers' => []
        ];
        foreach ($customers as $customer) {
            $passport = $customer->getLastPassport();
            if (is_null($passport)) {
                continue;
            }
            $debtor = \App\Debtor::where('customer_id_1c', $customer->id_1c)->where('is_debtor', 1)->orderBy('fixation_date', 'desc')->first();
            if (is_null($debtor)) {
                continue;
            }
            $data['customers'][] = [
                'fio' => $passport->fio,
                'telephone' => \App\StrUtils::beautifyPhone($customer->telephone),
                'debtorcard-link' => '<a href="' . url('debtors/debtorcard/' . $debtor->id) . '">' . $passport->fio . '</a>'
            ];
            $data['customers'][] = [
                'fio' => $passport->fio . 'copy',
                'telephone' => \App\StrUtils::beautifyPhone($customer->telephone),
                'debtorcard-link' => '<a href="' . url('debtors/debtorcard/' . $debtor->id) . '">' . $passport->fio . '</a>'
            ];
            $data['customers'][] = [
                'fio' => $passport->fio . 'copy',
                'telephone' => \App\StrUtils::beautifyPhone($customer->telephone),
                'debtorcard-link' => '<a href="' . url('debtors/debtorcard/' . $debtor->id) . '">' . $passport->fio . '</a>'
            ];
        }
        Redis::publish('incoming-calls', json_encode([
            'data' => $data
        ]));
    }

    function getDebtorByPhone($telephone) {
        $customers = DB::Table('armf.customers')->select(DB::raw('*'))->where('telephone', (int) $telephone)->get();
        if (count($customers) > 1) {
            //logger('InfinityController.getDebtorByPhone more than 1 customers', ['tel' => $telephone, 'res' => 0]);
            Log::info("InfinityController.getDebtorByPhone more than 1 customers", ['tel' => $telephone, 'res' => 0]);
            return 0;
        }
        $customer = \App\Customer::where('telephone', (int) $telephone)->orderBy('created_at', 'desc')->first();
        if (is_null($customer)) {
            //logger('InfinityController.getDebtorByPhone customer is null', ['tel' => $telephone, 'res' => 0]);
            Log::info("InfinityController.getDebtorByPhone customer is null", ['tel' => $telephone, 'res' => 0]);
            return null;
        }
        /*$loan = \App\Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->where('claims.customer_id', $customer->id)
                ->orderBy('loans.created_at', 'desc')
                ->select(['loans.id_1c'])
                ->first();
        if (is_null($loan)) {
            //logger('InfinityController.getDebtorByPhone loan is null', ['tel' => $telephone, 'res' => 0]);
            Log::info("InfinityController.getDebtorByPhone loan is null", ['tel' => $telephone, 'res' => 0]);
            return null;
        }*/
        return \App\Debtor::where('customer_id_1c', $customer->id_1c)
                ->where('is_debtor', 1)
                ->orderBy('created_at', 'desc')
                ->first();
    }

    public function getDebtorTimeByPhone($telephone) {
        logger('InfinityController.getDebtorTimeByPhone 1', ['telephone' => $telephone]);
        if (empty($telephone)) {
            //logger('InfinityController.getDebtorTimeByPhone empty phone', ['tel' => $telephone, 'res' => 0]);
            Log::info("InfinityController.getDebtorTimeByPhone empty phone", ['tel' => $telephone, 'res' => 0]);
            return 0;
        }
        if ($telephone[0] == '8') {
            $telephone[0] = '7';
        }
        $debtor = $this->getDebtorByPhone($telephone);
        if (is_null($debtor)) {
            //logger('InfinityController.getDebtorTimeByPhone 2 debtor is null', ['tel' => $telephone, 'debtor' => $debtor, 'res' => 0]);
            Log::info("InfinityController.getDebtorTimeByPhone 2 debtor is null", ['tel' => $telephone, 'debtor' => $debtor, 'res' => 0]);
            return 0;
        }
        if (!isset($debtor->base)) {
            //logger('InfinityController.getDebtorTimeByPhone 6', ['tel' => $telephone, 'debtor' => $debtor, 'res' => 0]);
            Log::info("InfinityController.getDebtorTimeByPhone 6", ['tel' => $telephone, 'debtor' => $debtor, 'res' => 0]);
            return 0;
        }
        if ($debtor->base == 'Архив ЗД' || $debtor->base == 'Архив ОВК') {
            //logger('InfinityController.getDebtorTimeByPhone 3', ['tel' => $telephone, 'debtor' => $debtor, 'res' => 0]);
            Log::info("InfinityController.getDebtorTimeByPhone 3", ['tel' => $telephone, 'debtor' => $debtor, 'res' => 0]);
            return 0;
        }
        if ($debtor->is_debtor == 1 && $debtor->str_podr == '000000000006') {
            //logger('InfinityController.getDebtorTimeByPhone 4', ['tel' => $telephone, 'debtor' => $debtor, 'res' => 22]);
            Log::info("InfinityController.getDebtorTimeByPhone 4", ['tel' => $telephone, 'debtor' => $debtor, 'res' => 22]);
            return 22;
        } else {
            //logger('InfinityController.getDebtorTimeByPhone 5', ['tel' => $telephone, 'debtor' => $debtor, 'res' => 0]);
            Log::info("InfinityController.getDebtorTimeByPhone 5", ['tel' => $telephone, 'debtor' => $debtor, 'res' => 0]);
            return 0;
        }
        //return (int) $debtor->qty_delays;
    }

    public function getDebtorTimeByPhoneWithRequest(Request $req) {
        return $this->getDebtorTimeByPhone($req->get('telephone', ''));
    }

    public function getUserInfinityIdByDebtorPhone($telephone) {
        $debtor = $this->getDebtorByPhone($telephone);
        if (is_null($debtor)) {
            //logger('InfinityController.getUserInfinityIdByDebtorPhone', ['tel' => $telephone, 'debtor' => $debtor]);
            Log::info("InfinityController.getUserInfinityIdByDebtorPhone debtor is null", ['tel' => $telephone, 'debtor' => $debtor]);
            return 0;
        }
        $user = \App\User::where('id_1c', $debtor->responsible_user_id_1c)->first();
        if (is_null($user)) {
            //logger('InfinityController.getUserInfinityIdByDebtorPhone', ['tel' => $telephone, 'user' => $user, 'debtor' => $debtor]);
            Log::info("InfinityController.getUserInfinityIdByDebtorPhone user is null", ['tel' => $telephone, 'debtor' => $debtor]);
            return 0;
        }
        logger('InfinityController.getUserInfinityIdByDebtorPhone', ['tel' => $telephone, 'user' => $user, 'debtor' => $debtor]);
        return $user->infinity_user_id;
    }

    public function fromInfinityLossCalls(Request $req) {
        $input = $req->input();

        logger('InfinityController.fromInfinityLossCalls', ['input' => $input]);

        if (isset($input['customer_telephone']) && !empty($input['customer_telephone'])) {
            return \App\DebtorsLossCalls::addRecord($input['customer_telephone']);
        }

        return 0;
    }

}
