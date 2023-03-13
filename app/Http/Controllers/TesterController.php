<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use Storage;
use DB;
use App\MySoap;
use Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\User;
use App\Order;
use App\Loan;
use App\Subdivision;
use App\Passport;
use App\OrderType;
use Illuminate\Support\Facades\Mail;
use App\DebtorEvent;
use App\Debtor;
use Illuminate\Support\Facades\Redis;
use App\Utils\HelperUtil;

class TesterController extends BasicController {

    public function testButton(Request $req) {
//        logger(' asdasdasd',['data'=>  json_encode(['name'=>'Дмитрий Александрович'])]);
//        $this->getSmsClaimsWithoutLoan();
//        $this->updateDailyCashReports(407, '2018-01-12', '2018-03-24');
//        \PC::debug(MySoap::sendExchangeArm(MySoap::createXML(['type'=>'ChangeOrderSubdivision','order_number'=>'П0000235725','subdivision_id_1c'=>'Т004'])));
//        \PC::debug(MySoap::sendExchangeArm(MySoap::createXML([
//                'type' => 'UpdateAsp', 
//                'customer_id_1c' => '000202095', 
//                'asp_key' => 'QWE', 
//                'date' => Carbon::now()->format('YmdHis'), 
//                'subdivision_id_1c' => '001'
//            ])));
//        return $this->getCustomersWithNoAspSignAndAspApproved();
//        $this->resendTeleportclaims();
//        $this->resolveAspDates();
//        $this->resendAsp();
//        \PC::debug(\App\Customer::whereNull('asp_approved_at')->whereNotNull('asp_key')->whereNotNull('asp_generated_at')->count(), 'customers_to_send_count');
//        $this->sendAspForOneClient();
//        $this->sendDerealization();
//        $this->solveTerminalOrderSubdivisionProblem();
//        $this->terminalRefill();
//        $this->sendPayturePay();
//        $this->sendScorista();
//        $this->sendScoristaTo1c();
//        $this->writeTerminalReports();
//        $tc = new TerminalController();
//        \PC::debug($tc->authCustomer('70000001526', '123', \App\Terminal::find(1)));
//        MySoap::createClaimRepayment([
//            "created_at" => "20180124164619", "Number" => "000046463", "passport_series" => "6901",
//            "passport_number" => "320461", "money" => "695532", "loan_id_1c" => "00000898036",
//            "subdivision_id_1c" => "154     ", "user_id_1c" => "Пашкевич А.А.                                ",
//            "od" => 300000, "pc" => 195300, "exp_pc" => 195300, "fine" => 4932,
//            "comment" => "", "time" => 30, "claim_type" => 0
//        ]);
//        $contracts = \App\ContractForm::get();
//        $num = 0;
//        foreach ($contracts as $item) {
//            if(substr_count($item->template, 'Седов')){
//                \PC::debug(['name'=>$item->name,'id'=>$item->id]);
//                $num++;
//            }
//        }
//        return $num;
//        return (string)\App\Utils\SMSer::sendBySmsFeedback('79530627328', 'Привет', 'FinTerra');
//        $this->updateOrdersForDebtorsFromBuhBase();
//        $this->sendUnsentTeleportClaims();
//        $this->checkPayturePaymentStatus();
//        $this->resendWorkTime();
//        \App\Utils\Unicom::getLeadAndHandle();
//        \PC::debug('wooooop');
//        $customer = \App\Customer::where('id_1c','П00178050')->first();
//        \PC::debug($customer);
//        \PC::debug($customer->canGetGNS());
//        \PC::debug($customer->getDataFrom1c(['uki_check'=>1]));
//        $this->changeAllUserDocs();
//        \App\Utils\Unicom::getLeadAndHandle();
//        \App\Utils\SMSer::send('79530627328', 'Ваш баланс пополнен на 4000. Дата возврата: 12.04.2018г. Для получения денежных средств обратитесь к ближайшему терминалу.', \App\SmsSent::SERVICE_SMS_FEEDBACK);
//        \PC::debug(\App\SmsLoanTelephone::SendSmsPack(200));
//        $si = new \App\SmsInbox();
//        $si->message = 'ЗАЕМ 3000 ';
//        \PC::debug($si->isValidClaimSms());
//        \PC::debug(\App\Utils\SMSer::getMessagesFromSmsc('79235520181'));
//        \PC::debug(MySoap::createXML(['type' => 'GetKcTeleportClaims', 'user_id_1c' => auth()->user()->id_1c]));
//        \PC::debug(MySoap::createXML(['user_claims' => ['item1' => ['id_1c' => '000000000']], 'other_claims' => ['item1' => ['id_1c' => '000000000']]]));
//        \PC::debug(MySoap::createXML(['type' => 'SetClaimUser', 'user_id_1c' => auth()->user()->id_1c, 'claim_id_1c'=>'000000000']));
//        \PC::debug(MySoap::createXML(['result' => 1]));
//        $customers = \App\Customer::where('telephone','like','9%')->limit(100)->get();
//        foreach($customers as $customer){
//            $customer->telephone = '7'.$customer->telephone;
//            $customer->save();
//        }
//        $this->resendUnicomStatuses();
//        \PC::debug(\App\TelephoneCheck::hasTodayForTelephone('79530627328'));
//        $this->getDebtorsNoticeReport();
//        $this->getUnicomReport();
//        $this->solveDebtorNoClaim(74);
        \App\Utils\SMSer::send('79530627328','smsc');
    }
    function solveDebtorNoClaim($debtor_id){
        $debtor = \App\Debtor::find($debtor_id);
        if(is_null($debtor)){
            \PC::debug('no debtor');
            return;
        }
        $customer = \App\Customer::where('id_1c',$debtor->customer_id_1c)->first();
        if(is_null($customer)){
            \PC::debug('no customer');
            return;
        }
        $passport = Passport::where('series',$debtor->passport_series)->where('number',$debtor->passport_number)->first();
        if(is_null($passport)){
            \PC::debug('no passport');
            return;
        }
        $res1c = \App\Synchronizer::getContractsFrom1c($passport->series, $passport->number);
        \PC::debug($res1c);
        $loan = Loan::where('id_1c',$debtor->loan_id_1c)->first();
        if(is_null($loan) && isset($res1c['loan'])){
            $loan = new Loan();
            $loan->id_1c = $res1c['loan']['Number'];
            $loan->created_at = with(new Carbon($res1c['loan']['created_at']))->format('Y-m-d H:i:s');
            $loan->loantype_id = \App\LoanType::where('id_1c',$res1c['loan']['loantype_id_1c'])->value('id');
            $loan->enrolled = $res1c['loan']['enrolled'];
            $loan->money = $res1c['loan']['money'];
            $loan->time = $res1c['loan']['time'];
            $subdiv = Subdivision::where('name_id',$res1c['loan']['subdivision_id_1c'])->first();
            if(is_null($subdiv)){
                $subdiv = Subdivision::find(668);
            }
            $loan->subdivision_id = $subdiv->id;
            $user = User::where('id_1c',$res1c['loan']['user_id_1c'])->first();
            if(is_null($user)){
                $user = User::find(5);
            }
            $loan->user_id = $user->id;
            
            $claim = null;
            if(!empty($res1c['claim']['Number'])){
               $claim = \App\Claim::where('id_1c',$res1c['claim']['Number'])->first();
            }
            if(is_null($claim)){
                $about = new \App\about_client();
                $about->customer_id = $customer->id;
                $about->stepenrodstv = 1;
                $about->marital_type_id = 1;
                $about->save();
                
                $claim = new \App\Claim();
                $claim->id_1c = (empty($res1c['claim']['Number']))?($loan->id_1c.'-Z'):$res1c['claim']['Number'];
                $claim->created_at = $loan->created_at->format('Y-m-d H:i:s');
                $claim->summa = (empty($res1c['claim']['money']))?$loan->money:$res1c['claim']['money'];
                $claim->srok = ($res1c['claim']['time']=="0")?$loan->time:$res1c['claim']['time'];
                $claim->status = \App\Claim::STATUS_ACCEPTED;
                $claim->subdivision_id = $subdiv->id;
                $claim->user_id = $user->id;
                $claim->passport_id = $passport->id;
                $claim->customer_id = $customer->id;
                $claim->about_client_id = $about->id;
                $claim->save();
            }
            $loan->claim_id = $claim->id;
            $loan->save();
            \PC::debug(['loan'=>$loan,'claim'=>$claim]);
        }
        $debtor->uploaded = 1;
        $debtor->save();
        return redirect()->back()->with('msg_suc', \App\Utils\StrLib::SUC);
    }

    function getUnicomReport() {
        $dates = ['2018-04-06', '2018-04-07', '2018-04-08', '2018-04-09', '2018-04-10'];
        $str = '<table><tr><td>Дата</td><td>Количество</td><td>Первая заявка</td><td>Последняя заявка</td></tr>';
        foreach ($dates as $date) {
            $sd = new Carbon($date);
            $ed = $sd->copy()->addDay();
            $period = [$sd->format('Y-m-d H:i:s'), $ed->format('Y-m-d H:i:s')];
            \PC::debug($period);
            $sql = \App\Claim::where('subdivision_id', '721')->whereBetween('created_at', $period);
            $claims_num = $sql->count();
            $claims_start_date = $sql->min('created_at');
            $claims_end_date = $sql->max('created_at');
            $str .= '<tr>'
                    . '<td>'.$sd->format('d.m.Y').'</td><td>'.$claims_num.'</td>'
                    . '<td>'.with(new Carbon($claims_start_date))->format('d.m.Y H:i:s').'</td>'
                    . '<td>'.with(new Carbon($claims_end_date))->format('d.m.Y H:i:s').'</td>'
                    . '</tr>';
        }
        $str .='</table>';
        echo $str;
    }

    public function getDebtorsNoticeReport() {
        $data = DB::connection('debtors215')->table('notice_numbers')
                ->select(['notice_numbers.id', 'notice_numbers.str_podr', 'debtors.loan_id_1c', 'passports.fio', 'notice_numbers.created_at'])
                ->where('notice_numbers.user_id_1c', '<>', 'KAadmin')
                ->groupBy('notice_numbers.debtor_id_1c')
                ->orderBy('notice_numbers.id', 'desc')
//                ->leftJoin('struct_subdivisions','struct_subdivisions.id_1c','=','notice_numbers.str_podr')
                ->leftJoin('debtors', 'debtors.debtor_id_1c', '=', 'notice_numbers.debtor_id_1c')
                ->join('passports', function($join) {
                    $join->on('passports.series', '=', 'debtors.passport_series');
                    $join->on('passports.number', '=', 'debtors.passport_number');
                })
                ->get();
        $str = '<table>';
//        $str = '';
        foreach ($data as $item) {
            $pfx = '';
            switch ($item->str_podr) {
                case '000000000006':
                    $pfx = 'УВ';
                    break;
                case '000000000007':
                    $pfx = 'ЛВ';
                    break;
                case 'СУЗ':
                    $pfx = 'СУЗ';
                    break;
            }
            $str .= '<tr><td>' . $item->fio . '</td><td>"' . $item->loan_id_1c . '"</td><td>' . $item->id . '/' . $pfx . '</td><td>' . with(new Carbon($item->created_at))->format('d.m.Y') . '</td></tr>';
//            $str .= $item->fio.';'.$item->loan_id_1c.';'.$item->id.'/'.$pfx.'\\r\\n';
        }
        $str .= '</table>';
        echo $str;
    }

    public function resendUnicomStatuses() {
        $claims = \App\Claim::where('subdivision_id', 721)->whereNotNull('teleport_status')->get();
        foreach ($claims as $claim) {
            $status = \App\UnicomData::STATUS_RECEIVED;
            if ($claim->teleport_status == 'double') {
                $status = \App\UnicomData::STATUS_REJECTED;
            } else if ($claim->teleport_status == 'sell') {
                $status = \App\UnicomData::STATUS_APPROVED;
            } else if ($claim->teleport_status == 'cancel') {
                $status = \App\UnicomData::STATUS_DECLINED;
            }
            \PC::debug(\App\Utils\Unicom::sendAndUpdateStatus($claim->id_teleport, $status));
        }
    }

    public function changeAllUserDocs() {
        $users = User::where('banned', 0)->get();
        $i = 0;
        $changed = 0;
        foreach ($users as $u) {
            $doc_params = explode(' от ', $u->doc);
            if (count($doc_params) == 1) {
                continue;
            }
            $datestr = $doc_params[1];
            $datestr = str_replace(' января ', '.01.', $datestr);
            $datestr = str_replace(' февраля ', '.02.', $datestr);
            $datestr = str_replace(' марта ', '.03.', $datestr);
            $datestr = str_replace(' апреля ', '.04.', $datestr);
            $datestr = str_replace(' мая ', '.05.', $datestr);
            $datestr = str_replace(' июня ', '.06.', $datestr);
            $datestr = str_replace(' июля ', '.07.', $datestr);
            $datestr = str_replace(' августа ', '.08.', $datestr);
            $datestr = str_replace(' сентября ', '.09.', $datestr);
            $datestr = str_replace(' октября ', '.10.', $datestr);
            $datestr = str_replace(' ноября ', '.11.', $datestr);
            $datestr = str_replace(' декабря ', '.12.', $datestr);
            $datestr = preg_replace('/[^\\d.]+/', '', $datestr);
            $date = new Carbon($datestr);
            if ($date->lt(new Carbon('2017-03-21'))) {
                $ndoc = $doc_params[0] . ' от 21 марта 2017 г.';
                \PC::debug(['user' => $u->name, 'doc_was' => $u->doc, 'doc_new' => $ndoc]);
                $u->doc = $ndoc;
                $u->save();
                $changed++;
            }
            $i++;
        }
        \PC::debug(['total' => count($users), 'changed' => $changed]);
    }

    function resendWorkTime() {
        $worktime = \App\WorkTime::find('130942');
        $user = User::find($worktime->user_id);
        $subdiv = Subdivision::find($worktime->subdivision_id);
        $dateStart = new Carbon('2018-04-04');
        $data = [
            'id_1c' => ((is_null($worktime->id_1c)) ? "" : $worktime->id_1c),
            'created_at' => (is_null($worktime->created_at)) ? Carbon::now()->format("Ymd") : $worktime->created_at->format("Ymd"),
            'user_id_1c' => $user->id_1c,
            'date_start' => (is_null($worktime->date_start)) ? Carbon::now()->format('YmdHis') : (with(new Carbon($worktime->date_start))->format('YmdHis')),
            'date_end' => (is_null($worktime->date_end)) ? "00010101" : with(new Carbon($worktime->date_end))->format('YmdHis'),
            'subdivision_id_1c' => (!is_null($subdiv) && !is_null($subdiv->name_id)) ? $subdiv->name_id : "",
            'comment' => (is_null($worktime->comment)) ? "" : $worktime->comment,
            'evaluation' => $worktime->evaluation,
            'review' => (is_null($worktime->review)) ? "" : $worktime->review,
            'reason' => (is_null($worktime->reason)) ? "" : $this->JSONtoXML(json_decode($worktime->reason, true), $dateStart),
            'birth_date' => (is_null($user->birth_date)) ? '' : with(new Carbon($user->birth_date))->format('Ymd')
        ];
        $res1c = MySoap::saveWorkTime($data);
        \PC::debug($res1c);
    }

    public function JSONtoXML($json, $date = null) {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');
        if (is_null($date)) {
            $date = Carbon::now();
        }
        if (is_array($json)) {
            foreach ($json as $item) {
                $xml_item = $xml->addChild('item');
                $xml_item->addAttribute('date_start', $date->format('Ymd') . (substr($item["absent_start"], 0, strpos($item["absent_start"], ':'))) . (substr($item["absent_start"], strpos($item["absent_start"], ':') + 1)) . '00');
                $xml_item->addAttribute('date_end', $date->format('Ymd') . (substr($item["absent_end"], 0, strpos($item["absent_end"], ':'))) . (substr($item["absent_end"], strpos($item["absent_end"], ':') + 1)) . '00');
                $xml_item->addAttribute('reason', $item["absent_reason"]);
                $xml_item->addAttribute('time', with(new Carbon($item["absent_start"]))->diffInMinutes(new Carbon($item["absent_end"])));
            }
        }
        return $xml->asXML();
    }

    function writeTerminalReports() {
        $terminals = \App\Terminal::all();
        foreach ($terminals as $t) {
            $t->updateTerminalReport();
        }
    }

    function sendScoristaTo1c() {
        $claims = \App\Claim::where('agrid', 'agrid5a584a6c6e144')->first();
        $claim->scorista_status = $data->status;
        $claim->scorista_decision = $data->data->decision->decisionBinnar;
        $conscientiousnessNPL15 = $data->data->additional->creditHistory->score;
        $reliabilityNPL15 = $data->data->additional->trustRating->score;
        $res1c = MySoap::sendExchangeArm(MySoap::createXML([
                            'type' => 'SetScoristaDecision',
                            'claim_id_1c' => $claim->id_1c,
                            'decisionBinnar' => $claim->scorista_decision,
                            'conscientiousnessNPL15' => (empty($conscientiousnessNPL15) || $conscientiousnessNPL15 == 'null') ? '' : $conscientiousnessNPL15,
                            'reliabilityNPL15' => (empty($reliabilityNPL15) || $reliabilityNPL15 == 'null') ? '' : $reliabilityNPL15
        ]));
        $claim->save();
    }

    function sendScorista() {
        $username = 't.popova@pdengi.ru';
        $token = '3d7f9600bd6b0352dae50eaac030f870e4dbe4c3';

        $date = Carbon::now()->subMinute()->format('Y-m-d H:i:s');
//        $claims = \App\Claim::where('agrid', 'agrid5a584a6c6e144')->get();
        $claims = \App\Claim::whereNotNull('agrid')->where('created_at', '>=', '2018-01-01')->whereNull('scorista_decision')->get();
        \PC::debug(count($claims));
//        $claims = Claim::whereRaw('agrid is not null and (scorista_status is null or scorista_status not in("ERROR","DONE")) and updated_at<\'' . $date . '\'')->orderBy('created_at', 'desc')->limit(50)->get();
        foreach ($claims as $claim) {
            $nonce = sha1(uniqid(true));
            $password = sha1($nonce . $token);
            $params = ['requestid' => $claim->agrid];
            $headers = ['username' => $username, 'password' => $password, 'nonce' => $nonce];
            $response = HelperUtil::SendPostByCurl('https://api.scorista.ru/mixed/json', $params, $headers);
            $data = json_decode($response);
            if (is_null($data)) {
                Log::error('Scorista', ['response' => $response]);
                continue;
            }
            \PC::debug($data);
            Log::info('Scorista', ['nonce' => $nonce, 'params' => $params, 'headers' => $headers, 'requestid' => $claim->agrid, 'data' => $data]);
            if (isset($data->status)) {
                $claim->scorista_status = $data->status;
                if ($data->status == 'DONE') {
                    $claim->scorista_decision = $data->data->decision->decisionBinnar;
                    $conscientiousnessNPL15 = $data->data->additional->creditHistory->score;
                    $reliabilityNPL15 = $data->data->additional->trustRating->score;
                    $res1c = MySoap::sendExchangeArm(MySoap::createXML([
                                        'type' => 'SetScoristaDecision',
                                        'claim_id_1c' => $claim->id_1c,
                                        'decisionBinnar' => $claim->scorista_decision,
                                        'conscientiousnessNPL15' => (empty($conscientiousnessNPL15) || $conscientiousnessNPL15 == 'null') ? '' : $conscientiousnessNPL15,
                                        'reliabilityNPL15' => (empty($reliabilityNPL15) || $reliabilityNPL15 == 'null') ? '' : $reliabilityNPL15
                    ]));
                }
                $claim->save();
            }
        }
    }

    function terminalRefill() {
        $order = new \App\Order();
        $order->fill([
            'money' => '6.66',
            'passport_id' => '290219',
            'type' => \App\OrderType::getIdByTextId(\App\OrderType::TERMINALREFILL),
            'user_id' => auth()->user()->id,
            'subdivision_id' => '702'
        ]);
        return $order->saveThrough1c();
    }

    function makeTerminalCashout() {
        $creditID = '';
        $PayPointID = '2';
        $Amount = '0';
        $ExtInt = 0;
        $created_at = '';
        $loan = Loan::where('claim_id', $creditID)->first();
        if (is_null($loan)) {
            \PC::debug('no loan');
            return false;
        }
        $terminal = Terminal::find($PayPointID);
        if (is_null($terminal)) {
            \PC::debug('no terminal');
            return false;
        }
        $terminal->dispenser_count -= $ExtInt;
        if (!is_null($loan->claim) && !is_null($loan->claim->customer)) {
            $customer = $loan->claim->customer;
            $customer->balance -= $Amount * 100;
            DB::beginTransaction();
            $action = new TerminalAction();
            $action->CreditID = $creditID;
            $action->ClientID = $customer->id;
            $action->ActionType = 10;
            $action->created_at = $created_at;
            $action->updated_at = $created_at;
            if (!$action->save()) {
                DB::rollback();
                return false;
            }
            if (!$customer->save()) {
                DB::rollback();
                return false;
            }
            if (!$terminal->save()) {
                DB::rollback();
                return false;
            }
            DB::commit();
        }
    }

    function sendPayturePay() {
        $fields = [
            'date' => '20180206020910',
            'loan_id_1c' => '00000935664',
            'customer_id_1c' => '000208202',
            'amount' => '6447',
            'type' => 'AddPayture'
        ];
        $connection = [
//            'url' => '192.168.1.21:443/PersonaArea1/ws/Payture/?wsdl',
            'url' => '192.168.1.34:81/PersonaArea1/ws/Payture/?wsdl',
//            'url' => '192.168.1.47:8080/Repository/ws/Payture/?wsdl',
            'login' => 'KAadmin',
            'password' => 'Dune25',
            'absolute_url' => true
        ];
        \PC::debug(MySoap::call1C('main_payture', ['params' => MySoap::createXML($fields)], false, false, $connection));
    }

    function sendDerealization() {
//        $repayment = \App\Repayment::where('id_1c','000047055')->first();
        $subdiv = Subdivision::find(547);
        $loan = Loan::find(382756);
        $user = User::find(194);
        $xml = [
            'created_at' => Carbon::now()->format('YmdHis'),
            'Number' => '',
            'customer_id' => $loan->claim->customer->id_1c,
            'money' => \App\StrUtils::kopToRub(52800), //сумма которую платит
            'moneyRKO' => \App\StrUtils::kopToRub(19200), //сумма которая осталась
            'loan_id_1c' => $loan->id_1c,
            'type' => 'CreateDeRealization',
            'subdivision_id_1c' => $subdiv->name_id,
            'user_id_1c' => $user->id_1c
        ];
        \PC::debug($xml);

//        if ($dopComCalc->money_spent <= 0) {
//            Log::error('RepaymentController.createDopCommissionDerealization dopcomcalc.money_spent<=0', ['dopcomcalc' => $dopComCalc, 'repayment' => $repayment]);
//            return 0;
//        }
        $resRealiz = MySoap::sendExchangeArm(MySoap::createXML($xml));
//        return ((int) $resRealiz->result == 1);
    }

    function sendAspForOneClient() {
        $customer = \App\Customer::where('id_1c', 'П00029018')->first();
        \PC::debug($customer->generateAspKey());
        $customer->asp_key = $customer->generateAspKey();
        $customer->asp_generated_at = $customer->asp_approved_at->copy()->subMinute();
        $customer->save();
        $customer->sendAspTo1c();
    }

    function resendAsp() {
//        $customer = \App\Customer::where('id_1c','П00043508')->first();
//        \PC::debug($customer->generateAspKey());
//                    $customer->asp_key = $customer->generateAspKey();
//            $customer->asp_generated_at = $customer->asp_approved_at->copy()->subMinute();
//            $customer->save();
//        $customer->sendAspTo1c();
//        \PC::debug(\App\Customer::whereNull('asp_approved_at')->whereNotNull('asp_key')->whereNotNull('asp_generated_at')->count(), 'customers_to_send_count');
        $customers = \App\Customer::whereNotNull('asp_approved_at')->whereNotNull('asp_key')->whereNull('asp_generated_at')->limit(1000)->get();
        foreach ($customers as $customer) {
//            \PC::debug($customer->toArray(),'before');
            $customer->asp_key = $customer->generateAspKey();
            $customer->asp_generated_at = $customer->asp_approved_at->copy()->subMinute();
////            $subdivision_id_1c = Loan::leftJoin('claims','claims.id','=','loans.claim_id')
////                    ->leftJoin('subdivisions','subdivisions.id','=','loans.subdivision_id')
////                    ->whereBetween('loans.created_at',[$customer->asp_approved_at->copy()->setTime(0,0,0),$customer->asp_approved_at->copy()->setTime(23,59,59)])
////                    ->where('claims.customer_id',$customer->id)
////                    ->value('subdivisions.name_id');
//            $subdivision_id_1c = Order::leftJoin('passports','passports.id','=','orders.passport_id')
//                    ->whereBetween('loans.created_at',[$customer->asp_approved_at->copy()->setTime(0,0,0),$customer->asp_approved_at->copy()->setTime(23,59,59)])
//                    ->get();
//            \PC::debug($subdivision_id_1c);
            $customer->save();
            $customer->sendAspTo1c();
//            \PC::debug($customer->toArray(),'after');
            sleep(2);
        }
    }

    function resolveAspDates() {
        $customers = \App\Customer::whereRaw('asp_approved_at<asp_generated_at')->whereNotNull('asp_approved_at')->whereNotNull('asp_generated_at')->limit(1000)->get();
        foreach ($customers as $item) {
            $item->asp_approved_at = $item->asp_generated_at->copy()->addMinute();
            $item->save();
        }
    }

    function solveTerminalOrderSubdivisionProblem() {
        $actions = \App\TerminalAction::where('terminal_actions.created_at', '>', '2017-12-20')->where('ActionType', '10')->where('Amount', '>', '0')
                ->whereNotIn('id', [11014, 11015])
                ->get();
//        $actions = \App\TerminalAction::where('terminal_actions.created_at','>','2017-12-20')->where('ActionType','10')->where('Amount','>','0')->where('id','11021')->get();
        foreach ($actions as $a) {
            $loan = Loan::where('claim_id', $a->CreditID)->first();
            $actionTerminal = \App\Terminal::where('pay_point_id', $a->PayPointID)->first();
            if (!is_null($actionTerminal) && !is_null($actionTerminal->user) && !is_null($actionTerminal->user->subdivision) && !is_null($loan) && !is_null($loan->order) && !is_null($loan->order->subdivision)) {
                if ($actionTerminal->user->subdivision->id != $loan->order->subdivision->id) {
                    \PC::debug(['action' => $a->toArray(), 'fio' => $loan->claim->passport->fio, 'action_subdiv' => $actionTerminal->user->subdivision->name, 'order_subdiv' => $loan->order->subdivision->name, 'order' => $loan->order->toArray()]);
//                    MySoap::sendExchangeArm(MySoap::createXML(['type' => 'ChangeOrderSubdivision', 'order_number' => $loan->order->number, 'subdivision_id_1c' => $actionTerminal->user->subdivision->name_id]));
                }
            }
        }
    }

    function solveTerminalOrderSubdivisionProblem2() {
        $actions = \App\TerminalAction::where('terminal_actions.created_at', '>', '2017-12-01')->where('Amount', '>', '0')->get();
        foreach ($actions as $a) {
            $loan = Loan::where('claim_id', $a->CreditID)->first();
            $actionTerminal = \App\Terminal::where('pay_point_id', $a->PayPointID)->first();
//            $order = Order::where()
//            if(!is_null($actionTerminal) && !is_null($actionTerminal->user) && !is_null($actionTerminal->user->subdivision) && !is_null($loan) && !is_null($loan->order) && !is_null($loan->order->subdivision)){
//                if($actionTerminal->user->subdivision->id != $loan->order->subdivision->id){
//                    \PC::debug(['action'=>$a->toArray(),'fio'=>$loan->claim->passport->fio, 'action_subdiv'=>$actionTerminal->user->subdivision->name,'order_subdiv'=>$loan->order->subdivision->name, 'order'=>$loan->order->toArray()]);
////                    MySoap::sendExchangeArm(MySoap::createXML(['type'=>'ChangeOrderSubdivision','order_number'=>$loan->order->number,'subdivision_id_1c'=>$actionTerminal->user->subdivision->name_id]));
//                }
//            }
        }
    }

    public function resendTeleportclaims() {
        $teleportCtrl = new TeleportController();
        $claims = \App\Claim::whereNull('id_1c')->where('created_at', '>', '2017-12-01')->where('subdivision_id', 658)->limit(307)->get();
        \PC::debug(count($claims), 'claims num');
        foreach ($claims as $claim) {
            $res = $teleportCtrl->sendClaimTo1c($claim);
            sleep(15);
        }
        \PC::debug('ready');
    }

    public function rewriteDateForCustomersApprovedAt() {
//        $customers = \App\Customer::where('')
    }

    public function getCustomersWithNoAspSignAndAspApproved() {
        
    }

    function getSmsClaimsWithoutLoan() {
        $sql = "SELECT distinct(sms_inbox.phone) as phone, sms_inbox.created_at as sms_date
                FROM armf.sms_inbox 
                where sms_inbox.message like 'Заем%' or sms_inbox.message like 'ЗАЕМ%' or sms_inbox.message like 'заем%'
                and sms_inbox.created_at>='2017-11-01'
                order by sms_inbox.created_at desc
                limit 2000";
        $openedLoanSql = "SELECT count(*) as loans_num FROM armf.loans
                            left join claims on claims.id = loans.claim_id
                            left join customers on customers.id = claims.customer_id
                            where loans.closed = 0 and customers.telephone=?";
        $haveClaimAfterSmsSql = "SELECT count(*) as claims_num FROM armf.claims
                            left join customers on customers.id = claims.customer_id
                            where customers.telephone=? and claims.created_at>=? and claims.status>0";
        $lastSmsIsNoAsp = "SELECT message FROM armf.sms_sent where telephone=? order by created_at desc limit 1";
        $noAspSmsText = 'Данный способ для получения займа недоступен. Для получения займа необходимо пройти в удобное Вам отделение продаж для подписания документов. ФинТерра88003014344';
        $phones = DB::connection('arm115')->select($sql);
        $res = [];
        $total = 0;
        foreach ($phones as $p) {
            $lastSentSms = DB::connection('arm115')->select($lastSmsIsNoAsp, [$p->phone]);
            if (count($lastSentSms) > 0 && $lastSentSms[0]->message == $noAspSmsText) {
                $haveClaim = DB::connection('arm115')->select($haveClaimAfterSmsSql, [$p->phone, $p->sms_date]);
                if ($haveClaim[0]->claims_num == 0) {
                    $openedLoan = DB::connection('arm115')->select($openedLoanSql, [$p->phone]);
                    if ($openedLoan[0]->loans_num == 0) {
                        echo $p->phone . '<br>';
                        $total++;
                    }
                }
            }
        }
        echo '<hr>' . $total;
    }

    function hophey() {
//        $save = ['type'=>'SaveTeleportMeeting','customer_id_1c'=>'-','subdivision_id_1c'=>'-','date'=>'20171108100000'];
//        \PC::debug(MySoap::createXML($save));
//        $get = ['type'=>'GetSubdivisionMeetingSchedule','subdivision_id_1c'=>'-','date'=>'20171108'];
//        \PC::debug(MySoap::createXML($get));
//        $resp = ['start_time'=>'10:00:00','end_time'=>'19:00:00','break_time'=>'14:00:00','schedule'=>['time'=>'10:00:00','customer_id_1c'=>'-','fio'=>'фыв фыв фыв']];
//        \PC::debug(MySoap::createXML($resp));
        $resp = ['id_1c' => '-', 'result' => 1];
        \PC::debug(MySoap::createXML($resp));
    }

    function setResponsiblesOnDebtors() {
        $debtors22 = Debtor::where('debtors.qty_delays', 82)
                //leftJoin('debtors', 'debtors.debtor_id_1c', '=', 'debtor_events.debtor_id_1c')
//                ->where('debtors.base','Б-0')
//                ->where('debtors.refresh_date', '2017-09-27 01:02:04')
                ->groupBy('debtors.debtor_id_1c')
                ->get();
        $i = 1;
        $s = 0;
        $f = 0;
        foreach ($debtors22 as $dbt) {
            $lastevent = DebtorEvent::where('created_at', '>=', '2017-09-26')
                    ->where('created_at', '<', '2017-09-27')
                    ->where('debtor_id_1c', $dbt->debtor_id_1c)
                    ->where('date', '0000-00-00 00:00:00')
                    ->orderBy('created_at', 'desc')
                    ->first();
            $debtor = \App\Debtor::where('debtor_id_1c', $dbt->debtor_id_1c)->first();
//            $passport = Passport::where('series', $dbt->passport_series)->where('number', $dbt->passport_number)->first();

            $str = $i . ' ' . $debtor->debtor_id_1c;
            if (!is_null($lastevent)) {
//                $str .= $lastevent->user_id_1c;
//                $debtor->responsible_user_id_1c = $lastevent->user_id_1c;
//                $debtor->refresh_date = '2017-09-27 01:02:03';
//                $debtor->base = 'Б-1';
                $debtor->str_podr = '000000000007';
                $debtor->fixation_date = '2017-09-26 01:02:04';
                $debtor->debt_group_id = $lastevent->debt_group_id;
                \PC::debug($debtor->toArray());
                $debtor->save();
                $s++;
//                \PC::debug($str);
            } else {
                $f++;
//                \PC::debug($str);  
            }
//            \PC::debug($str);
            $i++;
        }
        \PC::debug(['total' => count($debtors22), 'success' => $s, 'fail' => $f]);
    }

    public function index(Request $req) {
        if ($req->has('send_teleport')) {
            return $this->sendUnsentTeleportClaims();
        }
//        \PC::debug(\App\Utils\SMSer::sendByGoIpApi('79059060315', 'whoop'),'send by goip');
        if ($req->has('handle_inbox')) {
            if ($req->has('goip')) {
                \App\SmsInbox::uploadFromGoIpDb();
//                $date = \App\SmsInbox::where('processed',0)->min('created_at');
//                if(is_null($date)){
                return \App\SmsInbox::handleInbox('2017-05-24');
//                } else {
//                    return 'Нет необработанных смс';
//                }
            } else {
                return \App\SmsInbox::handleInbox('2017-05-24');
            }
        }
        if ($req->has('message') && $req->has('phone')) {
            $sms = new \App\SmsInbox([
                'message' => $req->get('message'),
                'phone' => $req->get('phone'),
                'sent' => Carbon::now()->format('Y-m-d H:i:s'),
                'received' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            $sms->save();
            return redirect('adminpanel/tester');
        }
        if ($req->has('func')) {
            return $this->handleFunc($req->get('func'));
        }
        if ($req->has('sms')) {
            return view('adminpanel.sms_tester');
        } else {
            return view('adminpanel.tester');
        }
    }

    public function handleFunc($func) {
        return $this->$func();
    }

    public function soapLoadTesting() {
        return view('adminpanel.load_tester');
    }

    public function ajaxSendSoapRequest(Request $req) {
        $module_1c = $req->get('module_1c');
        $params = $req->get('params');
        $function_1c = $req->get('function_1c');
        $password = $req->get('password_1c', config('1c.password'));
        if (array_key_exists('response', $params)) {
            unset($params['response']);
        }
        $isXML = in_array($module_1c, ['Mole', 'ExchangeARM', 'ArmOut']);
        if ($isXML) {
            $params = ['params' => MySoap::createXML($params)];
        }
        Log::info('url', [$req->get('server_1c') . '/ws/' . $module_1c . '/?wsdl']);
        $res1c = MySoap::call1C($function_1c, $params, false, false, [
                    'url' => $req->get('server_1c') . '/ws/' . $module_1c . '/?wsdl',
                    'password' => (empty($password)) ? config('1c.password') : $password,
                    'user' => $req->get('user_1c'),
                    'absolute_url' => true
                        ], false, $isXML);
        return $res1c;
    }

    /**
     * 
     * @param Request $req
     */
    public function checkWsdl(Request $req) {
        $url = 'http://192.168.1.31/11SPD34/ws/ARM/?wsdl';
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_USERPWD, 'KAadmin' . ":" . 'Dune25');

        /* Get the HTML or whatever is linked in $url. */
        $res = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        \PC::debug([$res, $httpCode]);
        if ($httpCode == 200) {
            return true;
        } else {
            return false;
        }
    }

    public function sendIssueForAllUsers() {
        $users = \App\User::where('banned', 0)->get();
        foreach ($users as $user) {
            $passport = \App\Passport::where('fio', $user->name)->where('birth_date', $user->birth_date)->first();
            if (!is_null($passport)) {
                MySoap::sendExchangeArm(MySoap::createXML([
                            'money' => 300000,
                            'reason' => '',
                            'comment' => '',
                            'passport_series' => $passport->series,
                            'passport_number' => $passport->number,
                            'customer_id_1c' => $passport->customer->id_1c,
                            'user_id_1c' => 'KAadmin',
                            'subdivision_id_1c' => '001',
                            'order_Type' => '24',
                            'type' => 'CreateClaimForIssue',
                            'items' => [
                                [
                                    'goal' => 'азазаза',
                                    'money' => '300000'
                                ]
                            ]
                        ]), false);
            }
        }
    }

    public function getLogTimes() {
        echo 'AMAZING!<br>';
        $data = DB::connection('spylogs115')
                ->table('logs')
                ->whereBetween('logs.created_at', ['2017-05-12 10:15:00', '2017-05-12 11:10:00'])
                ->leftJoin('log_data', 'log_data.id', '=', 'logs.data_id')
                ->where('action', \App\Spylog\Spylog::ACTION_CALL1C)
//                ->limit(1000)
                ->get();

        $fp = fopen('file.csv', 'w');
        foreach ($data as $d) {
            $row = [];
            $json = json_decode($d->data);
            $row['name'] = $json->name;
            $row['start'] = $json->_start_req_date;
            $row['end'] = $json->_end_req_date;
            $row['time'] = $json->_req_time;
            $row['params'] = (is_object($json->params)) ? html_entity_decode($string) : html_entity_decode($json->params);
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    function teleportDuplicatesTest() {
        $claims = \App\Claim::whereNotNull('id_teleport')->where('created_at', '>', Carbon::today()->format('Y-m-d'))->whereNull('id_1c')->get();
        $duplicates = [];
        foreach ($claims as $claim) {
            $dupls = \App\Claim::where('id_teleport', $claim->id_teleport)->where('id', '<>', $claim->id)->whereNotNull('id_1c')->get();
            foreach ($dupls as $d) {
                $customer = $d->customer;
                $passport = $d->passport;
                $ordersOnPassport = \App\Order::where('passport_id', $passport->id)->count();
                $passportsOnClaim = \App\Claim::where('passport_id', $customer->id)->where('id', '<>', $d->id)->count();
                $claimsOnCustomer = \App\Claim::where('customer_id', $customer->id)->where('id', '<>', $d->id)->count();
                if ($ordersOnPassport == 0 && $passportsOnClaim == 0 && $claimsOnCustomer == 1) {
                    \PC::debug(['cust' => $customer->toArray(), 'passport' => $passport->toArray(), 'oop' => $ordersOnPassport, 'coc' => $claimsOnCustomer, 'd' => $d->toArray(), 'claim' => $claim->toArray()], 'delete');
                    $claim->passport->delete();
                    $claim->customer->delete();
                }
//                \PC::debug(['cust' => $customer->toArray(), 'passport' => $passport->toArray(), 'oop' => $ordersOnPassport, 'coc' => $claimsOnCustomer, 'poc' => $passportsOnClaim, 'd' => $d->toArray(), 'claim' => $claim->toArray()], 'fail');
                try {
                    $claim->delete();
                } catch (\Exception $ex) {
                    
                }
//                die();
            }
        }

        \PC::debug(['dupl' => $duplicates]);
    }

    function createUserCertificate() {
        $opensslConf = "/var/www/armff.ru/storage/app/ssl/openssl.cnf";
        $config = array('config' => $opensslConf);
//        $client_keys_folder = storage_path('app/ssl/clients/');
        $req_key = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        $key_exported = openssl_pkey_export($req_key, $out_key);
//        Storage::disk('local')->put('ssl/clients/' . auth()->user()->name . '.key', $out_key);
        if ($key_exported) {
            $sign_config = array(
                "countryName" => "RU",
                "stateOrProvinceName" => "N/A",
                "localityName" => "Kemerovo",
                "organizationName" => "Finterra",
                "organizationalUnitName" => "IT",
                "commonName" => auth()->user()->name,
                "emailAddress" => "N/A"
            );
            $req_csr = openssl_csr_new($sign_config, $req_key);

            \PC::debug($req_csr, 'req_csr');
            $ca_cert = openssl_x509_read(file_get_contents('/var/www/armff.ru/storage/app/ssl/ca/root.pem'));
//            $ca_cert = file_get_contents('/var/www/armff.ru/storage/app/ssl/ca/root.pem');
//            \PC::debug($ca_cert,'ca_cert');
            $priv_key = openssl_pkey_get_private(file_get_contents('/var/www/armff.ru/storage/app/ssl/ca/root.key'));
//            $priv_key = file_get_contents('/var/www/armff.ru/storage/app/ssl/ca/root.key');
            \PC::debug($priv_key);
            $req_cert = openssl_csr_sign($req_csr, $ca_cert, $priv_key, 3650, ['config' => $sign_config]);
            \PC::debug($req_cert, 'req_cert');
            if ($req_cert === FALSE) {
                \PC::debug('FAIL TO SIGN');
                return;
            }
            $cert_exported = openssl_x509_export($req_cert, $out_cert);
            if ($cert_exported) {
                echo "$out_key\n";
                echo "$out_cert\n";
                Storage::disk('local')->put('ssl/clients/' . auth()->user()->name . '.crt', $out_cert);
            } else {
                echo "Failed Cert\n";
            }
        } else {
            echo "FailedKey\n";
        }
    }

    public function createUserSSLByBash() {
        $filename = 'client' . auth()->user()->id;
        $certsFolder = '/var/www/armff.ru/storage/app/ssl/clients/';
        $clientFilepath = $certsFolder . $filename;
        $rootFilePath = '/var/www/armff.ru/storage/app/ssl/ca/root';
        $p12Password = '1';

        $com1 = $this->startShellProcess('openssl genrsa -out ' . $clientFilepath . '.key 2048');
        $com2 = $this->startShellProcess('openssl req -new -key ' . $clientFilepath . '.key -subj "/C=RU/ST=NA/L=Kemerovo/O=Finterra/OU=Sales/CN=' . $filename . '" -out ' . $clientFilepath . '.csr');
        $com3 = $this->startShellProcess('openssl x509 -req -in ' . $clientFilepath . '.csr -CA ' . $rootFilePath . '.pem -CAkey ' . $rootFilePath . '.key -CAcreateserial -out ' . $clientFilepath . '.pem -days 3650 -set_serial 1024');
        $com4 = $this->startShellProcess('openssl pkcs12 -export -in ' . $clientFilepath . '.pem -inkey ' . $clientFilepath . '.key -certfile ' . $rootFilePath . '.pem -out ' . $clientFilepath . '.p12 -password pass:' . $p12Password);
    }

    function startShellProcess($str) {
        $process = new Process($str);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
//            throw new ProcessFailedException($process);
            \PC::debug(new ProcessFailedException($process), 'error');
            return FALSE;
        }
        return $process->getOutput();
    }

    function updateDailyCashReportsFrom1c() {
        $reports = \App\DailyCashReport::where('subdivision_id', 484)->where('created_at', '>', '2017-11-14')->where('created_at', '<', '2017-12-02')->get();
        foreach ($reports as $r) {
            $res1c = MySoap::getCashbookBalance(with(new Carbon($r->created_at))->format('Ymd'), $r->subdivision->name_id);
            $r->start_balance = $res1c['cashStart'] * 100;
            $r->end_balance = $res1c['cashEnd'] * 100;
            $r->report_start_balance = $r->start_balance;
            $r->report_end_balance = $r->end_balance;
            $r->save();
            sleep(1);
        }
        \PC::debug('ready');
    }

    function updateDailyCashReports($subdiv_id, $start_date, $end_date) {
        $reports = \App\DailyCashReport::where('subdivision_id', $subdiv_id)->where('created_at', '>', $start_date)->where('created_at', '<', $end_date)->get();
        foreach ($reports as $r) {
//            $r->edit_enabled = 1;
            $r->report_start_balance = $r->start_balance;
            $r->report_end_balance = $r->end_balance;
            $r->save();
        }
    }

    function uploadBankOrdersFromCSV() {
        set_time_limit(600);
        if (($handle = fopen(storage_path() . '/app/debtors/pays.txt', 'r')) !== false) {
            $pid = 0;
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                $pid++;
                $order = \App\Order::where('number', $data[0])->first();
                if (is_null($order)) {
                    $respUser = User::where('id_1c', $data[2])->first();
                    $subdivision = Subdivision::where('id', '113')->first();
                    $passport = Passport::where('series', $data[11])->where('number', $data[12])->first();
                    if (empty($data[7])) {
                        continue;
                    }
                    $loan = Loan::where('id_1c', $data[7])->first();
//                    if($pid<600){
//                        continue;
//                    }
//                    if ($pid > 600) {
////                        \PC::debug([$passport,$loan,$respUser,$data]);
//                        break;
//                    }

                    if (is_null($loan)) {
                        continue;
                    }

                    if (is_null($passport)) {
                        continue;
                    }

                    if ((strstr($data[6], 'Возврат процентов') !== FALSE || strstr($data[6], 'Оплата процентов и основного долга') !== FALSE) && $data[8] == '62.01') {
                        $purpose = Order::P_PC;
                    } else
//                if ((strstr($reason, 'Возврат процентов') !== FALSE || strstr($reason, 'Оплата процентов и основного долга') !== FALSE || strstr($item, 'Просроченные проценты') !== FALSE) && $account == '62.04') {
                    if ($data[8] == '62.04') {
                        $purpose = Order::P_EXPPC;
                    } else if ((strstr($data[6], 'основного долга') !== FALSE || strstr($data[6], 'Оплата процентов и основного долга') !== FALSE) && $data[8] == '58.03') {
                        $purpose = Order::P_OD;
                    } else if ($data[8] == '76.02') {
                        $purpose = Order::P_FINE;
                    } else if ($data[8] == '76.09' || $data[8] == '76.12') {
                        if (strstr($data[6], 'НДС с комиссии') !== FALSE) {
                            $purpose = Order::P_COMMISSION_NDS;
                        } else if (strstr($data[6], 'НДС') !== FALSE) {
                            $purpose = Order::P_UKI_NDS;
                        } else if (strstr($data[6], 'Комиссия за продление') !== FALSE) {
                            $purpose = Order::P_COMMISSION;
                        } else if (strstr($data[6], 'Комиссия') !== FALSE) {
                            $purpose = Order::P_UKI;
                        }
                    } else if ($data[8] == '76.10') {
                        $purpose = Order::P_TAX;
                    }

                    $new_order = new Order();
                    $new_order->type = 22;
                    try {
                        $new_order->created_at = Carbon::createFromFormat('d.m.Y H:i:s', $data[1])->format('Y-m-d H:i:s');
                    } catch (\Exception $ex) {
                        $new_order->created_at = null;
                    }
                    $new_order->user_id = (!is_null($respUser)) ? $respUser->id : 18;
                    $new_order->subdivision_id = 113;
                    $new_order->money = $data[5] * 100;
                    $new_order->passport_id = $passport->id;
                    $new_order->reason = $data[6];
                    if (isset($purpose)) {
                        $new_order->purpose = $purpose;
                    }
                    $new_order->loan_id = $loan->id;
                    $new_order->used = 1;
//                    if (!empty($data[9])) {
//                        $new_order->comment = $data[9];
//                    }
                    $new_order->comment = '777';
                    $new_order->fio = $data[10];
                    if (!empty($data[11]) && !empty($data[12]) && !empty($data[13]) && !empty($data[14]) && !empty($data[15])) {
                        $new_order->passport_data = 'Паспорт гражданина Российской Федерации, серия: ' . $data[11] . ', № ' . $data[12] . ', выдан: ' . \App\StrUtils::dateToStr($data[13]) . ', ' . $data[14] . ', № подр. ' . $data[15];
                    }
                    $new_order->sync = 1;

                    $new_order->save();

                    unset($purpose);
                }
            }
            fclose($handle);
        }
    }

    function resurectReplica() {
        $slave_data = DB::select(DB::raw('show slave status'));
        if ($slave_data[0]->Slave_SQL_Running == 'No') {
            $master_data = DB::connection('arm115')->select(DB::raw('show master status'));
            DB::select(DB::raw('STOP SLAVE'));
            DB::select(DB::raw("CHANGE MASTER TO MASTER_LOG_FILE='" . $master_data[0]->File . "', MASTER_LOG_POS=" . $master_data[0]->Position . ";"));
            DB::select(DB::raw('START SLAVE'));
        }
    }

    function removeDuplicateEvents() {
//        for ($i = 0; $i < 10; $i++) {
        $events = DebtorEvent::where('created_at', '>=', '2017-09-27')
                ->where('created_at', '<', '2017-09-28')
                ->where('id_1c', 'like', 'Б%')
//                    ->skip($i*500)
//                ->take(500)
                ->get();
//            \PC::debug($events, 'eventsnum');
        $i = 1;
        foreach ($events as $event) {
            $dupl = DebtorEvent::where("debtor_id_1c", $event->debtor_id_1c)
                    ->where('id_1c', 'like', 'М%')
                    ->where('id', '<>', $event->id)
                    ->where('created_at', $event->created_at)
                    ->first();
            if (!is_null($dupl)) {
                $debtor = \App\Debtor::where('debtor_id_1c', $event->debtor_id_1c)->first();
                $passport = Passport::where('series', $debtor->passport_series)->where('number', $debtor->passport_number)->first();
                if ($dupl->report == $event->report || (empty($dupl->report) && empty($event->report))) {
                    \PC::debug([$event->toArray(), $dupl->toArray(), $passport->fio], 'event' . $i);
                    $dupl->delete();
                }
                $i++;
            }
        }
//            sleep(2);
//        }
    }

    function updateAllUsersWithDebtors() {
        $users = DB::select(DB::raw('SELECT users.id FROM users
left join role_user on role_user.user_id = users.id
where role_user.role_id = 11'));
        foreach ($users as $u) {
            $user = \App\User::find($u->id);
            if (!is_null($user)) {
                $user->doc = '35/17р от 15.02.17';
                $user->save();
            }
        }
        \PC::debug(count($users));
    }

    /**
     * Отправить неотправленные заявки
     */
    function sendUnsentTeleportClaims() {
        $claims_ids = [
            'с74825863',
            'с74827614',
            'с74827643',
            'с74830268',
            'с74830401',
            'с74823671',
            'с74825910',
            'с74803651',
            'с74826049',
            'с74791439',
            'с74822468',
            'с74827724',
            'с74827697',
            'с74827699',
            'с74825858',
            'с74827500',
            'с74829085',
            'с74830287',
            'с74818273',
            'с74825127',
            'с74827386'
        ];
//        $claims = \App\Claim::whereBetween('created_at', ['2017-04-01', '2017-05-01'])->whereNotNull('id_teleport')->whereNull('teleport_status')->get();
        $claims = \App\Claim::whereIn('id_1c', $claims_ids)->get();
//        \PC::debug(count($claims));
        foreach ($claims as $claim) {
//            $tstatus = $claim->teleport_status;
            $tstatus = 'sell';
            $claim->update(['teleport_status' => null]);
            \PC::debug(TeleportController::sendStatusToTeleport($claim, $tstatus));
        }
    }

    public function testExchangeArm(Request $req) {
        if (!$req->has('data') && !$req->has('rawXml')) {
            return ['result' => 0, 'error' => 'Неверные входные данные 1'];
        }
        $sendData = null;
        if ($req->has('rawXml') && $req->rawXml != '') {
            $sendData = $req->rawXml;
        } else {
            $data = json_decode($req->data);
            if (is_null($data)) {
                return ['result' => 0, 'error' => 'Неверные входные данные 2'];
            }
            $xmldata = [];
            foreach ($data as $row) {
                $xmldata[$row->tag] = $row->val;
            }
            $sendData = \App\MySoap::createXML($xmldata);
        }
        if (is_null($sendData)) {
            return ['result' => 0, 'error' => 'Неверные входные данные 3'];
        }
        if ($req->module == 'Mole') {
            $res = \App\MySoap::sendXML($sendData, false);
        } else {
            $res = \App\MySoap::sendExchangeArm($sendData);
        }
        return $res->asXML();
    }

    public function addClaimsAndLoans($customer_id, $num = 9, $maxDate = null) {
        if (is_null($maxDate)) {
            $maxDate = Carbon::now();
        }
        $customer = \App\Customer::find($customer_id);
        if (is_null($customer)) {
            return $this->backWithErr();
        }
        $date = $maxDate->subDays($num);
        for ($i = 0; $i < $num; $i++) {
            $claim = new \App\Claim();
            $claim->customer_id = $customer->id;
            $claim->passport_id = $customer->getLastPassport()->id;

            $about_client = new \App\about_client();
            $about_client->postclient = 1;
            $about_client->customer_id = $customer->id;
            $about_client->save();

            $claim->about_client_id = $about_client->id;
            $claim->summa = 3000;
            $claim->srok = 5;
            $claim->subdivision_id = Auth::user()->subdivision_id;
            $claim->user_id = Auth::user()->id;
            $claim->created_at = $date->format('Y-m-d H:i:s');
            $claim->save();

            $loan = new \App\Loan();
            $loan->claim_id = $claim->id;
            $loan->money = $claim->summa;
            $loan->time = 5;
            $loan->in_cash = 1;
            $loan->loantype_id = 20;
            $loan->closed = 1;
            $loan->subdivision_id = Auth::user()->subdivision_id;
            $loan->user_id = Auth::user()->id;
            $loan->created_at = $date->format('Y-m-d H:i:s');
            $loan->save();

            $date->addDay();
        }
        return $this->backWithSuc();
    }

    public function count1cReqsTime() {
        
    }

    public function count1cReqs() {
        $logs = DB::connection('spylogs115')->table('logs')->whereBetween('logs.created_at', ['2017-10-08', '2017-10-09'])
                ->leftJoin('log_data', 'logs.data_id', '=', 'log_data.id')
                ->where('logs.action', \App\Spylog\Spylog::ACTION_CALL1C)
                ->get();
        $reqs = [
            'read' => [
                'names' => [
                    'CheckK' => 0,
                    'LoanK_number' => 0,
                    'LoanK' => 0,
                    'Loan_K_FIO' => 0,
                    'GetSubdivisionCash' => 0,
                    'GetDebtByNumber' => 0,
                    'GetSheet' => 0,
                    'GetDocsRegister' => 0,
                    'GetDailyCashReport' => 0,
                    'checkPromocode' => 0,
                    'CheckClaimForIssue' => 0,
                    'GetExpDopData' => 0,
                    'GetDebtorPayment' => 0,
                    'CheckCard' => 0,
                    'GetExpenditureList' => 0,
                    'CheckAdvanceReport' => 0,
                    'GetNomenclatureList' => 0,
                ],
                'mole_types' => [
                    '8' => 0,
                    //сальдо
                    '7' => 0,
                    '10' => 0,
                    '4' => 0,
                    '3' => 0
                ],
                'total' => 0
            ],
            'write' => [
                'names' => [
                    'Auto_okay' => 0,
                    'CreateK' => 0,
                    'CreateCreditAgreement' => 0,
                    'Create_order' => 0,
                    'Create_order_card' => 0,
                    'CreateK_Other' => 0,
                    'CreateZPP' => 0,
                    'CreateNPF' => 0,
                    'CreateFL' => 0,
                    'CreateDailyCashReport' => 0,
                    'SaveMatClaim' => 0,
                    'CreateWorkTime' => 0,
                    'AddTerminal' => 0,
                    'EditSUZ' => 0,
                    'Delete' => 0,
                    'CreateMS' => 0,
                    'CreateK_other' => 0,
                    'Create_KO' => 0,
                    'ActivateClose' => 0,
                    'ActivateDopnik' => 0,
                    'SetScoristaDecision' => 0,
                    'CreateClaimForIssue' => 0,
                    'AddSuzSchedule' => 0,
                    'CreateClose' => 0,
                    'CreateDeRealization' => 0,
                    'Create_Realization' => 0,
                    'SinchronizeCreditRequest' => 0,
                    'CreateMultiAgreement' => 0,
                    'UpdateAdvanceReport' => 0,
                    'AddNomenclature' => 0,
                ],
                'mole_types' => [
                    //обзвон
                    '9' => 0,
                    '0' => 0,
                    '5' => 0
                ],
                'total' => 0
            ]
        ];
        foreach ($logs as $log) {
            foreach ($reqs as $rtk => &$rtv) {
                foreach ($rtv['names'] as $reqk => &$reqv) {
                    if (strpos($log->data, $reqk) !== FALSE) {
                        $rtv['names'][$reqk] ++;
                        $rtv['total'] ++;
                        break;
                    } else if (strpos($log->data, 'IAmMole') !== FALSE) {
                        $json = json_decode($log->data);
                        $params = new \SimpleXMLElement(html_entity_decode($json->params));
//                        \PC::debug((string) $params->type);
                        foreach ($rtv['mole_types'] as $mtk => &$mtv) {
                            if ((string) $params->type == $mtk) {
                                $rtv['mole_types'][(string) $params->type] ++;
                                $rtv['total'] ++;
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        }
//        \PC::debug($reqs);
        return $reqs;
    }

    function updateOrdersForDebtorsFromBuhBase() {
        $limit = 1;
        $offset = 0;
        while ($offset < 10) {
            $debtors = DB::connection('debtors')->select(DB::raw('SELECT * FROM debtors.debtors where is_debtor = 1 and (debt_group_id = 32 or decommissioned = 1) limit ' . $limit . ' offset ' . $offset));
            foreach ($debtors as $d) {
                \PC::debug($d);
                $loan = \App\Loan::where('id_1c', $d->loan_id_1c)->first();
                \App\Synchronizer::getContractsFrom1c($d->passport_series, $d->passport_number);
                if (is_null($loan)) {
                    $loan = \App\Loan::where('id_1c', $d->loan_id_1c)->first();
                }
                \PC::debug($loan);
                if (!is_null($loan)) {
                    try {
                        \App\Synchronizer::updateOrders($loan->created_at->format('Y-m-d'), $d->passport_series, $d->passport_number, null, $loan, '2018-02-08', ['url' => '192.168.1.23:8080/111SPD/ws/Mole/?wsdl', 'absolute_url' => true]);
                    } catch (\Exception $ex) {
                        
                    }
                }
            }
            $offset += $limit;
        }
        logger('TesterController.updateOrdersForDebtorsFromBuhBase', ['offset' => $offset, 'limit' => $limit]);
    }

    function checkPayturePaymentStatus() {
        $res = file_get_contents('https://sandbox.payture.com/apim/PayStatus?Key=FinterraAFTPSB&OrderId=4a789ed366d8c8102be57a79a2f33457');
        \PC::debug($res);
    }

    function changeAdminRole(Request $req) {
//        return hash('gost','abc');
        if (is_null(Auth::user())) {
            return 'Не авторизован';
        }
        if (!$req->has('answer')) {
            return 'Нет обязательных параметров';
        }
        $user = Auth::user();
        if (!in_array($user->id, ['1', '5', '784'])) {
            return 'Нет прав';
        }
        $user->group_id = ($req->answer == 'yes') ? '1' : '-1';
        $user->save();
        $admin_role_id = \App\Role::where('name', \App\Role::ADMIN)->value('id');
        $spec_role_id = \App\Role::where('name', \App\Role::SPEC)->value('id');
        $role_ids = $user->roles->pluck('id')->toArray();
        if ($req->answer == 'yes') {
            if (in_array($admin_role_id, $role_ids)) {
                $user->roles()->detach($admin_role_id);
            }
            if (!in_array($spec_role_id, $role_ids)) {
                $user->roles()->attach($spec_role_id);
            }
        } else {
            if (!in_array($admin_role_id, $role_ids)) {
                $user->roles()->attach($admin_role_id);
            }
            if (!in_array($spec_role_id, $role_ids)) {
                $user->roles()->attach($spec_role_id);
            }
        }
        return 'Успех';
    }

}
