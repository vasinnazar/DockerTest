<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Request;
use Carbon\Carbon;
use Input;
use App\LoanType;
use App\Claim;
use App\Customer;
use App\about_client;
use App\Passport;
use App\User;
use App\Terminal;
use App\Loan;
use App\StrUtils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Storage;
use App\Photo;
use App\TerminalAction;
use App\Synchronizer;
use App\MySoap;
use App\Utils\SMSer;
use App\Spylog\Spylog;
use App\TerminalCommand;
use App\Utils\HelperUtil;
use App\Promocode;

class TerminalController extends Controller {

    const HARDWARE_ID = 23423423;
    const ID = 123;
    const PASSWORD = 'test';
    const TEMP_PASSWORD = 123;
    const PIN = 123;
    const isLocked = 0;
    const CONTACT_PHONE = '8-800-301-4344';
    const REFRESH_RATE = 120;
//    const REFRESH_RATE = 30;
    const TEST_PHONE = '79000000999';
    const CC_PHONE = '79059060342';
//    const ALERT_PHONES = ['79030466344'];
    //количество минут до смски о том что терминал недоступен
    const MINS_TO_ALERT = 10;
//    const ALERT_PHONES = ['79059060166', '79234852777', '79609051805'];
    const ALERT_PHONES = ['79059060166', '79609051805', '79059060559'];
    const PROMO_LENGTH = 4;
    const PROMO_SUM1 = 17;
    const PROMO_SUM2 = 17;
    const PROMO_SUM3 = 17;
    const PROMO_TYPE_ID1C_1 = 'ARM000011';
    const PROMO_TYPE_ID1C_2 = 'ARM000011';
    const PROMO_TYPE_ID1C_3 = 'ARM000011';

#PayPointID , ConnTicks, HardwareID, HashData
    #ответ IsLocked - блокировка, AuthDescr - описание блокировки , AuthCode=0 -нет, AuthCode=1 -ок

    public function __construct() {
        
    }

    public function index() {
        return view('terminal.index');
    }

    /**
     * Предварительная авторизация
     * @param Request $req
     * @return type
     */
    public function preauth(Request $req) {
        Header('Content-type: text/xml');
        if (!$req->has('PayPointID') || !$req->has('HardwareID') || !$req->has('ConnTicks') || !$req->has('HashData')) {
            return response($this->createXML(['isLocked' => '0', 'AuthDescr' => 'не все параметры', 'AuthCode' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        $terminal = Terminal::find((int) $req->get('PayPointID'));
        if (is_null($terminal)) {
            return response($this->createXML(["isLocked" => "0", 'AuthDescr' => 'неверный ID', 'AuthCode' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        if (is_null($terminal->HardwareID)) {
            $terminal->HardwareID = $req->get('HardwareID');
            $terminal->save();
        } else if ($terminal->HardwareID != $req->HardwareID) {
            //Log::info('хуй:',[$this->createXML(['isLocked' => '0', 'AuthDescr' => '', 'AuthCode' => '1']), '200', ['Content-Type', 'text/xml']]);
            //return response($this->createXML(['isLocked' => '0', 'AuthDescr' => '', 'AuthCode' => '1']), '200', ['Content-Type', 'text/xml']);
            return response($this->createXML(['isLocked' => '0', 'AuthDescr' => 'неверный hardware ID', 'AuthCode' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        if (md5($req->ConnTicks . $terminal->password) == $req->HashData) {
            if ($terminal->is_locked) {
                return response($this->createXML(['isLocked' => '1', 'AuthDescr' => 'заблокирован', 'AuthCode' => '1']), '200', ['Content-Type', 'text/xml']);
            } else {
                session_start();
                session_set_cookie_params(3600);
                session(['ppid' => $req->PayPointID]);
//                Log::info('preauth session:', ['session' => session_id(), 'cookie' => session_get_cookie_params(), 'ppid' => session('ppid')]);
                return response($this->createXML(['isLocked' => '0', 'AuthDescr' => '', 'AuthCode' => '1']), '200', ['Content-Type', 'text/xml']);
            }
        } else {
            return response($this->createXML(['isLocked' => '0', 'AuthDescr' => 'неверный пароль', 'AuthCode' => '0']), '200', ['Content-Type', 'text/xml']);
        }
    }

    /**
     * Отправляет статусное сообщение на терминал (интервал запроса данных с сервера, даты последнего обновления данных в таблицах(виды займа и терминалы)
     * @param Request $req
     * @return type
     */
    public function status(Request $req) {
        if (!$req->has('AppVer') || !$req->has('TerminalState')) {
            return null;
        }
        if ($req->has('PayPointID')) {
            $state = $this->parseTerminalState($req->TerminalState);
            $terminal = Terminal::find($req->PayPointID);
            $terminal->fill($state);
            $terminal->last_status = Carbon::now()->format('Y-m-d H:i:s');
            $terminal->save();
        }
        $otherTerminals = Terminal::where('is_locked', '0')->get();
        $nowMinusTime = Carbon::now()->subMinutes(TerminalController::MINS_TO_ALERT);
        foreach ($otherTerminals as $t) {
            $lastStatus = new Carbon($t->last_status);
            if (!is_null($lastStatus) &&
                    Carbon::now()->hour > 8 && Carbon::now()->hour < 21 &&
                    $lastStatus->lt($nowMinusTime) &&
                    (is_null($t->last_disconnect_sms) || with(new Carbon($t->last_disconnect_sms))->lt($nowMinusTime))) {
                foreach (TerminalController::ALERT_PHONES as $ap) {
                    $t->last_disconnect_sms = Carbon::now()->format('Y-m-d H:i:s');
                    $t->save();
                    $this->sendSMS($ap, 'Терминал ' . $t->address . ' недоступен с ' . $lastStatus->format('d.m.y H:i:s'));
                }
            }
        }
        $terminalsUpdDate = new Carbon(Terminal::max('updated_at'));
//        $loanTypesUpdDate = Carbon::now();
        $loanTypesUpdDate = new Carbon('2017-01-09 16:00:00');
        $commands = TerminalCommand::where('PayPointID', $req->PayPointID)->where('sync', 0)->get();
        $coms = [];
        $xml = new \SimpleXMLElement('<root/>');
        $xml->addChild('RefreshRate', TerminalController::REFRESH_RATE);
        $tableversions = $xml->addChild('TableVersions');
        $tableversions->addChild('TPItems', $loanTypesUpdDate->format('d.m.Y H:i:s'));
        $tableversions->addChild('PayPoints', $terminalsUpdDate->format('d.m.Y H:i:s'));
        $commands_xml = $xml->addChild('Commands');
        foreach ($commands as $com) {
            if (!is_null($com->params)) {
                $comstr = $com->name . '(' . $com->params . ')';
                $cmd_xml = $commands_xml->addChild('cmd', (string) $comstr);
                $cmd_xml->addAttribute('id', $com->id);
            } else {
                $cmd_xml = $commands_xml->addChild('cmd', $com->name);
                $cmd_xml->addAttribute('id', $com->id);
            }
        }
        $xml->addChild('ContactPhone', TerminalController::CONTACT_PHONE);
        Log::info('TerminalController.status', ['xml' => $xml->asXML(), 'req' => $req->all()]);
        return response($xml->asXML(), '200', ['Content-Type', 'text/xml']);
    }

    function parseTerminalState($state) {
        $r = explode('|', $state);
        return [
            'DispenserStatus' => $r[0],
            'stWebcamStatus' => $r[1],
            'stValidatorStatus' => $r[2],
            'stPrinterStatus' => $r[3],
            'stScannerStatus' => $r[4],
            'MODE' => $r[5]
        ];
    }

    /**
     * Запрос пинкода пользователем
     * @param Request $req
     * @return type
     */
    public function auth(Request $req) {
        if (!$req->has('phone')) {
            Log::info('TerminalController.' . __FUNCTION__ . ' Нет телефона: ', $req->all());
            return response($this->createXML(['Status' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        if (config('app.dev')) {
            $pin = '123';
        } else {
            $pin = $this->generatePIN();
        }
        $customer = Customer::where('telephone', '7' . StrUtils::parsePhone($req->phone))->first();
        if (is_null($customer)) {
            $customer = new Customer();
            $customer->telephone = '7' . StrUtils::parsePhone($req->phone);
            Log::info('создан новый контрагент (TerminalController.auth)', ['customer' => $customer->toArray()]);
        } else {
            Log::info('Найден: ', $customer->toArray());
        }
//        if (is_null($customer->last_pin_change) || Carbon::now()->diffInHours(new Carbon($customer->last_pin_change)) > 24) {
        $customer->pin = $pin;
//        } else {
//            $pin = $customer->pin;
//        }
        if (!$customer->save()) {
            Log::error('TerminalController.auth: контрагент не сохранился');
            return response($this->createXML(['Status' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        if (is_null(Passport::where('customer_id', $customer->id)->first())) {
            if (!$this->createPassport($customer->id)) {
                return response($this->createXML(['Status' => '0']), '200', ['Content-Type', 'text/xml']);
            }
        }
        if (TerminalController::sendSMS($customer->telephone, 'Код авторизации: ' . $pin)) {
            return response($this->createXML(['Status' => '1']), '200', ['Content-Type', 'text/xml']);
        } else {
            return response($this->createXML(['Status' => '0']), '200', ['Content-Type', 'text/xml']);
        }
    }

    /**
     * Генерация пина
     * @return integer
     */
    function generatePIN() {
//        return TerminalController::PIN;
        return rand(100, 999);
//        return 375;
    }

    /**
     * Отправка СМС
     * @param type $sms
     * @return boolean
     */
    static function sendSMS($telephone, $sms) {
        if (config('app.dev')) {
//            return SMSer::send($telephone, $sms);
            Log::info('TerminalController.sendSMS', ['sms' => $sms, 'telephone' => $telephone]);
            return true;
        } else {
            return SMSer::send($telephone, $sms);
        }
//        return true;
    }

    static function sendSMSToList($telephones, $sms, $terminal) {
        if (config('app.dev')) {
            return;
        } else {
            foreach ($telephones as $ap) {
                SMSer::send($ap, $sms);
            }
        }
    }

    /**
     * Авторизация пользователя
     * @param Request $req
     * @return type
     */
    public function pinauth(Request $req) {
        if (!$req->has('phone') || !$req->has('pin')) {
            return null;
        }
        if ($req->has('PayPointID')) {
            $terminal = Terminal::find($req->PayPointID);
        } else {
            $terminal = Terminal::find(1);
        }
        return $this->authCustomer('7' . $req->get('phone'), $req->get('pin'), $terminal);
    }

    /**
     * Возвращает список доступных видов займа
     * @return type
     */
    public function TPItems() {
        $xml = new \SimpleXMLElement('<root/>');
        $loantypes = LoanType::where('show_in_terminal', '1')
                ->where('status', LoanType::STATUS_ACTIVE)
                ->where('start_date','<=',Carbon::now()->format('Y-m-d'))
                ->where('end_date','>=',Carbon::now()->format('Y-m-d'))
                ->limit(1)
                ->get();
        foreach ($loantypes as $ltype) {
            $tpitem = $xml->addChild('TPItem');
            $tpitem->addAttribute('ID', $ltype->id);
            $tpitem->addAttribute('Percent', with(\App\LoanRate::getByDate(Carbon::now()->format('Y-m-d H:i:s')))->pc);
            $tpitem->addAttribute('MaxAmount', $ltype->money);
            $tpitem->addAttribute('MinAmount', 1000);
//            $tpitem->addAttribute('TPName', $ltype->docs);
            $tpitem->addAttribute('MinDays', 1);
            $tpitem->addAttribute('MaxDays', $ltype->time);
//            $tpitem->addAttribute('Documents', $ltype->docs);
        }
        Log::info('TerminalController.TPItems', ['xml' => $xml->asXML()]);
        return response($xml->asXML(), '200', ['Content-Type', 'text/xml']);
    }

    /**
     * Возвращает список доступных терминалов
     * @return type
     */
    public function paypoints() {
        $xml = new \SimpleXMLElement('<root/>');
        $terminals = Terminal::where('is_locked', '0')->get();
        foreach ($terminals as $terminal) {
            $paypoint = $xml->addChild('PayPoint');
            $paypoint->addChild('PayPointName', $terminal->description);
            $paypoint->addChild('PayPointID', $terminal->id);
            $paypoint->addChild('IsOnline', 1);
            $paypoint->addChild('FactAddress', $terminal->address);
        }
        return response($xml->asXML(), '200', ['Content-Type', 'text/xml']);
    }

    public function getPromoSum($number) {
        $numLength = strlen($number);
        $sum = 0;
        for ($i = 0; $i < $numLength; $i++) {
            $sum += (int) substr($number, $i, 1);
        }
        return $sum;
    }

    public function promo(Request $req) {
        //общая сумма всех чисел в промокоде должна быть равна числу ниже
        $badpromomsg = 'Неверное значение промокода';
        if (!$req->has('PayPointID') || !$req->has('PromoCode')) {
            return response($this->createXML(['Answer' => $badpromomsg . '1']), '200', ['Content-Type', 'text/xml']);
        }
        $number = $req->PromoCode;
        $numLength = strlen($number);
//        $promocode = \App\Promocode::where('number', $number)->first();
//        if (!is_null($promocode)) {
//            return response($this->createXML(['Answer' => 'Промокод уже использован']), '200', ['Content-Type', 'text/xml']);
//        }
        if ($numLength != TerminalController::PROMO_LENGTH) {
            return response($this->createXML(['Answer' => $badpromomsg]), '200', ['Content-Type', 'text/xml']);
        }
        $promocode = Promocode::where('number', $number)->first();
        //проверка на уникальность промокода
        if (!is_null($promocode) && Claim::where('promocode_id', $promocode->id)->count() > 0) {
            return response($this->createXML(['Answer' => $badpromomsg]), '200', ['Content-Type', 'text/xml']);
        }
        $sum = $this->getPromoSum($number);
        if ($sum == TerminalController::PROMO_SUM1) {
            $loantype = LoanType::getTerminalPromoLoantype();
        } else {
            return response($this->createXML(['Answer' => $badpromomsg]), '200', ['Content-Type', 'text/xml']);
        }
//        if (is_null($promocode)) {
//            $promocode = new \App\Promocode();
//            $promocode->number = $number;
//            $promocode->save();
//        }

        $xml = [
            'Amount' => $loantype->terminal_promo_discount,
            'Answer' => '',
            'tpid' => $loantype->id,
            'Percent' => $loantype->percent,
            'MinAmount' => 1000,
            'MaxAmount' => $loantype->money,
            'MaxDays' => $loantype->time,
            'MinDays' => 1
        ];
        Log::info("TerminalController.promo", ['xmltosend' => $xml, 'req' => $req]);
        return response($this->createXML($xml), '200', ['Content-Type', 'text/xml']);
    }

    /**
     * Авторизация контрагента в терминале
     * @param type $telephone
     * @param type $pin
     * @return type
     */
    function authCustomer($telephone, $pin, $terminal) {
        $customer = Customer::where('telephone', $telephone)->where('pin', $pin)->first();
        if (is_null($customer)) {
            Log::error('TerminalController.authCustomer.customer: клиент не найден');
            return $this->getAuthFailXML();
//            $customer = $this->createCustomer($terminal->id, ['telephone' => $telephone, 'pin' => $pin]);
//            $this->createPassport($customer->id);
        }
        $lastPassport = Passport::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
        if (!is_null($lastPassport) && $lastPassport->series!='0000' && $lastPassport->number!='000000') {
            $docs = Synchronizer::updateLoanRepayments($lastPassport->series, $lastPassport->number);
        }
        Log::info('TerminalController.authCustomer.customer', ['customer'=>$customer->toArray(),'passport'=>$lastPassport->toArray()]);
        $claim = Claim::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
        //костыль для Юрьича, в случае если приходит он, то заявки всегда нет
        if($telephone=='79059060559' || $telephone=='9059060559'){
            $claim = null;
        }
//        if (is_null($claim)) {
//            Log::error('TerminalController.authCustomer.claim: заявка не найдена');
//            return $this->getAuthFailXML();
//        }
        if (!is_null($claim)) {
            $loan = Loan::where('claim_id', $claim->id)->first();
            if (is_null($loan) || $loan->closed || (is_null($loan) && $claim->created_at->lt(new Carbon('2016-05-01')))) {
                $claim = null;
            }
        }
        if (!is_null($claim)) {
//            Log::info('TerminalController.authCustomer.claim.' . $claim->id, $claim->toArray());
            $loan = Loan::where('claim_id', $claim->id)->first();
            if (is_null($loan) || $loan->closed) {
                $longAmount = 0;
                $repayAmount = 0;
                $endDate = with(new Carbon($claim->created_at))->addDays($claim->srok);
                $debt = 0;
                if (!is_null($claim->terminal_loantype)) {
                    $dayPercent = $claim->terminal_loantype->getPercent($claim->created_at);
                    $percents = $claim->summa * $dayPercent / 100 * $claim->srok;
                    if ($claim->terminal_loantype->terminal_promo_discount > 0) {
                        $percents = ($claim->terminal_loantype->terminal_promo_discount > $percents) ? 0 : $percents - $claim->terminal_loantype->terminal_promo_discount;
                    }
                    $endDateMoney = $claim->summa + $percents;
                    $percent = $dayPercent;
                } else {
                    $endDateMoney = 0;
                    $percent = 0;
                }
            } else {
//                Log::info('TerminalController.authCustomer.loan.' . $loan->id, $loan->toArray());
                //если средства зачислены то считается текущая задолженность, если нет, то на конец срока займа
                if (!isset($docs) || is_null($docs)) {
//                    if (!config('app.dev')) {
                    $docs = Synchronizer::updateLoanRepayments(null, null, $loan->id_1c);
//                    }
                    if (is_null($docs)) {
                        return $this->getAuthFailXML();
                    }
                }
                $dayPercent = with($loan->getLoanRate())->pc;
                $percents = $loan->money * ($dayPercent / 100) * $loan->time;
                $percentsWithoutPromocode = $percents;
                $endDateMoneyWithoutPromocode = $loan->money + $percents;
                if ($loan->loantype->terminal_promo_discount > 0) {
                    $percents = ($percents < $loan->loantype->terminal_promo_discount) ? 0 : ($percents - $loan->loantype->terminal_promo_discount);
                }
                $endDateMoney = $loan->money + $percents;
                $percent = $dayPercent;
                if ($loan->enrolled) {
                    $mDet = $loan->getRequiredMoneyDetails();
                    $longAmount = ($mDet->pc + $mDet->exp_pc) / 100;
                    $repayAmount = $mDet->money / 100;
                    $debt = $mDet->money;
                    
                } else {
                    $longAmount = $loan->money * $loan->time * ($dayPercent / 100);
                    $repayAmount = $loan->money + $loan->money * ($dayPercent / 100) * $loan->time;
                    $debt = $repayAmount;
                }
                $endDate = with(new Carbon($claim->created_at))->addDays($loan->time);
                if (Carbon::now()->setTime(0, 0, 0)->gte($endDate)) {
                    $endDateMoney = $mDet->money / 100;
                }
            }
        }

        $passport = (!is_null($claim)) ? $claim->passport : Passport::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
        if (is_null($passport)) {
            Log::error('Паспорт не найден ', ['customer_id' => $customer->id, 'claim' => $claim]);
            return $this->getAuthFailXML();
        }
//        0 - Новая заявка
//        10 - не одобренная
//        100 - Одобрен (ожидание подписи договора)
//        101 - Сверка подписи (договор подписан и рассматривается оператором)
//        150 - Займ погашен (финальный успешный статус)
//        151 - Закрыт, не востребован (если все одобрили, но бабки не сняли по таймауту - закрываем автоматом)
//        200 - Одобрен, подписан ( это когда можно получить деньги)
//        250 - Просрочен - беда.
//        201 - Деньги выданы , активный займ, норм. состояние.
        if (!isset($loan) || is_null($loan)) {
            if (!is_null($claim) && $claim->status == Claim::STATUS_ACCEPTED) {
                //если на заявку уже приходил скан подписанного договора
                if (Photo::where('description', 'Договор')->where('claim_id', $claim->id)->count() > 0) {
                    $claimStatus = '101';
                    $claimStatusText = 'Договор на рассмотрении';
                } else {
                    $claimStatus = '0';
                    $claimStatusText = 'Заявка на рассмотрении';
                }
            } else {
                $claimStatus = '0';
                $claimStatusText = 'Заявка на рассмотрении';
            }
        } else if ($loan->enrolled) {
            if (!$loan->closed) {
                //выдано
                if (TerminalAction::where('ActionType', TerminalAction::ACTION_CASH_OUT)->where('CreditID', $loan->claim_id)->count() > 0) {
                    $claimStatus = '201';
                    $claimStatusText = 'Текущая задолженность: ' . StrUtils::kopToRub($mDet->money);
                } else if ($loan->on_balance) {
                    //зачислено на баланс но не выдано
                    $claimStatus = '200';
                    $claimStatusText = 'Текущая задолженность: ' . StrUtils::kopToRub($mDet->money);
                } else if (Photo::where('description', 'Договор')->where('claim_id', $claim->id)->count() > 0) {
                    //есть фотка договора
                    $claimStatus = '101';
                    $claimStatusText = 'Договор на рассмотрении';
                } else {
                    //создан кредитник и расходник, 
                    $claimStatus = '100';
                    $claimStatusText = 'Ожидание подписания договора';
                }
                if ($loan->created_at->addDays($loan->time)->lt(Carbon::now())) {
                    $claimStatus = '250';
                    $claimStatusText = 'Просрочен';
                }
            } else {
                $claimStatus = 0;
                $claimStatusText = 'Заявка на рассмотрении';
            }
        } else {
            $claimStatus = '100';
            $claimStatusText = 'Заявка одобрена';
            TerminalController::sendSMS(TerminalController::CC_PHONE, 'Клиент ' . $claim->passport->fio . ' пришел повторно');
        }
        $customerUser = User::where('customer_id', $customer->id)->first();
        $isAdmin = (!is_null($customerUser) && $customerUser->isAdmin()) ? 1 : 0;

        if (!is_null($claim)) {
            if (isset($loan) && !is_null($loan) && $loan->closed) {
                
            } else {
                if (is_null($claim->terminal_guid)) {
                    $claim->terminal_guid = $this->generateGUID();
                }
                $credits = [
                    'ID' => $claim->id,
                    'Amount' => $claim->summa,
                    'OrderID' => $claim->terminal_guid,
                    'Status' => $claimStatus,
                    'Percent' => str_replace(',', '.', $percent),
                    'DateIns' => $claim->created_at->format('d.m.Y H:i:s'),
                    'CreateDate' => $claim->created_at->format('d.m.Y H:i:s'),
                    'CreditPeriod' => $claim->srok,
                    'Debt' => $debt,
                    'ReturnAmount' => $endDateMoney,
                    'StatusText' => $claimStatusText,
                    'RepayAmount' => $repayAmount,
                    'LongAmount' => $longAmount,
                    'EndDate' => $endDate->format('d.m.Y H:i:s'),
                ];
            }
            if (isset($loan) && !is_null($loan) && !$loan->closed) {
                $credits['XV-DocumentID'] = $loan->id_1c;
                $yearPercent = $dayPercent * (365 + date("L"));
                $yearPercent365 = $dayPercent * 365;
                $yearPercent366 = $dayPercent * 366;
                $credits['XV-DayPercent'] = $dayPercent;
                $credits['XV-WordDayPercent'] = StrUtils::percentsToStr($dayPercent);
//                $credits['XV-YearPercent'] = number_format($yearPercent, 3, ",", "");
//                $credits['XV-WordYearPercent'] = StrUtils::percentsToStr(number_format($yearPercent, 3));
                $credits['XV-YearPercent365'] = number_format($yearPercent365, 3, ",", "");
                $credits['XV-WordYearPercent365'] = mb_strtoupper(StrUtils::percentsToStr(number_format($yearPercent365, 3)), 'UTF-8');
                $credits['XV-YearPercent366'] = number_format($yearPercent366, 3, ",", "");
                $credits['XV-WordYearPercent366'] = mb_strtoupper(StrUtils::percentsToStr(number_format($yearPercent366, 3)), 'UTF-8');
                $credits['XV-WordDays'] = StrUtils::num2str($loan->time);
//                $credits['XV-FullAmount'] = $endDateMoney;
                $credits['XV-FullAmount'] = $endDateMoneyWithoutPromocode;
                $credits['XV-Rate'] = $percentsWithoutPromocode;
                $credits['XV-WordRate'] = StrUtils::num2str($percentsWithoutPromocode);
                $credits['XV-EndDateMoney'] = $endDateMoneyWithoutPromocode;
                $credits['XV-EndDate'] = $endDate->format('d.m.Y H:i:s');
                $credits['XV-Notification'] = '';
                $credits['XV-ExpPcText'] = '';
                $credits['XV-ExpPcText2'] = '';
                $credits['XV-Multiplier'] = 'четырехкратного';
                $credits['XV-RespText'] = 'За неисполнение обязательств по возврату суммы займа '
                        . 'и начисленных процентов в срок, установленный договором займа, с первого'
                        . ' дня нарушения условий Договора потребительского займа на сумму займа и '
                        . 'начисленных процентов начинает начисляться пеня в размере 20% годовых, '
                        . 'данная пеня начисляется по  день фактического пользования суммой займа,'
                        . ' когда сумма начисленных процентов достигнет четырехкратного размера суммы '
                        . 'займа. Со следующего дня после даты достижения вышеуказанного размера начинает'
                        . ' начисляться пеня в размере  0,1 % от суммы просроченной задолженности '
                        . '( просроченная задолженность состоит из сумма займа  и начисленных процентов) '
                        . 'за каждый день нарушения обязательств.';
                $credits['XV-Sixth'] = 'Заемщик обязуется вернуть сумму займа и '
                            . 'начисленные проценты единовременным платежом в дату, '
                            . 'указанную в п. 2 настоящих индивидуальных условий. Проценты за пользование '
                            . 'суммой займа, указанные в п. 4 настоящих индивидуальных условий, начисляются '
                            . 'за фактическое количество дней пользования суммой займа, начиная со дня, '
                            . 'следующего за днем выдачи займа по  день фактического пользования суммой '
                            . 'займа, когда сумма начисленных процентов достигнет четырехкратного размера '
                            . 'суммы займа. Со следующего дня после даты достижения вышеуказанного'
                            . ' размера начисление процентов прекращается.';
                
                if($loan->loantype->isTerminal_010117()){
                    $credits['XV-Sixth'] = 'Заемщик обязуется вернуть сумму займа и '
                            . 'начисленные проценты единовременным платежом в дату, '
                            . 'указанную в п. 2 настоящих индивидуальных условий. Проценты за пользование '
                            . 'суммой займа, указанные в п. 4 настоящих индивидуальных условий, начисляются '
                            . 'за фактическое количество дней пользования суммой займа, начиная со дня, '
                            . 'следующего за днем выдачи займа по  день фактического пользования суммой '
                            . 'займа, когда сумма начисленных процентов достигнет трехкратного размера '
                            . 'суммы займа. Со следующего дня после даты достижения вышеуказанного'
                            . ' размера начисление процентов прекращается.';
                    $credits['XV-Multiplier'] = 'трехкратного';
                    $credits['XV-ExpPcText'] = 'не могут превышать трехкратный размер суммы займа. '
                            . 'После возникновения просрочки по возврату суммы займа и причитающихся '
                            . 'процентов, проценты на не погашенную заемщиком часть суммы основного '
                            . 'долга продолжают начисляться до достижения общей суммы подлежащих уплате '
                            . 'процентов размера, составляющего двукратную сумму непогашенной части займа.'
                            . ' Проценты не начисляются за период времени с момента достижения общей суммы '
                            . 'подлежащих уплате процентов размера, составляющего двукратную сумму непогашенной '
                            . 'части займа, до момента частичного погашения заемщиком суммы займа '
                            . 'и (или) уплаты причитающихся процентов. После возникновения просрочки '
                            . 'по возврату суммы займа и причитающихся процентов начисление неустойки '
                            . '(штрафа, пени) и иные меры ответственности только на не погашенную '
                            . 'заемщиком часть суммы основного долга';
                    $credits['XV-RespText'] = 'За неисполнение обязательств по возврату суммы '
                            . 'займа и начисленных процентов в срок, установленный договором займа, '
                            . 'с первого дня нарушения условий Договора потребительского займа на '
                            . 'непогашенную часть суммы займа продолжают начисляться проценты до '
                            . 'достижения общей суммы подлежащих уплате процентов размера, составляющего '
                            . 'двукратную сумму непогашенной части займа до момента частичного погашения '
                            . 'заемщиком суммы займа и (или) начисленных процентов, а также начинает начисляться '
                            . 'пеня в размере 20% годовых на непогашенную часть суммы основного долга, данная '
                            . 'пеня начисляется по  день фактического пользования суммой займа, когда сумма'
                            . ' начисленных процентов достигнет двукратного, либо трехкратного (в случае частичного'
                            . ' погашения заемщиком суммы займа и (или) начисленных процентов) размера суммы займа.'
                            . ' Со следующего дня после даты достижения вышеуказанного размера начинает начисляться '
                            . 'пеня в размере  0,1 % от суммы  непогашенной части займа за каждый день нарушения обязательств.';
                } else {
                    $credits['XV-ExpPcText'] = 'не могут превышать четырехкратный размер суммы займа.';
                }

                if ($loan->loantype->isTerminal0() && $loan->time>=24) {
                    $daysToZeroPc = ($loan->time > 30) ? 30 : $loan->time;
                    $startStockDate = with(new Carbon($loan->created_at))->addDays(24);
                    $endStockDate = with(new Carbon($loan->created_at))->addDays($daysToZeroPc);

                    $credits['XV-Notification'] = 'Уведомление! В соответствии с '
                            . 'п. 16 ст. 5 Федерального закона от 21.12.2013 N 353-ФЗ '
                            . '(ред. от 21.07.2014) "О потребительском кредите (займе)" '
                            . 'ООО МФО «ПростоДЕНЬГИ» на период с  ' . $startStockDate . 'г. и по ' . $endStockDate . ' '
                            . 'процент за пользование суммой займа составляет 0 (ноль) % в день'
                            . ' по настоящему Договору. В случае невыполнения всех обязательств '
                            . 'по договору потребительского займа № ' . $loan->id_1c . ' от ' . $loan->created_at->format('d.m.Y') . 'г. '
                            . '(с учетом настоящего Уведомления) процентная ставка за пользование '
                            . 'суммой займа, с даты следующей за датой, выдачи займа и до '
                            . 'фактического возврата суммы займа, начисляется в соответствии '
                            . 'с условиями договора, т.е. ';
                    if($loan->loantype->isTerminal_010117()){
                        $credits['XV-Notification'] .= '792,050 % годовых (2,17% в день)';
                    } else {
                        $credits['XV-Notification'] .= '803,000 % годовых (2,2% в день)';
                    }
                    
                }

                if (!is_null($loan->order_id)) {
                    $credits['XV-OrderID'] = $loan->order->number;
                }
            }
            $actions = TerminalAction::where('CreditID', $claim->id)->get();
        }
        $xmlArray = [
            'Auth' => '1',
            'IsVirified' => '1',
            'IsAdmin' => $isAdmin,
            'DispenserCount' => $terminal->dispenser_count,
            'BillCount' => $terminal->bill_count,
            'BillCash' => $terminal->bill_cash / 100,
            'FIO' => $passport->fio,
            'BirthDate' => with(new Carbon($passport->birth_date))->format('d.m.Y'),
            'PassportDate' => with(new Carbon($passport->issued_date))->format('d.m.Y'),
            'Address' => ContractEditorController::getFullAddressString($passport->toArray()),
            'PassportNumber' => $passport->series . ' ' . $passport->number,
            'PassportFrom' => $passport->issued,
            'City' => $passport->birth_city,
            'Balance' => $customer->balance / 100,
            'ClientID' => $customer->id,
            'Actions' => [],
        ];
//        if (isset($actions)) {
//            foreach ($actions as $a) {
//                $xmlArray['Actions']['Action'] = [
//                    'ID' => $a->id,
//                    'ActionDate' => $a->created_at->format('d.m.Y H:i:s'),
//                    'ActionType' => $a->ActionType,
//                    'ActionText' => (is_null($a->ActionText)) ? '' : $a->ActionText,
//                    'Status' => (is_null($a->Status)) ? 200 : $a->Status
//                ];
//            }
//            Log::info('xml array actions: ', $xmlArray['Actions']);
//        }
        if (isset($credits)) {
            $xmlArray['Credits'] = $credits;
        }
        $res = $this->createXML($xmlArray);
        Log::info('XML TO SEND: ', ['xml' => html_entity_decode($res)]);
        Spylog::log(Spylog::ACTION_TERMINAL_AUTH, null, null, json_encode(['xml' => html_entity_decode($res)]));
        return response($res, '200', ['Content-Type', 'text/xml']);
    }

    /**
     * 1. создается со статусом 0
     * 2. статус 100 когда оператор одобряет заявку. 
     * 3. статус 101 когда приходит кредитный договор (скан с подписью)
     * 4. статус 200 разрешение на выдачу. перед этим уже должны быть средства на балансе
     * 
     * PostData.Add("PayPointID", PayPointID) ид терминала
     * PostData.Add("ClientID", H("ClientID")) ид клиента
     * PostData.Add("CreditPeriod", H("CreditPeriod")) срок заявки
     * PostData.Add("Amount", H("Amount")) сумма заявки
     * PostData.Add("TPID", H("TPID")) ид вида займа
     * PostData.Add("OrderID", H("OrderID")) ид вида займа
     *    
     * iRet("SResult")=1
     */
    public function order(Request $req) {
        Log::info('TerminalController.order', $req->all());
        if (!$req->has('PayPointID') || !$req->has('ClientID') || !$req->has('CreditPeriod') || !$req->has('Amount') || !$req->has('TPID') || !$req->has('OrderID')) {
            return response($this->createXML(['SResult' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        $terminal = Terminal::where('pay_point_id', $req->get('PayPointID'))->first();
        $customer = Customer::find($req->get('ClientID'));
        if ($req->has('PromoCode')) {
            $promosum = $this->getPromoSum($req->PromoCode);
            if ($promosum == TerminalController::PROMO_SUM1) {
                $loanType = LoanType::getTerminalPromoLoantype();
            } else {
                $loanType = LoanType::where('id', $req->get('TPID'))->first();
            }
            $promocode = \App\Promocode::where('number', $req->PromoCode)->first();
            if (is_null($promocode)) {
                $promocode = \App\Promocode::create(['number' => $req->PromoCode]);
            }
        } else {
            $loanType = LoanType::where('id', $req->get('TPID'))->first();
        }
        if (is_null($loanType) || is_null($customer) || is_null($terminal)) {
            return response($this->createXML(['SResult' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        $claim = Claim::where('terminal_guid', $req->OrderID)->first();
        if (is_null($claim)) {
            $claimData = [
                'srok' => $req->get('CreditPeriod'),
                'summa' => $req->get('Amount'),
                'terminal_guid' => $req->get('OrderID'),
                'terminal_loantype_id' => $loanType->id
            ];
            if (isset($promocode) && !is_null($promocode)) {
                $claimData['promocode_id'] = $promocode->id;
            }
            $claim = $this->createClaim($terminal->id, $customer->id, $claimData);
        } else {
            $claim->srok = $req->get('CreditPeriod');
            $claim->summa = $req->get('Amount');
            $claim->terminal_loantype_id = $loanType->id;
            if (isset($promocode) && !is_null($promocode)) {
                $claim->promocode_id = $promocode->id;
            }
            if (!$claim->save()) {
                return response($this->createXML(['SResult' => '0']), '200', ['Content-Type', 'text/xml']);
            }
        }
        if (is_null($claim)) {
            return response($this->createXML(['SResult' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        TerminalController::sendSMS(TerminalController::CC_PHONE, 'Новая заявка с терминала!');
        Spylog::log(Spylog::ACTION_TERMINAL_ORDER, null, null, json_encode(['req' => $req->all()]));
        return response($this->createXML(['SResult' => '1']), '200', ['Content-Type', 'text/xml']);
    }

    public function file(Request $req) {
        $dir = 'terminal/' . (Carbon::now()->format('Ymd')) . '/';
        Log::info('TerminalController.file', ['req' => $req->all()]);
        $file = Input::file('file');
        if (is_null($file)) {
            Log::error('file is null', ['file' => $file]);
            return response($this->createXML(['result' => '1']), '200', ['Content-Type', 'text/xml']);
        } else {
            //переносим файл в папку
            $filename = $file->getClientOriginalName();
            //переименовываем файл, если с таким именем в папке уже есть
//            if (\App\Utils\HelperUtil::FtpFileExists($dir . $filename)) {
//                $filename = uniqid() . $file->getClientOriginalName();
//            }
            if(config('app.dev')){
                return response($this->createXML(['result' => '1']), '200', ['Content-Type', 'text/xml']);
            }
//            if (!HelperUtil::FtpFileExists($dir)) {
            if (!Storage::exists($dir)) {
                if (!Storage::makeDirectory($dir)) {
                    Log::error('TerminalController.file: Ошибка при создании папки', ['dir' => $dir]);
                    return response($this->createXML(['result' => '0']), '200', ['Content-Type', 'text/xml']);
                }
            }
            if (Storage::put($dir . $filename, file_get_contents($file))) {
                Log::info('TerminalController.file: Сохранили файл', ['filename' => $filename, 'dir' => $dir, 'file' => $file]);
            } else {
                Log::error('TerminalController.file: Ошибка при сохранении файла', ['dir' => $dir, 'file' => $file, 'filename' => $filename]);
            }
            return response($this->createXML(['result' => '1']), '200', ['Content-Type', 'text/xml']);
        }
    }

    /**
     * Приходят данные о файле:
     * PayPointID терминал
     * FileName имя файла,
     * FileType тип файла (цифровое значение)
     * OrderID - гуид заявки
     * ClientID - ид клиента (уже int)
     * Description - описание (текст, "паспорт", "документ" итп.  - важное поле)
     * @param Request $req
     * @return type
     */
    public function fileinfo(Request $req) {
        Log::info('TerminalController.fileinfo', ['req' => $req->all()]);
        //добавляем запись о файле в бд
        $claim = Claim::where('terminal_guid', $req->OrderID)->first();
        $customer = Customer::find($req->ClientID);
        if (is_null($claim) && $req->OrderID != '00000000-0000-0000-0000-000000000000' && !is_null($customer)) {
//            $claim = $this->createClaim($req->PayPointID, $customer->id, ["terminal_guid" => $req->OrderID]);
        }
        $photo = new Photo();
        if (!is_null($claim)) {
            $photo->claim_id = $claim->id;
            if (is_null($customer) && !is_null($claim->customer_id)) {
                $photo->customer_id = $claim->customer_id;
            }
        }
        $oldDir = 'terminal/' . (Carbon::now()->format('Ymd')) . '/';
        $dir = 'terminal/' . ((!is_null($customer)) ? ($customer->id . '/') : '') . (Carbon::now()->format('Ymd')) . '/' . $req->OrderID . '/';
        if(config('app.dev')){
            return response($this->createXML(['result' => '1']), '200', ['Content-Type', 'text/xml']);
        }
//        if (!HelperUtil::FtpFileExists($dir)) {
        if (!Storage::exists($dir)) {
            Storage::makeDirectory($dir);
        }
        $filename = substr($req->FileName, strrpos($req->FileName, '\\') + 1);
        //переносим файлы из общей папки за сегодняшний день, в папку пользователя
        try {
            if (Storage::exists($oldDir . $filename) && !Storage::exists($dir . $filename)) {
                Storage::move($oldDir . $filename, $dir . $filename);
            }
        } catch (Exception $ex) {
            
        }

        $photo->path = $dir . $filename;
//        $photo->path = $oldDir . $filename;
        if (!is_null($customer)) {
            $photo->customer_id = $customer->id;
//            if (is_null($photo->claim_id)) {
//                $claim2 = Claim::where('customer_id', $customer->id)->whereBetween('created_at', [Carbon::now()->setTime(0, 0, 0)->format('Y-m-d'), Carbon::now()->setTime(23, 59, 59)->format('Y-m-d')])->first();
//                if(!is_null($claim2)){
//                    $photo->claim_id = $claim2->id;
//                }
//            }
        }
        $photo->description = $req->Description;
//            if (!$hasMain) {
//                $photo->is_main = 1;
//                $hasMain = true;
//            }
        if ($photo->save()) {
//                Spylog::logModelAction(Spylog::ACTION_CREATE, 'photos', $photo);
        }
        return response($this->createXML(['result' => '1']), '200', ['Content-Type', 'text/xml']);
    }

    /**
     * Создаёт контрагента
     * @param type $terminal_id ид терминала
     * @param array $params параметры контрагента
     * @return Customer
     */
    function createCustomer($terminal_id, $params) {
        $customer = new Customer();
        $customer->fill($params);
        if ($customer->save()) {
            Log::info('добавлен новый контрагент: ', ['terminal_id' => $customer->toArray(), 'customer' => $customer->toArray()]);
            return $customer;
        } else {
            Log::error('ошибка при добавлении контрагента: ', ['terminal_id' => $customer->toArray(), 'customer' => $customer->toArray()]);
            return null;
        }
    }

    function createClaim($terminal_id, $customer_id, $params) {
        DB::beginTransaction();
        $terminal = Terminal::find($terminal_id);
        if (is_null($terminal)) {
            Log::error('не найден терминал: ', ['terminal_id' => $terminal_id]);
            DB::rollback();
            return null;
        }
        $claim = Claim::where('terminal_guid', $params['terminal_guid'])->first();
        if (is_null($claim)) {
            $claim2 = Claim::where('customer_id', $customer_id)->whereBetween('created_at', [Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'), Carbon::now()->setTime(23, 59, 59)->format('Y-m-d H:i:s')])->first();
            if (is_null($claim2)) {
                $claim = new Claim();
                //            $claim->terminal_guid = $params["OrderID"];
                $claim->date = Carbon::now();
            } else if (Loan::where('claim_id', $claim2->id)->count() > 0) {
                $claim = new Claim();
                $claim->date = Carbon::now();
            } else {
                $claim = $claim2;
            }
        }
        $claim->fill($params);
        $claim->customer_id = $customer_id;
        $claim->user_id = $terminal->user_id;
        $claim->subdivision_id = $terminal->user->subdivision_id;
        if (array_key_exists('promocode_id', $params) && !is_null($params['promocode_id'])) {
            $claim->promocode_id = $params['promocode_id'];
        }

        $about_client = new about_client();
        $about_client->customer_id = $customer_id;
        if (!$about_client->save()) {
            Log::error('ошибка при создании данных о клиенте: ', ['terminal_id' => $terminal_id, 'about_client' => $about_client->toArray()]);
            DB::rollback();
            return null;
        }
        $claim->about_client_id = $about_client->id;

        if (!array_key_exists('passport_id', $params)) {
            $passport = Passport::where('customer_id', $customer_id)->orderBy('created_at', 'desc')->first();
            if (is_null($passport)) {
                if (!$this->createPassport($customer_id)) {
                    DB::rollback();
                    Log::error('ошибка при создании паспорта: ', ['terminal_id' => $terminal_id, 'claim' => $passport->toArray()]);
                }
                $claim->passport_id = $passport->id;
            } else {
                $claim->passport_id = $passport->id;
            }
        } else {
            $claim->passport_id = $params['passport_id'];
        }

        if (!$claim->save()) {
            DB::rollback();
            Log::error('ошибка при создании заявки: ', ['terminal_id' => $terminal_id, 'claim' => $claim->toArray()]);
            return null;
        }
        Log::info('создана заявка: ', ['terminal_id' => $terminal_id, 'claim' => $claim->toArray()]);
        DB::commit();
        return $claim;
    }

    /**
     * 
     * 
     * от терминала приходит следующее:
     *  Guid ActionID = уникальный и действия
     *  Integer CreditID = ид займа
     *  Integer ClientID = ид клиента
     *  Datetime DateIns = время действия
     *  Integer ActionType = тип действия
     *  String ActionText = текст с описанием действия
     *  Integer Amount = сумма в рублях, при её наличии
     *  Integer ExtInt = число (в зависимости от действий разные значения) , в большинстве случаев = количество купюр (снятие/пополнение/инкасации)
     *  ActionType (не все передаются с терминала)
     *  0 - регистрация нового пользователя
     *  1 - операции с заявкой/займом
     *  5 - подписание договора
     *  10 - снятие наличных
     *  50 - пополнение баланса
     *  51,52 - гашение/продление кредита
     *  99 - штраф за просрочку
     *  --->100 системные<-------
     *  100 - начисление процентов
     *  101 - операции с займом (сохранение, редактирование, итп) не видимые пользователю
     * 
     * @param Request $req
     * @return type
     */
    public function sendaction(Request $req) {
        Log::info('TerminalController.sendaction', $req->all());
        if ($this->makeAction($req)) {
            return response($this->createXML(['result' => '1']), '200', ['Content-Type', 'text/xml']);
        } else {
            return response($this->createXML(['result' => '0']), '200', ['Content-Type', 'text/xml']);
        }
    }

    function makeAction(Request $req) {
        switch ($req->ActionType) {
            case TerminalAction::ACTION_REGISTER:
                return $this->actionCustomerReg($req);
            case TerminalAction::ACTION_CLAIM:
                return $this->actionClaim($req);
            case TerminalAction::ACTION_SIGN:
                return $this->actionContractSign($req);
            case TerminalAction::ACTION_CASH_OUT:
                return $this->actionCashOut($req);
            case TerminalAction::ACTION_CASH_IN:
                return $this->actionCashIn($req);
            case TerminalAction::ACTION_REPAY:
            case TerminalAction::ACTION_PROLONGATION:
                return $this->actionRepay($req);
            case TerminalAction::ACTION_FINE:
                return $this->actionFine($req);
            case TerminalAction::ACTION_INCASS:
                return $this->actionIncass($req);
            case TerminalAction::ACTION_REFILL:
                return $this->actionRefill($req);
        }
        return false;
    }

    public function actionIncass(Request $req) {
        Log::info('action incass: ', $req->all());
        $terminal = Terminal::find($req->get('PayPointID'));
        if (is_null($terminal)) {
            return false;
        }
        DB::beginTransaction();
//        if(!$this->createIncassRKO($terminal->user,  Customer::where('id_1c','000008007')->first(), $terminal->bill_cash)){
//            DB::rollback();
//            return false;
//        }
        $terminal->bill_count = 0;
        $terminal->bill_cash = 0;
        if (!$terminal->save()) {
            DB::rollback();
            return false;
        }
        $action = TerminalAction::create($req->all());
        if (is_null($action)) {
            DB::rollback();
            return false;
        }
        DB::commit();
        return true;
    }
    /**
     * Создает расходник на инкассацию
     * @param \App\User $user пользователь (терминал)
     * @param \App\Customer $customer контрагент
     * @param integer $amount сумма
     * @return boolean
     */
    function createIncassRKO($user,$customer,$amount){
        if(is_null($customer)){
            return false;
        }
        $passport = $customer->getLastPassport();
        if(is_null($passport)){
            return false;
        }
        $order = new \App\Order();
        $order->fill([
            'money' => $amount,
            'passport_id'=>$passport->id,
            'type' => \App\OrderType::getIdByTextId(\App\OrderType::PODOTCHET),
            'user_id' => $user->id, 
            'subdivision_id' => $user->subdivision->id
        ]);
        return $order->saveThrough1c();
    }
    /**
     * Пополнение диспенсера
     * @param Request $req
     * @return boolean
     */
    public function actionRefill(Request $req) {
        Log::info('action refill: ', $req->all());
        $terminal = Terminal::find($req->get('PayPointID'));
        if (is_null($terminal)) {
            return false;
        }
        DB::beginTransaction();
        $customer = Customer::where('id_1c','000008007')->first();
//        if(!$this->createIncassRKO($terminal->user, $customer, $terminal->dispenser_count*100000)){
//            DB::rollback();
//            return false;
//        }
        $terminal->dispenser_count = $req->ExtInt;
//        if(!$this->createRefillPKO($terminal->user, $customer, $terminal->dispenser_count*100000)){
//            DB::rollback();
//            return false;
//        }
        if (!$terminal->save()) {
            DB::rollback();
            return false;
        }
        $action = TerminalAction::create($req->all());
        if (is_null($action)) {
            DB::rollback();
            return false;
        }
        DB::commit();
        return true;
    }
    /**
     * Создает приходник на возврат в подотчет
     * @param \App\User $user пользователь (терминал)
     * @param \App\Customer $customer контрагент
     * @param integer $amount сумма в копейках
     * @return boolean
     */
    function createRefillPKO($user, $customer, $amount){
        if(is_null($customer)){
            return false;
        }
        $passport = $customer->getLastPassport();
        if(is_null($passport)){
            return false;
        }
        $order = new \App\Order();
        $order->fill([
            'money' => $amount,
            'passport_id'=>$passport->id,
            'type' => \App\OrderType::getIdByTextId(\App\OrderType::VOZVRAT),
            'user_id' => $user->id, 
            'subdivision_id' => $user->subdivision->id
        ]);
        return $order->saveThrough1c();
    }

    public function actionCustomerReg(Request $req) {
        Log::info('action customer reg: ', $req->all());
        return (!is_null(TerminalAction::create($req->all()))) ? true : false;
    }

    public function actionClaim(Request $req) {
        Log::info('action claim: ', $req->all());
        return (!is_null(TerminalAction::create($req->all()))) ? true : false;
    }

    public function actionContractSign(Request $req) {
        Log::info('action contract sign: ', $req->all());
        $action = new TerminalAction();
        $action->fill($req->all());
        if (!$action->save()) {
            return false;
        }
        return true;
    }

    public function actionFine(Request $req) {
        Log::info('action fine: ', $req->all());
        return (!is_null(TerminalAction::create($req->all()))) ? true : false;
    }

    /**
     * Снятие наличных
     * @return type
     */
    public function actionCashOut(Request $req) {
        Log::info('actioncashout: ', $req->all());
        $loan = Loan::where('claim_id', $req->CreditID)->first();
        if (is_null($loan)) {
            Log::error('TerminalController.actionCashOut: не найден кредитник', $req->all());
            return false;
        }
        if (!$req->has('PayPointID')) {
            return false;
        }
        $terminal = Terminal::find($req->PayPointID);
        if (is_null($terminal)) {
            return false;
        }
        if ($req->has('Amount') && $req->Amount == 0) {
            Log::error('TerminalController.actionCashOut: zero amount', ['req' => $req->all()]);
            TerminalController::sendSMSToList(TerminalController::ALERT_PHONES, 'Терминал ' . $terminal->user->subdivision->address . ' не может выдать деньги',$terminal);
        }
        $terminal->dispenser_count -= $req->ExtInt;
        if (!is_null($loan->claim) && !is_null($loan->claim->customer)) {
            $customer = $loan->claim->customer;
            $customer->balance -= $req->Amount * 100;
            DB::beginTransaction();
            $action = new TerminalAction();
            $action->fill($req->all());
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
            Spylog::log(Spylog::ACTION_TERMINAL_CASHOUT, null, null, json_encode(['req' => $req->all()]));
            if (!$customer->isPostClient()) {
                if (is_null($loan->promocode_id)) {
                    $promocode = Promocode::create(['number' => HelperUtil::GenerateTerminalPromocode(TerminalController::PROMO_SUM1, true)]);
                    if (!is_null($promocode)) {
                        $loan->promocode_id = $promocode->id;
                        $loan->save();
                        TerminalController::sendSMS($customer->telephone, 'Приведите друга и получите скидку. Промо код: ' . $promocode->number);
                    }
                }
                if (!is_null($loan->claim->promocode_id)) {
                    $ownerLoan = Loan::where('promocode_id', $loan->claim->promocode_id)->first();
                    if (!is_null($ownerLoan) && is_null($ownerLoan->claim->promocode_id)) {
                        $promoLoantype = LoanType::getTerminalPromoLoantype();
                        if (!is_null($promoLoantype)) {
                            $ownerLoan->loantype_id = $promoLoantype->id;
                            $ownerLoan->claim->promocode_id = $ownerLoan->promocode_id;
                            $ownerLoan->claim->save();
                            if ($ownerLoan->saveThrough1c() !== FALSE) {
                                TerminalController::sendSMS($ownerLoan->claim->customer->telephone, 'Ваш друг пришел. Скидка 100 рублей активирована');
                            }
                        }
                    }
                }
            }

            return true;
        } else {
            return false;
        }
    }

    public function actionCashIn(Request $req) {
        Log::info('action cash in: ', $req->all());
        $customer = Customer::find($req->ClientID);
        if (is_null($customer)) {
            return false;
        }
        $customer->balance += $req->Amount * 100;
        DB::beginTransaction();
        if (!$customer->save()) {
            DB::rollback();
            return false;
        }
        $action = TerminalAction::create($req->all());
        if (is_null($action)) {
            DB::rollback();
            return false;
        }
        DB::commit();
        Spylog::log(Spylog::ACTION_TERMINAL_CASHIN, null, null, json_encode(['req' => $req->all()]));
        return true;
    }

    public function actionRepay(Request $req) {
        Log::info('actionRepay.req', $req->all());
        $action = new TerminalAction($req->all());
        $action->DateIns = with(new Carbon($req->DateIns))->format('Y-m-d H:i:s');
        DB::beginTransaction();
        if (!$action->save()) {
            return false;
        }
        $terminal = Terminal::find($req->PayPointID);
        if (is_null($terminal)) {
            return false;
        }
        $terminal->bill_cash += $req->Amount * 100;
        $terminal->bill_count += $req->ExtInt;
        if (!$terminal->save()) {
            Log::error('TerminalController.actionRepay no terminal', ['terminal' => $req->PayPointID]);
            return false;
        }
        DB::commit();
        $claim = Claim::find($req->CreditID);
        if (is_null($claim)) {
            Log::error('TerminalController.actionRepay no claim', ['claim' => $req->CreditID]);
            return false;
        }
        $loan = Loan::where('claim_id', $claim->id)->first();
        if (is_null($loan)) {
            Log::error('TerminalController.actionRepay no loan', ['claim' => $req->CreditID]);
            return false;
        }
        $passport = $claim->passport;
//        if (!config('app.dev')) {
        $docs = Synchronizer::updateLoanRepayments(null, null, $loan->id_1c);
//        }
        if (is_null($docs)) {
            return false;
        }
        $mDet = $loan->getRequiredMoneyDetails();
        Log::info('mdet_money', ['mdet_money' => $mDet->money, 'reqamount' => $req->Amount]);
        if ($mDet->money < $req->Amount) {
            $toBalance = $req->Amount * 100 - $mDet->money;
            $customer = $claim->customer;
            $customer->balance += $toBalance;
            if (!$customer->save()) {
                return false;
            }
        }
        $res1c = \App\MySoap::terminalPayment([
                    'Seria' => $passport->series,
                    'Number' => $passport->number,
                    'summ' => $req->Amount,
                    'claim_id_1c' => $loan->id_1c,
                    'subdivision_id_1c' => $terminal->user->subdivision->name_id
        ]);
        Log::info('action repay res1c', $res1c);
        if ($res1c['res'] && $res1c['value'] == 'true') {
            Log::error('repay true');
            $this->sendSMS($claim->customer->telephone, $claim->passport->fio . ', вы оплатили ' . $req->Amount . ' руб. Если вам нужна квитанция об оплате, обратитесь в контактный центр ' . TerminalController::CONTACT_PHONE);
            return true;
        } else {
            Log::error('repay false');
            return false;
        }
    }

    /**
     * запрос на команду для терминала
     * @param Request $req
     * @return type
     */
    public function scmd(Request $req) {
        Log::info('TerminalController.scmd: start', [$req->all()]);
        if (!$req->has('ID')) {
            Log::error('TerminalController.scmd: id not in params', [$req->all()]);
            return response($this->createXML(['Status' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        $cmd = TerminalCommand::find($req->ID);
        if (is_null($cmd)) {
            Log::error('TerminalController.scmd: id not found', [$req->all()]);
            return response($this->createXML(['Status' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        $cmd->fill($req->all());
        if ($req->Success == "True") {
            $cmd->Success = 1;
        }
        $cmd->Sync = 1;
        if ($req->isExecuted == "True") {
            $cmd->isExecuted = 1;
            $cmd->DateExec = with(new Carbon($req->DateExec))->format('Y-m-d H:i:s');
        }
        if (!$cmd->save()) {
            Log::error('TerminalController.scmd: not saved', ['req' => $req->all(), 'cmd' => $cmd]);
            return response($this->createXML(['Status' => '0']), '200', ['Content-Type', 'text/xml']);
        }
        return response($this->createXML(['Status' => '1']), '200', ['Content-Type', 'text/xml']);
    }

    /**
     * Создаёт паспорт для контрагента с переданным ид
     * @param int $customer_id ид контрагента
     * @return boolean
     */
    function createPassport($customer_id) {
        $passport = new Passport();
        $passport->series = '0000';
        $passport->number = '000000';
        $passport->birth_date = '0000-00-00 00:00:00';
        $passport->issued_date = '0000-00-00 00:00:00';
        $passport->address_reg_date = '0000-00-00 00:00:00';
        $passport->subdivision_code = '';
        $passport->birth_city = '';
        $passport->address_region = '';
        $passport->address_city = '';
        $passport->address_street = '';
        $passport->address_house = '';
        $passport->fio = '';
        $passport->customer_id = $customer_id;
        if ($passport->save()) {
            Log::info('Создан новый паспорт для контрагента ' . $customer_id);
            return 1;
        } else {
            Log::error('Ошибка создания паспорта для контрагента ' . $customer_id);
            return 0;
        }
    }

    /**
     * Создает кредитник
     * @param type $terminal_id ид терминала
     * @param type $params параметры кредитника
     * @return Loan
     */
    function createLoan($terminal_id, $params) {
        $loan = new Loan();
        $loan->fill($params);
        if ($loan->save()) {
            Log::info('добавлен кредитник из терминала: ', ['terminal_id' => $terminal_id, 'loan' => $loan->toArray()]);
            return $loan;
        } else {
            Log::error('ошибка при добавлении кредитника: ', ['terminal_id' => $terminal_id, 'loan' => $loan->toArray()]);
            return null;
        }
    }

    /**
     * Возвращает ХМЛ на случай ошибки
     * @return type
     */
    public function getAuthFailXML() {
        $res = $this->createXML([
            'Auth' => '0',
            'IsVirified' => '0',
            'IsAdmin' => '0',
            'DispenserCount' => '0',
            'BillCount' => '0',
            'BillCash' => '0',
            'FIO' => '',
            'BirthDate' => '00.00.0000 00:00:00',
            'PassportDate' => '00.00.0000 00:00:00',
            'Address' => '',
            'PassportNumber' => '0000 000000',
            'PassportFrom' => '',
            'City' => '',
            'Balance' => '0',
            'ClientID' => '',
            'Credits' => [
                'ID' => '0',
                'Amount' => '0',
                'OrderID' => '00000000-0000-0000-0000-000000000000',
                'Status' => '0',
                'Percent' => '0',
                'DateIns' => '00.00.0000 00:00:00',
                'CreateDate' => '00.00.0000 00:00:00',
                'CreditPeriod' => '0',
                'Debt' => '0',
                'ReturnAmount' => '0',
                'StatusText' => '0',
                'RepayAmount' => '0',
                'LongAmount' => '0',
                'EndDate' => '00.00.0000 00:00:00'
            ],
            'Actions' => [
                'Action' => [
                    'ID' => '0',
                    'ActionDate' => '00.00.0000 00:00:00',
                    'ActionType' => '0',
                    'ActionText' => '',
                    'Status' => '0'
                ]
            ]
        ]);
        return response($res, '200', ['Content-Type', 'text/xml']);
    }

    /**
     * возвращает хмл из массива
     * @param type $params
     * @return type
     */
    public function createXML($params) {
        $xml = new \SimpleXMLElement('<root/>');
        $this->arrayToXML($params, $xml);
        Log::info($xml->asXML());
        return $xml->asXML();
    }

    /**
     * добавляет данные из массива в в хмл
     * @param type $data
     * @param type $xml_data
     */
    function arrayToXML($data, &$xml_data) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key; //для массивов с числовым ключом
                }
                $subnode = $xml_data->addChild($key);
                $this->arrayToXML($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    function generateGUID() {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
//        return uniqid();
    }

    function generatePromocode($maxval = 17) {
        return HelperUtil::GenerateTerminalPromocode($maxval);
    }

}
