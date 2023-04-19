<?php

namespace App\Http\Controllers;

use App\Debtor;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Events\Infinity\IncomingCallEvent;
use App\Events\Infinity\ClosingModalsEvent;

class InfinityController extends BasicController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function callbacks(Request $req)
    {
    }

    public function incomingCall(Request $request)
    {
        //TODO FormRequest
        if ($request->get('State') !== 'Ringing' || !is_numeric($request->get('Extension'))) {
            return;
        }
        $user = User::where('infinity_extension', $request->get('Extension'))->first();
        if (!is_null($user) && $user->hasRole('debtors_remote')) {
            event(new IncomingCallEvent(
                $request->get('Number'),
                $request->get('IDCall'),
                $request->get('Extension')
            ));
        }
    }

    public function closingModals(Request $request)
    {
        event(new ClosingModalsEvent($request->get('user_extension')));
    }

    public function main(Request $req)
    {
        $this->middleware('auth');
        $serverAddress = 'http://192.168.1.50/';
        $extension = auth()->user()->infinity_extension;

        if (empty($extension)) {
            return $this->ajaxResult(0, 'Неверный идентификатор пользователя в инфинити');
        }
        $customerTelephone = \App\StrUtils::parsePhone($req->get('telephone'));

        if (empty($customerTelephone)) {
            return $this->ajaxResult(0, 'Неверный телефон');
        }

        switch ($req->get('type')) {
            case 'call':
                return $this->ajaxResult(1, '', json_decode(@file_get_contents($serverAddress . 'call/make/?Extension=' . $extension . '&Number=' . $customerTelephone . '&Tag=1'), true));
            default:
                return $this->ajaxResult(0, 'Неизвестный тип операции');
        }
    }

    public function incoming(Request $req)
    {
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
            $debtor = \App\Debtor::where('customer_id_1c', $customer->id_1c)
                ->where('is_debtor', 1)
                ->orderBy('fixation_date', 'desc')
                ->first();
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
        Redis::publish('incoming-calls', json_encode(['data' => $data]));
    }

    public function getDebtorByPhone($telephone): ?Debtor
    {
        $customers = DB::table('armf.customers')
            ->select(DB::raw('*'))
            ->where('telephone', (int)$telephone)
            ->get();
        if (count($customers) > 1) {
            Log::info("InfinityController.getDebtorByPhone more than 1 customers", ['tel' => $telephone, 'res' => 0]);
            return null;
        }
        $customer = \App\Customer::where('telephone', (int)$telephone)->orderBy('created_at', 'desc')->first();
        if (is_null($customer)) {
            Log::info("InfinityController.getDebtorByPhone customer is null", ['tel' => $telephone, 'res' => 0]);
            return null;
        }
        return \App\Debtor::where('customer_id_1c', $customer->id_1c)
            ->where('is_debtor', 1)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function getDebtorTimeByPhone($telephone)
    {
        logger('InfinityController.getDebtorTimeByPhone 1', ['telephone' => $telephone]);
        if (empty($telephone)) {
            Log::info("InfinityController.getDebtorTimeByPhone empty phone", ['tel' => $telephone, 'res' => 0]);
            return 0;
        }
        if ($telephone[0] == '8') {
            $telephone[0] = '7';
        }
        $debtor = $this->getDebtorByPhone($telephone);
        if (is_null($debtor)) {
            Log::info("InfinityController.getDebtorTimeByPhone 2 debtor is null", [
                'tel' => $telephone,
                'debtor' => $debtor,
                'res' => 0
            ]);
            return 0;
        }
        if (!isset($debtor->base)) {
            Log::info("InfinityController.getDebtorTimeByPhone 6", [
                'tel' => $telephone,
                'debtor' => $debtor,
                'res' => 0
            ]);
            return 0;
        }
        if ($debtor->base === 'Архив ЗД' || $debtor->base === 'Архив ОВК') {
            Log::info("InfinityController.getDebtorTimeByPhone 3", [
                'tel' => $telephone,
                'debtor' => $debtor,
                'res' => 0
            ]);
            return 0;
        }
        if ($debtor->is_debtor == 1 && $debtor->str_podr == '000000000006') {
            Log::info("InfinityController.getDebtorTimeByPhone 4", [
                'tel' => $telephone,
                'debtor' => $debtor,
                'res' => 22
            ]);
            return 22;
        }
        Log::info("InfinityController.getDebtorTimeByPhone 5", [
            'tel' => $telephone,
            'debtor' => $debtor,
            'res' => 0
        ]);
        return 0;
    }

    public function getDebtorTimeByPhoneWithRequest(Request $req)
    {
        return $this->getDebtorTimeByPhone($req->get('telephone', ''));
    }

    public function getUserInfinityIdByDebtorPhone($telephone)
    {
        $debtor = $this->getDebtorByPhone($telephone);
        if (is_null($debtor)) {
            Log::info("InfinityController.getUserInfinityIdByDebtorPhone debtor is null", [
                'tel' => $telephone,
                'debtor' => $debtor
            ]);
            return 0;
        }
        $user = \App\User::where('id_1c', $debtor->responsible_user_id_1c)->first();
        if (is_null($user)) {
            Log::info("InfinityController.getUserInfinityIdByDebtorPhone user is null", [
                'tel' => $telephone,
                'debtor' => $debtor
            ]);
            return 0;
        }
        logger('InfinityController.getUserInfinityIdByDebtorPhone', [
            'tel' => $telephone,
            'user' => $user,
            'debtor' => $debtor
        ]);
        return $user->infinity_user_id;
    }

    public function fromInfinityLossCalls(Request $req)
    {
        $input = $req->input();
        logger('InfinityController.fromInfinityLossCalls', ['input' => $input]);
        if (isset($input['customer_telephone']) && !empty($input['customer_telephone'])) {
            return \App\DebtorsLossCalls::addRecord($input['customer_telephone']);
        }

        return 0;
    }

}
