<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Customer;
use App\Loan;
use App\Passport;
use App\Synchronizer;
use App\MySoap;
use App\Utils\SMSer;
use Log;
use App\Spylog\Spylog;
use App\StrUtils;
use App\Utils\StrLib;
use Illuminate\Support\Facades\Validator;
use App\Claim;

class FinterraController extends Controller {

    const FIN_PASS = ',tpjgfcyfz,tpjgfcyjcnm';
    const FIN_SALT = 'qjlbhjdfyyfz';

    public function __construct() {
        
    }

    /**
     * Получение номера контрагента для сайта
     * @param Request $req
     * @return type
     */
    public function getCustomerIdForFinterra(Request $req) {
        if (!$req->has('telephone') || !$req->has('birth_date') || !$req->has('token')) {
            return ['id_1c' => '', 'msg_err' => StrLib::ERR_NO_PARAMS, 'res' => 0];
        }
        if (!$this->checkCustomAuth($req->token)) {
            return ['res' => 0, 'msg_err' => StrLib::ERR_NOT_ADMIN];
        }

        $customer = Customer::where('telephone', $req->get('telephone'))
                        ->leftJoin('passports', 'passports.customer_id', '=', 'customers.id')
                        ->where('birth_date', $req->birth_date)->first();
        if (is_null($customer)) {
            return ['id_1c' => '', 'msg_err' => StrLib::ERR_NO_CUSTOMER, 'res' => 0];
        } else {
            return ['id_1c' => $customer->id_1c, 'res' => 1];
        }
    }

    /**
     * Полчение инфы о задолженности для сайта
     * @param Request $req
     * @return type
     */
    public function getLoanInfo(Request $req) {
        if (!$req->has('id') || !$req->has('token')) {
            return ['res' => 0, 'msg_err' => StrLib::ERR_NO_PARAMS];
        }
        if (!$this->checkCustomAuth($req->token)) {
            return ['res' => 0, 'msg_err' => StrLib::ERR_NOT_ADMIN];
        }
        $customer = Customer::where('id_1c', $req->id)->first();
        if (is_null($customer)) {
            return ['res' => 0, 'msg_err' => StrLib::ERR_NO_CUSTOMER];
        }
        $passport = Passport::where('customer_id', $customer->id)->orderBy('issued_date', 'desc')->first();
        if (is_null($passport)) {
            return ['res' => 0, 'msg_err' => StrLib::ERR_NO_CUSTOMER];
        }
//        $loan = Loan::select('loans.id as loan_id')->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')->where('claims.customer_id', $customer->id)->first();
//        $loan = Loan::find($loan->loan_id);
        $docs = Synchronizer::updateLoanRepayments($passport->series, $passport->number);
        if (array_key_exists('loan', $docs) && !$docs['loan']->closed) {
            $loan = $docs['loan'];
            $mDet = $loan->getRequiredMoneyDetails();
        } else {
            return ['res' => 2, 'msg_err' => 'У вас нет открытого договора'];
        }
        $endDate = with(new Carbon($loan->created_at))->addDays($loan->time)->format('d.m.Y');
//        $contractDate = 
        if (array_key_exists('repayments', $docs) && count($docs['repayments']) > 0) {
            $lastRep = $docs['repayments'][0];
            foreach ($docs['repayments'] as $d) {
                if ($d->created_at->gt($lastRep->created_at)) {
                    $lastRep = $d;
                }
            }
            $timeTillEnd = with(new Carbon($lastRep->created_at))->addDays(with(new Carbon($lastRep->created_at))->setTime(0, 0, 0)->diffInDays(Carbon::now()->setTime(0, 0, 0)));
        } else {
            $timeTillEnd = with(new Carbon($loan->created_at))->diffInDays();
        }
        $res = [
            'res' => 1,
            'fio' => $passport->fio,
            'loan' => [
                'created_at' => $loan->created_at->format('d.m.Y'),
                'id_1c' => $loan->id_1c,
                'percent' => $mDet->percent,
                'end_date_money' => StrUtils::kopToRub($mDet->od + $mDet->od * ($mDet->percent / 100) * $loan->time),
                'od' => StrUtils::kopToRub($mDet->od),
                'pc' => StrUtils::kopToRub($mDet->pc + $mDet->exp_pc),
                'end_date_pc' => $loan->money * ($loan->loantype->percent / 100) * $loan->time,
                'pay_before' => $endDate,
                'money' => $loan->money,
                'total' => StrUtils::kopToRub($mDet->money),
                'psk' => 2.2 * (365 + date("L")),
                'year_percent' => $mDet->percent * (365 + date("L")),
            ]
        ];
        $pays = \App\Order::where('passport_id', $passport->id)->where('created_at', '>', $loan->created_at)->where('type', \App\OrderType::getPKOid())->get();
        if (count($pays) > 0) {
            $res['payhistory'] = [];
            foreach ($pays as $p) {
                $res['payhistory'][] = ['date' => $p->created_at->format('d.m.Y'), 'money' => StrUtils::kopToRub($p->money)];
            }
        }
        $card = \App\Card::where('customer_id', $customer->id)->where('status', \App\Card::STATUS_ACTIVE)->orderBy('created_at', 'desc')->first();
        if (!is_null($card)) {
            $res['card'] = [
                'created_at' => $card->created_at->format('d.m.Y'),
                'number' => $card->card_number
            ];
        }
        Log::info('FinterraController.getLoanInfo', ['res' => $res, 'req' => $req]);
        return $res;
    }

    /**
     * типа авторизации
     * @param type $token
     * @return type
     */
    function checkCustomAuth($token) {
        return ($token == crypt(FinterraController::FIN_PASS, FinterraController::FIN_SALT));
    }

    /**
     * Создание кредитника по номеру контрагента с сайта
     * @param Request $req
     */
    public function createLoanForCustomer(Request $req) {
        Log::error('finterra start', ['req' => $req->all()]);
        $validator = Validator::make($req->all(), [
                    'money' => 'required|integer',
                    'time' => 'required|integer',
                    'id' => 'required',
                    'token' => 'required'
        ]);
        if ($validator->fails()) {
            Log::error('finterra validate', ['req' => $req->all()]);
            return $this->errorResponse();
        }
        if (!$this->checkCustomAuth($req->token)) {
            Log::error('finterra auth', ['req' => $req->all()]);
            return $this->errorResponse();
        }
        $user = \App\User::where('id_1c', 'ФинТерра.рф                                  ')->first();
        if (is_null($user)) {
            Log::error('finterra', ['user' => $user, 'req' => $req->all()]);
            return $this->errorResponse();
        }
        $customer = Customer::where('id_1c', $req->id)->first();
        if (is_null($customer)) {
            Log::error('finterra', ['user' => $user, 'req' => $req->all(), 'customer' => $customer]);
            return $this->errorResponse();
        }
        $lastClaim = Claim::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
        if (!is_null($lastClaim)) {
            $lastClaimLoan = Loan::where('claim_id', $lastClaim->id)->where('closed', 0)->count();
            if ($lastClaimLoan > 0) {
                Log::error('finterra',['lastClaimLoan'=>$lastClaimLoan]);
                return $this->errorResponse('У Вас есть непогашенный займ!');
            } else if ($lastClaim->status != Claim::STATUS_DECLINED && $lastClaim->created_at->gt(Carbon::now()->subDays(config('options.claim_exp_time')))) {
                Log::error('finterra',['lastclaim'=>$lastClaim]);
                return $this->errorResponse('Ваша заявка находится на рассмотрении', 2);
            }
        }
        $claim = Claim::createEmptyClaimForCustomer($customer, $req->money, $req->time, $user, $user->subdivision);
        if ($claim === false) {
            Log::error('finterra', ['user' => $user, 'req' => $req->all(), 'customer' => $customer, 'claim' => $claim]);
            return $this->errorResponse();
        } else {
            $claim->comment = 'Заявка с Личного Кабинета';
            $claim->save();
            $claimCtrl = new ClaimController();
            $sendRes = $claimCtrl->sendClaimTo1c($claim);
            if ($sendRes['res']) {
                return $this->successResponse([]);
            } else {
                Log::error('finterra send', ['user' => $user, 'req' => $req->all(), 'customer' => $customer, 'claim' => $claim, 'sendres'=>$sendRes]);
                return $this->errorResponse();
            }
        }
    }

    function errorResponse($msg_err = 'Ошибка', $result = 0) {
        return ['result' => $result, 'msg_err' => $msg_err];
    }

    function successResponse($data) {
        $data['result'] = 1;
        return $data;
    }

}
