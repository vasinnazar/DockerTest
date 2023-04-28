<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\Utils\StrLib,
    Auth,
    App\MySoap,
    Log,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    Input,
    App\PhoneCall,
    Carbon\Carbon;

class PhoneCallController extends BasicController {

    public function __construct() {
        
    }

    public function getView(Request $req) {
        if (is_null(Auth::user())) {
            return redirect()->back()->with('msg_err', 'Попробуйте еще раз');
        }
        $phonecall = new PhoneCall();
        if ($req->has('show_id') && is_numeric($req->show_id)) {
            $phonecall = PhoneCall::find($req->show_id);
            if (!is_null($phonecall)) {
                $phonecall->birth_date = with(new Carbon($phonecall->birth_date))->format('d.m.Y');
                $phonecall->last_date_call = with(new Carbon($phonecall->last_date_call))->format('d.m.Y');
                return view('reports.phonecall')->with('phonecall', $phonecall);
            }
        }
        if ($req->has('id')) {
            $callToSave = PhoneCall::find($req->id);
            if ($callToSave->comment != Input::get('comment') || $callToSave->call_Result != Input::get('call_Result')) {
                $callToSave->fill(Input::all());
                $callToSave->save();
                $this->sendDataTo1c($callToSave);
            }
            return $this->goToNextPhonecallId(null, $req->phone_call_type, $callToSave);
        }
        if ($req->has('phone_call_type')) {
            if ($this->countTodayCalls($req->phone_call_type) == 0) {
                $this->getDataFrom1c($req->get('phone_call_type', 1));
            }
            return $this->goToNextPhonecallId(null, $req->phone_call_type);
        }
        return view('reports.phonecall')->with('phonecall', $phonecall);
    }

    public function saveAndShowNext(Request $req) {
        if (is_null(Auth::user())) {
            return redirect()->back()->with('msg_err', 'Попробуйте еще раз');
        }
        $callToSave = PhoneCall::find($req->id);
        if (is_null($callToSave)) {
            return $this->getFrom1cIfNoDataAndGoToNext($req->phone_call_type);
        } else {
            if (is_null(Input::get('comment')) || Input::get('comment') == '' || strlen(Input::get('comment')) < 5) {
                return redirect()->back()->with('msg_err', 'Комментарий не может быть меньше 5 символов');
            }
            $oldCall = $callToSave->toArray();
            $callToSave->fill(Input::all());
            $callToSave->call_result = Input::get('call_result');
            $callToSave->updated_at = Carbon::now()->format('Y-m-d H:i:s');
            $callToSave->last_date_call = Carbon::now()->format('Y-m-d H:i:s');
            $callToSave->save();
            
            //указать что этому контрагенту уже звонили сегодня
            $callsForCustomer = PhoneCall::where('customer_id_1c', $callToSave->customer_id_1c)->where('id', '<>', $callToSave->id)->get();
            foreach ($callsForCustomer as $call) {
                $call->last_date_call = Carbon::now()->format('Y-m-d H:i:s');
                $call->save();
            }
            if ($callToSave->comment != $oldCall['comment'] || $callToSave->call_result != $oldCall['call_result']) {
                $this->sendDataTo1c($callToSave);
            }
            return $this->getFrom1cIfNoDataAndGoToNext($req->phone_call_type, $callToSave);
        }
        return $this->goToNextPhonecallId(null, $req->phone_call_type, $callToSave);
    }

    function getFrom1cIfNoDataAndGoToNext($phone_call_type, $callToSave = null) {
        if ($this->countTodayCalls($phone_call_type) == 0) {
            $phonecall = $this->getDataFrom1c($phone_call_type);
            if (!is_null($phonecall)) {
                return $this->goToNextPhonecallId($phonecall->id);
            }
            return redirect('reports/phonecall');
        }
        return $this->goToNextPhonecallId(null, $phone_call_type, $callToSave);
    }

    function countTodayCalls($phone_call_type) {
        return PhoneCall::where('created_at', '>=', Carbon::now()->setTime(0, 0, 0))
                        ->where('phone_call_type', $phone_call_type)
                        ->where('subdivision_id', Auth::user()->subdivision_id)->count();
    }

    function getNextPhonecall($type, $curCall = null) {
        $place = 1;
        if (!is_null($curCall)) {
            $phonecall = PhoneCall::where('created_at', '>=', Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'))
                    ->where('subdivision_id', Auth::user()->subdivision_id)
                    ->where('phone_call_type', $type)
                    ->where('id', '>', $curCall->id)
                    ->whereNull('comment')
                    ->whereRaw('(updated_at <="' . Carbon::now()->subMinutes(30)->format('Y-m-d H:i:s') . '" or created_at = updated_at)')
                    ->first();
            if (is_null($phonecall)) {
                $phonecall = PhoneCall::where('created_at', '>=', Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'))
                        ->where('subdivision_id', Auth::user()->subdivision_id)
                        ->where('phone_call_type', $type)
                        ->where('id', '<>', $curCall->id)
                        ->whereNull('comment')
                        ->whereRaw('(updated_at <="' . Carbon::now()->subMinutes(30)->format('Y-m-d H:i:s') . '" or created_at = updated_at)')
                        ->first();
            }
            if (is_null($phonecall)) {
                $phonecall = PhoneCall::where('created_at', '>=', Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'))
                        ->where('subdivision_id', Auth::user()->subdivision_id)
                        ->where('phone_call_type', $type)
                        ->where('id', '<>', $curCall->id)
                        ->whereRaw('(updated_at <="' . Carbon::now()->subMinutes(30)->format('Y-m-d H:i:s') . '" or created_at = updated_at)')
                        ->first();
            }
        } else {
            $phonecall = PhoneCall::where('created_at', '>=', Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'))
                    ->where('subdivision_id', Auth::user()->subdivision_id)
                    ->where('phone_call_type', $type)
                    ->whereRaw('(updated_at <="' . Carbon::now()->subMinutes(30)->format('Y-m-d H:i:s') . '" or created_at = updated_at)')
                    ->whereNull('comment')
                    ->first();
            if (is_null($phonecall)) {
                $phonecall = PhoneCall::where('created_at', '>=', Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'))
                        ->where('subdivision_id', Auth::user()->subdivision_id)
                        ->where('phone_call_type', $type)
                        ->whereRaw('(updated_at <="' . Carbon::now()->subMinutes(30)->format('Y-m-d H:i:s') . '" or created_at = updated_at)')
                        ->first();
            }
        }
        Log::info('PhonecallController', ['phonecall' => $phonecall, 'user' => Auth::user(), 'curcall' => $curCall, 'place' => $place]);
        return $phonecall;
    }

    function getNextPhonecallId($type, $curCall = null) {
        $phonecall = $this->getNextPhonecall($type, $curCall);
        return (is_null($phonecall)) ? '' : $phonecall->id;
    }

    function goToNextPhonecallId($phoneCallId = null, $type = null, $curCall = null) {
        if (!is_null($phoneCallId)) {
            return redirect('reports/phonecall?show_id=' . $phoneCallId);
        }
        if (!is_null($type)) {
            $phonecall = $this->getNextPhonecall($type, $curCall);
            if (is_null($phonecall)) {
                return redirect('reports/phonecall')->with('msg_suc', 'Список на сегодня закончен. Можно повторить через 30 минут.');
            } else {
                return redirect('reports/phonecall?show_id=' . $phonecall->id);
            }
        }
        return redirect('reports/phonecall');
    }

    public function sendDataTo1c($phonecall) {
        if (is_null(Auth::user())) {
            return;
        }
        if (is_null($phonecall->customer_id_1c) ||
                is_null($phonecall->telephone) ||
                is_null($phonecall->comment) ||
                $phonecall->customer_id_1c == '' ||
                $phonecall->telephone == '' ||
                $phonecall->comment == '') {
            return;
        }
        $xml = [
            'type' => '9',
            'user_id_1c' => Auth::user()->id_1c,
            'subdivision_id_1c' => Auth::user()->subdivision->name_id,
            'customer_id_1c' => $phonecall->customer_id_1c,
            'call_result' => $this->getCallResult($phonecall->call_Result),
            'comment' => $phonecall->comment,
            'phone_call_type' => $this->getPhoneCallType($phonecall->phone_call_type),
            'telephone' => $phonecall->telephone,
            'date' => Carbon::now()->format('YmdHis'),
            'birth_date' => with(new Carbon($phonecall->birth_date))->format('Ymd'),
        ];
        $res1c = MySoap::sendXML(MySoap::createXML($xml));
        if (!(int) $res1c->result) {
            return;
        }
    }

    function getPhoneCallType($type) {
        $vals = [
            '',
            'ДеньРождения',
            'ЗакрытыеДоговора',
            'Пенсионеры'
        ];
        return ($type < count($vals) && $type >= 0) ? $vals[$type] : $vals[1];
    }

    function getCallResult($type) {
        $vals = [
            '',
            'Недозвон',
            'Дозвон',
            'ОформитьЗаявку',
            'НикогдаНеЗвонить'
        ];
        return (!is_null($type) && $type < count($vals) && $type >= 0) ? $vals[$type] : $vals[1];
    }

    public function getDataFrom1c($type) {
        $xml = [
            'type' => '9',
            'user_id_1c' => Auth::user()->id_1c,
            'subdivision_id_1c' => Auth::user()->subdivision->name_id,
            'customer_id_1c' => '',
            'call_result' => '',
            'comment' => '',
            'phone_call_type' => $type,
            'telephone' => '',
            'date' => ''
        ];
        $res1c = MySoap::sendXML(MySoap::createXML($xml),false);
        if (!(int) $res1c->result) {
            return;
        }
        PhoneCall::where('created_at', '<', Carbon::now()->setTime(0, 0, 0))->delete();
//        PhoneCall::where('created_at', '<', Carbon::now()->setTime(0, 0, 0))->withTrashed()->forceDelete();
//        DB::select('delete from phone_calls where created_at<'.Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
        $first = null;
        DB::beginTransaction();
        foreach ($res1c->Tablo->stroka as $stroka) {
            try {
                $json = json_decode(json_encode($stroka), true);
            } catch (Exception $ex) {
                continue;
            }
            $phonecall = new PhoneCall();
            foreach ($json as $k => $v) {
                if (in_array($k, $phonecall->getFillable())) {
                    $phonecall->{$k} = (!is_array($v)) ? ((string) $v) : null;
                }
            }
            $phonecall->phone_call_type = $type;
            $phonecall->user_id = Auth::user()->id;
            $phonecall->subdivision_id = Auth::user()->subdivision_id;
            $phonecall->fio = (string) $stroka->customer;
            if (!$phonecall->save()) {
                DB::rollback();
            }
            $first = $phonecall;
        }
        DB::commit();
        return $first;
    }

}
