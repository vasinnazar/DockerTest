<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use App\Passport;
use App\Claim;
use App\about_client;
use App\Customer;
use Illuminate\Support\Facades\DB;
use App\Utils\StrLib;
use App\Utils\HelperUtil;
use App\StrUtils;
use App\Subdivision;
use App\User;
use Illuminate\Support\Facades\Validator;
use Log;
use App\Spylog\Spylog;

class TeleportController extends BasicController {

    const TELEPORT_SEND_STATUS_URL = 'http://gate.vteleport.ru/sendstatus';
//    const TELEPORT_SECRET = '796c3a91f30e';
    const TELEPORT_SECRET = '728c2dfbaf92';
    const CLAIM_STATUS_SELL = 'sell';
    const CLAIM_STATUS_DOUBLE = 'double';
    const CLAIM_STATUS_CANCEL = 'cancel';

    public function __construct() {
        
    }

    public function receiveClaimAndSendTo1c(Request $req) {
        Log::info('TeleportController.receive start', ['req' => $req->all()]);
        if (!$req->has('data') || !$req->has('uid')) {
            return response()->json(['result' => 0, 'aid' => StrLib::ERR_NO_PARAMS]);
        }

        $uid = $req->uid;
        $data = json_decode($req->data, true);
        if (is_null($data)) {
            Log::error('TeleportController.receive bad json', ['data' => $data, 'req' => $req->all()]);
            return response()->json(['result' => 0, 'aid' => 'Ошибка при разборе JSON']);
        }
        $claim = $this->createShortClaim($data);
        if (!is_null($claim)) {
            if (is_null($claim->id_1c)) {
                if ($this->sendClaimTo1c($claim)) {
                    Log::info('TeleportController.receive claim saved', ['req' => $req->all(), 'claim' => $claim, 'data' => $data]);
                    return response()->json(['result' => 1, 'aid' => $claim->id_1c]);
                } else {
                    Log::error('TeleportController.receive no claim', ['req' => $req->all(), 'claim' => $claim, 'data' => $data]);
                    return response()->json(['result' => 0, 'aid' => StrLib::ERR . '(2)']);
                }
            } else {
                Log::info('TeleportController.receive claim found', ['req' => $req->all(), 'claim' => $claim, 'data' => $data]);
                return response()->json(['result' => 1, 'aid' => $claim->id_1c]);
            }
        } else {
            Log::error('TeleportController.receive no claim', ['req' => $req->all(), 'claim' => $claim, 'data' => $data]);
            return response()->json(['result' => 0, 'aid' => StrLib::ERR]);
        }
    }

    public function statusTest(Request $req) {
        Log::info('teleport.statustest', ['req' => $req->all()]);
        return ['result' => 'hi there'];
    }

    public function sendStatus($id) {
//        if(Auth::user()->id==5){
//            return $this->sendAllStatusesForDate(new Carbon('2017-02-07'));
//        }
        $claim = Claim::find($id);
        if (is_null($claim) || is_null($claim->teleport_status)) {
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        $data = [
            'id' => $claim->id_teleport,
            'outer_id' => $claim->id_1c,
            'status' => $claim->teleport_status,
            'secret' => TeleportController::TELEPORT_SECRET
        ];
        \PC::debug($claim, 'claim');
        \PC::debug($data, 'data');
//        $contents = HelperUtil::SendPostByCurl(TeleportController::TELEPORT_SEND_STATUS_URL, $data);

        $contents = TeleportController::sendCustomPost($data);

        \PC::debug($contents, 'res');
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }

    /**
     * 
     * @param Carbon $date
     */
    function sendAllStatusesForDate($date) {
        $claims = Claim::whereBetween('created_at', [$date->setTime(0, 0, 0)->format('Y-m-d H:i:s'), $date->setTime(23, 59, 59)->format('Y-m-d H:i:s')])
                ->whereNotNull('teleport_status')
                ->get();
        foreach ($claims as $claim) {
            if (is_null($claim) || is_null($claim->teleport_status)) {
                return redirect()->back()->with('msg_err', StrLib::ERR);
            }
            $data = [
                'id' => $claim->id_teleport,
                'outer_id' => $claim->id_1c,
                'status' => $claim->teleport_status,
                'secret' => TeleportController::TELEPORT_SECRET
            ];
            \PC::debug($claim->toArray(), 'claim');
            \PC::debug($data, 'data');
            $contents = TeleportController::sendCustomPost($data);
            \PC::debug($contents, 'res');
            sleep(1);
        }
    }

    static function sendCustomPost($data) {
        $sURL = TeleportController::TELEPORT_SEND_STATUS_URL; // The POST URL
        $sPD = ""; // The POST Data
        foreach ($data as $k => $v) {
            $sPD .= $k . '=' . $v . '&';
        }
        $sPD = rtrim($sPD, '&');
        $aHTTP = array(
            'http' => // The wrapper to be used
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $sPD
            )
        );
        $context = stream_context_create($aHTTP);
        $contents = @file_get_contents($sURL, false, $context);
        return $contents;
    }

    static function sendStatusToTeleport($claim, $status_teleport) {
        if (!is_null($claim->teleport_status)) {
            return;
        }
        $data = [
            'id' => $claim->id_teleport,
            'outer_id' => $claim->id_1c,
            'status' => $status_teleport,
            'secret' => TeleportController::TELEPORT_SECRET
        ];
        Log::info('TeleportController.sendStatusToTeleport', ['data' => $data]);
        if(!config('app.dev')){
            $res = TeleportController::sendCustomPost($data);
            Log::info('TeleportController.sendStatusToTeleport result', ['res' => $res]);
        }
//        $res = HelperUtil::SendPostByCurl(TeleportController::TELEPORT_SEND_STATUS_URL, $data);
//        $res = HelperUtil::SendPostRequest(TeleportController::TELEPORT_SEND_STATUS_URL, $data);
        $claim->teleport_status = $data['status'];
        $claim->save();
    }

    static function getStatusValue($claim) {
        if ($claim->status == Claim::STATUS_CLIENT_DECLINED) {
            return TeleportController::CLAIM_STATUS_CANCEL;
        } else if ($claim->status == Claim::STATUS_DOUBLE) {
            return TeleportController::CLAIM_STATUS_DOUBLE;
        } else if ($claim->status == Claim::STATUS_ACCEPTED || $claim->status == Claim::STATUS_PRECONFIRM || $claim->status == Claim::STATUS_ONCHECK) {
            return TeleportController::CLAIM_STATUS_SELL;
        }
    }

    public function alreadyHasClaim($series, $number) {
        $reps = \App\Synchronizer::updateLoanRepayments($series, $number);
        if (is_null($reps)) {
            return false;
        }
        if (array_key_exists('loan', $reps) && !$reps['loan']->closed) {
            return true;
        }
        if (array_key_exists('claim', $reps)) {
            return true;
        }
        return false;
    }

    public function createShortClaim($data) {
        DB::beginTransaction();
        $oldClaim = Claim::where('id_teleport', $this->getData($data, 'id'))->first();
        if (!is_null($oldClaim)) {
            return $oldClaim;
        }

        $passport_series = $this->getData($data, 'passport_series');
        $passport_number = $this->getData($data, 'passport_number');

        $spylog = new Spylog();

//        if ($passport_number == '' && $passport_number == '') {
//            $passport = null;
//        } else {
//            $passport = Passport::where('series', $passport_series)->where('number', $passport_number)->first();
//        }
//        if (is_null($passport)) {
//            $customer = new Customer();
//            $customer->telephone = StrUtils::parsePhone($this->getData($data, 'phone'));
//            $customer->save();
//
//            $passport = new Passport();
//
//            $passport->customer_id = $customer->id;
//        } else {
//            $customer = $passport->customer;
//        }
        $customer = new Customer();
        $customer->telephone = StrUtils::parsePhone($this->getData($data, 'phone'));
        $customer->save();
        $spylog->addModelData(Spylog::TABLE_CUSTOMERS, $customer->toArray());

        $passport = new Passport();

        $passport->customer_id = $customer->id;

        $pdata = [
            'series' => $this->getData($data, 'passport_series'),
            'number' => $this->getData($data, 'passport_number'),
            'issued_date' => $this->getData($data, 'passport_date_of_issue'),
            'birth_city' => $this->getData($data, 'birthplace'),
            'issued' => $this->getData($data, 'passport_org'),
            'subdivision_code' => $this->getData($data, 'passport_code'),
            'fio' => $this->getData($data, 'last_name') . ' ' . $this->getData($data, 'first_name') . ' ' . $this->getData($data, 'middle_name'),
            'birth_date' => $this->getData($data, 'birthday'),
            'address_region' => $this->getData($data, 'registrarion_region'),
            'address_city' => $this->getData($data, 'registrarion_city'),
            'address_street' => $this->getData($data, 'registrarion_street'),
            'address_house' => $this->getData($data, 'registrarion_house'),
            'address_building' => $this->getData($data, 'registrarion_building'),
            'address_apartment' => $this->getData($data, 'registrarion_apartment'),
            'fact_address_region' => $this->getData($data, 'residential_region'),
            'fact_address_city' => $this->getData($data, 'residential_city'),
            'fact_address_street' => $this->getData($data, 'residential_street'),
            'fact_address_house' => $this->getData($data, 'residential_house'),
            'fact_address_building' => $this->getData($data, 'residential_building'),
            'fact_address_apartment' => $this->getData($data, 'residential_apartment'),
            'address_reg_date' => (is_null($passport->address_reg_date)) ? '1800-01-01' : $passport->address_reg_date
        ];
        $passport->fill($pdata);
        if ($passport_series == '' && $passport_number == '') {
            $lastTelePassport = Passport::where('series', 'TELE')->orderBy('number', 'desc')->first();
            $passport->series = 'TELE';
            $passport->number = (is_null($lastTelePassport)) ? 100000 : ($lastTelePassport->number + 1);
        }
        try {
            $passport->save();
        } catch (\Exception $ex) {
            Log::error('TeleportController.createShortClaim', ['ex' => $ex, 'data' => $data]);
            DB::rollback();
            return null;
        }
        $spylog->addModelData(Spylog::TABLE_PASSPORTS, $passport);

        $about = new about_client();
        $about->fill([
            'organizacia' => $this->getData($data, 'work_name'),
            'stazlet' => $this->getData($data, 'experience'),
            'telephoneorganiz' => StrUtils::parsePhone($this->getData($data, 'work_phone')),
            'sex' => ($this->getData($data, 'id_sex') == 1) ? 1 : 0,
            'avto' => ($this->getData($data, 'car') == 1) ? 1 : 0,
            'telephonehome' => StrUtils::parsePhone($this->getData($data, 'home_phone')),
//            'telephoneboss' => StrUtils::parsePhone($this->getData($data, 'boss_phone')),
//            'inn' => $this->getData($data, 'inn_number'),
            'email' => $this->getData($data, 'email'),
//            'occupation' => $this->getData($data, 'occupation'),
            'dohod' => $this->getData($data, 'incoming'),
            'anothertelephone' => $this->getData($data, 'home_phone'),
        ]);
        $about->customer_id = $customer->id;
        try {
            $about->save();
        } catch (\Exception $ex) {
            Log::error('TeleportController.createShortClaim', ['ex' => $ex, 'data' => $data]);
            DB::rollback();
            return null;
        }
        $spylog->addModelData(Spylog::TABLE_ABOUT_CLIENTS, $about);

        $claim = new Claim();
        $claim->srok = $this->getData($data, 'period');
        $claim->summa = $this->getData($data, 'amount');
        $claim->id_teleport = $this->getData($data, 'id');
        $claim->customer_id = $customer->id;
        $claim->passport_id = $passport->id;
        $claim->about_client_id = $about->id;
        $user = User::where('login', 'Teleport')->first();

        $teleportUserId = 832;

        if (is_null($user)) {
            $user = User::find($teleportUserId);
        }
        if (!is_null($user)) {
            $teleportUserId = $user->id;
            $claim->user_id = $user->id;
            $claim->subdivision_id = $user->subdivision_id;
        }
        if (is_null($user)) {
            $claim->user_id = $teleportUserId;
            $claim->subdivision_id = 658;
        }
        try {
            $claim->save();
        } catch (\Exception $ex) {
            Log::error('TeleportController.createShortClaim', ['ex' => $ex, 'data' => $data]);
            DB::rollback();
            return null;
        }
        $spylog->addModelData(Spylog::TABLE_CLAIMS, $claim);
        $spylog->save(Spylog::ACTION_CREATE, Spylog::TABLE_CLAIMS, $claim->id, $teleportUserId);
        DB::commit();

        return $claim;
    }

    public function getData($data, $key) {
        return (array_key_exists($key, $data)) ? $data[$key] : '';
    }

    public function sendClaimTo1c($claim) {
        $claimCtrl = new ClaimController();
        $res = $claimCtrl->sendClaimTo1c($claim);
        if (is_array($res) && array_key_exists('res', $res) && $res['res']==1) {
            $claim->status = Claim::STATUS_ONCHECK;
            $claim->save();
            return true;
        }
        return false;
    }

}
