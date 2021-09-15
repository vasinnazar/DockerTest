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

class DebtController extends Controller {

    public function __construct() {
        
    }

    public function getDebtByPhone(Request $req) {
//        Log::info('debt req ',$req->all());
        $err_null = -3;
        $err = -2;
        if (!$req->has('telephone') || $req->telephone == '' || strlen($req->telephone) < 11 || substr($req->telephone, 0, 2) != '79') {
            return $this->remove_utf8_bom($err);
        }
        $res1c = MySoap::getDebtByNumber($req->telephone);
        if (!$res1c['res']) {
            return $this->remove_utf8_bom($err);
        }
        //если нету договора в 1С
        if ($res1c['value'] == 'Error0') {
            return $this->remove_utf8_bom($err_null);
        }
        //если в 1С два должника с этим номером
        if ($res1c['value'] == 'Error1') {
            return $this->remove_utf8_bom($err);
        }
        //договор не найден
        if ($res1c['value'] == 'Error2') {
            return $this->remove_utf8_bom($err_null);
        }
        //есть просрочка но дата договора меньше марта 2013
        if ($res1c['value'] == 'Error3') {
            return $this->remove_utf8_bom($err);
        }
        //есть просрочка но дата договора меньше мая 2013
        if ($res1c['value'] == 'Error4') {
            return $this->remove_utf8_bom($err);
        }
        return $this->remove_utf8_bom($res1c['value']);
    }

    function remove_utf8_bom($text) {
        return $text;
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return response($text, 200, ['Content-Type' => 'application/json']);
    }

    public function getDebtByPhone2(Request $req) {
        $failResult = '';
        if (!$req->has('telephone') || $req->telephone == '') {
            return $failResult;
        }
        $customer = Customer::where('telephone', $req->telephone)->first();
        if (is_null($customer)) {
            return $failResult;
        }
        $passport = Passport::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
        if (is_null($passport)) {
            return $failResult;
        }
        $res1c = Synchronizer::updateLoanRepayments($passport->series, $passport->number);
        if (is_null($res1c)) {
            return $failResult;
        }
        $mDet = $res1c['loan']->getRequiredMoneyDetails();
        return $mDet->money;
    }

}
