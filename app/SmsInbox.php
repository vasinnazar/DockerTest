<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Utils\SMSer;
use Carbon\Carbon;
use DB;
use App\Spylog\Spylog;
use Log;

class SmsInbox extends Model {

    const CLAIM_WORD = 'ЗАЕМ';
    const APPROVE_WORD = 'Да';

    protected $table = 'sms_inbox';
    protected $fillable = ['received', 'sent', 'phone', 'message', 'to_phone', 'smsc_id'];

    /**
     * загружает в базу входящие смски
     * @return type
     */
    static function uploadFromSmsc() {
        $lastSmscId = SmsInbox::max('smsc_id');
        $inbox = SMSer::getInbox($lastSmscId);
        if (!is_array($inbox)) {
            return;
        }
        $res = [];
        foreach ($inbox as $sms) {
            $smsInbox = new SmsInbox([
                'sent' => with(new Carbon($sms->sent))->format('Y-m-d H:i:s'),
                'received' => with(new Carbon($sms->received))->format('Y-m-d H:i:s'),
                'phone' => $sms->phone,
                'message' => $sms->message,
                'to_phone' => $sms->to_phone,
                'smsc_id' => $sms->id
            ]);
            $smsInbox->save();
            $res[] = $smsInbox;
        }
        return $res;
    }

    /**
     * Собирает смски из веб интерфейса админки goip
     * @return type
     */
    static function uploadFromGoIp() {
        $goip_addr = "http://192.168.1.116:80/";
        $goip_user = "admin";
        $goip_password = "admin";
        $context = stream_context_create(array('http' => array('header' => ["Content-Type: text/xml; charset=utf-8", "Authorization: Basic " . base64_encode("$goip_user:$goip_password")])));
        $content = file_get_contents($goip_addr . "/default/en_US/tools.html?type=sms_inbox", false, $context);
        $data = iconv('gb2312', "UTF-8", $content);
        $data = str_replace('\"', '"', $data); //fix 
        //выдираем содержимое sms из js-скрипта в html, ключ = каналу sim 
        preg_match_all("|sms= \[(.*?)\]|is", $data, $sms_dump_arr);
        $sms = [];
        //первый цикл - по "каналам sim", которые содержат 5 смс-ок, разделенных запятой и в кавычках. То есть в csv. 
        //Во втором цикле мы с помощью str_getcsv добываем валидно данные уже по каждой смс раздельно. 
        foreach ($sms_dump_arr[1] as $sim_key => $sim_val) {
            foreach (str_getcsv($sim_val) as $sms_key => $sms_val) {
                $sms[$sim_key][$sms_key] = explode(',', $sms_val, 3);
                //ключи 0,1,2 содержат датувремя, номер, текст смс. 
                $sms[$sim_key][$sms_key][] = md5($sms_val);
            }
        }
        return var_dump($sms);
    }

    /**
     * скачивает заявки с смс сервера
     */
    static function uploadFromGoIpDb() {
        $maxdate = DB::table('sms_inbox')->select(DB::raw('max(received) as m'))->value('m');
        $maxdate = (empty($maxdate)) ? '2017-05-24' : $maxdate;
        $inbox = DB::connection('goip')->table('receive')->where('time', '>', with(new Carbon($maxdate))->format('Y-m-d H:i:s'))->get();
        $res = [];
        Log::info('SmsInbox', ['uploaded' => $inbox]);
        foreach ($inbox as $sms) {
            $smsInbox = new SmsInbox([
                'received' => with(new Carbon($sms->time))->format('Y-m-d H:i:s'),
                'phone' => StrUtils::removeNonDigits($sms->srcnum),
                'message' => $sms->msg,
            ]);
            $smsInbox->save();
            $res[] = $smsInbox;
        }
        return $res;
    }

    static function uploadFromBeeline() {
        // Construct transport and client
        $transport = new SocketTransport(array('smpp.provider.com'), 2775);
        $transport->setRecvTimeout(10000);
        $smpp = new SmppClient($transport);

        // Activate binary hex-output of server interaction
        $smpp->debug = true;
        $transport->debug = true;

        // Open the connection
        $transport->open();
        $smpp->bindTransmitter("USERNAME", "PASSWORD");

        // Optional connection specific overrides
        //SmppClient::$sms_null_terminate_octetstrings = false;
        //SmppClient::$csms_method = SmppClient::CSMS_PAYLOAD;
        //SmppClient::$sms_registered_delivery_flag = SMPP::REG_DELIVERY_SMSC_BOTH;
        // Prepare message
        $message = 'H€llo world';
        $encodedMessage = GsmEncoder::utf8_to_gsm0338($message);
        $from = new SmppAddress('SMPP Test', SMPP::TON_ALPHANUMERIC);
        $to = new SmppAddress(4512345678, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164);

        // Send
        $smpp->sendSMS($from, $to, $encodedMessage, $tags);

        // Close connection
        $smpp->close();
    }

    static function handleInbox($date = '2017-05-24') {
        $inbox = SmsInbox::where('created_at', '>=', $date)->where('processed', 0)->orderBy('created_at', 'desc')->get();
        foreach ($inbox as $sms) {
            $sms->handleSms();
            $sms->processed = 1;
            $sms->save();
        }
    }

    /**
     * обработать случай когда нет правильного смс с заявкой на займ в течении суток
     * @return boolean
     */
    function handleNoValidClaimSms() {
        if (is_null($this->getClaimSmsBeforeLimit(24))) {
            return $this->sendValidClaimSms();
        }
        $lastClaim = $this->getLastClaim();
        if (is_null($lastClaim)) {
            return $this->sendValidClaimSms();
        }
        $loan = $lastClaim->loan;
        if (is_null($loan)) {
            if ($lastClaim->deleteThrough1c()) {
                return $this->sendValidClaimSms(1);
            } else {
                return $this->sendUnavaliableSms();
            }
        } else {
            if ($loan->closed) {
                return $this->sendValidClaimSms();
            } else {
                return $this->sendUnavaliableSms();
            }
        }
        if (!is_null($lastClaim)) {
            if ($lastClaim->deleteThrough1c()) {
                return $this->sendValidClaimSms(1);
            } else {
                return $this->sendUnavaliableSms();
            }
        } else {
            return $this->sendValidClaimSms();
        }
    }

    /**
     * Обрабатывает смс
     * @return type
     */
    function handleSms() {
//        if($this->phone!='79030466344'){
//            return;
//        }
        $lastValidClaimSms = SmsInbox::getLastValidClaimSms($this->phone, $this->created_at->subHours(24));
        if (is_null($lastValidClaimSms)) {
            Log::info('SmsInbox', ['error' => 'no valid claim sms', 'sms' => $this]);
            return $this->handleNoValidClaimSms();
        }
        if (!$lastValidClaimSms->isValidAmount()) {
            Log::info('SmsInbox', ['error' => 'no valid amount', 'sms' => $this]);
            return $this->sendValidAmountSms();
        }
        $claimAmount = $lastValidClaimSms->getAmount();

        $customer = Customer::where('telephone', $this->phone)->orderBy('created_at', 'desc')->first();
        if (is_null($customer)) {
            Log::info('SmsInbox', ['error' => 'no customer', 'sms' => $this]);
            return $this->sendUnavaliableSms();
        }
        $passport = Passport::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
        if (is_null($passport)) {
            Log::info('SmsInbox', ['error' => 'no passport', 'sms' => $this]);
            return $this->sendUnavaliableSms();
        }
        $checkKData = MySoap::passport(['series' => $passport->series, 'number' => $passport->number, 'old_series' => '', 'old_number' => '']);
        if ((array_key_exists('result', $checkKData) && $checkKData['result'] == 0) || (array_key_exists('res', $checkKData) && $checkKData['res'] == 0)) {
            Log::info('SmsInbox', ['error' => 'bad passport1', 'sms' => $this, 'passport' => $passport, 'checkk' => $checkKData]);
            return $this->sendUnavaliableSms();
        }
        if ($checkKData['postclient'] != 'Да') {
            Log::info('SmsInbox', ['error' => 'not postclient', 'sms' => $this, 'passport' => $passport, 'checkk' => $checkKData]);
            return $this->sendUnavaliableSms();
        }
        if ($passport->fio != $checkKData['fio'] || with(new Carbon($passport->birth_date))->ne(new Carbon($checkKData['birth_date']))) {
//        if($passport->fio != $checkKData['fio']){
            Log::info('SmsInbox', ['error' => 'bad passport2', 'sms' => $this, 'passport' => $passport, 'checkk' => $checkKData]);
            return $this->sendUnavaliableSms();
        }

        $card = Card::where('customer_id', $passport->customer_id)->where('status', Card::STATUS_ACTIVE)->orderBy('created_at', 'desc')->first();
        if (is_null($card)) {
            Log::error('no card');
            Log::info('SmsInbox', ['error' => 'no card', 'sms' => $this]);
            return $this->sendUnavaliableSms();
        }
        $loanKData = Synchronizer::updateLoanRepayments($passport->series, $passport->number);
        if (!is_array($loanKData)) {
            Log::error('no loan_k_data');
            Log::info('SmsInbox', ['error' => 'no card', 'sms' => $this, 'loank' => $loanKData]);
            return $this->sendUnavaliableSms();
        }
        if (array_key_exists('loan', $loanKData) && !$loanKData['loan']->closed) {
            Log::error('unclosed loan');
            Log::info('SmsInbox', ['error' => 'unclosed loan', 'sms' => $this, 'loank' => $loanKData]);
            return $this->sendUnclosedLoanSms($loanKData['loan']);
        }
        $lastClaim = Claim::where('passport_id', $passport->id)
                ->orderBy('claims.created_at', 'desc')
                ->first();
        if (!is_null($lastClaim)) {
            $lastClaimLoan = Loan::where('claim_id', $lastClaim->id)->first();
            if (!is_null($lastClaimLoan)) {
                if ($lastClaimLoan->closed) {
                    $lastClaim = null;
                } else {
                    Log::error('unclosed loan 2');
                    Log::info('SmsInbox', ['error' => 'unclosed loan 2', 'sms' => $this, 'loank' => $loanKData]);
                    return $this->sendUnclosedLoanSms($lastClaimLoan);
                }
            }
            if (!is_null($lastClaim) && $lastClaim->status == Claim::STATUS_DECLINED) {
                if ($lastClaim->created_at->lt(Carbon::today())) {
                    $lastClaim = null;
                } else {
                    Log::error('SmsInbox', ['error' => 'claim declined', 'claim' => $lastClaim, 'sms' => $this]);
                    return $this->sendDeclineSms();
                }
            }
        }
        if (is_null($lastClaim)) {
            Claim::sendToAutoApprove($passport->series, $passport->number);
            $claim = $this->createClaim($passport, $claimAmount);
        } else {
            $claim = $lastClaim;
        }
        if (empty($claim->id_1c)) {
            $claim->sendTo1c();
        }
//        if (is_null($claim) || empty($claim->id_1c) || $claim->status != Claim::STATUS_ACCEPTED) {
        if (is_null($claim) || empty($claim->id_1c)) {
            Log::info('SmsInbox', ['error' => 'no claim', 'sms' => $this]);
            return $this->sendUnavaliableSms();
        }
        if ($claim->status == Claim::STATUS_DECLINED) {
            Log::error('SmsInbox', ['error' => 'claim declined', 'claim' => $claim, 'sms' => $this]);
            return $this->sendDeclineSms();
        }

        $lastAcceptSms = (in_array($this->message, ['DA', 'Da', 'Да', 'ДА'])) ? $this : null;
        if (!is_null($lastAcceptSms) && $lastAcceptSms->created_at->gte($lastValidClaimSms->created_at)) {
            $loan = $this->createLoanAndEnroll($claim, $card);
            if (!is_null($loan)) {
                Log::info('SmsInbox', ['info' => 'creating loan', 'sms' => $this, 'loan' => $loan]);
                return $this->sendLoanSms($loan);
            }
        } else {
            Log::info('SmsInbox', ['error' => 'no approve sms', 'sms' => $this, 'claim' => $claim]);
            return $this->sendClaimApprovedSms($claim);
        }
        Log::error('SmsInbox', ['error' => 'skipped to the end']);
    }

    /**
     * вернуть последнюю правильную смс с заявкой на займ до даты
     * @param integer $limit количество дней от даты смс до которых нужно искать смс с заявкой 
     * @return \App\SmsInbox
     */
    function getClaimSmsBeforeLimit($limit = 24) {
        $prevSms = SmsInbox::getLastValidClaimSms($this->phone, '2017-05-25', $this->created_at->subDays($limit));
        return $prevSms;
    }

    /**
     * Получает последнюю активную заявку в базе по телефонному номеру
     * @return \App\Claim
     */
    function getLastClaim() {
        $customer = Customer::where('telephone', $this->phone)->first();
        if (is_null($customer)) {
            return null;
        }
        $user = SmsInbox::getSmsUser();
        $claim = Claim::where('customer_id', $customer->id)
                ->orderBy('created_at', 'desc')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', with(new Carbon($this->created_at))->subDays(30))
                ->first();
        return $claim;
    }

    /**
     * Проверяет есть ли не закрытый займ на клиенте с телефоном
     * @param string $phone
     * @return boolean
     */
    function customerHasLoan($phone) {
        $customer = Customer::where('telephone', $phone)->first();
        if (!is_null($customer)) {
            return (Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')->where('claims.customer_id', $customer->id)->where('closed', 0)->count() > 0);
        } else {
            return false;
        }
    }

    /**
     * Найти последнее сообщение подтверждающее взятие займа
     * @param string $telephone
     * @param string $date
     * @return type
     */
    static function getLastAcceptSms($telephone, $date) {
        return SmsInbox::where('phone', $telephone)->where('created_at', '>=', $date)->where('message', 'DA')->orderBy('created_at', 'desc')->first();
    }

    /**
     * Найти последнее сообщение с заявкой на займ
     * @param string $telephone
     * @param string $date
     * @return type
     */
    static function getLastValidClaimSms($telephone, $fromDate, $endDate = null) {
        $inbox = SmsInbox::where('phone', $telephone)
                ->where('message', 'like', SmsInbox::CLAIM_WORD . ' %')
                ->where('created_at', '>=', $fromDate)
                ->orderBy('created_at', 'desc');
        if (!is_null($endDate)) {
            $inbox->where('created_at', '<', $endDate);
        }
        $inbox = $inbox->get();
        foreach ($inbox as $sms) {
            if ($sms->isValidClaimSms()) {
                return $sms;
            }
        }
        return null;
    }

    /**
     * Найти последнее сообщение с паспортом
     * @param string $telephone
     * @param string $date
     * @return type
     */
    static function getLastValidPassportSms($telephone, $date) {
        $inbox = SmsInbox::where('phone', $telephone)->where('created_at', '>=', $date)->orderBy('created_at', 'desc')->get();
        foreach ($inbox as $sms) {
            if ($sms->isValidPassportSms()) {
                return $sms;
            }
        }
        return null;
    }

    /**
     * Проверяет СМС на правильность
     * @return boolean
     */
    public function isValidClaimSms() {
        return (preg_match("/" . SmsInbox::CLAIM_WORD . " \d{4,5}\z/", $this->message));
    }

    /**
     * Проверяет СМС на правильные паспортные данные
     * @return boolean
     */
    public function isValidPassportSms() {
        return (preg_match("/\d{10}\z/", $this->message));
    }

    /**
     * Проверяет правильная ли пришла сумма в смс
     * @return boolean
     */
    public function isValidAmount() {
        $amount = $this->getAmount();
        return ($amount >= 2000 && $amount <= 15000);
    }

    public function getAmount() {
        if ($this->isValidClaimSms()) {
            $amount = intval(substr($this->message, strpos($this->message, ' ') + 1));
            return floor($amount / 1000) * 1000;
        } else {
            return 0;
        }
    }

    function sendPassportSms() {
        return SMSer::sendByGoIpApi($this->phone, 'Отправьте без пробела серию и номер паспорта, указанные при заключении последнего договора займа');
    }

    function sendUnavaliableSms() {
        return SMSer::sendByGoIpApi($this->phone, 'Данный способ для получения займа недоступен. ФинТерра88003014344');
    }

    function sendClaimApprovedSms($claim) {
        return SMSer::sendByGoIpApi($this->phone, 'Заявка одобрена на сумму ' . $claim->summa . ', срок займа (30 дней), суммой ПСК ' . LoanRate::getPSK() . '% годовых. Данная заявка действует 24 часа с момента получения данного смс. Финтерра 88003014344. Отправьте ' . SmsInbox::APPROVE_WORD . ' если согласны с условиями.');
    }

    function sendValidClaimSms($type = 0) {
        switch ($type) {
            case 1:
                return SMSer::sendByGoIpApi($this->phone, 'С момента одобрения заявки прошло более 24 часов. Пройдите процедуру заново. Отправьте: ' . SmsInbox::CLAIM_WORD . ' пробел размер займа');
            default :
                return SMSer::sendByGoIpApi($this->phone, 'Неверный формат. Внимание! Правильный формат: ' . SmsInbox::CLAIM_WORD . ' пробел размер займа');
        }
    }

    function sendDeclineSms() {
        return SMSer::sendByGoIpApi($this->phone, 'Ваша заявка на получение займа отклонена. ФинТерра88003014344');
    }

    function sendValidAmountSms() {
        return SMSer::sendByGoIpApi($this->phone, 'Доступная сумма займа: от 2000 до 15000 руб. Правильный формат: ' . SmsInbox::CLAIM_WORD . '_пробел_размерзайма');
    }

    function sendUnclosedLoanSms($loan) {
        return SMSer::sendByGoIpApi($this->phone, 'У Вас есть непогашенный займ № '
                        . $loan->id_1c
                        . ' от ' . $loan->created_at->format('d.m.Y')
                        . ' г. Сроком до ' . $loan->getEndDate()->format('d.m.Y')
                        . ' ПСК ' . $loan->getPSK(true) . '% годовых');
    }

    /**
     * Отправить смс о создании кредитника
     * @param \App\Loan $loan кредитник
     * @return type
     */
    function sendLoanSms($loan) {
        return SMSer::sendByGoIpApi(
                        $this->phone, 'Заем акцептован на сумму ' . $loan->money . ' рублей. Дата погашения'
                        . $loan->created_at->format('d.m.Y') . ' Сумма к возврату '
                        . StrUtils::kopToRub($loan->getEndDateMoney(true, true)) . ' рублей. ПСК займа ' . $loan->getPSK(true)
                        . ' % годовых. Договор займа № ' . $loan->id_1c
        );
    }

    /**
     * Создает заявку
     * @param \App\Passport $passport
     * @param integer $money
     * @return \App\Claim
     */
    function createClaim($passport, $money = 2000) {
        DB::beginTransaction();
        $spylog = new Spylog();
        $customer = $passport->customer;
        $lastAbout = about_client::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
        if (!is_null($lastAbout)) {
            $about = $lastAbout->replicate();
        } else {
            $about = new about_client();
            $about->fill([
                'organizacia' => '-',
                'stazlet' => 0,
                'sex' => 0,
                'avto' => 0,
                'dohod' => 0,
                'anothertelephone' => '-',
            ]);
        }
        $about->postclient = 1;
        $about->customer_id = $customer->id;
        try {
            $about->save();
        } catch (\Exception $ex) {
            DB::rollback();
            return null;
        }
        $spylog->addModelData(Spylog::TABLE_ABOUT_CLIENTS, $about);

        $claim = new Claim();
        $claim->srok = 30;
        $claim->summa = $money;
        $claim->customer_id = $customer->id;
        $claim->passport_id = $passport->id;
        $claim->about_client_id = $about->id;
        $user = SmsInbox::getSmsUser();
        $claim->user_id = $user->id;
        $claim->subdivision_id = $user->subdivision_id;
        try {
            $claim->save();
        } catch (\Exception $ex) {
            DB::rollback();
            return null;
        }
        $spylog->addModelData(Spylog::TABLE_CLAIMS, $claim);
        $spylog->save(Spylog::ACTION_CREATE, Spylog::TABLE_CLAIMS, $claim->id, 1);
        DB::commit();

        return $claim;
    }

    /**
     * Создает кредитник и делает зачисление
     * @param \App\Claim $claim заявка
     * @param \App\Card $card карта на которую зачислить
     * @return \App\Loan
     */
    public function createLoanAndEnroll($claim, $card) {
        $user = SmsInbox::getSmsUser();
        $subdivision = SmsInbox::getSmsSubdivision();
        $loantype = LoanType::where('id_1c', 'ARM000021')->first();
        DB::beginTransaction();
        $loan = new Loan();
        $loan->fill([
            'money' => $claim->summa,
            'time' => $claim->srok,
            'claim_id' => $claim->id,
            'loantype_id' => $loantype->id,
            'card_id' => $card->id,
            'in_cash' => 0,
        ]);
        $loan->user_id = $user->id;
        $loan->subdivision_id = $subdivision->id;
        try {
            if (!$loan->saveThrough1c()) {
                DB::rollback();
                Log::error('SmsInbox', ['error' => 'loan unsaved', 'loan' => $loan]);
                return null;
            }
            $enroll = $loan->enroll();
            if (!$enroll || is_null($enroll)) {
                DB::rollback();
                Log::error('SmsInbox', ['error' => 'loan unrolled', 'loan' => $loan, 'enroll' => $enroll]);
                return null;
            }
        } catch (\Exception $ex) {
            DB::rollback();
            Log::error('SmsInbox', ['error' => 'error catched', 'loan' => $loan, 'ex' => $ex]);
            return null;
        }
        DB::commit();
        return $loan;
    }

    /**
     * Возвращает пользователя для создания займов по смс
     * @return \App\User
     */
    static function getSmsUser() {
        return User::where('id_1c', 'like', '%ЗаймПоСМС%')->first();
    }

    static function getSmsSubdivision() {
        return Subdivision::where('name_id', 'like', '%ЗаймПоСМС%')->first();
    }

}
