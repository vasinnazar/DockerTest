<?php

namespace App;

use Illuminate\Database\Eloquent\Model,
    Illuminate\Support\Facades\DB,
    App\MySoap,
    Carbon\Carbon,
    Illuminate\Database\Eloquent\SoftDeletes;
use App\Spylog\Spylog;
use Illuminate\Support\Facades\Log;
use Auth;
use App\Http\Controllers\ClaimController;

/**
 * Заявка на займ
 */
class Claim extends Model {

    use SoftDeletes;

    const STATUS_NEW = 0;
    const STATUS_ONCHECK = 1;
    const STATUS_ONEDIT = 2;
    const STATUS_DECLINED = 3;
    const STATUS_ACCEPTED = 4;
    const STATUS_CREDITSTORY = 5;
    const STATUS_DINNER = 6;
    const STATUS_INWORK = 7;
    const STATUS_REGISTRATION = 8;
    const STATUS_NOANSWER_CLIENT = 9;
    const STATUS_BADPASSPORT = 10;
    const STATUS_NOANSWER_WORK = 11;
    const STATUS_NOANSWER_RELATIVES = 12;
    const STATUS_NOPASSPORT = 13;
    const STATUS_TILL_MONDAY = 14;
    const STATUS_TILL_TOMORROW = 15;
    const STATUS_SPRAVKA = 16;
    const STATUS_BADWORK = 17;
    const STATUS_NOPASSPORT_DATA = 18;
    const STATUS_BADPHONE = 19;
    const STATUS_PRECONFIRM = 20;
    const STATUS_CLIENT_DECLINED = 21;
    const STATUS_DOUBLE = 22;
    const STATUS_SCORISTA = 23;
    const STATUS_TELEPORT = 24;
    const STATUS_BADDOCS = 25;
    const STATUS_FINTERRA = 26;

    //добавил статус здесь - добавь в getStatusName

    protected $table = 'claims';
    protected $fillable = ['customer_id', 'srok', 'summa', 'date', 'comment', 'status', 'seb_phone', 'terminal_guid', 'terminal_loantype_id', 'special_percent', 'max_money', 'uki', 'timestart', 'id_teleport', 'agrid', 'scorista_status', 'scorista_decision', 'teleport_status'];
    protected $dates = ['deleted_at'];

    public function customer() {
        return $this->belongsTo('App\Customer', 'customer_id');
    }

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function subdivision() {
        return $this->belongsTo('App\Subdivision', 'subdivision_id');
    }

    public function promocode() {
        return $this->belongsTo('App\Promocode');
    }

    public function passport() {
        return $this->belongsTo('App\Passport');
    }

    public function about_client() {
        return $this->belongsTo('App\about_client');
    }

    public function terminal_loantype() {
        return $this->belongsTo('App\LoanType', 'terminal_loantype_id');
    }

    static function isPERENOS($status) {
        return ($status >= Claim::STATUS_DINNER && $status <= Claim::STATUS_PRECONFIRM) ? true : false;
    }

    static function getStatusName($status) {
        return with([
                    "Новая", "На проверке", "На редактировании", "Отказано", "Одобрено",
                    "Исправление КИ", "Обед", "В работе", "Заканчивается временная прописка",
                    "Клиент не ответил", "Неверные паспортные данные", "Не дозвонились до места работы",
                    "Не дозвонились до родственников", "Не имеет паспорта", "Перенос на понедельник",
                    "Перенос на след. день", "Повтор со справкой", "Попала в другую орг.",
                    "Специалист не забивает паспортные данные", "Указан неверный раб. тел. или адрес",
                    "Временное одобрение", "Клиент отказался", "Задвоенная заявка", "Временное одобрение Scorista", "Временное одобрение TELEPORT",
                    "Некорректные Доки Фото", "Финтерра"
                ])[$status];
    }

    static function getEditableStatuses() {
        return [Claim::STATUS_ONEDIT, Claim::STATUS_NEW, Claim::STATUS_TELEPORT, Claim::STATUS_BADDOCS, Claim::STATUS_FINTERRA];
    }

    public function deleteThrough1c() {
        DB::beginTransaction();
        if (!$this->delete()) {
            DB::rollback();
            return false;
        }
        $res1c = MySoap::removeItem(MySoap::ITEM_CLAIM, $this->id_1c, $this->customer->id_1c);
//        if((config('app.dev') && (int)$res1c->result==0) || (!config('app.dev') && $res1c['res']==0)){
        if ($res1c->result == 0) {
            DB::rollback();
            return false;
        }
        DB::commit();
        return true;
    }

    static function hasSignedContract($claim_id) {
        return (Photo::where('claim_id', $claim_id)->where('description', 'Договор')->count() > 0) ? true : false;
    }

    /**
     * Создает заявку на последний паспорт с последними доп данными или пустыми
     * @param \App\Customer $customer
     * @param int $money
     * @param int $time
     * @param \App\User $user
     * @param \App\Subdivision $subdivision
     * @return \App\Claim
     */
    static function createEmptyClaimForCustomer($customer, $money = 1000, $time = 1, $user = null, $subdivision = null) {
        DB::beginTransaction();
        $claim = new Claim();
        $claim->customer_id = $customer->id;
        $claim->passport_id = $customer->getLastPassport()->id;
        $lastAboutClient = $customer->getLastAboutClient();
        if (!is_null($lastAboutClient)) {
            $aboutClient = $lastAboutClient->replicate();
        }
        if (!isset($aboutClient) || is_null($aboutClient)) {
            $aboutClient = new about_client();
        }
        $aboutClient->save();
        $claim->about_client_id = $aboutClient->id;
        $claim->summa = $money;
        $claim->srok = $time;
        $claim->date = Carbon::now()->format('Y-m-d H:i:s');
        $claim->user_id = (is_null($user)) ? Auth::user()->id : $user->id;
        $claim->subdivision_id = (is_null($user)) ? Auth::user()->subdivision_id : $subdivision->id;
        $claim->save();
        DB::commit();
        return $claim;
    }

    public function updateFrom1c() {
        if (is_null(Auth::user()) || Auth::user()->id != 5) {
            return true;
        }
//        return;
        if (is_null($this->id_1c)) {
            return false;
        }
        $data = MySoap::passport(['series' => $this->passport->series, 'number' => $this->passport->number, 'claim_id' => $this->id_1c]);
        if (!array_key_exists('result', $data) || !$data['result']) {
            return false;
        }
        $res = $this->updateByData($data);
        if ($res['res']) {
            return true;
        } else {
            return false;
        }
    }

    public function updateByData($data) {
        $spylog = new Spylog();
        $input = $data;
        foreach ($input as $i) {
            if ($i == '') {
                $i = NULL;
            }
        }
        $checkboxes = Http\Controllers\ClaimController::$claimCheckboxes;
        foreach ($checkboxes as $cb) {
            if (!array_key_exists($cb, $input)) {
                $input[$cb] = 0;
            }
        }
        foreach ($input as $k => $v) {
            if (in_array($k, $checkboxes)) {
                $input[$k] = ($v == 'Да' || $v == 1) ? 1 : 0;
            }
        }
        $claim = $this;
        $spylog->addModelChangeData('claims', $claim, $input);
        $claim->fill($input);

        //меняем запятую в спец проценте на точку (из 1с приходит через запятую)
        $claim->special_percent = (array_key_exists('special_percent', $input) && !is_null($input['special_percent'])) ? str_replace(',', '.', $input['special_percent']) : null;
        if (array_key_exists('subdivision_id_1c', $input)) {
            $subdiv = Subdivision::where('name_id', $input['subdivision_id_1c'])->first();
            if (is_null($subdiv)) {
                $subdiv = new Subdivision();
                $subdiv->name_id = $input['subdivision_id_1c'];
                $subdiv->save();
            }
            if (!is_null($subdiv)) {
                $claim->subdivision_id = $subdiv->id;
            }
        }
        /**
         * выставляем специалиста в заявке, и добавляем в базу если такого нет
         */
        if (array_key_exists('user_id_1c', $input)) {
            if (!is_null($claim->subdivision) && $claim->subdivision->is_terminal && !is_null($claim->user_id)) {
                
            } else {
                $user = User::where('id_1c', 'like', \App\StrUtils::stripWhitespaces($input['user_id_1c']) . '%')->orWhere('login', \App\StrUtils::stripWhitespaces($input['user_id_1c']))->first();
                if (!is_null($user)) {
                    $user_id = $user->id;
                    $claim->user_id = $user_id;
                } else {
                    $user = new User();
                    $user->login = \App\StrUtils::stripWhitespaces($input['user_id_1c']);
                    $user->name = \App\StrUtils::stripWhitespaces($input['user_id_1c']);
                    $user->id_1c = $input['user_id_1c'];
                    $user->banned = 1;
                    $user->group_id = 1;
                    if ($user->save()) {
                        $claim->user_id = $user->id;
                    } else {
                        $claim->user_id = Spylog::USERS_ID_1C;
                    }
                    if (isset($subdiv) && !is_null($subdiv)) {
                        $claim->subdivision_id = $subdiv->id;
                    }
                }
            }
        }

        $claim->date = (!is_null($claim->created_at)) ? $claim->created_at->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s');
        if (array_key_exists('claim_id', $input)) {
            $claim->id_1c = $input['claim_id'];
        }
        DB::beginTransaction();

        if (is_null($claim->id)) {
            if (!empty($input['series']) && !empty($input['number'])) {
                $passport = Passport::where('series', $input['series'])->where('number', $input['number'])->first();
            } else {
                $passport = null;
            }
            if (is_null($passport)) {
                $passport = new Passport();
                $customer = Customer::where('id_1c', $input['customer_id_1c'])->first();
                if (is_null($customer)) {
                    $customer = new Customer();
                    if (isset($user_id)) {
                        $customer->creator_id = $user_id;
                    }
                }
            } else {
                $customer = $passport->customer;
            }
        } else {
            //проверяем а нет ли в базе уже такого паспорта, и если есть то подставить 
            //в заявку паспорт и контрагента из базы, а те что есть удалить
            //сделано для пустых контрагентов и паспортов из телепорта
            if (!empty($input['series']) && !empty($input['number'])) {
                $passport = Passport::where('series', $input['series'])->where('number', $input['number'])->first();
                if (!is_null($passport)) {
                    if ($passport->id != $claim->passport_id) {
                        $passportToRemove = Passport::find($claim->passport_id);
                        if (!is_null($passportToRemove)) {
                            $passportToRemove->delete();
                        }
                        $customerToRemove = Customer::find($claim->customer_id);
                        if (!is_null($customerToRemove)) {
                            $customerToRemove->delete();
                        }
                        $claim->passport_id = $passport->id;
                    }
                    $customer = $passport->customer;
                } else {
                    $customer = Customer::find($claim->customer_id);
                    $passport = Passport::find($claim->passport_id);
                }
            } else {
                $customer = Customer::find($claim->customer_id);
                $passport = Passport::find($claim->passport_id);
            }
        }
        if (is_null($customer)) {
            DB::rollback();
            Log::error('Claim.updateByData Пользователь не найден 1', $data);
            return ['res' => 0, 'msg_err' => $data];
        }

        $spylog->addModelChangeData('customers', $customer, $input);
        $customer->fill($input);
        if (array_key_exists('snils', $input)) {
            $customer->snils = StrUtils::removeNonDigits($input['snils']);
            if ($customer->snils == '') {
                $customer->snils = null;
            }
        }
        $customer->id_1c = $input['customer_id_1c'];
        if (!$customer->save()) {
            DB::rollback();
            Log::error('Claim.updateByData Пользователь не сохранен', $data);
            return ['res' => 0, 'msg_err' => $data];
        }

        if (array_key_exists('card_number', $input) && array_key_exists('secret_word', $input) && $input['card_number'] != "0") {
            Card::createCard($input['card_number'], $input['secret_word'], $customer->id);
        }

        if (is_null($passport)) {
            DB::rollback();
            Log::error('Claim.updateByData Паспорт не найден', $data);
            return ['res' => 0, 'msg_err' => $data];
        }
        $passport->customer_id = $customer->id;
        $input['birth_date'] = with(new Carbon($input['birth_date']))->format('Y-m-d');
        if (array_key_exists('issued_date', $input)) {
            $input['issued_date'] = with(new Carbon($input['issued_date']))->format('Y-m-d');
        }
        if (array_key_exists('address_reg_date', $input)) {
            $input['address_reg_date'] = with(new Carbon($input['address_reg_date']))->format('Y-m-d');
        }
        $spylog->addModelChangeData('passports', $passport, $input);
        if (empty($input['series'])) {
            unset($input['series']);
        }
        if (empty($input['number'])) {
            unset($input['number']);
        }
        $passport->fill($input);
        if (!$passport->save()) {
            DB::rollback();
            Log::error('Claim.updateByData Паспорт не сохранен', $data);
            return ['res' => 0, 'msg_err' => $data];
        }
        $about_client = (is_null($claim->about_client_id)) ? (new about_client()) : (about_client::find($claim->about_client_id));
        if (is_null($about_client)) {
            DB::rollback();
            Log::error('Claim.updateByData Инфа о клиенте не найдена', $data);
            return ['res' => 0, 'msg_err' => $data];
        }
        $spylog->addModelChangeData('about_clients', $about_client, $input);

        if (is_null($input['adsource']) || is_null(AdSource::find((int) $input['adsource'])) || is_null(AdSource::where('name', 'like', '%' . $input['adsource'] . '%'))) {
            Log::error('Claim.updateByData Неверный adsource', ['adsource' => $input['adsource']]);
            $input['adsource'] = 1;
        }
        if (is_null($input['zhusl']) || is_null(LiveCondition::find((int) $input['zhusl']))) {
            Log::error('Claim.updateByData Неверный источник zhusl', ['zhusl' => $input['zhusl']]);
            $input['zhusl'] = 1;
        }
        if (is_null($input['obrasovanie']) || is_null(EducationLevel::find((int) $input['obrasovanie']))) {
            Log::error('Claim.updateByData Неверный obrasovanie', ['obrasovanie' => $input['obrasovanie']]);
            $input['obrasovanie'] = 1;
        }
        if (is_null($input['marital_type_id']) || is_null(MaritalType::find((int) $input['marital_type_id']))) {
            Log::error('Claim.updateByData Неверный marital_type_id', ['marital_type_id' => $input['marital_type_id']]);
            $input['marital_type_id'] = 1;
        }
        if (array_key_exists('sex', $input) && $input['sex'] == 'Мужской') {
            $input['sex'] = 1;
        } else {
            $input['sex'] = 0;
        }
        $about_client->fill($input);
        $about_client->customer_id = $customer->id;
        if (!$about_client->save()) {
            DB::rollback();
            Log::error('Claim.updateByData Инфа о клиенте не сохранена', $data);
            return ['res' => 0, 'msg_err' => $data];
        }
        $claim->customer_id = $customer->id;
        $claim->passport_id = $passport->id;
        $claim->about_client_id = $about_client->id;
        if (array_key_exists('agrid', $input)) {
            $claim->agrid = $input['agrid'];
        }
        if (!$claim->save()) {
            DB::rollback();
            Log::error('Claim.updateByData Заявка не обновлена', $data);
            return ['res' => 0, 'msg_err' => $data];
        }

        if (!$spylog->save(Spylog::ACTION_UPDATE, 'claims', $claim->id)) {
            DB::rollback();
            Log::error('Claim.updateByData Логи не сохранились', $data);
            return ['res' => 0, 'msg_err' => 'Не сохранились логи'];
        }
        DB::commit();
        Log::info('Claim.updateByData Сохранено', $data);
        return ['res' => 1, 'claim' => $claim];
    }

    /**
     * Возвращает путь до папки с фотографиями для заявки
     * @param boolean $withHost добавить путь до сервера
     * @param boolean $withDate добавить в путь дату заявки
     * @param boolean $backslash обратный или обычный слэш в пути
     * @return string
     */
    public function getPhotosFolderPath($withHost = true, $withDate = true, $backslash = true) {
//        $photos_path = '\\\192.168.1.31\\photos\\';
//        $photos_terminal_path = '\\\192.168.1.31\\photos\\';
//
//        $path = '';
//        $slash = ($backslash) ? '\\' : '/';
//        $isTerminal = $this->subdivision->is_terminal;
//
//        if ($isTerminal) {
//            $path .= (($withHost) ? $photos_terminal_path : '') . 'terminal' . $slash;
////            $path .= $photos_terminal_path . $slash;
//            $path .= (string) $this->customer_id;
//        } else {
//            $path .= (($withHost) ? ($photos_path) : '') . 'claims' . $slash;
////            $path .= $photos_path . $slash;
//            $path .= $this->created_at->format('Ym') . $slash;
//            $path .= (string) $this->passport->series . (string) $this->passport->number;
//        }
//        if ($withDate) {
//            $path.= $slash . $this->created_at->format('Y-m-d') . $slash;
//        }
//        return $path;
        $path = '';
        $slash = ($backslash) ? '\\' : '/';
        $isTerminal = $this->subdivision->is_terminal;

        if ($isTerminal) {
            $path .= (($withHost) ? config('options.photos_terminal_path') : 'terminal') . $slash;
            $path .= (string) $this->customer_id;
        } else {
            $path .= ($withHost) ? (config('options.photos_path') . $slash) : '';
//            $path .= '1'.$slash;
            $path .= (string) $this->passport->series . (string) $this->passport->number;
        }
        if ($withDate) {
            $path.= $slash . $this->created_at->format('Y-m-d') . $slash;
        }
        return $path;
    }

    /**
     * Отсылает телепортовские заявки с пустыми статусами в телепорт
     * @param string $start_date
     * @param string $end_date
     */
    public static function resendTeleportClaimsWithNullStatus($start_date, $end_date) {
        $claims = Claim::whereNotNull('id_teleport')
                ->whereNull('teleport_status')
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get();
        foreach ($claims as $claim) {
            Http\Controllers\TeleportController::sendStatusToTeleport($claim, 'cancel');
        }
    }

    /**
     * Отправляет заявку на автоодобрение
     * @param type $loan_id_1c
     * @param type $claim_id_1c
     * @param type $closing_id_1c
     * @param type $customer_id_1c
     * @return type
     */
    static function sendToAutoApprove($series, $number, $loanKData = null) {
        $xml = new \SimpleXMLElement('<root/>');
        $item = $xml->addChild('item');
        if (is_null($loanKData)) {
            $loanKData = Synchronizer::updateLoanRepayments($series, $number);
        }
        if (is_array($loanKData)) {
            if (array_key_exists('loan', $loanKData)) {
                $item->addAttribute('loan_id_1c', $loanKData['loan']->id_1c);
            }
            $item->addAttribute('claim_id_1c', $loanKData['claim']->id_1c);
            $item->addAttribute('customer_id_1c', $loanKData['claim']->customer->id_1c);
            $item->addAttribute('closing_id_1c', '');
            if (array_key_exists('repayments', $loanKData)) {
                foreach ($loanKData['repayments'] as $rep) {
                    if ($rep->repaymentType->isClosing()) {
                        $item->attributes()->closing_id_1c = $rep->id_1c;
                    }
                }
            }
        }
        $res1c = MySoap::sendToAutoApprove($xml->asXML());
        return $res1c;
    }

    /**
     * отправляет данную заявку в 1с
     * @return boolean
     */
    public function sendTo1c() {
        $claimCtrl = new ClaimController();
        $res = $claimCtrl->sendClaimTo1c($this);
        if (is_array($res) && array_key_exists('res', $res) && $res['res'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * является ли данная заявка заявкой по смс
     * @return boolean
     */
    public function isClaimBySms($toint = false) {
        $user = SmsInbox::getSmsUser();
        $subdiv = SmsInbox::getSmsSubdivision();
        $res = (!is_null($subdiv) && $this->subdivision_id == $subdiv->id && !is_null($user) && $this->user_id == $user->id);
        if ($toint) {
            return ($res) ? 1 : 0;
        } else {
            return ($res);
        }
    }

}
