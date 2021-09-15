<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Loan;
use App\Synchronizer;
use App\Debtor;
use App\User;
use Carbon\Carbon;
use Log;

class FromDebtorsController extends Controller {

    /**
     * Установить главным фото в заявке
     * @param Request $req
     * @return int
     */
    public function setMainPhoto(Request $req) {
        $photo = \App\Photo::find($req->get('main_id'));
        if (is_null($photo)) {
            return 0;
        }
        return ($photo->setMain()) ? 1 : 0;
    }

    /**
     * Возвращает детализацию по задолженности
     * @param Request $req
     * @return type
     */
    public function getDebt(Request $req) {
        $data = json_decode($req->get('data', json_encode([])));
        $loan = Loan::getById1cAndCustomerId1c2($data->loan_id_1c, $data->customer_id_1c);
        $customer = \App\Customer::where('id_1c', $data->customer_id_1c)->first();
        if (!is_null($customer)) {
            $passport = $customer->getLastPassport();
            if (!is_null($passport)) {
                Synchronizer::updateLoanRepayments($passport->series, $passport->number);
            }
        }
        Synchronizer::updateLoanRepayments(null, null, $data->loan_id_1c, $data->customer_id_1c);
        if (is_null($loan)) {
            $loan = Loan::getById1cAndCustomerId1c2($data->loan_id_1c, $data->customer_id_1c);
        }
        if (is_null($loan)) {
            $loan = Loan::where('id_1c', $data->loan_id_1c)->first();
        }
        if (!isset($loan) || is_null($loan)) {
            return ['req' => $data->loan_id_1c, 'loan' => $loan];
        } else {
            return json_encode($loan->getDebtFrom1c($loan, $data->date));
        }
    }

    public function uploadOrders(Request $req) {
        $data = json_decode($req->get('data', json_encode([])));
        $orders = [];
        foreach ($data as $debtor) {
            Log::info('upload Orders', ['data' => $data, 'debtor' => $debtor]);
            $loan = Loan::getById1cAndCustomerId1c($debtor->loan_id_1c, $debtor->customer_id_1c);
            if (!is_null($loan)) {
                $claim = $loan->claim;
                $start_date = new Carbon($debtor->start_date);
                $end_date = new Carbon($debtor->end_date);
                $connection = [
                    'url' => '192.168.1.23:8080/111SPD_debtors/ws/Mole/?wsdl',
                    'login' => config('1c.login'),
                    'password' => config('1c.password'),
                    'absolute_url' => true
                ];
                if ($end_date->gte(Carbon::today())) {
                    $orders = array_merge($orders, Synchronizer::updateOrders($start_date, $claim->passport->series, $claim->passport->number, null, $loan, $end_date->subDay(), $connection));
                    $todayOrders = Synchronizer::updateOrders(Carbon::today(), $claim->passport->series, $claim->passport->number, null, $loan, Carbon::now());
                    if (is_array($todayOrders)) {
                        $orders = array_merge($orders, $todayOrders);
                    }
                } else {
                    $orders = array_merge($orders, Synchronizer::updateOrders($start_date, $claim->passport->series, $claim->passport->number, null, $loan, $end_date, $connection));
                }
            }
        }
        return json_encode($orders);
    }
    
    /**
     * Запросом из продажного АРМ отправляет ответственного (для 21 дня просрочки)
     * @param Request $req
     * @return mixed
     */
    public function respUserForSellingARM(Request $req) {
        $customer_id_1c = $req->get('customer_id_1c');
        $loan_id_1c = $req->get('loan_id_1c');
        $debtor = Debtor::where('customer_id_1c', $customer_id_1c)
                ->where('loan_id_1c', $loan_id_1c)->first();
        
        if (is_null($debtor)) {
            return 0;
        }
        
        $user = User::where('id_1c', $debtor->responsible_user_id_1c)->first();
        
        if (is_null($user)) {
            return [];
        }
        
        $arr = [
            "username" => $user->name,
            "phone" => $user->phone
        ];
        
        return json_encode($arr);
    }

}
