<?php

namespace App\Http\Controllers;

use App\UploadSqlFile;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Spylog\Spylog;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\User;
use Illuminate\Support\Facades\Hash;
use App\Passport;
use App\Subdivision;
use App\Claim;
use App\about_client;
use App\AdSource;
use App\EducationLevel;
use App\LiveCondition;
use App\MaritalType;
use App\Card;
use App\DailyCashReport;
use App\StrUtils;
use Illuminate\Support\Facades\Storage;
use App\Customer;
use App\Utils\DebtorsInfoUploader;
use App\DebtorEvent;
use App\Debtor;
use App\SmsInbox;
use App\Loan;
use App\Order;

/**
 * обработчик запросов из 1С
 */
class From1cController extends Controller
{

    /**
     * добавляет пользователя по запросу из 1С
     */
    public function addUserFrom1c(Request $request): array
    {
        Log::info('From1cController.addUserFrom1c', $request->all());
        $validator = Validator::make($request->all(), [
            'login' => 'required',
//                    'password' => 'required',
            'subdivision_id_1c' => 'required',
            'id_1c' => 'required',
            'doc' => 'required'
        ]);
        if ($validator->fails()) {
            Log::error('Не прошло валидацию', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
        }
        $subdiv = \App\Subdivision::where('name_id', $request->get('subdivision_id_1c'))->first();
        if (is_null($subdiv)) {
            Log::error('Не найдено подразделение', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
        }
        $user = User::where('id_1c', 'like', StrUtils::stripWhitespaces($request->get('id_1c')) . '%')
            ->orWhere('login', StrUtils::stripWhitespaces($request->get('id_1c')))->first();
        if (!is_null($user)) {
            try {
                $user->update([
                    'name' => $request->get('login'),
                    'login' => $request->get('login'),
                    'password' => Hash::make($request->get('password')),
                    'subdivision_id' => $subdiv->id,
                    'doc' => $request->get('doc'),
                    'last_login' => Carbon::now()->format('Y-m-d H:i:s')
                ]);
            } catch (Exception $exc) {
                Log::error('Не смог обновить пользователя', $request->all());
                return ['res' => 0, 'msg_err' => $request->all()];
            }
        } else {
            try {
                $user = User::create([
                    'name' => $request->get('login'),
                    'login' => $request->get('login'),
                    'password' => Hash::make($request->get('password')),
                    'subdivision_id' => $subdiv->id,
                    'doc' => $request->get('doc'),
                    'id_1c' => $request->get('id_1c'),
                    'last_login' => Carbon::now()->format('Y-m-d H:i:s'),
                    'begin_time' => '08:00:00',
                    'end_time' => '22:00:00'
                ]);

            } catch (Exception $exc) {
                Log::error('Не смог добавить пользователя', $request->all());
                return ['res' => 0, 'msg_err' => $request->all()];
            }
        }
        if (!is_null($user)) {
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'users', $user);
            Log::info('Добавлен пользователь', ['login' => $user->login, 'id_1c' => $user->id_1c]);
            return ['res' => 1];
        } else {
            Log::error('Не смог добавить пользователя', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
        }
    }

    /**
     * обновляет данные в заявке,клиенте,паспорте,данных о клиенте по запросу из 1С
     * @param Request $request
     * @return type
     */
    public function updateClaim(Request $request)
    {
        Log::info('From1cController.updateClaim request', $request->all());
        $validator = Validator::make($request->all(), [
            'fio' => 'required',
            'series' => 'required',
            'number' => 'required',
            'birth_city' => 'required',
            'issued' => 'required',
            'subdivision_code' => 'required',
            'issued_date' => 'required',
            'id' => 'required',
            'address_region' => 'required',
            'address_street' => 'required',
            'address_house' => 'required',
            'fact_address_region' => 'required',
            'fact_address_street' => 'required',
            'fact_address_house' => 'required',
        ]);
        $spylog = new Spylog();
        $input = $request->all();
        foreach ($input as $i) {
            if ($i == '') {
                $i = null;
            }
        }
        $claim = Claim::where('id_1c', $request->id)->withTrashed()->first();
        if (!is_null($claim) && !is_null($claim->deleted_at)) {
            $claim->restore();
        }
        Log::info('From1cController.claim', ['claim' => $claim]);
        if (is_null($claim)) {
            $claim = new Claim();
            if (!array_key_exists('status', $input)) {
                $claim->status = Claim::STATUS_ONCHECK;
            }

        }
        $input['summa'] = $input['summa'];
        $spylog->addModelChangeData('claims', $claim, $input);
        $claim->fill($input);

        //меняем запятую в спец проценте на точку (из 1с приходит через запятую)
        $claim->special_percent = (array_key_exists('special_percent',
                $input) && !is_null($input['special_percent'])) ? str_replace(',', '.',
            $input['special_percent']) : null;
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
        if (array_key_exists('user_id_1c', $input)) {
            if (!is_null($claim->subdivision) && $claim->subdivision->is_terminal && !is_null($claim->user_id)) {

            } else {
                $user = User::where('id_1c', 'like',
                    \App\StrUtils::stripWhitespaces($input['user_id_1c']) . '%')->orWhere('login',
                    \App\StrUtils::stripWhitespaces($input['user_id_1c']))->first();
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
        $claim->id_1c = $request->id;
        DB::beginTransaction();

        if (is_null($claim->id)) {
            $passport = Passport::where('series', $input['series'])->where('number', $input['number'])->first();
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
            if (!isset($customer) || is_null($customer)) {
                $customer = $claim->customer;
            }
            if (!empty($input['series']) && !empty($input['number'])) {
                $passport = Passport::where('series', $input['series'])->where('number', $input['number'])->first();
            } else {
                $passport = $claim->passport;
            }
            if (isset($passport) && !is_null($passport)) {
                if (!is_null($claim->id_teleport) && $passport->id != $claim->passport_id) {
                    Log::info('FROM 1c Controller PASSPORT REMOVER', ['passport' => $passport, 'claim' => $claim]);
                    $otherClaimsOnClaimPassportNum = Claim::where('passport_id', $claim->passport_id)->where('id', '<>',
                        $claim->id)->where('created_at', '<', $claim->created_at->format('Y-m-d'))->count();
                    $ordersOnClaimPassportNum = \App\Order::where('passport_id', $claim->passport_id)->count();
                    $isTempCustomer = ($otherClaimsOnClaimPassportNum == 0 && $ordersOnClaimPassportNum == 0 && is_null($claim->passport->subdivision_code));
                    Log::info('From1cController PASSPORTREMOVER2 ', [
                        'otherclaims' => $otherClaimsOnClaimPassportNum,
                        'orders' => $ordersOnClaimPassportNum,
                        'tempcustomer' => $isTempCustomer
                    ]);
                    if (!is_null($claim->passport) && ($claim->passport->series == 'TELE' || $isTempCustomer)) {
                        $passportToRemove = Passport::find($claim->passport_id);
                        $customerToRemove = Customer::find($claim->customer_id);
                        Log::info('From1cController PASSPORT TO REMOVE',
                            ['customer' => $customerToRemove, 'passport' => $passportToRemove]);
                        $claim->customer_id = $passport->customer->id;
                        $claim->passport_id = $passport->id;
                        $customer = $passport->customer;
                    }
                }
//                $customer = $passport->customer;
            } else {
                $customer = Customer::find($claim->customer_id);
                $passport = Passport::find($claim->passport_id);
            }
        }
        if (is_null($customer)) {
            DB::rollback();
            Log::error('From1cController.updateClaim Пользователь не найден 1', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
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
            Log::error('From1cController.updateClaim Пользователь не сохранен', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
        }

        if (array_key_exists('card_number', $input) && array_key_exists('secret_word',
                $input) && $input['card_number'] != "0") {
            Card::createCard($input['card_number'], $input['secret_word'], $customer->id);
        }

        if (is_null($passport)) {
            DB::rollback();
            Log::error('From1cController.updateClaim Паспорт не найден', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
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
        //заглушка для телепортовских заявок которые приходят с пустым паспортом, 
        //чтобы оставлять сформированные значения
        if (array_key_exists('series', $input) && empty($input['series'])) {
            $oldPassportSeries = $passport->series;
        }
        if (array_key_exists('number', $input) && empty($input['number'])) {
            $oldPassportNumber = $passport->number;
        }

        $passport->fill($input);
        if (isset($oldPassportSeries)) {
            $passport->series = $oldPassportSeries;
        }
        if (isset($oldPassportNumber)) {
            $passport->number = $oldPassportNumber;
        }
        if (!$passport->save()) {
            DB::rollback();
            Log::error('From1cController.updateClaim Паспорт не сохранен', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
        }
        $about_client = (is_null($claim->about_client_id)) ? (new about_client()) : (about_client::find($claim->about_client_id));
        if (is_null($about_client)) {
            $about_client = new about_client();
        }
        if (is_null($about_client)) {
            DB::rollback();
            Log::error('From1cController.updateClaim Инфа о клиенте не найдена', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
        }
        $spylog->addModelChangeData('about_clients', $about_client, $input);
        $checkboxes = [
            'drugs',
            'alco',
            'stupid',
            'badspeak',
            'pressure',
            'dirty',
            'smell',
            'badbehaviour',
            'soldier',
            'watch',
            'other',
            'pensioner',
            'postclient',
            'armia',
            'poruchitelstvo',
            'zarplatcard'
        ];
        foreach ($checkboxes as $cb) {
            if (!array_key_exists($cb, $input)) {
                $input[$cb] = 0;
            }
        }
        if (is_null($input['adsource']) || is_null(AdSource::find((int)$input['adsource']))) {
            Log::error('From1cController.updateClaim Неверный adsource', ['adsource' => $input['adsource']]);
//            $input['adsource'] = NULL;
            $input['adsource'] = 1;
        }
        if (is_null($input['zhusl']) || is_null(LiveCondition::find((int)$input['zhusl']))) {
            Log::error('From1cController.updateClaim Неверный источник zhusl', ['zhusl' => $input['zhusl']]);
//            $input['zhusl'] = NULL;
            $input['zhusl'] = 1;
        }
        if (is_null($input['obrasovanie']) || is_null(EducationLevel::find((int)$input['obrasovanie']))) {
            Log::error('From1cController.updateClaim Неверный obrasovanie', ['obrasovanie' => $input['obrasovanie']]);
//            $input['obrasovanie'] = NULL;
            $input['obrasovanie'] = 1;
        }
        if (is_null($input['marital_type_id']) || is_null(MaritalType::find((int)$input['marital_type_id']))) {
            Log::error('From1cController.updateClaim Неверный marital_type_id',
                ['marital_type_id' => $input['marital_type_id']]);
//            $input['marital_type_id'] = NULL;
            $input['marital_type_id'] = 1;
        }
        $about_client->fill($input);
        $about_client->customer_id = $customer->id;
        if (!$about_client->save()) {
            DB::rollback();
            Log::error('From1cController.updateClaim Инфа о клиенте не сохранена', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
        }
        $claim->customer_id = $customer->id;
        $claim->passport_id = $passport->id;
        $claim->about_client_id = $about_client->id;
        if (array_key_exists('agrid', $input)) {
            $claim->agrid = $input['agrid'];
        }
        if (array_key_exists('status', $input) && $input['status'] == '2') {
            $claim->status = 2;
        }
        if ($claim->status == Claim::STATUS_ONCHECK && !is_null($claim->subdivision) && $claim->subdivision->name == 'Teleport') {
            if (!array_key_exists('status', $input)) {
                $claim->status = 2;
            } else {
                if (array_key_exists('status', $input) && !in_array($input['status'],
                        [Claim::STATUS_ACCEPTED, Claim::STATUS_DECLINED])) {
                    $claim->status = 2;
                }
            }
        }
        if (!$claim->save()) {
            DB::rollback();
            Log::error('From1cController.updateClaim Заявка не обновлена', $request->all());
            return ['res' => 0, 'msg_err' => $request->all()];
        }

        if (!$spylog->save(Spylog::ACTION_UPDATE, 'claims', $claim->id)) {
//            DB::rollback();
            Log::error('From1cController.updateClaim Логи не сохранились', $request->all());
//            return ['res' => 0, 'msg_err' => 'Не сохранились логи'];
        }
        //есть косяк с тем что из 1с заявка приходит раньше чем ответ о сохранении заявки. 
        //поэтому заявка задваивается и остаются пустые контрагент паспорт и заявка
        //если при сохранении такие находятся, то они удаляются
        if (!is_null($claim->id_teleport)) {
            $teleportEmptyClaim = Claim::where('id_teleport', $claim->id_teleport)->whereNull('id_1c')->where('id', '<',
                $claim->id)->first();
            Log::info('From1cController removing empty teleport claim', ['claim' => $claim]);
            if (!is_null($teleportEmptyClaim)) {
                if (!is_null($teleportEmptyClaim->passport) && $teleportEmptyClaim->passport->address_reg_date == '1800-01-01' && empty($teleportEmptyClaim->issued)) {
                    try {
                        $teleportEmptyClaim->passport->delete();
                    } catch (\Exception $ex) {
                        Log::error('From1cController removing empty teleport passport',
                            ['claim' => $teleportEmptyClaim, 'ex' => $ex]);
                    }
                }
                if (!is_null($teleportEmptyClaim->customer) && is_null($teleportEmptyClaim->customer->id_1c) && $teleportEmptyClaim->customer->id != $claim->customer->id) {
                    try {
                        $teleportEmptyClaim->customer->delete();
                    } catch (\Exception $ex) {
                        Log::error('From1cController removing empty teleport customer',
                            ['claim' => $teleportEmptyClaim, 'ex' => $ex]);
                    }
                }
                try {
                    $teleportEmptyClaim->delete();
                } catch (\Exception $ex) {
                    Log::error('From1cController removing empty teleport claim',
                        ['claim' => $teleportEmptyClaim, 'ex' => $ex]);
                }
            }
        }


        if (isset($passportToRemove) && !is_null($passportToRemove) && $passportToRemove->series == 'TELE') {
            if (\App\Order::where('passport_id', $passportToRemove->id)->count() == 0) {
                try {
                    $passportToRemove->delete();
                } catch (\Exception $ex) {

                }

                if (isset($customerToRemove) && !is_null($customerToRemove)) {
                    try {
                        $customerToRemove->delete();
                    } catch (\Exception $ex) {

                    }
                }
            }
        }

        DB::commit();
        if (isset($claim->id_teleport) && !is_null($claim->id_teleport) && array_key_exists('status_teleport',
                $input)) {
            try {
                TeleportController::sendStatusToTeleport($claim, $input['status_teleport']);
            } catch (\Exception $ex) {
                Log::error('From1cController.updateClaim.ERROR_ON_SEND_TO_TELEPORT', ['claim' => $claim, 'ex' => $ex]);
            }
        }
        Log::info('From1cController.updateClaim Сохранено', $request->all());
        return ['res' => 1];
    }

    /**
     * Добавляет номер телефона специалиста СЭБ
     * @param Request $req
     * @return type
     */
    public function addSEBNumber(Request $req)
    {
        if ($req->has('id') && $req->has('seb_phone')) {
            $claim = Claim::where('id_1c', $req->id)->first();
            if (!is_null($claim)) {
                $claim->seb_phone = StrUtils::parsePhone($req->seb_phone);
                $res = $claim->save();
                if (!$res) {
                    Log::error('From1cController.addSEBNumber: заявка не сохранена', $req->all());
                }
                return ['res' => $res];
            } else {
                Log::error('From1cController.addSEBNumber: заявка не найдена', $req->all());
                return ['res' => 0, 'msg_err' => 'заявка не найдена'];
            }
        } else {
            Log::error('From1cController.addSEBNumber: не все параметры', $req->all());
            return ['res' => 0, 'msg_err' => 'Не все параметры'];
        }
    }

    public function dailyCashReportUpdate(Request $req)
    {
//        Log::info('From1cController.dailyCashReportUpdate', $req->all());
        if (!$req->has('id_1c') || !$req->has('subdivision_id_1c') || !$req->has('user_id_1c') || !$req->has('created_at')) {
            Log::error('From1cController.dailyCashReportUpdate 1', $req->all());
            return 0;
        }
        $report = DailyCashReport::where('id_1c', $req->id_1c)->first();
        if (is_null($report)) {
            $report = new DailyCashReport();
            $report->id_1c = $req->id_1c;
        } else {
            $oldreport = $report->toArray();
        }
        $subdiv = Subdivision::where('name_id', $req->subdivision_id_1c)->first();
        $user = User::where('id_1c', $req->user_id_1c)->first();
        if (is_null($subdiv) || is_null($user)) {
            Log::error('From1cController.dailyCashReportUpdate 2',
                ['req' => $req->all(), 'user' => $user, 'sub' => $subdiv]);
            return 0;
        }
        $report->subdivision_id = $subdiv->id;
        $report->user_id = $user->id;
        $report->created_at = with(new Carbon($req->created_at))->format('Y-m-d H:i:s');

        $dom = new \DOMDocument;
        $dom->loadXML($req->data);
        if (!$dom) {
            return 0;
        }

        $json = [];
        $i = 0;

        $doctypes = [
            'Основной договор' => '0',
            'Доп договор' => '1',
            'Соглашение об урегулировании задолженноти' => '2',
            'Соглашение о приостановке начисления процентов' => '3',
            'Судебное урегулирование задолженности' => '4'
        ];
        $actions = [
            'Приход' => '0',
            'Расход' => '1',
            'ВыдачаЗаймаНаКарту' => '2',
            'ПеремещеноИзОфиса' => '3',
            'ПеремещеноВОфис' => '4',
            'ИнкассацияСТочекБанк' => '5'
        ];
        $items = $dom->getElementsByTagName('item');
        foreach ($items as $item) {
            $json[$i] = [
                'fio' => $item->getAttribute('fio'),
                'action' => ((array_key_exists($item->getAttribute('action'),
                    $actions)) ? $actions[$item->getAttribute('action')] : ''),
                'doctype' => ((array_key_exists($item->getAttribute('doctype'),
                    $doctypes)) ? $doctypes[$item->getAttribute('doctype')] : ''),
                'doc' => $item->getAttribute('doc'),
                'money' => ($item->getAttribute('money') != '' && strpos($item->getAttribute('money'),
                        '.') === false) ? ($item->getAttribute('money') . '.00') : $item->getAttribute('money'),
                'comment' => $item->getAttribute('comment')
            ];
            $i++;
        }
        $report->data = json_encode($json);
        if ($report->save()) {
            if (isset($oldreport)) {
                Spylog::logModelChange(Spylog::TABLE_DAILY_CASH_REPORTS, $oldreport, $report);
            } else {
                Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_DAILY_CASH_REPORTS, $report);
            }
            return 1;
        } else {
            Log::error('From1cController.dailyCashReportUpdate 4',
                ['req' => $req->all(), 'user' => $user, 'sub' => $subdiv, 'xml' => $xml, 'report' => $report]);
            return 0;
        }
    }

    /**
     * отдает хмл с ордерами и доп документами
     * @param Request $req
     * @return type
     */
    public function getOrdersAndDocs(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'date_start' => 'required',
            'passport_series' => 'required',
            'passport_number' => 'required'
        ]);

        if ($validator->fails()) {
            return;
        }
//        $loan = \App\Loan::where('id_1c', $req->loan_id_1c)->first();
        $date_start = Carbon::createFromFormat('dmY', $req->date_start);
        $date_end = ($req->has('date_end')) ? (Carbon::createFromFormat('dmY', $req->date_end)) : Carbon::now();
        $passport = Passport::where('series', $req->passport_series)->where('number', $req->passport_number)->first();
        $where = (!is_null($passport)) ? ('passport_id=' . $passport->id . ' OR ') : '';
        $where .= '(passport_data like "%' . $req->passport_series . '%" AND passport_data like "%' . $req->passport_number . '%")';
        $orders = \App\Order::where('created_at', '>', $date_start->format('Y-m-d'))
            ->where('created_at', '<', $date_end->format('Y-m-d'))
            ->whereRaw($where)
            ->get();
        $xml = new \SimpleXMLElement('<root/>');
        $docsNode = $xml->addChild('docs');
        $ordersNode = $xml->addChild('orders');
        foreach ($orders as $order) {
            $oNode = $ordersNode->addChild('order');
            $oNode->addChild('date', $order->created_at->format('d.m.Y H:i:s'));
            $oNode->addChild('number', $order->number);
            $oNode->addChild('sum', StrUtils::kopToRub($order->money));
            $oNode->addChild('reason', $order->reason);
            $oNode->addChild('type', $order->getMySoapItemID());
            $oNode->addChild('subdivision_id_1c', $order->subdivision->name_id);
            $oNode->addChild('fio', $order->fio);
            $oNode->addChild('user_id_1c', $order->user->id_1c);
            $oNode->addChild('passport_series', $order->user->id_1c);
            $oNode->addChild('passport_number', $order->user->id_1c);
//            $oNode->addChild('loan_id_1c', (!is_null($loan)) ? $loan->id_1c : '');
            $oNode->addChild('customer_id_1c',
                (!is_null($passport) && !is_null($passport->customer)) ? $passport->customer->id_1c : '');
            $oNode->addChild('passport', $order->passport_data);
        }
        if ($req->has('with_docs') && isset($loan) && !is_null($loan)) {
            $reps = \App\Repayment::where('loan_id', $loan->id)->get();
            foreach ($reps as $rep) {
                $str = 'doc';
                if ($rep->repaymentType->isDopnik()) {
                    $str = 'dopnik';
                } else {
                    if ($rep->repaymentType->isPeace()) {
                        $str = 'peace';
                    } else {
                        if ($rep->repaymentType->isClaim()) {
                            $str = 'claim';
                        } else {
                            if ($rep->repaymentType->isSUZ()) {
                                $str = 'suz';
                            } else {
                                if ($rep->repaymentType->isClosing()) {
                                    $str = 'closing';
                                }
                            }
                        }
                    }
                }
                $docNode = $docsNode->addChild($str);
                $docNode->addChild('id_1c', $rep->id_1c);
                $docNode->addChild('date', $rep->created_at->format('d.m.Y H:i:s'));
                $docNode->addChild('user', $rep->user->id_1c);
                $docNode->addChild('subdivision', $rep->subdivision->name_id);
            }
        }
        return response($xml->asXML(), '200', ['Content-Type', 'text/xml']);
    }

    /**
     * Обновляет статус переданной заявки
     * @param Request $req
     */
    public function updateClaimStatus(Request $req)
    {
        if ($req->has('status') && $req->has('claim_id_1c')) {
            $claim = Claim::where('id_1c', $req->get('claim_id_1c'))->first();
            if (!is_null($claim)) {
                $oldClaim = $claim->toArray();
                $claim->status = $req->get('status');
                $claim->save();
                Spylog::logModelChange(Spylog::TABLE_CLAIMS, $oldClaim, $claim->toArray());
                return 1;
            }
        }
        return 0;
    }

    /**
     * Возвращает 500 или 0 в зависимости от того есть ли действующий промокод на переданном кредитнике
     * @param Request $req
     * @return int
     */
    public function getPromocode(Request $req)
    {
        if (!$req->has('loan_id_1c') || !$req->has('customer_id_1c')) {
//            return MySoap::createXML(['result' => 0, 'error' => StrLib::ERR_NO_PARAMS]);
            return 0;
        }
        $loan = \App\Loan::getById1cAndCustomerId1c($req->loan_id_1c, $req->customer_id_1c);
        if (is_null($loan)) {
//            return MySoap::createXML(['result' => 0, 'error' => StrLib::ERR_NULL]);
            return 0;
        } else {
            if (is_null($loan->claim) || is_null($loan->claim->promocode)) {
                return 0;
            }
            return ($loan->canCloseWithPromocode()) ? 500 : 0;
//            return MySoap::createXML([
//                        'result' => 1,
//                        'claim_promocode_number' => (!is_null($loan->claim) && !is_null($loan->claim->promocode)) ? $loan->claim->promocode->number : '0',
//                        'loan_promocode_number' => (!is_null($loan->promocode)) ? $loan->promocode->number : '0',
//            ]);
        }
    }

    /**
     * Загрузить должников из файла в базу
     * @param Request $req
     * @return int
     */
    public function uploadSqlFile(Request $req)
    {
        if (!$req->has('filename')) {
            return 0;
        }
        $path = 'debtors/' . $req->get('filename');
        if (!Storage::disk('ftp')->has($path)) {
            return 0;
        }
//        DB::select(DB::raw('SET FOREIGN_KEY_CHECKS = 0; SET AUTOCOMMIT = 0;'));
//        DB::select(DB::raw('SET FOREIGN_KEY_CHECKS = 0; SET UNIQUE_CHECKS = 0; SET AUTOCOMMIT = 0;'));
        DB::unprepared(Storage::disk('ftp')->get($path));
//        DB::select(DB::raw('SET UNIQUE_CHECKS = 1; SET FOREIGN_KEY_CHECKS = 1; COMMIT;'));
//        DB::select(DB::raw('SET FOREIGN_KEY_CHECKS = 1; COMMIT;'));
//        $debtors = \App\Debtor::getDebtorsWithEmptyCustomer();
//        \App\Utils\HelperUtil::SendPostByCurl('http://192.168.1.115/debtors/customers/upload', ['debtors'=>json_encode($debtors)]);        
        return 1;
    }

    public function uploadCsvFile(Request $req)
    {
        Log::info('From1cController uploadCsv', ['req' => $req->all()]);
        $input = $req->input();
        if (!array_key_exists('filename', $input)) {
            return 0;
        }
        $filenames = explode(',', $input['filename']);
        $uploader = new DebtorsInfoUploader();
        $res = $uploader->uploadByFilenames($filenames);
        Log::info('From1cController uploader res',
            ['res' => $res, 'filenames' => $filenames, 'req' => $req->all(), 'input' => $input]);
        return 1;
    }

    /**
     * Загружает данные по должникам для обновления
     */
    public function updateClientInfo(Request $req)
    {
        if (!$req->has('filename')) {
            return 0;
        }
        $path = 'debtors/' . $req->get('filename');
        if (!Storage::disk('ftp')->has($path)) {
            return 0;
        }

        $input = $req->input();

        return (new DebtorsInfoUploader())->updateClientInfo($input['filename']);

    }

    public function clearOtherPhones()
    {
        return (new DebtorsInfoUploader())->clearOtherPhones();
    }

    public function uploadDebtorsWithEmptyCustomers(Request $req)
    {
        return $this->_uploadDebtorsWithEmptyCustomers(json_decode($req->get('debtors', json_encode([])), true));
        return 1;
    }

    function _uploadDebtorsWithEmptyCustomers($debtors)
    {
        Log::info('DebtorsUpload', ['debtors' => $debtors]);
        $res = [];
        foreach ($debtors as $debtor) {
            if (Passport::where('series', $debtor['passport_series'])->where('number',
                    $debtor['passport_number'])->count() == 0) {
                Customer::getFrom1c($debtor['passport_series'], $debtor['passport_number']);
            }
            $loansNum = \App\Loan::where('loans.id_1c', $debtor['loan_id_1c'])
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
                ->where('customers.id_1c', $debtor['customer_id_1c'])
                ->count();
            if ($loansNum > 0) {
                continue;
            }
//            $synced = \App\Synchronizer::updateLoanRepayments(null, null, $debtor['loan_id_1c'], $debtor['customer_id_1c']);
            $synced = \App\Synchronizer::updateLoanRepayments($debtor['passport_series'], $debtor['passport_number']);
            Log::info('DebtorsUpload', ['debtor' => $debtor, 'synced' => $synced]);
            $res[] = ['debtor' => $debtor, 'synced' => $synced];
        }
        return $res;
    }

    /**
     * Назначает id_1c для мероприятий до выгрузки в 1С
     * @return int
     */
    public function setId1cForEvents()
    {
        $events = DebtorEvent::whereNull('id_1c')->get();
        foreach ($events as $event) {
            $event->id_1c = 'М' . StrUtils::addChars(strval($event->id), 9, '0', false);
            $event->save();
        }
        Log::info('Set id_1c for events', ['ok' => 'ok']);
        return 1;
    }

    public function getStoragePath($ftp = false)
    {
        if ($ftp) {
            return 'ftps://'
                . config('filesystems.disks')['ftp']['username']
                . ':'
                . config('filesystems.disks')['ftp']['password']
                . '@'
                . config('filesystems.disks')['ftp']['host']
                . config('filesystems.disks')['ftp']['root']
                . '/debtors/';
        } else {
            return storage_path() . '/app/debtors/';
        }
    }

    /**
     * Меняет ответственного, в случае, если должник уже в ведении СУЗ
     * refresh_date меняем, чтобы эти должники не ушли в 1С
     */
    public function changeResponsibleUserInDebtor()
    {
        $filename = 'otherRespUserDebtors_' . date('dmY', time()) . '.txt';
        if (!Storage::disk('local')->put('debtors/' . $filename, Storage::disk('ftp')->get('debtors/' . $filename))) {
            Log::info('Не удалось скопировать файл с FTP: changeResponsibleUserInDebtor');
        }

        if (($handle = fopen($this->getStoragePath() . $filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, ";")) !== false) {
                foreach ($data as $debtor_id_1c) {
                    $debtor = Debtor::where('debtor_id_1c', $debtor_id_1c)->first();
                    if (is_null($debtor)) {
                        continue;
                    }

                    $debtor->responsible_user_id_1c = 'СУЗ';
                    $debtor->refresh_date = null;

                    $debtor->save();
                }
            }
        }
    }

    /**
     * Возвращает хмл с неверными смсками без открытого займа
     * @param Request $req
     * @return type
     */
    public function getSmsWithNoLoan(Request $req)
    {
        logger('From1c',
            ['req' => $req->all(), 'start_date' => $req->get('start_date'), 'end_date' => $req->get('end_date')]);
        $startDate = new Carbon($req->get('start_date', Carbon::today()->format('Y-m-d H:i:s')));
        $endDate = new Carbon($req->get('end_date', Carbon::tomorrow()->format('Y-m-d H:i:s')));
        logger('From1c 2', ['sd' => $startDate, 'ed' => $endDate]);
        $smslist = SmsInbox::whereBetween('created_at',
            [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])->get();
//        $smslist = SmsInbox::get();
        $res = [];
        foreach ($smslist as $sms) {
            if ($sms->isValidApproveSms() || $sms->isValidClaimSms()) {
                continue;
            }
            $unclosed_loans = Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
                ->where('customers.telephone', $sms->phone)
                ->where('loans.closed', 0)
                ->count();
            if ($unclosed_loans == 0) {
                $res['item' . count($res)] = [
                    'phone' => $sms->phone,
                    'message' => $sms->message,
                    'received' => $sms->created_at->format('Y-m-d H:i:s')
                ];
            }
        }
        return \App\MySoap::createXML(['items' => $res]);
    }

    public function updateOrder(Request $req)
    {
        Log::info('From1cController.updateOrder', ['req' => $req->all()]);
        $input = $req->input();
        $data = $req;
        try {
            $order = Order::where('number', $req->get('number'))->where('created_at', $req->get('created_at'))->first();
            if (is_null($order)) {
                $order = new Order($input);
            }
            $order->subdivision_id = Subdivision::where('name_id', $data->subdivision_id_1c)->value('id');
            $order->user_id = User::where('id_1c', $data->user_id_1c)->value('id');
            if (is_null($order->user_id)) {
                $order->user_id = Spylog::USERS_ID_1C;
            }
            $order->passport_id = Passport::where('series', $data->passport_series)->where('number',
                $data->passport_number)->value('id');
            if (is_null($order->passport_id)) {
                $customer = Customer::where('id_1c', $data->customer_id_1c)->first();
                if (!is_null($customer)) {
                    $passport = $customer->getLastPassport();
                    if (!is_null($passport)) {
                        $order->passport_id = $passport->id;
                    }
                }
            }
            $loan = Loan::getById1cAndCustomerId1c($data->loan_id_1c, $data->customer_id_1c, true);
            $order->loan_id = (is_null($loan)) ? null : $loan->id;
            if (is_null($order->loan_id) && is_null($order->passport_id)) {
                $syncData = \App\Synchronizer::updateLoanRepayments($data->passport_series, $data->passport_number);
                if (is_array($syncData)) {
                    if (array_key_exists('claim', $syncData)) {
                        $order->passport_id = $syncData['claim']->passport_id;
                    }
                    if (array_key_exists('loan', $syncData)) {
                        $order->loan_id = $syncData['loan']->id;
                    }
                }
            }
            $order->save();
            return '1';
        } catch (Exception $ex) {
            Log::error('From1cController.updateOrder', ['ex' => $ex]);
            return '0';
        }
    }

    public function readAsp(Request $req)
    {
        logger('From1cController', [$req->all()]);
        $customer = Customer::where('id_1c', $req->get('customer_id_1c'))->first();
        if (is_null($customer)) {
            return \App\MySoap::createXML(['result' => 0, 'error' => 'Нет контрагента']);
        }
        return \App\MySoap::createXML([
            'result' => 1,
            'asp_key' => $customer->asp_key,
            'date' => (is_null($customer->asp_approved_at)) ?
                '00010101' :
                with(new Carbon($customer->asp_approved_at))->format('Ymd')
        ]);
    }

    public function addRecordToUploadSqlFilesType1(Request $request)
    {
        if (!$request->has('filename')) {
            return 0;
        }

        $uploadSqlFile = new UploadSqlFile();

        $uploadSqlFile->filetype = 1;
        $uploadSqlFile->filename = $request->get('filename');
        $uploadSqlFile->save();

        return 1;
    }

    public function addRecordToUploadSqlFilesType2(Request $request)
    {
        if (!$request->has('filename')) {
            return 0;
        }
        $uploadSqlFile = new UploadSqlFile();

        $uploadSqlFile->filetype = 2;
        $uploadSqlFile->filename = $request->get('filename');
        $uploadSqlFile->save();

        return 1;
    }
}
