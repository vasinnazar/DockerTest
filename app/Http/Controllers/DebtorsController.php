<?php

namespace App\Http\Controllers;

use App\Debtor;
use App\DebtorEvent;
use App\DebtorGeocode;
use App\DebtorsInfo;
use App\DebtorSmsTpls;
use App\DebtorUsersRef;
use App\Exceptions\DebtorException;
use App\Http\Requests\DebtorCard\MultiSumRequest;
use App\Loan;
use App\Message;
use App\NoticeNumbers;
use App\Order;
use App\Passport;
use App\Permission;
use App\PlannedDeparture;
use App\Repayment;
use App\Services\DebtorCardService;
use App\Services\DebtorEventService;
use App\StrUtils;
use App\User;
use App\Utils;
use App\Utils\HtmlHelper;
use App\Utils\PermLib;
use App\Utils\StrLib;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Image;
use Yajra\Datatables\Facades\Datatables;

class DebtorsController extends BasicController
{

    public $debtCardService;
    public $debtEventService;

    public function __construct(DebtorCardService $debtService,DebtorEventService $eventService)
    {
        $this->middleware('auth');
        if (is_null(Auth::user())) {
            return redirect('auth/login');
        }
        if (!Auth::user()->hasPermission(Permission::makeName(PermLib::ACTION_OPEN, PermLib::SUBJ_DEBTORS))) {
            return redirect('/')->with('msg_err', StrLib::ERR_NOT_ADMIN);
        }

        $this->debtCardService = $debtService;
        $this->debtEventService = $eventService;
    }

    /**
     * Открывает список должников
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_id = Auth::id();

        $objUser = User::find($user_id);
        $isPersonalGroup = $objUser->hasRole('debtors_personal');

        $canEditSmsCount = ($objUser->id == 817) ? true : false; // Яблонцев (редактирование количества SMS)

        $planDeparturesCount = 0;
        if ($isPersonalGroup) {
            $planDeparturesCount = PlannedDeparture::getCountPlanned();
        }
        $slavesUserIds = json_decode(DebtorUsersRef::getDebtorSlaveUsers($user_id), true);
        $slave_list = [];
        foreach ($slavesUserIds as $slave) {
            $slave_list[] = $slave['user_id'];
        }
        $arDebtorUsers = User::whereIn('id', $slave_list)->get();
        $debtorUsers = [];
        foreach ($arDebtorUsers as $debtorUser) {
            $debtorUsers[$debtorUser->id]['name'] = $debtorUser->name;
            $debtorUsers[$debtorUser->id]['group_id'] = $debtorUser->user_group_id;
        }
        $debtorUsers[$user_id]['name'] = $objUser->name;
        $debtorUsers[$user_id]['group_id'] = $objUser->user_group_id;

        \PC::debug($debtorUsers);

        $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
        $usersDebtors = User::select('users.id_1c')
            ->whereIn('id', $arResponsibleUserIds);

        $arUsersDebtors = $usersDebtors->get()->toArray();
        $arIn = [];
        foreach ($arUsersDebtors as $tmpUser) {
            $arIn[] = $tmpUser['id_1c'];
        }

        $isChief = $objUser->hasRole('debtors_chief');
        $dbt = Debtor::whereNotNull('debtors.recommend_created_at');

        if (!$isChief) {
            $dbt->whereIn('debtors.responsible_user_id_1c', $arIn);
            $dbt->where('debtors.recommend_completed', 0);
        }

        $recommends_count = count($dbt->get());
        $debtorsOverall = [];
        if ($user_id == 916 || $user_id == 227) {
            $debtorsOverall = Debtor::getOverall();
        }

        return view('debtors.index', [
            'is_chief' => $objUser->hasRole('debtors_chief'),
            'recommends_count' => $recommends_count,
            'user_id' => $user_id,
            'event_types' => config('debtors.event_types'),
            'overdue_reasons' => config('debtors.overdue_reasons'),
            'event_results' => config('debtors.event_results'),
            'debt_groups' => \App\DebtGroup::getDebtGroups(),
            'debtorsSearchFields' => Debtor::getSearchFields(),
            'debtorEventsSearchFields' => DebtorEvent::getSearchFields(),
            'debtorEventsGroupPlanFields' => DebtorEvent::getGroupPlanFields(),
            'debtorUsers' => $debtorUsers,
            'personalGroup' => [
                'isGroup' => $isPersonalGroup,
                'count' => $planDeparturesCount
            ],
            'canEditSmsCount' => $canEditSmsCount
        ]);
    }

    public function totalNumberPlaned(Request $request)
    {
        $user = User::where('id', $request->userId)->first();
        $debtorsOverall = [];
        if ($user->id == 916 || $user->id == 227) {
            $debtorsOverall = Debtor::getOverall();
        }
        return view('debtors.totalnumberPlaned', [
            'user_id' => $user->id,
            'event_types' => config('debtors.event_types'),
            'debtorsOverall' => $debtorsOverall,
            'total_debtor_events' => DebtorEvent::getPlannedForUser(Auth::user(), Carbon::today()->subDays(15), 30),
        ]);

    }

    public function uploadOldDebtorEvents()
    {
        //$debtorsList = $this->getDebtorsQuery()->lists('debtor_id_1c');
        $debtorsList = Debtor::lists('debtor_id_1c');
        DebtorEvent::uploadFromOldEvents($debtorsList);
        return 1;
    }

    public function calendar()
    {
        return view('debtors.calendar', []);
    }

    /**
     * Открывает карточку должника
     * @param int $debtor_id
     * @return type
     */
    public function debtorcard(int $debtor_id)
    {
        $user_id = Auth::id();

        $objUser = User::find($user_id);
        $debt_roles = [];

        $debtor = Debtor::find($debtor_id);

        // проверяем был ли пропущенный звонок от должника и если был - удаляем запись
        $loss_call = \App\DebtorsLossCalls::where('debtor_id_1c', $debtor->debtor_id_1c)->first();
        if (!is_null($loss_call) && !$objUser->isAdmin()) {
            $loss_call->delete();
        }

        // если у должника есть уведомление "должник на точке" - "удаляем" его (при условии, если открыл не админ)
        if (!$objUser->isAdmin() && ($objUser->hasRole('debtors_remote') || ($objUser->hasRole('debtors_personal') && $objUser->hasRole('debtors_chief')))) {
            $pfx_loan = ($objUser->hasRole('debtors_personal')) ? 'pn' : 'ln';
            $msg_on_subdivision = Message::where('type', $pfx_loan . $debtor->loan_id_1c)->first();
            if (!is_null($msg_on_subdivision)) {
                $msg_on_subdivision->deleted_at = date('Y-m-d H:i:s', time());
                $msg_on_subdivision->save();
            }
        }

        // если у должника есть уведомление "должник на сайте" - "удаляем" его (при условии, если открыл не админ)
        if (!$objUser->isAdmin() && $objUser->hasRole('debtors_remote')) {
            $pfx_loan = 'sn';
            $msg_on_subdivision = Message::where('type', $pfx_loan . $debtor->loan_id_1c)->first();
            if (!is_null($msg_on_subdivision)) {
                $msg_on_subdivision->deleted_at = date('Y-m-d H:i:s', time());
                $msg_on_subdivision->save();
            }
        }

        $customer = \App\Customer::where('id_1c', $debtor->customer_id_1c)->first();
        // получаем данные по клиенту и договору
        $passport = Passport::where('series', $debtor->passport_series)->where('number',
            $debtor->passport_number)->first();

        $debtorcard = Debtor::select(DB::raw('*, loans.created_at as loans_created_at, loantypes.percent as loantype_percent, loantypes.exp_pc as loantype_exp_pc, 
                                                loans.special_percent as loan_special_percent, loantypes.special_pc as loantype_special_pc,
                                                loantypes.fine_pc as loantype_fine_pc, loantypes.id_1c as loantype_id_1c, claims.id as claim_id, 
                                                debtors.od as d_od, debtors.pc as d_pc, debtors.fine as d_fine, debtors.overpayments as d_overpayments, 
                                                debtors.exp_pc as d_exp_pc, loans.money as d_money, loans.id as loan_id, 
                                                loans.id_1c as loan_id_1c, customers.id_1c as c_customer_id_1c, 
                                                customers.telephone as telephone, debtors.debt_group_id as d_debt_group_id,
                                                loans.tranche_number as loan_tranche_number, loans.first_loan_id_1c as loan_first_loan_id_1c, loans.first_loan_date as loan_first_loan_date,
                                                claims.id as r_claim_id, loans.id as r_loan_id, struct_subdivisions.name as str_podr_name, loans.time as time'))
            ->leftJoin('loans', 'loans.id_1c', '=', 'debtors.debtors.loan_id_1c')
            ->leftJoin('customers', 'debtors.debtors.customer_id_1c', '=', 'customers.id_1c')
            ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
            ->leftJoin('passports', function ($join) {
                $join->on('passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('struct_subdivisions', 'debtors.str_podr', '=', 'struct_subdivisions.id_1c')
            ->leftJoin('about_clients', 'claims.about_client_id', '=', 'about_clients.id')
            ->leftJoin('subdivisions', 'claims.subdivision_id', '=', 'subdivisions.id')
            ->leftJoin('loantypes', 'loantypes.id', '=', 'loans.loantype_id')
            ->where('debtors.debtors.id', $debtor_id)
            ->get();

        $data = json_decode(json_encode($debtorcard), true);

        if (count($data) > 1) {
            foreach ($data as $k => $tmp_data) {
                if ($tmp_data['customer_id'] == $customer->id) {
                    $data[0] = $data[$k];
                    break;
                }
            }
        }


        if (count($data) == 0) {
            return $this->backWithErr('Данные по должнику не найдены');
        }

        // получаем данные об ответственном
        $objRespUser = User::where('id_1c', $data[0]['responsible_user_id_1c'])->first();

        $data[0]['not_responsible_user_open'] = false;
        if (!is_null($objRespUser)) {
            $data[0]['responsible_user_fio'] = $objRespUser->login;
            if ($objUser->id != $objRespUser->id) {
                $data[0]['not_responsible_user_open'] = true;
            }
            if ($objRespUser->hasRole('debtors_remote')) {
                $debt_roles['resp_user_remote'] = true;
            }
            if ($objRespUser->hasRole('debtors_personal')) {
                $debt_roles['resp_user_personal'] = true;
            }
        } else {
            $data[0]['responsible_user_fio'] = '<b>Не определен</b>';
            $data[0]['not_responsible_user_open'] = true;
        }

        $today = with(new Carbon())->today();

        $all_debts = Debtor::where('customer_id_1c', $debtor->customer_id_1c)->get();


        $ar_debtor_ids = [];
        foreach ($all_debts as $obj_debt) {
            $ar_debtor_ids[] = $obj_debt->debtor_id_1c;
        }

        // получаем данные по мероприятиям на клиента
        $debtorevents = DebtorEvent::select(DB::raw('*, debtor_events.id as id, debtor_events.created_at as de_created_at'))
            ->leftJoin('users', 'users.id', '=', 'debtor_events.user_id')
            ->leftJoin('customers', 'customers.id_1c', '=', 'debtor_events.customer_id_1c')
            //->where('debtor_id_1c', $debtor->debtor_id_1c)
            ->whereIn('debtor_id_1c', $ar_debtor_ids)
            //->where('customer_id_1c', $debtor->customer_id_1c)
//                ->where('debtor_events.user_id', $user_id)
            ->orderBy('de_created_at', 'desc');
        \PC::debug($debtorevents);
        //->whereBetween('debtor_events.created_at', array($today->setTime(0, 0, 0)->format('Y-m-d H:i:s'), $today->setTime(23, 59, 59)->format('Y-m-d H:i:s')));
        $dataevents = $debtorevents->get()->toArray();

        foreach ($dataevents as $k => $event) {
            $dataevents[$k]['day'] = date('d.m.Y', strtotime($event['de_created_at']));
        }

        $arPurposes = Order::getPurposeNames();

        $arDebtData = config('debtors');
        $whatsAppEvent = true;
        try{
            foreach($all_debts as $debt){
                $this->debtEventService->checkLimitEvent($debt);
            }
        }catch (DebtorException $e){
            $whatsAppEvent = false;
            unset($arDebtData['event_types'][DebtorEvent::SMS_EVENT]);
            unset($arDebtData['event_types'][DebtorEvent::AUTOINFORMER_OMICRON_EVENT]);
            unset($arDebtData['event_types'][DebtorEvent::WHATSAPP_EVENT]);
            unset($arDebtData['event_types'][DebtorEvent::EMAIL_EVENT]);
        }
        // получаем данные об ответственном пользователе
        $debtorRespUser = Debtor::select(DB::raw('*'))
            ->leftJoin('users', 'users.id_1c', '=', 'debtors.responsible_user_id_1c');

        // форматируем данные
//        if (Auth::user()->id == 5) {
        $passport = Passport::where('series', $debtor->passport_series)->where('number',
            $debtor->passport_number)->first();
//            \PC::debug($passport,'passport');
//        } else {
//            $passport = json_decode(json_encode(DB::connection('arm')->table('passports')->where('id', $data[0]['passport_id'])->first()), true);
//        }
        // получаем данные о платежах
//        $debtorpayments = DB::Table('armf.orders')->select(DB::raw('*'))->where('passport_id', $passport->id)->where('loan_id', $data[0]['loan_id'])->whereNotNull('passport_id');
//        $datapayments = $debtorpayments->get();
//        if(Auth::user()->id==5){

        $passport_armf = DB::Table('armf.passports')->select(DB::raw('*'))->where('series',
            $debtor->passport_series)->where('number', $debtor->passport_number)->first();
        if (!is_null($passport_armf)) {
            $loan_id_replica = $this->getLoanIdFromArm($debtor->loan_id_1c, $debtor->customer_id_1c);

            $debtorpayments = DB::Table('armf.orders')
                ->select(DB::raw('*'))
                //->where('passport_id', $passport_armf->id)
                ->whereIn('loan_id', $loan_id_replica)
                ->whereNotNull('passport_id')
                ->orderBy('created_at', 'asc');
            $datapayments = $debtorpayments->get();

            $arTypes = \App\OrderType::lists('name', 'id');

            if (!is_null($loan_id_replica) && !empty($loan_id_replica)) {
                $loan_replica = DB::Table('armf.loans')->select(DB::raw('*'))->where('id', $loan_id_replica)->first();

                foreach ($datapayments as $k => $datapayment) {
                    if ($datapayment->type == 0) {
                        if ($loan_replica->subdivision_id != $datapayment->subdivision_id) {
                            unset($datapayments[$k]);
                        }
                    }
                }
            }

            foreach ($datapayments as $k => $datapayment) {
                if (is_null($datapayment->reason) || empty($datapayment->reason)) {
                    if (isset($arTypes[$datapayment->type])) {
                        $datapayments[$k]->reason = $arTypes[$datapayment->type];
                    }
                }
            }
        } else {
            $datapayments = [];
        }

        //\PC::debug(json_decode($datapayments, true)); die();
//        }
        $arDebtGroups = \App\DebtGroup::getDebtGroups();
        \PC::debug($arDebtGroups);
        $data[0]['loans_created_at_time'] = strtotime($data[0]['loans_created_at']);
        \PC::debug($data[0]['loans_created_at_time']);
        $data[0]['passport_address'] = Passport::getFullAddress($passport);
        $data[0]['real_address'] = Passport::getFullAddress($passport, true);
        $data[0]['fact_address_region'] = $passport->fact_address_region;
        $data[0]['loan_date_start'] = date('d.m.Y', strtotime($data[0]['loans_created_at']));
        $period_type = ($debtor->is_bigmoney || $debtor->is_pledge || $debtor->is_pos) ? 'month' : 'days';
        $data[0]['loan_date_end'] = date('d.m.Y',
            strtotime("+" . $data[0]['time'] . " " . $period_type, strtotime($data[0]['loans_created_at'])));
        \PC::debug($data[0]['loans_created_at']);
        $data[0]['loans_created_at'] = StrUtils::dateToStr($data[0]['loans_created_at']);
        \PC::debug($data[0]['loans_created_at']);
        $data[0]['debt_group_text'] = (array_key_exists($data[0]['d_debt_group_id'],
            $arDebtGroups)) ? $arDebtGroups[$data[0]['d_debt_group_id']] : '';
        $data[0]['sms_available'] = (!is_null($objUser->sms_limit) ? $objUser->sms_limit : 0) - (!is_null($objUser->sms_sent) ? $objUser->sms_sent : 0);

        // определяем группу специалиста удаленного взыскания пользователя в "Должниках" (удаленное и личное)
        $debt_roles['canSend'] = $objUser->canSendSms();
        if ($debt_roles['canSend']) {
            if ($objUser->hasRole('debtors_remote')) {
                $is_ubytki = ($debtor->base == 'Архив убытки' || $debtor->base == 'Архив компании') ? true : false;
                $arSmsRemoteRows = DebtorSmsTpls::getSmsTpls('remote', $is_ubytki);
                foreach ($arSmsRemoteRows as $k => $row) {
                    $spec_phone = (mb_strlen($objUser->phone) < 6) ? '88003014344' : $objUser->phone;
                    $arSmsRemoteRows[$k]['text_tpl'] = str_replace('##spec_phone##', $spec_phone,
                        $arSmsRemoteRows[$k]['text_tpl']);
                    $arSmsRemoteRows[$k]['text_tpl'] = str_replace('##sms_till_date##', date('d.m.Y', time()),
                        $arSmsRemoteRows[$k]['text_tpl']);
                    $arSmsRemoteRows[$k]['text_tpl'] = str_replace('##sms_loan_info##',
                        $data[0]['loan_id_1c'] . ' от ' . $data[0]['loans_created_at'],
                        $arSmsRemoteRows[$k]['text_tpl']);
                }
                $debt_roles['remote'] = $arSmsRemoteRows;
            }
            if ($objUser->hasRole('debtors_personal')) {
                $arSmsPersonalRows = DebtorSmsTpls::getSmsTpls('personal');
                $arDebtorName = explode(' ', $data[0]['fio']);
                foreach ($arSmsPersonalRows as $k => $row) {
                    $spec_phone = (mb_strlen($objUser->phone) < 6) ? '88003014344' : $objUser->phone;
                    $arSmsPersonalRows[$k]['text_tpl'] = str_replace('##spec_phone##', $spec_phone,
                        $arSmsPersonalRows[$k]['text_tpl']);
                    $arSmsPersonalRows[$k]['text_tpl'] = str_replace('##sms_debtor_name##',
                        (isset($arDebtorName[1]) ? $arDebtorName[1] : '') . ' ' . (isset($arDebtorName[2]) ? $arDebtorName[2] : ''),
                        $arSmsPersonalRows[$k]['text_tpl']);
                }
                $debt_roles['personal'] = $arSmsPersonalRows;
            }
        }

        $debt_roles['is_chief'] = ($objUser->hasRole('debtors_chief')) ? true : false;

        if ($objUser->hasRole('debtors_remote')) {
            $debt_roles['remote_notice'] = true;
        }
        if ($objUser->hasRole('debtors_personal')) {
            $debt_roles['personal_notice'] = true;
        }

        $arContractFormsIds = [
            'anketa' => \App\ContractForm::getContractIdByTextId('debtors_anketa'),
            'notice_personal' => \App\ContractForm::getContractIdByTextId('debtors_notice_personal'),
            'requirement_personal' => \App\ContractForm::getContractIdByTextId('debtors_requirement_personal'),
            'requirement_personal_big_money' => \App\ContractForm::getContractIdByTextId('debtors_requirement_personal_big_money'),
            'notice_remote' => \App\ContractForm::getContractIdByTextId('debtors_notice_remote'),
            'notice_remote_big_money' => \App\ContractForm::getContractIdByTextId('debtors_notice_remote_big_money'),
            'notice_remote_cc' => \App\ContractForm::getContractIdByTextId('debtors_notice_remote_cc'),
            'trebovanie_personal_cc' => \App\ContractForm::getContractIdByTextId('trebovanie_personal_cc'),
            'trebovanie_personal_cc_60' => \App\ContractForm::getContractIdByTextId('trebovanie_personal_cc_60'),
        ];

        $recommend_user_name = '';
        if (!is_null($data[0]['recommend_user_id'])) {
            $recUser = User::find($data[0]['recommend_user_id']);
            if (!is_null($recUser)) {
                $recommend_user_name = $recUser->name;
            }
        }


        if ($debtor->decommissioned || $debtor->debt_group_id == 32) {
            $is_orders_loaded = \App\DebtorsLossBase::where('debtor_id_1c', $debtor->debtor_id_1c)->first();
            if (is_null($is_orders_loaded) || $is_orders_loaded->is_loaded == 0) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,
                    config('admin.sales_arm_url') . '/debtors/orders/upload?passport_series=' . $debtor->passport_series . '&passport_number=' . $debtor->passport_number . '&loan_id_1c=' . $debtor->loan_id_1c . '&customer_id_1c=' . $debtor->customer_id_1c);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $answer_curl = curl_exec($ch);
                curl_close($ch);
                \App\DebtorsLossBase::addRecord($debtor->debtor_id_1c);
                sleep(3);
            }
        }

        // определяем соглашение о персональных данных
        $arPdAgreement = [];
        $pdagreement = DB::Table('armf.pdagreements')->select(DB::raw('*'))->where('customer_id_1c',
            $debtor->customer_id_1c)->first();
        if (is_null($pdagreement)) {
            $loan_pd_date_cnt = DB::Table('armf.loans')
                ->leftJoin('armf.claims', 'armf.claims.id', '=', 'armf.loans.claim_id')
                ->leftJoin('armf.subdivisions', 'armf.subdivisions.id', '=', 'armf.loans.subdivision_id')
                ->leftJoin('armf.customers', 'armf.customers.id', '=', 'armf.claims.customer_id')
                ->where('armf.claims.created_at', '>', '2018-01-18')
                ->whereNotIn('armf.subdivisions.name_id', ['Teleport', '000000012', 'ЗаймПоСМС'])
                ->where('armf.subdivisions.is_terminal', '0')
                ->where('armf.customers.id_1c', $debtor->customer_id_1c)
                ->count();

            $bool_pd_agreement = (!$loan_pd_date_cnt) ? false : true;

            \PC::debug($loan_pd_date_cnt);

            if ($loan_pd_date_cnt) {
                $agrClaim = DB::Table('armf.claims')
                    ->leftJoin('armf.loans', 'armf.loans.claim_id', '=', 'armf.claims.id')
                    ->where('armf.loans.id_1c', $debtor->loan_id_1c)
                    ->first();

                if (!is_null($agrClaim)) {
                    $agrSubdivision = DB::Table('armf.subdivisions')->where('id', $agrClaim->subdivision_id)->first();
                    $agrUser = DB::Table('armf.users')->find($agrClaim->user_id);
                    $arPdAgreement['subdivision_name'] = (is_null($agrSubdivision)) ? 'Не найдено' : $agrSubdivision->name;
                    $arPdAgreement['user_name'] = (is_null($agrSubdivision)) ? 'Неизвестно' : $agrUser->name;
                    $arPdAgreement['signed_time'] = date('d.m.Y H:i', strtotime($agrClaim->created_at));
                }
            }
        } else {
            $agrSubdivision = DB::Table('armf.subdivisions')->find($pdagreement->subdivision_id);
            $agrUser = DB::Table('armf.users')->find($pdagreement->user_id);
            $arPdAgreement['subdivision_name'] = (is_null($agrSubdivision)) ? 'Не найдено' : $agrSubdivision->name;
            $arPdAgreement['user_name'] = (is_null($agrUser)) ? 'Неизвестно' : $agrUser->name;
            $arPdAgreement['signed_time'] = date('d.m.Y H:i', strtotime($pdagreement->signed_at));
        }

        // проверяем должника на соглашение о рекструктуризации
        $data[0]['date_restruct_agreement'] = false;
        if (!is_null($debtor->date_restruct_agreement) && $debtor->date_restruct_agreement != '0000-00-00 00:00:00') {
            $data[0]['date_restruct_agreement'] = date('d.m.Y', strtotime($debtor->date_restruct_agreement));
        }

        // получаем данные о выдаче займа налом или на карту из реплики
        $repl_loan = DB::Table('armf.loans')->select(DB::raw('*'))->where('id_1c', $debtor->loan_id_1c)->first();

        $data[0]['repl_in_cash'] = (!is_null($repl_loan)) ? $repl_loan->in_cash : false;

        if ($data[0]['repl_in_cash'] == 0) {
            if ($repl_loan) {
                $card = DB::Table('armf.cards')->where('id', $repl_loan->card_id)->first();
                if ($card) {
                    if ($card->card_type == 0) {
                        $data[0]['card_type_string'] = ' (Золотая корона)';
                    }
                    if ($card->card_type == 1) {
                        $data[0]['card_type_string'] = ' (Тинькофф)';
                    }
                }
            }
        }

        $data[0]['has_tranche'] = false;
        if ($data[0]['repl_in_cash'] == 0 && !is_null($repl_loan) && !is_null($repl_loan->first_loan_id_1c) && mb_strlen($repl_loan->first_loan_id_1c)) {
            $data[0]['has_tranche'] = true;
            $data[0]['loan_tranche_number'] = $repl_loan->tranche_number;
            $data[0]['loan_first_loan_id_1c'] = $repl_loan->first_loan_id_1c;
            $data[0]['loan_first_loan_date'] = $repl_loan->first_loan_date;
        }

        // получаем отправленные уведомления по текущему должнику
        $notices = NoticeNumbers::where('debtor_id_1c', $debtor->debtor_id_1c)->where('str_podr',
            '000000000006')->orderBy('created_at', 'desc')->get();
        if (is_null($notices)) {
            $notices = [];
        }

        // мультидоговора
        /* $customer_open_loans = DB::Table('armf.loans')
          ->select(DB::raw('*'))
          ->leftJoin('armf.claims', 'armf.claims.id', '=', 'armf.loans.claim_id')
          ->leftJoin('armf.customers', 'armf.customers.id', '=', 'armf.claims.customer_id')
          ->where('armf.customers.id_1c', $debtor->customer_id_1c)
          ->where('armf.loans.closed', 0)
          ->get()
          ->toArray(); */

        $multi_loan_debts = Loan::select(DB::raw('*, debtors.debtors.id as debtor_id, debtors.loans.id_1c as mloan_id_1c'))
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.debtors', 'debtors.debtors.loan_id_1c', '=', 'debtors.loans.id_1c')
            ->where('debtors.loans.claim_id', $data[0]['claim_id'])
            //->where('debtors.debtors.is_debtor', 1)
            ->get()
            ->toArray();

        $total_multi_sum = 0;

        foreach ($multi_loan_debts as $k => $mLoan) {
            $replica_loan = DB::Table('armf.loans')->select(DB::raw('*'))->where('id_1c',
                $mLoan['mloan_id_1c'])->first();

            if (!is_null($replica_loan)) {
                if (is_null($replica_loan->first_loan_date)) {
                    $multi_loan_debts[$k]['mloan_date'] = $replica_loan->created_at;
                } else {
                    $multi_loan_debts[$k]['mloan_date'] = date('d.m.Y', strtotime($replica_loan->first_loan_date));
                }
            }

            $tmpLoan = Loan::getById1cAndCustomerId1c($mLoan['mloan_id_1c'], $mLoan['customer_id_1c']);
            //$loan_debt = $tmpLoan->getDebtFrom1cWithoutRepayment(date('Y-m-d', time()));
            //$total_multi_sum += $loan_debt->money;

            \PC::debug($total_multi_sum);
        }

        $total_multi_sum = number_format($total_multi_sum / 100, 2, '.', ' ');

        $loan_percents = DB::Table('armf.loan_rates')->select(DB::raw('*'))
            ->where('start_date', '<=', date('Y-m-d H:i:s', $data[0]['loans_created_at_time']))
            ->orderBy('start_date', 'desc')
            ->limit(1)
            ->first();

        if (!is_null($repl_loan)) {
            $repl_loantype = DB::Table('armf.loantypes')->select(DB::raw('*'))
                ->where('id', $repl_loan->loantype_id)
                ->first();

            $loan_first_percent = $repl_loantype->percent;
        } else {
            $loan_first_percent = 'n/a';
        }

        $current_schedule = false;
        $create_schedule = false;
        $data_pledge = false;
        $data_pos = false;
        if ($debtor->is_pos || $debtor->is_bigmoney) {
            if ($repl_loan) {
                $schedule_row = DB::Table('armf.pos_loans')->where('loan_id', $repl_loan->id)->orderBy('created_at',
                    'desc')->first();
                if (!is_null($schedule_row->pays)) {
                    $current_schedule = json_decode($schedule_row->pays, true);
                }
                if (!is_null($schedule_row->create_pays)) {
                    $create_schedule = json_decode($schedule_row->create_pays, true);
                }

                if ($debtor->is_pos) {
                    $pos_claim = DB::Table('armf.pos_claims')->where('claim_id', $repl_loan->claim_id)->first();
                    if ($pos_claim) {
                        $claim_goods = DB::Table('armf.pos_claim_pos_nomenclature')->where('pos_claim_id',
                            $pos_claim->id)->get();
                        if ($claim_goods) {
                            foreach ($claim_goods as $claim_good) {
                                $good = DB::Table('armf.pos_nomenclatures')->where('id',
                                    $claim_good->pos_nomenclature_id)->first();
                                if ($good) {
                                    $data_pos[] = [
                                        'good_name' => $good->name,
                                        'good_price' => number_format($claim_good->price / 100, 2, '.', ''),
                                        'good_qty' => $claim_good->amount
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($debtor->is_pledge) {
            $schedule_first = DB::Table('armf.pledge_loans')->where('loan_id', $repl_loan->id)->orderBy('created_at',
                'asc')->first();
            $schedule_last = DB::Table('armf.pledge_loans')->where('loan_id', $repl_loan->id)->orderBy('created_at',
                'desc')->first();

            $current_schedule = json_decode($schedule_last->pays, true);

            if ($schedule_first !== $schedule_last) {
                $create_schedule = json_decode($schedule_first->pays, true);
            }

            $claim_pledge = DB::Table('armf.claims_pledge')->where('claim_id', $repl_loan->claim_id)->first();
            if ($claim_pledge) {
                $pledge_params = self::pledgeFormParams();
                if ($claim_pledge->type_pledge == 1) {
                    $data_pledge .= 'Залог: автомобиль<br>';
                    $data_pledge .= 'Марка: ';
                    $data_pledge .= (isset($pledge_params['car_brand'][$claim_pledge->car_brand])) ? $pledge_params['car_brand'][$claim_pledge->car_brand] . '<br>' : 'н/д<br>';
                    $data_pledge .= 'Модель: ' . $claim_pledge->car_model;
                }

                if ($claim_pledge->type_pledge == 2) {
                    $data_pledge .= 'Залог: недвижимость<br>';
                    $data_pledge .= 'Тип: ' . (isset($pledge_params['realty_type'][$claim_pledge->realty_type])) ? $pledge_params['realty_type'][$claim_pledge->realty_type] . '<br>' : 'н/д<br>';

                    $pledge_address = [];
                    if (!empty($claim_pledge->realty_address_zip)) {
                        $pledge_address[] = $claim_pledge->realty_address_zip;
                    }
                    if (!empty($claim_pledge->realty_address_region)) {
                        $pledge_address[] = $claim_pledge->realty_address_region;
                    }
                    if (!empty($claim_pledge->realty_address_district)) {
                        $pledge_address[] = $claim_pledge->realty_address_district;
                    }
                    if (!empty($claim_pledge->realty_address_city)) {
                        $pledge_address[] = $claim_pledge->realty_address_city;
                    }
                    if (!empty($claim_pledge->realty_address_locality)) {
                        $pledge_address[] = $claim_pledge->realty_address_locality;
                    }
                    if (!empty($claim_pledge->realty_address_street)) {
                        $pledge_address[] = $claim_pledge->realty_address_street;
                    }
                    if (!empty($claim_pledge->realty_address_house)) {
                        $pledge_address[] = $claim_pledge->realty_address_house;
                    }
                    if (!empty($claim_pledge->realty_address_building)) {
                        $pledge_address[] = $claim_pledge->realty_address_building;
                    }
                    if (!empty($claim_pledge->realty_address_apartment)) {
                        $pledge_address[] = $claim_pledge->realty_address_apartment;
                    }

                    $data_pledge .= 'Адрес: ' . implode(', ', $pledge_address);
                }
            }
        }

        $arInsurancesData = [];
        if (!is_null($repl_loan)) {
            $insurances = DB::Table('armf.insurance_claim_data')->where('claim_id',
                $repl_loan->claim_id)->whereNotNull('date_signed')->get();
            if (!is_null($insurances)) {
                foreach ($insurances as $k => $insurance) {
                    $arInsurancesData[$k]['policy_number'] = $insurance->policy_number;
                    $arInsurancesData[$k]['time'] = $insurance->time;
                    $arInsurancesData[$k]['money'] = $insurance->money;

                    $insurance_type = DB::Table('armf.insurance_types')->where('id',
                        $insurance->insurance_type_id)->first();

                    $arInsurancesData[$k]['insurance_name_company'] = '';
                    $arInsurancesData[$k]['name'] = '';

                    if (!is_null($insurance_type)) {
                        $arInsurancesData[$k]['insurance_name_company'] = $insurance_type->insurance_name_company;
                        $arInsurancesData[$k]['name'] = $insurance_type->name;
                    }
                }
            }

            $repl_subdivision_loan = DB::Table('armf.subdivisions')->where('id', $repl_loan->subdivision_id)->first();
            if (!is_null($repl_subdivision_loan)) {
                $data[0]['loan_subdivision_address'] = $repl_subdivision_loan->address;
            }
        }

        $repl_subdivision = DB::Table('armf.subdivisions')->where('address', $data[0]['address'])->first();
        if ($repl_subdivision) {
            $data[0]['address'] = ($repl_subdivision->is_lead == 1) ? 'Онлайн' : $data[0]['address'];
        }

        if (mb_strlen($data[0]['telephone']) == 11) {
            $smsSentJson = file_get_contents('http://192.168.35.51/api/messages/search?phone=' . $data[0]['telephone']);
            $arSmsSent = array_reverse(json_decode($smsSentJson, true));
        } else {
            $arSmsSent = [];
        }

        $hasSentNoticePersonal = false;
        if ($debtor->str_podr == '000000000007') {
            $debtors_personal = Debtor::where('customer_id_1c', $debtor->customer_id_1c)
                ->where('str_podr', '000000000007')
                ->where('is_debtor', 1)
                ->get();

            if (!is_null($debtors_personal)) {

                $arDebtorsPersonalIds = [];
                foreach ($debtors_personal as $debtor_personal) {
                    $arDebtorsPersonalIds[] = $debtor_personal->debtor_id_1c;
                }

                if (count($arDebtorsPersonalIds) > 0) {
                    $noticeToday = NoticeNumbers::whereIn('debtor_id_1c', $arDebtorsPersonalIds)
                        ->where('str_podr', '000000000007')
                        ->get();

                    if (count($noticeToday) > 0) {
                        $hasSentNoticePersonal = true;
                    }
                }
            }
        }

        /* $customer_changes = DB::Table('armf.customer_change_logs')
          ->leftJoin('armf.users', 'armf.customer_change_logs.user_id', '=', 'armf.users')
          ->where('customer_id_1c', $debtor->customer_id_1c)
          ->get(); */

        $credit_vacation_data = DB::Table('armf.credit_vacation')
            ->where('customer_id_1c', $debtor->customer_id_1c)
            ->where('loan_id_1c', $debtor->loan_id_1c)
            ->where('answer', 1)
            ->first();

        if (is_null($credit_vacation_data)) {
            $credit_vacation_data = false;
        }

        $third_people_agreement = DB::Table('armf.third_people_agreements')
            ->where('customer_id_1c', $debtor->customer_id_1c)
            ->where('loan_id_1c', $debtor->loan_id_1c)
            ->whereNotNull('confirmed')
            ->orderBy('id', 'desc')
            ->first();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,
            config('admin.sales_arm_url') . '/api/repayments/offers/status?loan_id_1c=' . $debtor->loan_id_1c);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resultPeace = curl_exec($ch);
        curl_close($ch);

        $dataHasPeaceClaim = json_decode($resultPeace, true);

        if (auth()->user()->hasRole('debtors_remote')) {
            $userStrPodr = '000000000006';
        } else {
            if (auth()->user()->hasRole('debtors_personal')) {
                $userStrPodr = '000000000007';
            } else {
                $userStrPodr = null;
            }
        }

        $sentRecurrentQueryToday = \App\DebtorRecurrentQuery::where('debtor_id', $debtor->id)
            ->where('created_at', '>=', date('Y-m-d', time()) . ' 00:00:00')
            ->where('created_at', '<=', date('Y-m-d', time()) . ' 23:59:59')
            ->first();

        $hasCardForRecurrentQuery = (isset($card) && $card->card_type == 1) ? true : false;
        $hasSentRecurrentQueryToday = ($sentRecurrentQueryToday) ? true : false;

        $recurrent_task = \App\MassRecurrentTask::where('created_at', '>=', date('Y-m-d 00:00:00', time()))
            ->where('created_at', '<=', date('Y-m-d 23:59:59', time()))
            ->where('str_podr', $userStrPodr)
            ->first();

        $enableRecurrentButton = ($hasCardForRecurrentQuery && !$hasSentRecurrentQueryToday && !$recurrent_task && $debtor->str_podr == $userStrPodr) ? true : false;

        if ($hasCardForRecurrentQuery && !$hasSentRecurrentQueryToday && in_array($debtor->debt_group_id, [1, 2, 3])) {
            $enableRecurrentButton = true;
        }

        $blockProlongation = \App\DebtorBlockProlongation::where('debtor_id', $debtor->id)->orderBy('id', 'desc')
            ->where('block_till_date', '>=', date('Y-m-d', time()) . ' 00:00:00')
            ->first();

        $armf_customer = DB::Table('armf.customers')->where('id_1c', $debtor->customer_id_1c)->first();
        if (!is_null($armf_customer)) {
            $armf_about = DB::Table('armf.about_clients')->where('customer_id', $armf_customer->id)->first();
            if (!is_null($armf_about)) {
                if (!is_null($armf_about->email)) {
                    $data[0]['email'] = $armf_about->email;
                }
            }
        }

        $arDataCcCard = false;

        if (str_contains($debtor->loan_id_1c, 'ККЗ')) {
            $json_string_cc = file_get_contents('http://192.168.35.54:8020/api/v1/loans/' . $debtor->loan_id_1c . '/schedule/' . date('d.m.Y',
                    time()));
            $arDataCcCard = json_decode($json_string_cc, true);
        }

        return view('debtors.debtorcard', [
            'debtor_id' => $debtor_id,
            'current_user_id' => $user_id,
            'data' => $data,
            'dataevents' => $dataevents,
            'datapayments' => $datapayments,
            'purposes' => $arPurposes,
            'debtdata' => $arDebtData,
            'debtroles' => $debt_roles,
            'debtor' => Debtor::find($debtor_id),
            'contractforms' => $arContractFormsIds,
            'isPlannedDeparture' => PlannedDeparture::isPlanned($debtor_id),
            'arPdAgreement' => $arPdAgreement,
            'recommend_user_name' => $recommend_user_name,
            'notices' => $notices,
            'regions_timezone' => config('debtors.regions_timezone'),
            'multi_loans' => $multi_loan_debts,
            'total_multi_sum' => $total_multi_sum,
            'loan_percents' => $loan_percents,
            'loan_first_percent' => $loan_first_percent,
            'create_schedule' => $create_schedule,
            'current_schedule' => $current_schedule,
            'data_pledge' => $data_pledge,
            'data_pos' => $data_pos,
            'insurances_data' => $arInsurancesData,
            'arSmsSent' => $arSmsSent,
            'hasSentNoticePersonal' => $hasSentNoticePersonal,
            'credit_vacation_data' => $credit_vacation_data,
            'third_people_agreement' => $third_people_agreement,
            'dataHasPeaceClaim' => $dataHasPeaceClaim,
            'enableRecurrentButton' => $enableRecurrentButton,
            'blockProlongation' => $blockProlongation,
            'arDataCcCard' => $arDataCcCard,
            'whatsApp' => $whatsAppEvent,
        ]);
    }

    function getLoanIdFromArm($loan_id_1c, $customer_id_1c)
    {
        return DB::connection('arm')->table('loans')->select('loans.id')->where('loans.id_1c',
            $loan_id_1c)->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')->leftJoin('customers', 'customers.id',
            '=', 'claims.customer_id')->where('customers.id_1c', $customer_id_1c)->lists('id');
    }

    /**
     * Добавляет мероприятие в БД и возвращает на исходную страницу
     * @param Request $req
     * @return type
     */
    public function addevent(Request $req)
    {
        $savePlanned = false;
        $saveProlongationBlock = false;
        $data = $req->input();

        if (isset($data['created_at']) && mb_strlen($data['created_at'])) {
            $today_date = date('Y-m-d', time());
            $created_at_date = date('Y-m-d', strtotime($data['created_at']));

            if ($today_date != $created_at_date) {
                return redirect()->back();
            }
        }

        $data['date'] = (mb_strlen($data['date']) > 0) ? date('Y-m-d H:i:s',
            strtotime($data['date'])) : '0000-00-00 00:00:00';

        $debtor = Debtor::find($data['debtor_id']);

        if (isset($data['search_field_users@id']) && $data['search_field_users@id'] != '') {
            $user_chief_from = User::find($data['search_field_users@id']);
        }

        if (isset($data['event_type_id_plan']) && mb_strlen($data['event_type_id_plan'])) {
            $datePlanned = $data['date'];
            $data['date'] = '0000-00-00 00:00:00';
            $savePlanned = true;
        }

        if (isset($data['dateProlongationBlock']) && mb_strlen($data['dateProlongationBlock'])) {
            $data['dateProlongationBlock'] = (mb_strlen($data['dateProlongationBlock']) > 0) ? date('Y-m-d H:i:s',
                strtotime($data['dateProlongationBlock'])) : null;
            $saveProlongationBlock = true;
        }

        $is_archive = false;
        if ($debtor->base == 'Архив убытки' || $debtor->base == 'Архив компании') {
            $is_archive = true;
            $data['debt_group_id'] = $debtor->debt_group_id;
        }

        if (mb_strlen($data['created_at']) && mb_strlen($data['event_type_id']) && (mb_strlen($data['debt_group_id']) || $is_archive) && mb_strlen($data['event_result_id'])) {
            $debtorEvent = new DebtorEvent();
            $data['created_at'] = date('Y-m-d H:i:s', strtotime($data['created_at']));
            $data['completed'] = 1;
            $debtorEvent->fill($data);
            $debtorEvent->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
            if (!is_null($debtor)) {
                $debtorEvent->debtor_id_1c = $debtor->debtor_id_1c;
                if ($debtor->debt_group_id != $data['debt_group_id'] && !$is_archive) {
                    Debtor::where('customer_id_1c', $debtor->customer_id_1c)
                        ->where('is_debtor', 1)
                        ->update([
                            'debt_group_id' => $data['debt_group_id'],
                            'refresh_date' => Carbon::now()->format('Y-m-d H:i:s')
                        ]);
                    $debtor->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
                }
                if ($debtor->decommissioned == 0 && !$is_archive) {
                    $debtor->debt_group_id = $data['debt_group_id'];
                }
                $debtor->save();
            }

            if (isset($data['search_field_users@id']) && $data['search_field_users@id'] != '') {
                $debtorEvent->user_id_1c = $user_chief_from->id_1c;
                $debtorEvent->user_id = $user_chief_from->id;
            } else {
                $debtorEvent->user_id_1c = Auth::user()->id_1c;
            }

            $debtorEvent->save();

            if ($req->hasFile('messenger_photo')) {
                $passport = Passport::where('series', $debtor->passport_series)
                    ->where('number', $debtor->passport_number)
                    ->first();

                $customer_replica = DB::Table('armf.customers')->where('id_1c', $debtor->customer_id_1c)->first();

                $arPath[] = substr($debtor->passport_series, 0, 2);
                $arPath[] = substr($debtor->passport_series, 2, 2);

                $arPath[] = substr($debtor->passport_number, 0, 2);
                $arPath[] = substr($debtor->passport_number, 2, 2);
                $arPath[] = substr($debtor->passport_number, 4, 2);

                $path = implode('/', $arPath);

                $path .= '/debts/' . date('Y-m-d',
                        time()) . '/' . $debtorEvent->id . '.' . $req->messenger_photo->getClientOriginalExtension();

                $cFile = curl_file_create($_FILES['messenger_photo']['tmp_name']);

                $postdata = [
                    'name' => $debtorEvent->id . '.' . $req->messenger_photo->getClientOriginalExtension(),
                    'filepath' => $path,
                    'type_id' => 7,
                    'user_id' => 125,
                    'status_id' => 4,
                    'customer_name' => ($passport) ? $passport->fio : null,
                    'customer_id' => ($customer_replica) ? $customer_replica->id : null,
                    'file' => $cFile
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://192.168.35.13/api/photos');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: multipart/form-data',
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                $result = curl_exec($ch);
                Log::error('addeventimage error', [$postdata, $result, curl_error($ch)]);
                curl_close($ch);
            }

            $debtor_unclosed = Debtor::where('customer_id_1c', $debtor->customer_id_1c)->where('is_debtor', 1)->get();
            foreach ($debtor_unclosed as $unclosed) {
                if ($unclosed->base == 'Архив убытки' || $unclosed->base == 'Архив компании') {
                    continue;
                }

                if ($debtor->id == $unclosed->id) {
                    continue;
                }

                if (isset($data['debt_group_id']) && mb_strlen($data['debt_group_id'])) {
                    if ($unclosed->debt_group_id != $data['debt_group_id']) {
                        $unclosed->debt_group_id = $data['debt_group_id'];
                        $unclosed->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
                        $unclosed->save();
                    }
                }
            }
        }

        if ($savePlanned) {
            $planEvent = new DebtorEvent();
            $planEvent->date = $datePlanned;
            $planEvent->event_type_id = $data['event_type_id_plan'];

            if (isset($data['search_field_users@id']) && $data['search_field_users@id'] != '') {
                $planEvent->user_id_1c = $user_chief_from->id_1c;
                $planEvent->user_id = $user_chief_from->id;
            } else {
                $planEvent->user_id = $data['user_id'];
                $planEvent->user_id_1c = Auth::user()->id_1c;
            }

            $planEvent->debtor_id = $debtor->id;
            $planEvent->debtor_id_1c = $debtor->debtor_id_1c;
            $planEvent->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
//            $planEvent->id_1c = DebtorEvent::getNextNumber();
            $planEvent->completed = 0;
            $planEvent->save();

            //$planEvent->id_1c = 'М' . StrUtils::addChars(strval($planEvent->id), 9, '0', false);
            //$planEvent->save();
        }

        if ($saveProlongationBlock) {
            $pbEvent = new DebtorEvent();
            $pbEvent->event_type_id = 9;

            if (isset($data['search_field_users@id']) && $data['search_field_users@id'] != '') {
                $pbEvent->user_id_1c = $user_chief_from->id_1c;
                $pbEvent->user_id = $user_chief_from->id;
            } else {
                $pbEvent->user_id = $data['user_id'];
                $pbEvent->user_id_1c = Auth::user()->id_1c;
            }

            $pbEvent->customer_id_1c = $debtor->customer_id_1c;
            $pbEvent->loan_id_1c = $debtor->loan_id_1c;
            $pbEvent->debt_group_id = $debtor->debt_group_id;
            $pbEvent->event_result_id = 17;
            $pbEvent->report = 'Достигнута договоренность о закрытии кредитного договора ' . date('d.m.Y',
                    strtotime($data['dateProlongationBlock'])) . ' г.';
            $pbEvent->debtor_id = $debtor->id;
            $pbEvent->debtor_id_1c = $debtor->debtor_id_1c;
            $pbEvent->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
            $pbEvent->completed = 1;

            $pbEvent->save();

            $dbp = new \App\DebtorBlockProlongation();

            $dbp->debtor_id = $debtor->id;
            $dbp->loan_id_1c = $debtor->loan_id_1c;
            $dbp->block_till_date = $data['dateProlongationBlock'];

            $dbp->save();

            $jsonPeaceClaims = file_get_contents('http://192.168.35.89/api/repayments/offers?loan_id_1c=' . $debtor->loan_id_1c);
            $arPeaceClaims = json_decode($jsonPeaceClaims, true);

            $nowTime = time();

            foreach ($arPeaceClaims as $peaceClaim) {
                if ($nowTime < strtotime($peaceClaim['end_at'])) {
                    $postData = [
                        'freeze_start_at' => date('Y-m-d', time()),
                        'freeze_end_at' => date('Y-m-d', strtotime('+1 day', strtotime($data['dateProlongationBlock'])))
                    ];

                    $ch = curl_init('http://192.168.35.89/api/repayments/offers/' . $peaceClaim['id']);

                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

                    $offer = curl_exec($ch);

                    curl_close($ch);
                }
            }
        }
        return redirect()->back();
    }

    /**
     * Поставить мероприятию статус выполнено и обновить ему дату обновления
     * @param Request $req
     * @return int
     */
    public function eventComplete(Request $req)
    {
        $debtorEvent = DebtorEvent::find($req->get('eventDone'));
        if ($debtorEvent) {
            if ($debtorEvent->completed == 1) {
                $debtorEvent->completed = 0;
            } else {
                $debtorEvent->completed = 1;
            }
        } else {
            return 0;
        }
        $debtorEvent->refresh_date = Carbon::now()->format('Y-m-d H:i:s');

        $debtorEvent->save();

        return 1;
    }

    /**
     * возвращает список колонок по должникам
     * @return type
     */
    function getDebtorsTableColumns($forPersonalDepartment = false)
    {
        if ($forPersonalDepartment) {
            return [
                'debtors.fixation_date' => 'debtors_fixation_date',
                'debtors.passports.fio' => 'passports_fio',
                'debtors.debtors.customer_id_1c' => 'debtor_customer_id_1c',
                'debtors.loan_id_1c' => 'debtors_loan_id_1c',
                'debtors.qty_delays' => 'debtors_qty_delays',
                'debtors.sum_indebt' => 'debtors_sum_indebt',
                'debtors.od' => 'debtors_od',
                'debtors.base' => 'debtors_base',
                'debtors.customers.telephone' => 'customers_telephone',
                'debtors.debt_groups.name' => 'debtors_debt_group_id',
                'debtors.id' => 'debtors_id',
                'debtors.users.name' => 'debtors_username',
                'debtors.debtor_id_1c' => 'debtor_id_1c',
                'debtors.struct_subdivisions.name' => 'debtor_str_podr',
                'debtors.passports.id' => 'passport_id',
                'debtors.debt_group_id' => 'debtors_debt_group',
                'debtors.responsible_user_id_1c' => 'debtors_responsible_user_id_1c',
                'debtors.is_bigmoney' => 'debtor_is_bigmoney',
                'debtors.is_pledge' => 'debtor_is_pledge',
                'debtors.is_pos' => 'debtor_is_pos',
                'debtors.subdivisions.is_lead' => 'debtor_is_online',
                'debtors.debtors.od_after_closing' => 'debtors_od_after_closing',
                'debtors.passports.fact_timezone' => 'passports_fact_timezone'
            ];
        }
        return [
            'debtors.fixation_date' => 'debtors_fixation_date',
            'debtors.passports.fio' => 'passports_fio',
            'debtors.debtors.customer_id_1c' => 'debtor_customer_id_1c',
            'debtors.loan_id_1c' => 'debtors_loan_id_1c',
            'debtors.qty_delays' => 'debtors_qty_delays',
            'debtors.sum_indebt' => 'debtors_sum_indebt',
            'debtors.od' => 'debtors_od',
            'debtors.base' => 'debtors_base',
            'debtors.customers.telephone' => 'customers_telephone',
            'debtors.debt_groups.name' => 'debtors_debt_group_id',
            'debtors.id' => 'debtors_id',
            'debtors.users.name' => 'debtors_username',
            'debtors.debtor_id_1c' => 'debtor_id_1c',
            'debtors.struct_subdivisions.name' => 'debtor_str_podr',
            'debtors.uploaded' => 'uploaded',
            'debtors.debt_group_id' => 'debtors_debt_group',
            'debtors.responsible_user_id_1c' => 'debtors_responsible_user_id_1c',
            'debtors.is_bigmoney' => 'debtor_is_bigmoney',
            'debtors.is_pledge' => 'debtor_is_pledge',
            'debtors.is_pos' => 'debtor_is_pos',
            'debtors.subdivisions.is_lead' => 'debtor_is_online',
            'debtors.debtors.od_after_closing' => 'debtors_od_after_closing',
            'debtors.passports.fact_timezone' => 'passports_fact_timezone'
        ];
    }

    function getDebtorsQuery($req, $forPersonalDepartment = false)
    {
        $cols = [];
        $tCols = $this->getDebtorsTableColumns($forPersonalDepartment);
        foreach ($tCols as $k => $v) {
            $cols[] = $k . ' as ' . $v;
        }
        $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
        $usersDebtors = User::select('users.id_1c')
            ->whereIn('id', $arResponsibleUserIds);

        $arUsersDebtors = $usersDebtors->get()->toArray();
        $arIn = [];
        foreach ($arUsersDebtors as $tmpUser) {
            $arIn[] = $tmpUser['id_1c'];
        }

        $input = $req->input();

        $currentUser = User::find(Auth::id());

        $debtor_vars = config('debtors');

        $by_address = ($currentUser->hasRole('debtors_personal')) ? 'address_city' : 'fact_address_city';

        $filterFields = [
            'search_field_debtors@fixation_date',
            'search_field_passports@id',
            'search_field_debtors@loan_id_1c',
            'search_field_debtors@qty_delays_from',
            'search_field_debtors@qty_delays_to',
            'search_field_debtors@sum_indebt',
            'search_field_debtors@base',
            'search_field_debtors@od',
            'search_field_customers@telephone',
            'search_field_other_phones@phone',
            'search_field_debtors@debt_group',
            'search_field_passports@' . $by_address,
            'search_field_users@id_1c',
            'search_field_passports@series',
            'search_field_passports@number',
            //'search_field_planned_departures@debtor_id',
            'search_field_struct_subdivisions@id_1c',
            'search_field_debt_groups@id',
        ];

        $boolSearchAll = false;
        $arrFields = [];
        foreach ($filterFields as $fieldName) {
            $tmpVarValue = $req->get($fieldName);
            if (!is_null($tmpVarValue) && !empty($tmpVarValue)) {
                $arrFields[$fieldName]['value'] = $tmpVarValue;
                $arrFields[$fieldName]['condition'] = (empty($req->get($fieldName . '_condition'))) ? '=' : $req->get($fieldName . '_condition');
                if (!is_null($arrFields[$fieldName]['value']) && mb_strlen($arrFields[$fieldName]['value'])) {
                    $boolSearchAll = true;
                }
            }
        }

//        if(Auth::user()->id==5){
        $debtors = Debtor::select($cols)
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.subdivisions', 'debtors.subdivisions.id', '=', 'debtors.loans.subdivision_id')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.customers', 'debtors.customers.id', '=', 'debtors.claims.customer_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('debtors.users', 'debtors.users.id_1c', '=', 'debtors.debtors.responsible_user_id_1c')
            ->leftJoin('debtors.struct_subdivisions', 'debtors.struct_subdivisions.id_1c', '=',
                'debtors.debtors.str_podr')
            ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debtors.debt_group_id')
            ->groupBy('debtors.id');

        if (isset($input['search_field_passports@fact_address_region']) && mb_strlen($input['search_field_passports@fact_address_region'])) {
            $debtors->where('debtors.passports.fact_address_region', 'like',
                '%' . $input['search_field_passports@fact_address_region'] . '%');
        }

        if (isset($input['search_field_passports@address_region']) && mb_strlen($input['search_field_passports@address_region'])) {
            $debtors->where('debtors.passports.address_region', 'like',
                '%' . $input['search_field_passports@address_region'] . '%');
        }


        if ($boolSearchAll) {
            foreach ($arrFields as $key => $arrField) {
                if ($key == 'search_field_planned_departures@debtor_id') {
                    $authUser = User::find(Auth::id());

                    $debtors->leftJoin('debtors.planned_departures', 'debtors.planned_departures.debtor_id', '=',
                        'debtors.debtors.id');
                    $debtors->whereNotNull('debtors.planned_departures.debtor_id');
                    //$debtors->where('debtors.responsible_user_id_1c', $authUser->id_1c);
                    $debtors->whereIn('debtors.responsible_user_id_1c', $arIn);
                    continue;
                }
                if ($key == 'search_field_debtors@qty_delays_from') {
                    $debtors->where('debtors.debtors.qty_delays', '>=', $arrField['value']);
                    continue;
                }
                if ($key == 'search_field_debtors@qty_delays_to') {
                    $debtors->where('debtors.debtors.qty_delays', '<=', $arrField['value']);
                    continue;
                }
                if ($key == 'search_field_debt_groups@id') {
                    $debtors->where('debtors.debtors.debt_group_id', $arrField['value']);
                    continue;
                }
                if ($key == 'search_field_debtors@fixation_date') {
                    if ($arrField['condition'] == '=') {
                        $sDate = new Carbon($arrField['value']);
                        $debtors->whereBetween('debtors.debtors.fixation_date', array(
                            $sDate->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                            $sDate->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                        ));
                        continue;
                    }
                }
                if ($key == 'search_field_customers@telephone') {
                    if (isset($arrFields['search_field_customers@telephone'])) {
                        //$debtors->leftJoin('debtors.debtors_other_phones', 'debtors.debtors_other_phones.debtor_id_1c', '=', 'debtors.debtors.debtor_id_1c');
//                        $debtors->leftJoin(DB::raw('(SELECT debtors.debtors_other_phones.debtor_id_1c as dop_di1c, debtors.debtors_other_phones.phone as phone FROM debtors.debtors_other_phones WHERE phone = \'' . $arrField['value'] . '\') as dop'), function($join) use ($arrField) {
//                            $join->on('dop.dop_di1c', '=', 'debtors.debtors.debtor_id_1c');
//                        });
                        $debtors->where('debtors.customers.telephone', $arrField['condition'], $arrField['value']);
                    }
                    continue;
                }
                if ($key == 'search_field_other_phones@phone' && isset($arrFields['search_field_other_phones@phone'])) {
                    $debtors->leftJoin('debtors.debtors_other_phones', 'debtors.debtors_other_phones.debtor_id_1c', '=',
                        'debtors.debtors.debtor_id_1c');

                    if ($arrField['condition'] == 'like') {
                        $arrField['value'] = '%' . $arrField['value'] . '%';
                    } else {
                        $arrField['condition'] = '=';
                    }
                    $debtors->where('debtors.debtors_other_phones.phone', $arrField['condition'], $arrField['value']);
                    continue;
                }
                $tmpStr = str_replace('search_field_', '', $key);
                $arrDbData = explode('@', $tmpStr);
                $tblName = (isset($arrDbData[0])) ? $arrDbData[0] : '';
                $colName = (isset($arrDbData[1])) ? $arrDbData[1] : '';

                $condition = ($arrField['condition'] == 'подобно') ? 'like' : $arrField['condition'];
                $valField = ($condition == 'like') ? '%' . $arrField['value'] . '%' : $arrField['value'];

                if (!empty($tblName) && !empty($colName)) {
                    if ($colName == 'od' || $colName == 'sum_indebt') {
                        $valField = $valField * 100;
                    }

                    $debtors->where('debtors.' . $tblName . '.' . $colName, $condition, $valField);
                }
            }
            if ((count($arrFields) == 1 && array_key_exists('search_field_passports@fact_address_city', $arrFields))) {
                $debtors->whereIn('debtors.responsible_user_id_1c', $arIn);
            }
        } else {
            // если по какой-то причине массив с ответственными будет пустым - выводим всех
            if (count($arIn)) {
                $debtors->whereIn('debtors.responsible_user_id_1c', $arIn);
            }
        }

        $is_bigmoney = (isset($input['search_field_debtors@is_bigmoney']) && $input['search_field_debtors@is_bigmoney'] == 1) ? 1 : 0;
        $is_pledge = (isset($input['search_field_debtors@is_pledge']) && $input['search_field_debtors@is_pledge'] == 1) ? 1 : 0;
        $is_pos = (isset($input['search_field_debtors@is_pos']) && $input['search_field_debtors@is_pos'] == 1) ? 1 : 0;

        if ($is_bigmoney || $is_pledge || $is_pos) {
            $debtors->where(function ($query) use ($is_bigmoney, $is_pledge, $is_pos) {
                if ($is_bigmoney) {
                    $query->where('debtors.debtors.is_bigmoney', 1);
                    if ($is_pledge) {
                        $query->orWhere('debtors.debtors.is_pledge', 1);
                    }
                    if ($is_pos) {
                        $query->orWhere('debtors.debtors.is_pos', 1);
                    }
                } else {
                    if ($is_pledge) {
                        $query->where('debtors.debtors.is_pledge', 1);
                        if ($is_pos) {
                            $query->orWhere('debtors.debtors.is_pos', 1);
                        }
                    } else {
                        if ($is_pos) {
                            $query->where('debtors.debtors.is_pos', 1);
                        }
                    }
                }
            });
        }

        return $debtors;
    }

    /**
     * возвращает список должников в таблицу
     * @param Request $req
     * @return type
     */
    public function ajaxList(Request $req)
    {
        $debtors = $this->getDebtorsQuery($req);

        $user = auth()->user();

        $collection = Datatables::of($debtors)
            ->editColumn('debtors_created_at', function ($item) {
                return (!is_null($item->d_created_at)) ? date('d.m.Y', strtotime($item->d_created_at)) : '-';
            })
            ->editColumn('debtors_fixation_date', function ($item) {
                return (!is_null($item->debtors_fixation_date)) ? date('d.m.Y',
                    strtotime($item->debtors_fixation_date)) : '-';
            })
            ->editColumn('debtors_od', function ($item) {
                return number_format($item->debtors_od / 100, 2, '.', '');
            })
            ->editColumn('debtors_sum_indebt', function ($item) {
                return number_format($item->debtors_sum_indebt / 100, 2, '.', '');
            })
            ->editColumn('customers_telephone', function ($item) {
                return \App\Services\PrivateDataService::formatPhone(auth()->user(), $item->customers_telephone);
            })
            ->addColumn('actions', function ($item) use ($user) {
                $glyph = $item->uploaded == 1 ? 'ok' : 'remove';
                $html = '';
                if (mb_strlen($item->debtors_responsible_user_id_1c)) {
                    $pos = strpos(Auth::user()->id_1c, $item->debtors_responsible_user_id_1c);
                    if ($user->hasRole('debtors_remote')) {
                        $pos = true;
                    }
                } else {
                    $pos = true;
                }
                if (Auth::user()->hasRole('debtors_chief') || $pos !== false) {
                    if (isset($item->passports_fact_timezone) && !is_null($item->passports_fact_timezone)) {
                        $region_time = date("H:i", strtotime($item->passports_fact_timezone . ' hour'));
                        $arRegionTime = explode(':', $region_time);
                        $weekday = date('N', time());
                        $hour = $arRegionTime[0];
                        if ($hour[0] == '0') {
                            $hour = substr($hour, 1);
                        }
                        if ($weekday == 6 || $weekday == 7) {
                            $dNoCall = ($hour < 9 || $hour >= 20) ? true : false;
                        } else {
                            $dNoCall = ($hour < 8 || $hour >= 22) ? true : false;
                        }
                    }
                    $arBtn = ['glyph' => 'eye-open', 'size' => 'xs', 'target' => '_blank'];
                    if (isset($dNoCall) && $dNoCall) {
                        $arBtn['style'] = 'color: red;';
                    }

                    $html .= HtmlHelper::Buttton(url('debtors/debtorcard/' . $item->debtors_id), $arBtn);
                }
                if (Auth::user()->isAdmin()) {
                    $html .= HtmlHelper::Buttton(url('ajax/debtors/changeloadstatus/' . $item->debtors_id),
                        ['glyph' => $glyph, 'size' => 'xs', 'class' => 'btn btn-default loadFlag']);
                }
                return $html;
            })
            ->removeColumn('debtors_id')
            ->removeColumn('debtor_id_1c')
            ->removeColumn('uploaded')
            ->removeColumn('debtors_debt_group')
            ->removeColumn('debtors_responsible_user_id_1c')
            ->removeColumn('debtor_customer_id_1c')
            ->removeColumn('debtor_is_bigmoney')
            ->removeColumn('debtor_is_pledge')
            ->removeColumn('debtor_is_pos')
            ->removeColumn('debtor_is_online')
            ->removeColumn('debtors_od_after_closing')
            ->removeColumn('passports_fact_timezone')
            ->setTotalRecords(1000)
            ->make();
        return $collection;
    }

    /**
     * Возвращает данные для таблицы "Запланированные мероприятия" в разделе "Должники"
     * @param Request $req
     * @return collection
     */
    public function ajaxEventsList(Request $req)
    {
        $cols = [];
        $tCols = [
            'debtor_events.date' => 'de_date',
            'debtor_events.event_type_id' => 'de_type_id',
            'debtors.passports.fio' => 'passports_fio',
            'debtor_events.created_at' => 'de_created_at',
            'users.login' => 'de_username',
            'debtors.id' => 'debtors_id',
            'debtors.passports.fact_timezone' => 'passports_fact_timezone'
        ];

        foreach ($tCols as $k => $v) {
            $cols[] = $k . ' as ' . $v;
        }

        $currentUser = User::find(Auth::id());

        $arIn = DebtorUsersRef::getUserRefs();
        $date = (is_null($req->get('search_field_debtor_events@date'))) ?
            Carbon::today() :
            (new Carbon($req->get('search_field_debtor_events@date')));

        $date_from = $req->get('search_field_debtor_events@date_from');
        $date_to = $req->get('search_field_debtor_events@date_to');

        $fact_timezone = $req->get('search_field_passports@fact_timezone');

        $debt_group_id = $req->get('search_field_debt_groups@id');

        $date_from_fmt = false;
        if (!is_null($date_from) && !empty($date_from)) {
            $date_from_fmt = date('Y-m-d 00:00:00', strtotime($date_from));
        }

        $date_to_fmt = false;
        if (!is_null($date_to) && !empty($date_to)) {
            $date_to_fmt = date('Y-m-d 23:59:59', strtotime($date_to));
        }

        $responsible_id_1c = $req->get('search_field_users@id_1c');

        // получаем список запланированных мероприятий на сегодня
        $debtorEvents = DB::table('debtor_events')->select($cols)
            ->leftJoin('debtors', 'debtors.id', '=', 'debtor_events.debtor_id')
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('users', 'users.id', '=', 'debtor_events.user_id')
            ->leftJoin('debtor_users_ref', 'debtor_users_ref.master_user_id', '=', 'users.id')
            ->leftJoin('debtors_event_types', 'debtors_event_types.id', '=', 'debtor_events.event_type_id')
            //->whereBetween('debtor_events.date', array($date->setTime(0, 0, 0)->format('Y-m-d H:i:s'), $date->setTime(23, 59, 59)->format('Y-m-d H:i:s')))
            ->where('debtor_events.completed', 0)
            ->groupBy('debtor_events.id');

        if (!$date_from_fmt && !$date_to_fmt) {
            $debtorEvents->whereBetween('debtor_events.date', array(
                $date->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $date->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            ));
        } else {
            if ($date_from_fmt) {
                $debtorEvents->where('debtor_events.date', '>=', $date_from_fmt);
            }

            if ($date_to_fmt) {
                $debtorEvents->where('debtor_events.date', '<=', $date_to_fmt);
            }
        }

        if (!is_null($debt_group_id) && mb_strlen($debt_group_id)) {
            $debtorEvents->where('debtors.debt_group_id', (int)$debt_group_id);
        }

        if (!is_null($fact_timezone) && mb_strlen($fact_timezone)) {
            $debtorEvents->where('passports.fact_timezone', (int)$fact_timezone);
        }

        if (!is_null($responsible_id_1c) && mb_strlen($responsible_id_1c)) {
            $debtorEvents->where('debtors.debtor_events.user_id_1c', $responsible_id_1c);
        }

        if ($currentUser->hasRole('debtors_personal')) {
            $debtorEvents->where('debtors.debtor_events.user_id', $currentUser->id);
        } else {
            // если придет пустой массив - будут показаны все планы на день
            if (count($arIn) && (is_null($responsible_id_1c) || !mb_strlen($responsible_id_1c))) {
                $debtorEvents->whereIn('debtors.debtor_events.user_id', $arIn);
            }
        }


        // формирование коллекции для заполнения таблицы
        $collection = Datatables::of($debtorEvents)
            ->editColumn('de_date', function ($item) {
                return date('d.m.Y', strtotime($item->de_date));
            })
            ->editColumn('de_created_at', function ($item) {
                return date('d.m.Y', strtotime($item->de_created_at));
            })
            ->editColumn('de_type_id', function ($item) {
                if (is_null($item->de_type_id)) {
                    return 'Неопределен';
                }
                $arDebtData = config('debtors');
                return $arDebtData['event_types'][$item->de_type_id];
            })
            ->removeColumn('debtors_id')
            ->removeColumn('passports_fact_timezone')
            ->addColumn('actions', function ($item) {
                $html = '';

                if (isset($item->passports_fact_timezone) && !is_null($item->passports_fact_timezone)) {
                    $region_time = date("H:i", strtotime($item->passports_fact_timezone . ' hour'));
                    $arRegionTime = explode(':', $region_time);
                    $weekday = date('N', time());
                    $hour = $arRegionTime[0];
                    if ($hour[0] == '0') {
                        $hour = substr($hour, 1);
                    }
                    if ($weekday == 6 || $weekday == 7) {
                        $dNoCall = ($hour < 9 || $hour >= 20) ? true : false;
                    } else {
                        $dNoCall = ($hour < 8 || $hour >= 22) ? true : false;
                    }
                }
                $arBtn = ['glyph' => 'eye-open', 'size' => 'xs', 'target' => '_blank'];
                if (isset($dNoCall) && $dNoCall) {
                    $arBtn['style'] = 'color: red;';
                }

                $html .= HtmlHelper::Buttton(url('debtors/debtorcard/' . $item->debtors_id), $arBtn);
                return $html;
            })
            ->filter(function ($query) use ($req) {
                $input = $req->input();
                $no_empty_date = false;
                if (!empty($input['search_field_debtor_events@date'])) {
                    $no_empty_date = true;
                }
                foreach ($input as $k => $v) {
                    if (strpos($k, 'search_field_') === 0 && strpos($k, '_condition') === false && !empty($v)) {
                        $fieldName = str_replace('search_field_', '', $k);
                        $tableName = substr($fieldName, 0, strpos($fieldName, '@'));
                        $colName = substr($fieldName, strlen($tableName) + 1);
                        $condColName = $k . '_condition';
                        $condition = (array_key_exists($condColName, $input)) ? $input[$condColName] : '=';
                        $condition = (empty($condition)) ? '=' : $condition;

                        if ($no_empty_date) {
                            if ($k == 'search_field_debtor_events@date') {
                                $dateFmtd = new Carbon($v);
                                $query->whereBetween('debtor_events.date', array(
                                    $dateFmtd->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                                    $dateFmtd->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                                ));
                                continue;
                            }
                        } else {
                            if ($k == 'search_field_debtor_events@date_from') {
                                $dateFmtd = new Carbon($v);
                                $query->where('debtor_events.date', '>=',
                                    $dateFmtd->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
                                continue;
                            }

                            if ($k == 'search_field_debtor_events@date_to') {
                                $dateFmtd = new Carbon($v);
                                $query->where('debtor_events.date', '<=',
                                    $dateFmtd->setTime(23, 59, 59)->format('Y-m-d H:i:s'));
                                continue;
                            }
                        }

                        if ($k == 'search_field_debt_groups@id' && mb_strlen($v)) {
                            $query->where('debtors.debt_group_id', (int)$v);
                            continue;
                        }

                        if ($condition == 'like') {
                            $v = '%' . $v . '%';
                        }
                        $query->where($tableName . '.' . $colName, $condition, $v);
                    }
                }
            })
            ->setTotalRecords(1000)
            ->make();

        return $collection;
    }

    /**
     * возвращает мероприятие по переданному идентификатору
     * @param Request $req
     * @return type
     */
    public function getDebtorEventData(Request $req)
    {
        $debtorEvent = DebtorEvent::find($req->get('id'));
        $user = User::find($debtorEvent->user_id);
        $debtorEvent->user_fio = $user->name;
        return $debtorEvent;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $req
     * @return \Illuminate\Http\Response
     */
    public function updateDebtorEvent(Request $req)
    {
        $debtEvent = DebtorEvent::findOrNew($req->get('id', null));
        $input = $req->input();
        //ларавель вместо пустых кавычек в числовые поля подставляет 0, 
        //поэтому ставим null где пустые значения
        foreach ($input as $k => $v) {
            if (strpos($k, '_id') !== false && empty($v)) {
                $input[$k] = null;
            } else {
                if ($v == '0000-00-00 00:00:00') {
                    $input[$k] = null;
                }
            }
        }
        $debtor = Debtor::find($input['debtor_id']);
        if (!is_null($debtor)) {
            if (isset($input['debt_group_id'])) {
                if ($debtor->debt_group_id != $input['debt_group_id']) {
                    $debtor->debt_group_id = $input['debt_group_id'];
                    $debtor->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
                    $debtor->save();
                }
            }
        }
        if (isset($input['search_field_users@id_1c']) && !empty($input['search_field_users@id_1c'])) {
            $user = User::where('id_1c', $input['search_field_users@id_1c'])->first();
            $debtEvent->user_id_1c = $user->id_1c;
            $debtEvent->user_id = $user->id;
        }
        $debtEvent->fill($input);
        $debtEvent->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
        $debtEvent->save();

        $debtor_unclosed = Debtor::where('customer_id_1c', $debtor->customer_id_1c)->where('is_debtor', 1)->get();
        foreach ($debtor_unclosed as $unclosed) {
            if ($unclosed->base == 'Архив убытки' || $unclosed->base == 'Архив компании') {
                continue;
            }

            if ($debtor->id == $unclosed->id) {
                continue;
            }

            if (isset($input['debt_group_id']) && mb_strlen($input['debt_group_id']) > 0) {
                if ($unclosed->debt_group_id != $input['debt_group_id']) {
                    $unclosed->debt_group_id = $input['debt_group_id'];
                    $unclosed->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
                    $unclosed->save();
                }
            }
        }

        return $this->backWithSuc();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroyDebtorEvent($id)
    {
        $debtEvent = DebtorEvent::find($id);
        if (!is_null($debtEvent)) {
            $debtEvent->delete();
            $this->backWithSuc();
        } else {
            $this->backWithErr(StrLib::ERR_NULL);
        }
    }

    /**
     * Выдает данные для подстановки в поисковые поля
     * @param Request $req
     * @return array
     */
    public function ajaxColumnAutocomplete(Request $req)
    {
        $fieldName = str_replace('search_field_', '', $req->get('field'));
        $valFieldName = str_replace('search_field_', '', $req->get('valfield'));
        $valueColumn = 'id';

        if ($fieldName == 'debtgroup') {
            $arDebtGroups = [];
            $arDebtGroupsDb = \App\DebtGroup::getDebtGroups();
            foreach ($arDebtGroupsDb as $id => $val) {
                $term = $req->get('term', '');
                if (empty($term)) {
                    return [];
                }
                if (strpos($val, $term) !== false) {
                    $arDebtGroups[] = [
                        'value' => (string)$id,
                        'label' => $val
                    ];
                }
            }
            return $arDebtGroups;
        }

        if (!empty($valFieldName)) {
            $valueColumn = substr($valFieldName, strpos($valFieldName, '@') + 1);
        }
        $term = $req->get('term', '');
        if (empty($fieldName) || empty($term)) {
            return [];
        }
        $tableName = substr($fieldName, 0, strpos($fieldName, '@'));
        $colName = substr($fieldName, strlen($tableName) + 1);
        if ($colName == 'fio' && $tableName == 'passports') {
            return DB::table($tableName)->select(DB::raw("CONCAT(fio,' (',series,' ',number,')') as label, " . $valueColumn . " as value"))->where($colName,
                'like', '%' . $term . '%')->get();
        } else {
            return DB::table($tableName)->select($colName . ' as label', $valueColumn . ' as value')->where($colName,
                'like', '%' . $term . '%')->groupBy($colName)->get();
        }
    }

    /**
     * Отправляет SMS должнику
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function sendSmsToDebtor(Request $req)
    {
        $debtor = Debtor::where('debtor_id_1c', $req->get('debtor_id_1c'))->first();
        if (is_null($debtor)) {
            return response()->json([
                'title' => 'Ошибка',
                'msg' => 'Не удалось получить информацию по должнику'
            ]);
        }

        $sms = DebtorSmsTpls::where('id',$req->sms_id)->first();
        if ($sms && !is_null($sms->is_excludes)) {
            try {
                $customer = $debtor->customer();
                $debtors = Debtor::where('customer_id_1c', $customer->id_1c)->get();
                foreach ($debtors as $debt) {
                    $this->debtEventService->checkLimitEvent($debt);
                }
            } catch (DebtorException $e) {
                return response()->json([
                    'title' => 'Ошибка',
                    'msg' => $e->errorMessage
                ],$e->errorCode);
            }
        }
        $user = Auth::user();
        if (!$user->canSendSms()) {
            return response()->json([
                'title' => 'Ошибка',
                'msg' => 'Первышел лимит СМС пользователя'
            ]);
        }

        // приводим номер телефона к виду, для отправки смс
        $phone = preg_replace("/[^0-9]/", "", $req->get('phone'));
        if (isset($phone[0]) && $phone[0] == '8') {
            $phone[0] = '7';
        }
        if (mb_strlen($phone) == 11) {

            $smsLink = '';
            $smsType = $req->get('sms_type', false);
            $smsText = $req->get('sms_text', false);

            if ($smsType && $smsType == 'link') {
                $amount = $req->get('amount', 0);
                $token = crypt('gfhjkmhfplsdfnhb12332hfp.', 'shitokudosai');
                $amount = $amount * 100;
                $smsText = 'Направляем ссылку для оплаты долга в ООО МКК"ФИНТЕРРА"88003014344';
                $smsLink = 'http://192.168.35.89/api/tinkoff/init?amount=' . $amount
                    . '&loan_1c_id=' . $debtor->loan_id_1c
                    . '&order_id=&customer_id=' . $debtor->customer_id_1c
                    . '&phone=' . $phone
                    . '&token=' . $token . '&version=2&details=null&order_type_id=&payment_type_id=&paysystem_type_id=&notification_url=https://xn--j1ab.xn--80ajiuqaln.xn--p1ai/api/payments/notification&success_url=';

                $jsonTinkoffLink = file_get_contents($smsLink);
                $arJson = json_decode($jsonTinkoffLink, true);

                if (isset($arJson['success']) && $arJson['success']) {
                    $smsLink = $arJson['url'];
                } else {
                    return response()->json([
                        'title' => 'Ошибка',
                        'msg' => 'Не удалось сформировать ссылку'
                    ]);
                }
            }

            if ($smsType && $smsType == 'msg') {
                $amount = $req->get('amount', 0);
                $token = crypt('gfhjkmhfplsdfnhb12332hfp.', 'shitokudosai');
                $amount = $amount * 100;
                $smsText = 'Направляем ссылку для оплаты долга в ООО МКК"ФИНТЕРРА"88003014344';
                $smsLink = 'http://192.168.35.89/api/tinkoff/init?amount=' . $amount
                    . '&loan_1c_id=' . $debtor->loan_id_1c
                    . '&order_id=&customer_id=' . $debtor->customer_id_1c
                    . '&phone=' . $phone
                    . '&token=' . $token . '&version=2&details=null&order_type_id=&payment_type_id=&paysystem_type_id=&notification_url=https://xn--j1ab.xn--80ajiuqaln.xn--p1ai/api/payments/notification&success_url=';

                $jsonTinkoffLink = file_get_contents($smsLink);
                $arJson = json_decode($jsonTinkoffLink, true);

                if (isset($arJson['success']) && $arJson['success']) {
                    $smsLink = $arJson['url'];
                } else {
                    return response()->json([
                        'title' => 'Ошибка',
                        'msg' => 'Не удалось сформировать сообщение'
                    ]);
                }

                return $smsText . ' ' . $smsLink;
            }

            if ($smsType && $smsType == 'props') {
                $smsText = 'Направляем реквизиты для оплаты долга в ООО МКК"ФИНТЕРРА"88003014344'
                . ' путем оплаты в отделении банка https://финтерра.рф/faq/rekvizity';
                $smsLink = '';
            }

            if (mb_strlen($smsLink) > 0) {
                $smsLink = ' ' . $smsLink;
            }

            if (Utils\SMSer::send($phone, $smsText . $smsLink)) {
                // увеличиваем счетчик отправленных пользователем смс
                $user->increaseSentSms();

                // создаем мероприятие отправки смс
                $debtorEvent = new DebtorEvent();
                $data = [];
                $data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                $data['event_type_id'] = 12;
                $data['overdue_reason_id'] = 0;
                $data['event_result_id'] = 22;
                $data['debt_group_id'] = $req->get('debt_group_id');
                $data['report'] = $phone . ' SMS: ' . $smsText;
                $data['completed'] = 1;
                $debtorEvent->fill($data);
                $debtorEvent->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
                $debtorEvent->debtor_id_1c = $debtor->debtor_id_1c;
                $debtorEvent->debtor_id = $debtor->id;
                $debtorEvent->user_id = $user->id;
                $debtorEvent->user_id_1c = $user->id_1c;
                $debtorEvent->save();

                return response()->json([
                    'title' => 'Готово',
                    'msg' => 'Сообщение отправленно'
                ]);
            }
        }

        return response()->json([
            'title' => 'Ошибка',
            'msg' => 'Не правильный номер'
        ]);
    }

    /**
     * История договоров по должнику
     * @param integer $id
     * @return type
     */
    public function debtorHistory($id)
    {
        $debtor = Debtor::find($id);
        if (is_null($debtor)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $loans = $debtor->getAllLoans();
//        foreach ($loans as &$loan) {
//            if(!$loan->closed){
//                $loan->loan_id_replica = DB::connection('arm')
//                        ->table('loans')
//                        ->leftJoin('claims','claims.id','=','loans.claim_id')
//                        ->leftJoin('customers','customers.id','=','claims.customer_id')
//                        ->where('loans.id_1c',$loan->loan_id_1c)
//                        ->where('customers.id_1c',$debtor->customer_id_1c)
//                        ->limit(1)
//                        ->value('loans.id');
//            }
//        }
        /* $cnt_opened = 0;
          foreach ($loans as $loan) {
          if (!$loan->closed) {
          $cnt_opened++;
          }
          }

          if ($cnt_opened == 0) {
          $result = file_get_contents(config('admin.sales_arm_url')."/debtors/loans/upload?passport_series={$debtor->passport_series}&passport_number={$debtor->passport_number}");
          $loans = $debtor->getAllLoans();
          }

          if ($cnt_opened > 1) {
          foreach ($loans as $loan) {
          if (!$loan->closed) {
          $result = file_get_contents(config('admin.sales_arm_url')."/debtors/loans/upload?loan_id_1c={$debtor->loan_id_1c}&customer_id_1c={$debtor->customer_id_1c}");
          }
          }
          $loans = $debtor->getAllLoans();
          } */

        return view('debtors.history', ['loans' => $loans, 'debtor_id' => $id]);
    }

    public function getLoanSummary($loan_id)
    {
//        config('database.default') = 'arm';
        Config::set('database.default', 'arm');
        $loan = Loan::where('id', $loan_id)->first();
        $claim = DB::connection('arm')->table('claims')->where('id', $loan->claim_id)->first();
        $passport = DB::connection('arm')->table('passports')->where('id', $claim->passport_id)->first();
        $about_client = DB::connection('arm')->table('about_clients')->where('id', $claim->about_client_id)->first();
        $customer = DB::connection('arm')->table('customers')->where('id', $claim->customer_id)->first();
        $loantype = DB::connection('arm')->table('loantypes')->where('id', $loan->loantype_id)->first();
        $liveCondition = DB::connection('arm')->table('live_conditions')->where('id', $about_client->zhusl)->first();
        $maritalType = DB::connection('arm')->table('marital_types')->where('id',
            $about_client->marital_type_id)->first();
        $educationLevel = DB::connection('arm')->table('education_levels')->where('id',
            $about_client->obrasovanie)->first();
//        if (is_null($loan) || is_null($loan->claim) || is_null($loan->claim->passport)) {
//            return redirect('loans')->with('msg_err', StrLib::ERR_NULL);
//        }
        $photos = DB::connection('arm')->table('photos')->where('claim_id', $claim->id)->get();
        $photo_res = [];
        foreach ($photos as $p) {
            if (Storage::exists($p->path)) {
                //после переезда убралось images из пути, но при показе оно должно быть чтобы нормально отработала функция в путях. 
                $p->src = url(((strpos($p->path, 'images') === false) ? 'images/' : '') . $p->path);
                $photo_res[] = $p;
            }
        }
        /* ==Акции по сузу================================================================ */
        $importantMoneyData = null;
        $akcsuzst46 = false;
        /* =================================================================== */
        $loanData = (is_null($loan->data)) ? null : json_decode($loan->data);
        $reqMoneyDet = $loan->getRequiredMoneyDetails();
        if (!is_null($loanData) && isset($loanData->spisan) && isset($loanData->spisan->total)) {
            $reqMoneyDet->money = StrUtils::removeNonDigits($loanData->spisan->total);
        }
        \PC::debug($reqMoneyDet);
//        if(Auth::user()->id==5){
//            $repayments = DB::connection('arm')->table('repayments')->where('loan_id', $loan->id)->orderBy('created_at', 'asc')->get();
//        } else {
        $repayments = Repayment::where('loan_id', $loan->id)->orderBy('created_at', 'asc')->get();
//        }        
        $repsNum = count($repayments);
        $debtorData = null;

        $viewData = [
            'loan' => $loan,
            'claim' => $claim,
            'loantype' => $loantype,
            'customer' => $customer,
            'about_client' => $about_client,
            'liveCondition' => $liveCondition,
            'maritalType' => $maritalType,
            'educationLevel' => $educationLevel,
            'photos' => $photo_res,
            'percents' => $loan->getPercents(),
            'repayments' => $repayments,
            'reqMoneyDet' => $reqMoneyDet,
            'debtor' => $debtorData,
            'ngbezdolgov' => $importantMoneyData,
            'akcsuzst46' => $akcsuzst46,
            'passport' => $passport,
            'reptypes' => DB::connection('arm')->table('repayment_types')->get()
        ];
        if (isset($loanData) && !is_null($loanData) && isset($loanData->spisan)) {
            $viewData['curPayMoney'] = $loanData->spisan->total;
            $viewData['spisan'] = $loanData->spisan->total;
        }
        return view('debtors.summary.summary', $viewData);
    }

    public function getLastRepayment($loan_id_1c, $customer_id_1c)
    {

        $loans_id = DB::connection('arm')->table('loans')
            ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
            ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
            ->where('loans.id_1c', $loan_id_1c)
            ->where('customers.id_1c', $customer_id_1c)
            ->value('loans.id');

        if (is_null($loans_id)) {
            return false;
        }

        $repayment_arm = DB::connection('arm')->table('repayments')
            ->select(['repayments.created_at as created_at', 'repayment_types.name as name'])
            ->leftJoin('repayment_types', 'repayment_types.id', '=', 'repayments.repayment_type_id')
            ->where('loan_id', $loan_id_1c)
            ->orderBy('created_at', 'desc')
            ->first();

        if (is_null($repayment_arm)) {
            return false;
        }

        return $repayment_arm;
    }

    /**
     * Формирует PDF (анкета/уведомления удаленного и личного взысканий)
     * @param string $debtor_id
     * @return pdf
     */
    public function createPdf($doc_id, $debtor_id, $date, $factAddress = 1)
    {
//        if (Auth::user()->id == 5) {
        $debtorData = Debtor::select(DB::raw('*, passports.id as d_passport_id, '
            . 'loans.id_1c as d_loan_id_1c, loans.created_at as d_loan_created_at, loans.time as d_loan_time, '
            . 'loans.tranche_number as d_loan_tranche_number, loans.first_loan_id_1c as d_loan_first_loan_id_1c, loans.first_loan_date as d_loan_first_loan_date, loans.in_cash as d_in_cash,'
            . 'loantypes.name as d_loan_name, claims.created_at as d_claim_created_at, '
            . 'users.login as spec_fio, users.phone as spec_phone, debtors.fine as d_fine, '
            . 'debtors.pc as d_pc, debtors.exp_pc as d_exp_pc, passports.birth_date as birth_date, '
            . 'loans.money as money, customers.id_1c as d_customer_id_1c, about_clients.customer_id as a_customer_id, '
            . 'claims.id as r_claim_id, loans.id as r_loan_id'))
            ->leftJoin('loans', 'loans.id_1c', '=', 'debtors.debtors.loan_id_1c')
            ->leftJoin('customers', 'customers.id_1c', '=', 'debtors.debtors.customer_id_1c')
            ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
            ->leftJoin('passports', function ($join) {
                $join->on('passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('loantypes', 'loantypes.id', '=', 'debtors.loans.loantype_id')
            ->leftJoin('about_clients', 'about_clients.id', '=', 'debtors.claims.about_client_id')
            ->leftJoin('debtors.users', 'debtors.users.id_1c', '=', 'debtors.responsible_user_id_1c')
            ->where('debtors.id', $debtor_id);

//            \PC::debug([$objDebtorData->r_claim_id, $objDebtorData->r_loan_id]);
//        } else {
//            $debtorData = Debtor::select(DB::raw('*, armf.passports.id as d_passport_id, armf.loans.id_1c as d_loan_id_1c, armf.loans.created_at as d_loan_created_at, armf.loantypes.name as d_loan_name, armf.claims.created_at as d_claim_created_at, users.login as spec_fio, users.phone as spec_phone, debtors.fine as d_fine, debtors.pc as d_pc, debtors.exp_pc as d_exp_pc, armf.passports.birth_date as birth_date, armf.loans.money as money, armf.customers.id_1c as d_customer_id_1c'))
//                    ->leftJoin('armf.loans', 'armf.loans.id_1c', '=', 'debtors.loan_id_1c')
//                    ->leftJoin('armf.claims', 'armf.claims.id', '=', 'armf.loans.claim_id')
//                    ->leftJoin('armf.customers', 'armf.customers.id_1c', '=', 'debtors.debtors.customer_id_1c')
//                    ->leftJoin('armf.passports', 'armf.passports.id', '=', 'armf.claims.passport_id')
//                    ->leftJoin('armf.loantypes', 'armf.loantypes.id', '=', 'armf.loans.loantype_id')
//                    ->leftJoin('armf.about_clients', 'armf.about_clients.customer_id', '=', 'armf.claims.customer_id')
//                    ->leftJoin('debtors.users', 'debtors.users.id_1c', '=', 'debtors.responsible_user_id_1c')
//                    ->where('debtors.id', $debtor_id);
//        }


        $objDebtorData = $debtorData->get();

        $debtorTmp = Debtor::find($debtor_id);
        $customer = \App\Customer::where('id_1c', $debtorTmp->customer_id_1c)->first();
        foreach ($objDebtorData as $tmpObj) {
            if ($tmpObj->a_customer_id == $customer->id) {
                $objDebtorData = $tmpObj;
                break;
            }
        }

        if (!is_null($objDebtorData)) {

            \PC::debug($objDebtorData);

            $doc = \App\ContractForm::where('id', (int)$doc_id)->first();

            $html = $doc->template;

            $date_personal = '';
            if ($date != '0') {
                $date_personal = date('d.m.Y', strtotime($date));
            }
//            if (Auth::user()->id == 5) {
            $passport = json_decode(json_encode(Passport::where('series',
                $objDebtorData->passport_series)->where('number', $objDebtorData->passport_number)->first()), true);
//            } else {
//                $passport = json_decode(json_encode(DB::connection('arm')->table('passports')->where('id', $objDebtorData->d_passport_id)->first()), true);
//            }
//            $passport = Passport::connection('arm')->find($objDebtorData->d_passport_id);

            $loan_percent = $objDebtorData->special_percent;
            if (is_null($loan_percent)) {
                $loan_percent = $objDebtorData->percent;
            }

            $return_date = date('d.m.Y',
                strtotime("+" . $objDebtorData->d_loan_time . " days", strtotime($objDebtorData->d_loan_created_at)));
            $arSpecName = explode(' ', $objDebtorData->spec_fio);

            $spec_surname = '';
            if (isset($arSpecName[0])) {
                $spec_surname = $arSpecName[0];
            }

            $spec_io = '';
            if (isset($arSpecName[1])) {
                $spec_io = $arSpecName[1];
                if (isset($arSpecName[2])) {
                    $spec_io .= ' ' . $arSpecName[2];
                }
            }

            $arBirthDate = explode('-', $objDebtorData->birth_date);
            if (!isset($arBirthDate[2]) || !isset($arBirthDate[1]) || !isset($arBirthDate[0])) {
                $debtor_birth_date = 'n/a';
            } else {
                $debtor_birth_date = $arBirthDate[2] . '.' . $arBirthDate[1] . '.' . $arBirthDate[0];
            }

            $loan = Loan::where('id_1c', $objDebtorData->loan_id_1c)->first();

            if ($date != '0') {
                /* $debtData = \App\Utils\HelperUtil::SendPostByCurl('192.168.1.115/debtors/debt', [
                  'data' => json_encode([
                  'loan_id_1c' => $objDebtorData->d_loan_id_1c,
                  'customer_id_1c' => $objDebtorData->d_customer_id_1c,
                  'date' => date('Y-m-d', strtotime($date_personal))
                  ])
                  ]); */

                $debtData = $loan->getDebtFrom1cWithoutRepayment(date('Y-m-d', strtotime($date_personal)));

                \PC::debug($debtData);

                //$arDebtData = json_decode($debtData, true);
                $debt = [];
                $arFrom1CFields = ['od', 'pc', 'exp_pc', 'fine', 'all_pc', 'money', 'pays'];

                foreach ($arFrom1CFields as $field) {
                    if (isset($debtData->$field) && !is_null($debtData->$field) && mb_strlen($debtData->$field)) {
                        $debt[$field] = $debtData->$field;
                    } else {
                        $debt[$field] = 0;
                    }
                }
            } else {
                $debt['od'] = $objDebtorData->od;
                $debt['pc'] = $objDebtorData->d_pc;
                $debt['exp_pc'] = $objDebtorData->d_exp_pc;
                $debt['fine'] = $objDebtorData->d_fine;
                $debt['money'] = $objDebtorData->sum_indebt;
                $debt['all_pc'] = $objDebtorData->d_pc + $objDebtorData->d_exp_pc;
            }

            if (isset($debt['pays']) && $debt['pays'] !== 0) {
                $fullpay_sum = 0;
                $fullpay_od_sum = 0;
                $fullpay_pc_sum = 0;
                $fullpay_exp_pc_sum = 0;
                $fullpay_fine_sum = 0;

                $open_debt = false;

                $arrayPays = json_decode(json_encode($debt['pays']), true);

                foreach ($arrayPays as $pay) {
                    if ($pay['expired'] == 1 && $pay['closed'] == 1) {
                        if (is_int($pay['exp_pc']) || is_string($pay['exp_pc'])) {
                            $fullpay_exp_pc_sum += $pay['exp_pc'];
                        }

                        if (is_int($pay['fine']) || is_string($pay['fine'])) {
                            $fullpay_fine_sum += $pay['fine'];
                        }

                        $fullpay_sum = $fullpay_sum + $fullpay_exp_pc_sum + $fullpay_fine_sum;
                    }

                    if ($pay['expired'] == 1 && $pay['closed'] == 0) {
                        $open_debt = true;

                        if (is_int($pay['od']) || is_string($pay['od'])) {
                            $fullpay_sum += $pay['od'];
                            $fullpay_od_sum += $pay['od'];
                        }

                        if (is_int($pay['pc']) || is_string($pay['pc'])) {
                            $fullpay_sum += $pay['pc'];
                            $fullpay_pc_sum += $pay['pc'];
                        }

                        if (is_int($pay['exp_pc']) || is_string($pay['exp_pc'])) {
                            $fullpay_sum += $pay['exp_pc'];
                            $fullpay_exp_pc_sum += $pay['exp_pc'];
                        }

                        if (is_int($pay['fine']) || is_string($pay['fine'])) {
                            $fullpay_sum += $pay['fine'];
                            $fullpay_fine_sum += $pay['fine'];
                        }
                    }

                    if ($pay['expired'] == 0 && $pay['closed'] == 1) {
                        continue;
                    }

                    if ($pay['expired'] == 0 && $pay['closed'] == 0) {
                        if ($open_debt) {
                            $open_debt = false;

                            $fullpay_pc_sum += $pay['pc'];
                            $fullpay_sum = $fullpay_sum + $pay['pc'];
                        }

                        $fullpay_od_sum += $pay['od'];
                        $fullpay_sum = $fullpay_sum + $pay['od'];
                    }
                }

                \PC::debug($arrayPays);
            }

            $od = number_format($debt['od'] / 100, 2, ',', ' ');
            $pc = number_format($debt['pc'] / 100, 2, ',', ' ');
            $exp_pc = number_format($debt['exp_pc'] / 100, 2, ',', ' ');
            $fine = number_format($debt['fine'] / 100, 2, ',', ' ');
            $sum_indebt = number_format($debt['money'] / 100, 2, ',', ' ');
            $sum_pc = number_format(($debt['exp_pc'] + $debt['pc']) / 100, 2, ',', ' ');

            $arOd = explode(',', $od);
            $arPc = explode(',', $pc);
            $arExp_pc = explode(',', $exp_pc);
            $arFine = explode(',', $fine);
            $arSumIndebt = explode(',', $sum_indebt);

            $fact_pc = number_format($debt['all_pc'] / 100, 2, ',', ' ');
            $arFactPc = explode(',', $fact_pc);

            $ur_address = Passport::getFullAddress($passport);
            $fact_address = Passport::getFullAddress($passport, true);
            if (!$factAddress) {
                $print_address = $ur_address;
            } else {
                $print_address = $fact_address;
            }

            $objDebtorData->this_loan_created_at = $objDebtorData->d_loan_created_at;

            // проверяем на наличие доп. соглашения и если есть - меняем даты
            /*$repl_loan = DB::Table('armf.loans')->select(DB::raw('*'))->where('id_1c', $debtorTmp->loan_id_1c)->first();
            if (!is_null($repl_loan)) {
                $repl_repayment = DB::Table('armf.repayments')->select(DB::raw('*'))->where('loan_id', $repl_loan->id)->whereIn('repayment_type_id', [1, 7, 8, 9, 10, 17, 20])->orderBy('created_at', 'desc')->first();
                if (!is_null($repl_repayment)) {
                    if ($repl_loan->in_cash == 1) {
                        $repayment_in_cash = true;
                        $repayment_return = date('Y-m-d H:i:s', strtotime($repl_repayment->created_at . ' +1 day'));
                    } else {
                        $objDebtorData->d_loan_created_at = date('Y-m-d H:i:s', strtotime($repl_repayment->created_at . ' +1 day'));
                        $repayment_return = $objDebtorData->d_loan_created_at;
                    }

                    $return_date = date('d.m.Y', strtotime("+" . $repl_repayment->time - 1 . " days", strtotime($repayment_return)));
                }
            }*/

            if (mb_strlen($date_personal)) {
                $ar_req_date = explode(' ', StrUtils::dateToStr($date_personal));
            }

            $req_day = (isset($ar_req_date)) ? $ar_req_date[0] : '';
            $req_month = (isset($ar_req_date)) ? $ar_req_date[1] : '';
            $req_year = (isset($ar_req_date)) ? $ar_req_date[2] : '';

            $arVidTruda = [
                'Официальное',
                'Неофициальное',
                'Пенсионер работающий',
                'Пенсионер неработающий',
                'Льготная категория',
                'Домохозяйка',
                'Студент',
            ];

            $arParams = [
                'print_date' => date('d.m.Y', time()),
                'debtor_fio' => $objDebtorData->fio,
                'debtor_birth_date' => $debtor_birth_date,
                'p_series' => $objDebtorData->series,
                'p_number' => $objDebtorData->number,
                'issued_date' => StrUtils::dateToStr($objDebtorData->issued_date),
                'issued' => $objDebtorData->issued,
                'subdivision_code' => $objDebtorData->subdivision_code,
                'address_reg_date' => StrUtils::dateToStr($objDebtorData->address_reg_date),
                'ur_address' => $ur_address,
                'fact_address' => $fact_address,
                'loan_id_1c' => $objDebtorData->d_loan_id_1c,
                //'loan_created_at' => StrUtils::dateToStr($objDebtorData->d_loan_created_at),
                'loan_created_at' => StrUtils::dateToStr($objDebtorData->this_loan_created_at),
                'money' => $od,
                'time' => $objDebtorData->d_loan_time,
                'loan_percent' => $loan_percent,
                'loan_name' => $objDebtorData->d_loan_name,
                'claim_created_at' => date('d.m.Y', strtotime($objDebtorData->d_claim_created_at)),
                'return_date' => $return_date,
                'sum_indebt' => $sum_indebt,
                'return_sum' => number_format(($objDebtorData->money + $objDebtorData->money * ($loan_percent / 100) * $objDebtorData->d_loan_time),
                    2, '.', ' '),
                'organizacia' => $objDebtorData->organizacia,
                'adresorganiz' => $objDebtorData->adresorganiz,
                'dolznost' => $objDebtorData->dolznost,
                'vidtruda' => (isset($arVidTruda[$objDebtorData->vidtruda])) ? $arVidTruda[$objDebtorData->vidtruda] : 'Не определено',
                'telephoneorganiz' => $objDebtorData->telephoneorganiz,
                'fiorukovoditel' => $objDebtorData->fiorukovoditel,
                'dohod' => number_format($objDebtorData->dohod, 0, '.', ' '),
                'dopdohod' => number_format($objDebtorData->dopdohod, 0, '.', ' '),
                'dohod_husband' => (!is_null($objDebtorData->dohod_husband)) ? number_format($objDebtorData->dohod_husband,
                    0, '.', ' ') : 0,
                'pension' => (!is_null($objDebtorData->pension)) ? number_format($objDebtorData->pension, 0, '.',
                    ' ') : 0,
                'live_condition' => \App\LiveCondition::getLiveConditionById($objDebtorData->zhusl),
                'birth_city' => $passport['birth_city'],
                'deti' => $objDebtorData->deti,
                'fiosuprugi' => $objDebtorData->fiosuprugi,
                'fioizmena' => $objDebtorData->fioizmena,
                'telephonehome' => $objDebtorData->telephonehome,
                'telephone' => $objDebtorData->telephone,
                'anothertelephone' => $objDebtorData->anothertelephone,
                'telephonerodstv' => $objDebtorData->telephonerodstv,
                'stepenrodstv' => \App\Stepenrodstv::getStepenById($objDebtorData->stepenrodstv),
                'credit' => $objDebtorData->credit,
                'od' => $od,
                'pc' => $pc,
                'exp_pc' => $exp_pc,
                'fine' => $fine,
                'sum_pc' => $sum_pc,
                'qty_delays' => $objDebtorData->qty_delays,
                'comment' => $objDebtorData->comment,
                'overdue_start' => date('d.m.Y', strtotime("+1 day", strtotime($return_date))),
                'spec_surname' => $spec_surname,
                'spec_io' => $spec_io,
                'spec_fio' => $objDebtorData->spec_fio,
                'spec_phone' => $objDebtorData->spec_phone,
                'spec_doc' => $objDebtorData->doc,
                'od_rub' => $arOd[0],
                'od_kop' => isset($arOd[1]) ? $arOd[1] : '00',
                'pc_rub' => $arPc[0],
                'pc_kop' => isset($arPc[1]) ? $arPc[1] : '00',
                'exp_pc_rub' => $arExp_pc[0],
                'exp_pc_kop' => isset($arExp_pc[1]) ? $arExp_pc[1] : '00',
                'fine_rub' => $arFine[0],
                'fine_kop' => isset($arFine[1]) ? $arFine[1] : '00',
                'sum_indebt_rub' => $arSumIndebt[0],
                'sum_indebt_kop' => isset($arSumIndebt[1]) ? $arSumIndebt[1] : '00',
                'pc_fact_rub' => $arFactPc[0],
                'pc_fact_kop' => isset($arFactPc[1]) ? $arFactPc[1] : '00',
                'date_personal' => $date_personal,
                'date_suspended' => date('d.m.Y', strtotime("+10 day", strtotime($date_personal))),
                'print_address' => $print_address,
                'tranche_data' => '',
                'req_day' => $req_day,
                'req_month' => $req_month,
                'req_year' => $req_year,
                'fact_pc' => $fact_pc,
                'fullpay_sum' => isset($fullpay_sum) ? $fullpay_sum : '',
                'fullpay_od_sum' => isset($fullpay_od_sum) ? $fullpay_od_sum : '',
                'fullpay_percents_sum' => isset($fullpay_pc_sum) ? $fullpay_pc_sum + $fullpay_exp_pc_sum : '',
                'fullpay_fine_sum' => isset($fullpay_fine_sum) ? $fullpay_fine_sum : ''
            ];

            $arParams['tranche_data'] = '';
            $arParams['dop_string'] = '';

            if (mb_strlen($arParams['spec_phone']) < 6) {
                $arParams['spec_phone'] = '88003014344';
            }

            if (isset($repayment_in_cash) && $repayment_in_cash) {
                $arParams['dop_string'] = 'Дополнительное соглашение от ' . date('d.m.Y',
                        strtotime($repl_repayment->created_at)) . ', ';
            }

            //$lids = $this->getLoanIdFromArm($objDebtorData->loan_id_1c, $objDebtorData->customer_id_1c);
            //if(is_array($lids) && count($lids)>0){

            /*$repl_tranche_data = DB::Table('armf.loans')->select(DB::raw('*'))
                    ->where('loans.id_1c', $objDebtorData->loan_id_1c)
                    ->where('loans.in_cash', 0)
                    ->select(['loans.tranche_number', 'loans.first_loan_date', 'loans.first_loan_id_1c'])
                    ->first();
            if (!is_null($repl_tranche_data) && !empty($repl_tranche_data->first_loan_id_1c)) {
                $arParams['loan_created_at'] = StrUtils::dateToStr($repl_tranche_data->first_loan_date);
                $arParams['loan_id_1c'] = $repl_tranche_data->first_loan_id_1c;
                if (!is_null($repl_repayment)) {
                    $arParams['dop_string'] = 'Дополнительное соглашение от ' . $arParams['loan_created_at'] . ', ';
                }
                //$arParams['tranche_data'] = ' (транш № ' . str_pad($repl_tranche_data->tranche_number, 3, '0', STR_PAD_LEFT) . ' к договору №' . $repl_tranche_data->first_loan_id_1c . ' от ' . with(new Carbon($repl_tranche_data->first_loan_date))->format('d.m.Y') . ' г.)';
                if (!is_null($repl_tranche_data->tranche_number) && mb_strlen($repl_tranche_data->tranche_number) > 0) {
                    $arParams['tranche_data'] = ' (транш № ' . str_pad($repl_tranche_data->tranche_number, 3, '0', STR_PAD_LEFT) . ' от ' . StrUtils::dateToStr($objDebtorData->this_loan_created_at) . ')';
                }
            }*/
            //}

            $arParams['dop_string'] = '';
            $arParams['tranche_data'] = '';

            if ($debtorTmp->is_bigmoney == 1) {
                //$repl_loan = DB::Table('armf.loans')->where('id_1c', $debtorTmp->loan_id_1c)->first();

                $postData = [
                    'loan_id_1c' => $debtorTmp->loan_id_1c,
                    'customer_id_1c' => $debtorTmp->customer_id_1c,
                    'date' => date('Y-m-d', time())
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://192.168.35.59/ajax/loan/get/debt');
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);

                $arResult = json_decode($result, true);

                if (isset($arResult['pays'])) {
                    $days_overdue = 0;
                    foreach ($arResult['pays'] as $payment) {
                        if ($payment['expired'] == 1 && $payment['closed'] == 0) {
                            $arBigMoneyExpDates[] = date('d.m.Y', strtotime($payment['date'])) . ' г.';
                            $days_overdue += $payment['days_overdue'];
                        }
                    }

                    $arParams['expBigMoneyDates'] = implode(',', $arBigMoneyExpDates);
                    $arParams['days_overdue'] = $days_overdue;
                }
            }

            if (str_contains($objDebtorData->loan_id_1c, 'ККЗ')) {
                if ($date == '0') {
                    $date_cc = date('d.m.Y', time());
                } else {
                    $date_cc = date('d.m.Y', strtotime($date));
                }

                $json_string_cc = file_get_contents('http://192.168.35.54:8020/api/v1/loans/' . $objDebtorData->loan_id_1c . '/schedule/' . $date_cc);
                $arDataCc = json_decode($json_string_cc, true);

                $arParams['loan_end_at_cc'] = date('d.m.Y', strtotime($arDataCc['data']['loan_end_at']));
                $arParams['overdue_mop_cc'] = ($arDataCc['data']['overdue_debt'] + $arDataCc['data']['overdue_percent'] + $arDataCc['data']['fine']) / 100;
                $arParams['overdue_debt_cc'] = $arDataCc['data']['overdue_debt'] / 100;
                $arParams['overdue_percent_cc'] = $arDataCc['data']['overdue_percent'] / 100;
                $arParams['overdue_fine_cc'] = $arDataCc['data']['fine'] / 100;

                $arParams['total_debt_cc'] = $arDataCc['data']['total_debt'] / 100;
                $arParams['debt_cc'] = ($arDataCc['data']['debt'] + $arDataCc['data']['mop_debt'] + $arDataCc['data']['overdue_debt']) / 100;
                $arParams['percent_cc'] = ($arDataCc['data']['percent'] + $arDataCc['data']['mop_percent'] + $arDataCc['data']['overdue_percent']) / 100;

                $arParams['current_percent_cc'] = ($arDataCc['data']['percent'] + $arDataCc['data']['mop_percent']) / 100;
                $arParams['current_debt_cc'] = ($arDataCc['data']['debt'] + $arDataCc['data']['mop_debt']) / 100;
            }

            if (mb_strlen($html) > 200) {
//                if(Auth::user()->id==5){
                //$claim_id = DB::connection('arm')->table('loans')->whereIn('loans.id', $this->getLoanIdFromArm($objDebtorData->loan_id_1c, $objDebtorData->customer_id_1c))->lists('claim_id');
                /* $photos = DB::connection('arm')->table('photos')->whereIn('claim_id', $claim_id)->get();
                  //                } else {
                  //                    $photos = DB::connection('arm')->table('photos')->where('claim_id', $objDebtorData->claim_id)->get();
                  //                    $photos = [];
                  //                }
                  $photos = json_decode(json_encode($photos), true);
                  $arPhotos = [];
                  $mainPhoto = null;
                  //if (!config('app.dev')) {
                  foreach ($photos as $photo) {
                  if (Storage::exists($photo['path'])) {
                  $photo['src'] = url($photo['path']);
                  $arPhotos[] = $photo;
                  if ($photo['is_main'] == 1) {
                  $mainPhoto = $photo;
                  }
                  }
                  }
                  if (is_null($mainPhoto)) {
                  $mainPhoto = (isset($arPhotos[0])) ? $arPhotos[0] : null;
                  }
                  if (!is_null($mainPhoto)) {
                  $pic = '<img style="width: 350px;" src="data:image/' . pathinfo(url($mainPhoto['path']), PATHINFO_EXTENSION)
                  . ';base64,' . base64_encode(Storage::get($mainPhoto['path'])) . '">';
                  } else {
                  $pic = '';
                  } */
                //}
                $pic = '';
                $arParams['pic'] = $pic;

                foreach ($arParams as $param => $value) {
                    $html = str_replace("{{" . $param . "}}", $value, $html);
                }

                return Utils\PdfUtil::getPdf($html);
            }

            $cUser = auth()->user();

            if ($doc_id == 140 || $doc_id == 141 || $doc_id == 144 || $doc_id == 145 || $doc_id == 146 || $doc_id == 147 || $doc_id == 148 || $doc_id == 149) {
                $userHeadChief = User::find(951);
                $arParams['headchief_doc'] = $userHeadChief->doc;

                $currentUser = auth()->user();
                $number_postfix = '';
                if ($currentUser->hasRole('debtors_remote')) {
                    $number_postfix = 'УВ';
                }
                if ($currentUser->hasRole('debtors_personal')) {
                    $number_postfix = 'ЛВ';
                }

                if ($cUser->id != 69) {
                    $notice_number = NoticeNumbers::addRecord($doc_id, $debtor_id, $factAddress);
                    if (!$notice_number) {
                        die('Не удалось сформировать исходящий номер. Обратитесь к программистам.');
                    }
                } else {
                    $notice_number = new \stdClass();
                    $notice_number->new_notice = false;
                    $notice_number->id = '123456';
                }

                if ($doc_id == 144 || $doc_id == 145 || $doc_id == 148 || $doc_id == 149) {
                    if ($cUser->id == 916) {
                        $arParams['req_spec_position'] = 'Ведущий специалист';
                        $arParams['spec_fio'] = 'Свиридов Павел Владимирович';
                    } else {
                        if ($cUser->hasRole('debtors_chief') && $cUser->hasRole('debtors_personal')) {
                            $arParams['req_spec_position'] = 'Старший специалист';
                        } else {
                            $arParams['req_spec_position'] = 'Специалист';
                        }
                    }
                }

                $address_type_txt = ($factAddress) ? 'проживания' : 'регистрации';

                if ($notice_number->new_notice && $currentUser->id != 69) {
                    $doctype = ($doc_id == 144 || $doc_id == 145 || $doc_id == 148 || $doc_id == 149) ? 'требование' : 'уведомление';

                    $pdfEvent = new DebtorEvent();
                    $pdfEvent->customer_id_1c = $debtorTmp->customer_id_1c;
                    $pdfEvent->loan_id_1c = $debtorTmp->loan_id_1c;
                    $pdfEvent->event_type_id = ($number_postfix == 'УВ') ? 13 : 20;
                    $pdfEvent->debt_group_id = $debtorTmp->debt_group_id;
                    $pdfEvent->overdue_reason_id = 0;
                    $pdfEvent->event_result_id = ($number_postfix == 'УВ') ? 9 : 27;
                    $pdfEvent->report = 'Отправлено ' . $doctype . ' по договору ' . $loan->id_1c . ' от ' . date('d.m.Y',
                            strtotime($objDebtorData->d_loan_created_at)) . ' г. Исх. № ' . $notice_number->id . '/' . $number_postfix . ' от ' . date('d.m.Y',
                            strtotime($notice_number->created_at)) . ' по адресу ' . $address_type_txt . ': ' . $print_address;
                    $pdfEvent->debtor_id = $debtorTmp->id;
                    $pdfEvent->user_id = $currentUser->id;
                    $pdfEvent->last_user_id = $currentUser->id;
                    $pdfEvent->completed = 1;
                    $pdfEvent->debtor_id_1c = $debtorTmp->debtor_id_1c;
                    $pdfEvent->user_id_1c = $currentUser->id_1c;
                    $pdfEvent->refresh_date = date('Y-m-d H:i:s', time());

                    $pdfEvent->save();
                }

                $postfix_number_doc = '';

                if ($doc_id == 144 && $number_postfix == 'УВ') {
                    $postfix_number_doc = '/УВ';
                }

                $arParams['notice_number'] = $notice_number->id . $postfix_number_doc;
                $arParams['date_sent'] = $arParams['print_date'];

                /* if ($cUser->id == 69) {
                  $arParams['req_spec_position'] = 'Cпециалист';
                  $arParams['spec_fio'] = 'Гензе Виталий Владимирович';
                  $arParams['spec_phone'] = '‭+79034101149‬';
                  } */

                /*if ($debtorTmp->id == 170278772) {
                  $arParams['req_spec_position'] = 'Специалист';
                  $arParams['spec_fio'] = 'Литасов Виктор Владимирович';
                  $arParams['spec_phone'] = '89039846339';

                  $arParams['spec_doc'] = '72/22р от 31 марта 2022 г';
                  //$arParams['req_spec_position'] = 'Руководитель';
                  //$arParams['loan_id_1c'] = '00001198819-007';
                  //$arParams['loan_created_at'] = '14 февраля 2019 г.';

                  $arParams['date_sent'] = '25.08.2022';
                  $arParams['print_date'] = '25.08.2022';
                  $arParams['notice_number'] = '812505';
                  }*/
            }

            return \App\Utils\FileToPdfUtil::replaceKeys($doc->tplFileName, $arParams, 'debtors');
        }
    }

    /**
     * История изменений
     * @param integer $id
     * @return type
     */
    public function debtorLogs($id)
    {
        $debtor = Debtor::find($id);
        if (is_null($debtor)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $data = $debtor->getAllDebtorLogs();
        foreach ($data as $d) {
            \PC::debug([$d->before, $d->after]);
        }
        \PC::debug($data);
        return view('debtors.logs', [
            'debtor_id' => $id,
            'logs' => $data
        ]);
    }

    /**
     * Страница прочих контактов
     * @param integer $id
     * @return type
     */
    public function contacts($id)
    {
        $debtor = Debtor::find($id);
        if (is_null($debtor)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }

        return view('debtors.contacts', [
            'contacts' => $debtor->getContactsData(),
            'debtor_id' => $id
        ]);
    }

    /**
     * Возвращает html-код для вставки в блок фотогорафий в карточке должника
     * @param string $claim_id
     * @return string
     */
    public function loadPhoto($claim_id)
    {
        // получаем фотографии, связанные с анкетой заемщика
//        $photos = Photo::select(DB::raw('*'))
//                ->where('claim_id', $claim_id);
        $loan = Loan::where('claim_id', $claim_id)->first();
        if (!is_null($loan) && !is_null($loan->claim) && !is_null($loan->claim->customer)) {
            $customer = $loan->claim->customer;
            $claim_id = DB::connection('arm')->table('loans')->whereIn('loans.id',
                $this->getLoanIdFromArm($loan->id_1c, $customer->id_1c))->lists('claim_id');
            $photos = DB::connection('arm')->table('photos')->whereIn('claim_id', $claim_id);
            $arDataPhotos = $photos->get();
            $arPhotos = [];

            \PC::debug($loan);

            //проверяем "физическое" наличие фото в хранилищах
            if (!config('app.dev')) {
                foreach ($arDataPhotos as $photo) {
//                    if(!is_null(Auth::user()) && Auth::user()->id==5){
                    if (with(new Carbon($photo->created_at))->gte(new Carbon('2017-12-22 16:00:00'))) {
                        $disk = 'ftp31';
                        $path = 'images/' . $photo->path;
                    } else {
                        if (with(new Carbon($photo->created_at))->gte(new Carbon('2017-04-26'))) {
                            $disk = 'ftp31_999';
                            $path = 'images/' . $photo->path;
                        } else {
                            if (with(new Carbon($photo->created_at))->gte(new Carbon('2017-04-07'))) {
                                $disk = 'ftp125';
                                $path = $photo->path;
                            } else {
                                $disk = 'ftp';
                                $path = $photo->path;
                            }
                        }
                    }
                    $photo->src = url($path);
                    $arPhotos[] = json_decode(json_encode($photo), true);
//                    } else {
//                        if (\App\Utils\HelperUtil::FtpFileExists($photo->path)) {
//                            $photo->src = url($photo->path);
//                            $arPhotos[] = json_decode(json_encode($photo), true);
//                        } else {
//                            if (Storage::exists($photo->path)) {
//                                $photo->src = url($photo->path);
//                                $arPhotos[] = json_decode(json_encode($photo), true);
//                            }
//                        }
//                    }
                }
            } else {
                $arPhotos = [];
            }
        }

        return view('elements.debtors.photoBlock', [
            'photos' => $arPhotos
        ]);
    }

    /**
     * Меняет метку запланированного выезда к должнику
     * @param string $debtor_id
     * @param string $action
     * @return int
     */
    public function changePlanDeparture($debtor_id, $action)
    {
        if ($action == 'add') {
            PlannedDeparture::addPlanDeparture($debtor_id);
        }

        if ($action == 'remove') {
            PlannedDeparture::removePlanDeparture($debtor_id);
        }

        return 1;
    }

    /**
     * Меняет флаг для различных отказов от взаимодействия должника
     * @param string $debtor_id
     * @param string $action
     * @return int
     */
    public function changePersonalData($debtor_id, $action)
    {
        if (strpos($action, 'on_') === 0) {
            $fieldname = substr($action, 3);
            $val = 1;
        } else {
            if (strpos($action, 'off_') === 0) {
                $fieldname = substr($action, 4);
                $val = 0;
            } else {
                return 0;
            }
        }

        $debtor = Debtor::find($debtor_id);
        if (!is_null($debtor)) {
            $debtor->{$fieldname} = $val;
            $debtor->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
            $debtor->save();
        } else {
            return 0;
        }

        return 1;
    }

    /**
     * Изменяет значения рекомендаций для специалистов: сохранение, редактирование, удаление
     * @param Request $req
     * @return int
     */
    public function changeRecommend(Request $req)
    {
        $currentUser = auth()->user();

        $input = $req->input();

        $debtor = Debtor::find($input['debtor_id']);

        if (!is_null($debtor)) {
            switch ($input['action']) {
                case 'save':
                case 'edit':
                    $debtor->recommend_created_at = Carbon::now()->format('Y-m-d H:i:s');
                    $debtor->recommend_text = $input['text'];
                    $debtor->recommend_completed = 0;
                    $debtor->recommend_user_id = $currentUser->id;
                    $debtor->save();
                    break;

                case 'remove':
                    $debtor->recommend_created_at = null;
                    $debtor->recommend_text = null;
                    $debtor->recommend_completed = 0;
                    $debtor->recommend_user_id = null;
                    $debtor->save();
                    break;

                case 'complete':
                    $debtor->recommend_completed = 1;
                    $debtor->save();
                    break;

                default:
                    break;
            }

            $debtor->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
        }

        return 1;
    }

    /**
     * Карта планирования выездов к должникам
     * @return resource
     */
    public function departureMap()
    {
        $cols = [
            'debtors.passports.address_region',
            'debtors.passports.address_district',
            'debtors.passports.address_city',
            'debtors.passports.address_street',
            'debtors.passports.address_building',
            'debtors.passports.address_apartment',
            'debtors.debtors_geocodes.geocode',
            'debtors.passports.fio',
            'debtors.debtors.debtor_id_1c',
            'debtors.planned_departures.debtor_id as planned',
            'debtors.debtors.id as debtor_id',
            'debtors.debtor_events.completed as completed',
            'debtors.debtor_events.event_type_id as event_type_id'
        ];
//        $debtors = Debtor::select($cols)
//                ->leftJoin('armf.loans', 'armf.loans.id_1c', '=', 'debtors.loan_id_1c')
//                ->leftJoin('armf.claims', 'armf.claims.id', '=', 'armf.loans.claim_id')
//                ->leftJoin('armf.customers', 'armf.claims.customer_id', '=', 'armf.customers.id')
//                ->leftJoin('armf.passports', 'armf.passports.id', '=', 'armf.claims.passport_id')
//                ->limit(50)
//                ->whereNotNull('armf.passports.address_region')
//                ->groupBy('debtors.id')
//                ->get();
//        \PC::debug($debtors);
//        return view('debtors.departuremap', [
//            'debtors' => json_encode($debtors)
//        ]);

        $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
        $usersDebtors = User::select('users.id_1c')
            ->whereIn('id', $arResponsibleUserIds);

        $arUsersDebtors = $usersDebtors->get()->toArray();
        $arIn = [];
        foreach ($arUsersDebtors as $tmpUser) {
            $arIn[] = $tmpUser['id_1c'];
        }

        if (!count($arIn)) {
            die("Нет ответственных пользователей.");
        }

        $debtor_vars = config('debtors');

        $debtorsGeo = Debtor::select(array(
            'debtors.debtors.id as d_id',
            'debtors.passports.address_region as region',
            'debtors.passports.address_district as district',
            'debtors.passports.address_city as city',
            'debtors.passports.address_street as street',
            'debtors.passports.address_house as house'
        ))
            ->leftJoin('debtors.debtors_geocodes', 'debtors.debtors_geocodes.debtor_id', '=', 'debtors.debtors.id')
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.passports', 'debtors.passports.id', '=', 'debtors.claims.passport_id')
            ->whereIn('debtors.responsible_user_id_1c', $arIn)
            ->whereNotNull('debtors.passports.address_region')
            ->get();

        foreach ($debtorsGeo as $debtor) {
            if (!DebtorGeocode::geocodeExists($debtor->d_id)) {
                $arAddress = [];
                if (!is_null($debtor->region) && mb_strlen($debtor->region)) {
                    $arAddress[0] = $debtor->region;
                } else {
                    continue;
                }
                if (!is_null($debtor->district) && mb_strlen($debtor->district)) {
                    $arAddress[1] = $debtor->district;
                }
                if (!is_null($debtor->city) && mb_strlen($debtor->city)) {
                    $arAddress[2] = $debtor->city;
                } else {
                    continue;
                }
                if (!is_null($debtor->street) && mb_strlen($debtor->street)) {
                    $arAddress[3] = $debtor->street;
                }
                if (!is_null($debtor->house) && mb_strlen($debtor->house)) {
                    $arAddress[4] = $debtor->house;
                } else {
                    continue;
                }
                $address_string = implode(', ', $arAddress);
                $address_string = str_replace(' ', '+', $address_string);

                $xml = simplexml_load_string(file_get_contents('https://geocode-maps.yandex.ru/1.x/?geocode=' . $address_string));
                if (!isset($xml->GeoObjectCollection->featureMember)) {
                    continue;
                }
                $point = $xml->GeoObjectCollection->featureMember->GeoObject->Point->pos;

                if (!is_null($point)) {
                    $arPoint = explode(' ', $point);
                    $point = $arPoint[1] . ',' . $arPoint[0];
                }

                DebtorGeocode::addGeocode($debtor->d_id, (!is_null($point)) ? $point : 'n/a');
            }
        }

        $today = with(new Carbon())->today();

        $debtors = Debtor::select($cols)
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.customers', 'debtors.claims.customer_id', '=', 'debtors.customers.id')
            ->leftJoin('debtors.passports', 'debtors.passports.id', '=', 'debtors.claims.passport_id')
            ->leftJoin('debtors.debtors_geocodes', 'debtors.debtors_geocodes.debtor_id', '=', 'debtors.debtors.id')
            ->leftJoin('debtors.planned_departures', 'debtors.planned_departures.debtor_id', '=', 'debtors.debtors.id')
            ->leftJoin('debtors.debtor_events', 'debtors.debtor_events.debtor_id', '=', 'debtors.debtors.id')
            ->whereIn('debtors.responsible_user_id_1c', $arIn)
            ->whereNotNull('debtors.passports.address_region')
            ->where('debtors.debtors_geocodes.geocode', '<>', 'n/a')
            ->groupBy('debtors.id')
            ->get()
            ->toArray();

        foreach ($debtors as $k => $debtor) {
            $row = DebtorEvent::select('*')
                ->leftJoin('debtors', 'debtors.id', '=', 'debtor_events.debtor_id')
                ->leftJoin('planned_departures', 'planned_departures.debtor_id', '=', 'debtors.id')
                ->whereNotNull('planned_departures.debtor_id')
                ->where('completed', 1)
                ->whereIn('event_type_id', array(0, 1, 2, 3))
                ->where('debtor_events.debtor_id', $debtor['debtor_id'])
                ->groupBy('planned_departures.debtor_id')
                ->first();
            //$row = DebtorEvent::where('completed', 1)->whereIn('event_type_id', array(0, 1, 2, 3))->where('debtor_id', $debtor->id)->first();

            if (!is_null($row)) {
                $debtors[$k]['execDeparture'] = true;
            } else {
                $debtors[$k]['execDeparture'] = false;
            }
        }

        \PC::debug($debtors);
        return view('debtors.departuremap', [
            'debtors' => json_encode($debtors)
        ]);
    }

    /**
     * Печать анкет и уведомлений для запланированных к выезду должникам
     * @return resource
     */
    public function departurePrint()
    {
        $cols = [
            'debtors.passports.fio',
            'debtors.debtors.debtor_id_1c',
            'debtors.planned_departures.debtor_id as planned',
            'debtors.debtors.id as debtor_id',
            'debtors.loans.id_1c as loan_id_1c',
            'debtors.customers.id_1c as customer_id_1c'
        ];

        $today = with(new Carbon())->today();

        $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
        $usersDebtors = User::select('users.id_1c')
            ->whereIn('id', $arResponsibleUserIds);

        $arUsersDebtors = $usersDebtors->get()->toArray();
        $arIn = [];
        foreach ($arUsersDebtors as $tmpUser) {
            $arIn[] = $tmpUser['id_1c'];
        }

        if (!count($arIn)) {
            die("Нет ответственных пользователей.");
        }

        $debtors = Debtor::select($cols)
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.customers', 'debtors.claims.customer_id', '=', 'debtors.customers.id')
            ->leftJoin('debtors.passports', 'debtors.passports.id', '=', 'debtors.claims.passport_id')
            ->leftJoin('debtors.planned_departures', 'debtors.planned_departures.debtor_id', '=', 'debtors.debtors.id')
            ->whereNotNull('debtors.planned_departures.debtor_id')
            ->whereIn('debtors.responsible_user_id_1c', $arIn)
            ->whereBetween('debtors.planned_departures.created_at', array(
                $today->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $today->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            ))
            ->groupBy('debtors.id')
            ->get();

        $arContractFormsIds = [
            'anketa' => \App\ContractForm::getContractIdByTextId('debtors_anketa'),
            'notice_personal' => \App\ContractForm::getContractIdByTextId('debtors_notice_personal'),
            'notice_remote' => \App\ContractForm::getContractIdByTextId('debtors_notice_remote')
        ];

        return view('debtors.departureprint', [
            'debtors' => $debtors,
            'contractforms' => $arContractFormsIds
        ]);
    }

    public function uploadOrdersFrom1c(Request $req)
    {
        $debtor = Debtor::find($req->get('debtor_id'));
        if (is_null($debtor)) {
            return 0;
        }
        $debtors = Debtor::select(['passport_series', 'passport_number', 'customer_id_1c', 'loan_id_1c'])->where('id',
            $req->get('debtor_id'))->get();
        $json = \App\Utils\HelperUtil::SendPostByCurl(config('admin.sales_arm_url') . '/debtors/orders/upload',
            ['data' => json_encode($debtors)]);
        $orders = json_decode($json);
        \PC::debug($orders, 'orders');
        \PC::debug($json, 'json');
        $res = [];
        $i = 1;
        foreach ($orders as $o) {
            $item = [];
            $item['number'] = $i;
            $item['created_at'] = with(new Carbon($o->created_at))->format('d.m.Y');
            $item['doc'] = $o->reason;
            $item['outcome'] = (\App\OrderType::find($o->type)->plus) ? '' : number_format($o->money / 100, 2, '.',
                    '') . ' руб.';
            $item['income'] = (\App\OrderType::find($o->type)->plus) ? number_format($o->money / 100, 2, '.',
                    '') . ' руб.' : '';
            $item['purpose'] = (is_null($o->purpose) || !array_key_exists($o->purpose,
                    Order::getPurposeNames())) ? '' : Order::getPurposeNames()[$o->purpose];
            $i++;
            $res[] = $item;
        }
        return json_encode($res);
    }

    public function refreshTotalEventTable(Request $req)
    {
        $id1c = $req->get('user_id_1c');
        if (is_null($id1c) || !mb_strlen($id1c)) {
            return view('elements.debtors.totalEventsTable', [
                'event_types' => config('debtors.event_types'),
                'total_debtor_events' => DebtorEvent::getPlannedForUser(Auth::user(), Carbon::today()->subDays(15), 30)
            ]);
        }

        $user = User::where('id_1c', $id1c)->first();

        if (!$user) {
            return 0;
        }

        return view('elements.debtors.totalEventsTable', [
            'event_types' => config('debtors.event_types'),
            'total_debtor_events' => DebtorEvent::getPlannedForUser($user, Carbon::today()->subDays(15), 30)
        ]);
    }

    public function refreshOverallTable(Request $req)
    {
        $id1c = $req->get('user_id_1c');
        if (is_null($id1c) || !mb_strlen($id1c)) {
            return 0;
        }

        $user = User::where('id_1c', $id1c)->first();

        if (!$user) {
            return 0;
        }

        return view('elements.debtors.overallEventsTable', [
            'event_types' => config('debtors.event_types'),
            'debtorsOverall' => Debtor::getOverall($user)
        ]);
    }

    /**
     * Экспорт должников в эксель
     * @param Request $req
     * @return string
     */
    public function exportToExcel(Request $req)
    {
        \PC::debug($req->input());
        $query = $this->getDebtorsQuery($req, true);
        $query->orderBy('passports_fio');
        $debtors = $query->get();
        \PC::debug($debtors);
        $html = '<table>';
        $firstRow = true;
        $colHeaders = [
            'debtors_fixation_date' => 'Дата закрепления',
            'passports_fio' => 'ФИО должника',
            'debtor_customer_id_1c' => 'Код контрагента',
            'debtors_loan_id_1c' => 'Номер договора',
            'debtors_qty_delays' => 'Срок просрочки',
            'debtors_sum_indebt' => 'Сумма задолженности',
            'debtors_od' => 'Сумма ОД',
            'debtors_base' => 'База',
            'debtor_with_schedule' => 'Тип договора',
            // col_num = 9 - getDebtorsQuery: customers_telephone - 9-й по счету
            'debtor_is_online' => 'Онлайн',
            'customers_telephone' => 'Телефон',
            'debtors_debt_group_id' => 'Группа долга',
            'debtors_username' => 'ФИО специалиста',
            'debtor_str_podr' => 'Стр. подр.',
            'debtor_address' => 'Юр. адрес',
            'debtor_fact_address' => 'Факт. адрес',
            'sp1' => '',
            'sp2' => '',
            'sp3' => '',
            'sp4' => '',
            'sp5' => '',
            'sp6' => '',
            'sp7' => '',
            'passports_fact_timezone' => 'Разница времени'
        ];
        $html .= '<thead>';
        $html .= '<tr>';
        foreach ($colHeaders as $k => $v) {
            $html .= '<th>' . $v . '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $arDebtGroups = \App\DebtGroup::getDebtGroups();
        foreach ($debtors as $debtor) {
            $debtorArray = $debtor->toArray();
            $html .= '<tr>';
            $col_num = 1;
            foreach ($debtorArray as $k => $v) {
                if ($col_num == 9) {
                    if ($debtorArray['debtor_is_pos'] == 1) {
                        $html .= '<td>Товарный</td>';
                    } else {
                        if ($debtorArray['debtor_is_bigmoney'] == 1) {
                            $html .= '<td>Б. деньги</td>';
                        } else {
                            if ($debtorArray['debtor_is_pledge'] == 1) {
                                $html .= '<td>Залоговый</td>';
                            } else {
                                $html .= '<td></td>';
                            }
                        }
                    }
                    if ($debtorArray['debtor_is_online'] == 1) {
                        $html .= '<td>Да</td>';
                    } else {
                        $html .= '<td>Нет</td>';
                    }
                    if ($k == 'customers_telephone') {
                        $html .= '<td>' . $v . '</td>';
                        $col_num++;
                        continue;
                    }
                }
                if ($k == 'customers_telephone') {
                    continue;
                }
                if ($k == 'passport_id') {
                    $passport = Passport::find($v);
                    if (is_null($passport)) {
                        continue;
                    }
                    $debtor_address = Passport::getFullAddress($passport);
                    $debtor_fact_address = Passport::getFullAddress($passport, true);

                    $html .= '<td>' . $debtor_address . '</td>';
                    $html .= '<td>' . $debtor_fact_address . '</td>';
                    $col_num++;
                    continue;
                }
                if ($k == 'debtors_od' || $k == 'debtors_sum_indebt') {
                    if ($k == 'debtors_od' && isset($debtorArray['debtors_od_after_closing']) && !is_null($debtorArray['debtors_od_after_closing']) && $debtorArray['debtors_od_after_closing'] != 0) {
                        $v = $debtorArray['debtors_od_after_closing'];
                    }
                    $html .= '<td>' . StrUtils::kopToRub($v) . '</td>';
                } else {
                    if (in_array($k, ['debtors_id', 'debtor_id_1c'])) {

                    } else {
                        if (in_array($k, ['debtors_fixation_date'])) {
                            $html .= '<td>' . with(new Carbon($v))->format('d.m.Y') . '</td>';
                        } else {
                            if ($k == 'debtors_debt_group') {
                                $html .= '<td>' . ((array_key_exists($v,
                                        $arDebtGroups)) ? $arDebtGroups[$v] : '') . '</td>';
                            } else {
                                if ($k == 'debtor_with_schedule') {

                                } else {
                                    $html .= '<td>' . $v . '</td>';
                                }
                            }
                        }
                    }
                }

                $col_num++;
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

//        return $html;

        $file = "report.xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
            ->header("Content-type", "application/vnd.ms-excel")
            ->header("Content-Disposition", "attachment; filename=$file");
    }

    /**
     * TODO: Refactoring [too much more equal code]
     */

    /**
     * Экспорт мероприятний в Excel
     * @param Request $req
     * @return string
     */
    public function exportEventsToExcel(Request $req)
    {
        $cols = [];
        $tCols = [
            'debtor_events.date' => 'de_date',
            'debtor_events.event_type_id' => 'de_type_id',
            'debtors.passports.fio' => 'passports_fio',
            'debtor_events.created_at' => 'de_created_at',
            'users.login' => 'de_username',
            'debtors.id' => 'debtors_id'
        ];

        foreach ($tCols as $k => $v) {
            $cols[] = $k . ' as ' . $v;
        }

        $currentUser = User::find(Auth::id());

        $arIn = DebtorUsersRef::getUserRefs();
        $date = (is_null($req->get('search_field_debtor_events@date'))) ?
            Carbon::today() :
            (new Carbon($req->get('search_field_debtor_events@date')));

        $date_from = $req->get('search_field_debtor_events@date_from');
        $date_to = $req->get('search_field_debtor_events@date_to');

        $debt_group_id = $req->get('search_field_debt_groups@id');

        $date_from_fmt = false;
        if (!is_null($date_from) && !empty($date_from)) {
            $date_from_fmt = date('Y-m-d 00:00:00', strtotime($date_from));
        }

        $date_to_fmt = false;
        if (!is_null($date_to) && !empty($date_to)) {
            $date_to_fmt = date('Y-m-d 23:59:59', strtotime($date_to));
        }

        $responsible_id_1c = $req->get('search_field_users@id_1c');

        // получаем список запланированных мероприятий на сегодня
        $debtorEvents = DB::table('debtor_events')->select($cols)
            ->leftJoin('debtors', 'debtors.id', '=', 'debtor_events.debtor_id')
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('users', 'users.id', '=', 'debtor_events.user_id')
            ->leftJoin('debtor_users_ref', 'debtor_users_ref.master_user_id', '=', 'users.id')
            ->leftJoin('debtors_event_types', 'debtors_event_types.id', '=', 'debtor_events.event_type_id')
            //->whereBetween('debtor_events.date', array($date->setTime(0, 0, 0)->format('Y-m-d H:i:s'), $date->setTime(23, 59, 59)->format('Y-m-d H:i:s')))
            ->where('debtor_events.completed', 0)
            ->groupBy('debtor_events.id');

        if (!$date_from_fmt && !$date_to_fmt) {
            $debtorEvents->whereBetween('debtor_events.date', array(
                $date->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $date->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            ));
        } else {
            if ($date_from_fmt) {
                $debtorEvents->where('debtor_events.date', '>=', $date_from_fmt);
            }

            if ($date_to_fmt) {
                $debtorEvents->where('debtor_events.date', '<=', $date_to_fmt);
            }
        }

        if (!is_null($debt_group_id) && mb_strlen($debt_group_id)) {
            $debtorEvents->where('debtors.debt_group_id', (int)$debt_group_id);
        }

        if (!is_null($responsible_id_1c) && mb_strlen($responsible_id_1c)) {
            $debtorEvents->where('debtors.debtor_events.user_id_1c', $responsible_id_1c);
        }

        if ($currentUser->hasRole('debtors_personal')) {
            $debtorEvents->where('debtors.debtor_events.user_id', $currentUser->id);
        } else {

            // если придет пустой массив - будут показаны все планы на день
            if (count($arIn) && (is_null($responsible_id_1c) || !mb_strlen($responsible_id_1c))) {
                $debtorEvents->whereIn('debtors.debtor_events.user_id', $arIn);
            }
        }

        $html = '<table>';
        $firstRow = true;
        $colHeaders = [
            'de_date' => 'Дата план',
            'de_type_id' => 'Тип мероприятия',
            'passports_fio' => 'ФИО должника',
            'de_created_at' => 'Дата факт',
            'de_username' => 'Ответственный',
        ];
        $html .= '<thead>';
        $html .= '<tr>';
        foreach ($colHeaders as $k => $v) {
            $html .= '<th>' . $v . '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $totalEvents = $debtorEvents->get();

        foreach ($totalEvents as $debtorEvent) {
            $debtorArray = (array)$debtorEvent;
            $html .= '<tr>';
            foreach ($debtorArray as $k => $v) {
                if (in_array($k, ['de_date', 'de_created_at'])) {
                    $html .= '<td>' . with(new Carbon($v))->format('d.m.Y') . '</td>';
                } else {
                    if ($k == 'debtors_id') {
                        continue;
                    } else {
                        if ($k == 'de_type_id') {
                            $event_types = \App\DebtorsEventType::get()->toArray();
                            $html .= '<td>' . $event_types[$v]['name'] . '</td>';
                        } else {
                            $html .= '<td>' . $v . '</td>';
                        }
                    }
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        $file = "report_events.xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
            ->header("Content-type", "application/vnd.ms-excel")
            ->header("Content-Disposition", "attachment; filename=$file");
    }

    public function exportForgottenToExcel(Request $req)
    {
        $qDebtors = $this->forgottenQuery($req);

        $colHeaders = [
            'debtors_fixation_date' => 'Дата закрепления',
            'passports_fio' => 'ФИО',
            'debtors_loan_id_1c' => 'Договор',
            'debtors_qty_delays' => 'Дней просрочки',
            'debtors_sum_indebt' => 'Задолженность',
            'debtors_od' => 'Осн. долг',
            'debtors_base' => 'База',
            'customers_telephone' => 'Телефон',
            'debtors_debt_group_id' => 'Группа долга',
            'debtors_username' => 'Ответственный',
            'debtor_str_podr' => 'Структурное подразделение'
        ];

        $html = '<table>';
        $firstRow = true;
        $html .= '<thead>';
        $html .= '<tr>';
        foreach ($colHeaders as $k => $v) {
            $html .= '<th>' . $v . '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $debtors = $qDebtors->get();

        foreach ($debtors as $debtor) {
            $debtorArray = $debtor->toArray();
            $html .= '<tr>';
            foreach ($debtorArray as $k => $v) {
                if (in_array($k, ['debtors_fixation_date'])) {
                    $html .= '<td>' . with(new Carbon($v))->format('d.m.Y') . '</td>';
                } else {
                    if (in_array($k, [
                        'debtors_id',
                        'debtor_id_1c',
                        'uploaded',
                        'debtors_debt_group',
                        'debtors_responsible_user_id_1c'
                    ])) {
                        continue;
                    } else {
                        $html .= '<td>' . $v . '</td>';
                    }
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        $file = "report_forgotten.xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
            ->header("Content-type", "application/vnd.ms-excel")
            ->header("Content-Disposition", "attachment; filename=$file");
    }

    public function changeLoadStatus($debtor_id)
    {
        if (Debtor::changeLoadStatus($debtor_id)) {
            return 1;
        }
        return 0;
    }

    public function uploadDataFromArmToDebtors()
    {
        $loantypes = DB::connection('arm')->table('loantypes')->where('created_at', '>',
            \App\LoanType::max('created_at'))->get()->toArray();
        foreach ($loantypes as $item) {
            if (\App\LoanType::where('id_1c', $item['id_1c'])->count() == 0) {
                $lt = new \App\LoanType();
                $lt->fill($item);
                $lt->save();
            }
        }
        $subdivs = DB::connection('arm')->table('subdivisions');
    }

    /**
     * Получает все платежи по должнику (текущему просроченному кредитнику)
     * @param type $debtor_id
     */
    public function getAllPayments($debtor_id)
    {
        if (Auth::user()->id != 69) {
            return 0;
        }
        $debtor = Debtor::find($debtor_id);
        if (is_null($debtor)) {
            return 0;
        }
        $loan = Loan::where('id_1c', $debtor->loan_id_1c)->first();
        if (is_null($loan)) {
            return 0;
        }

        $last_open = DebtorsInfo::rowExists($debtor_id);
        $need_post = true;
        $arForPostPayments = [
            "data" => [
                [
                    "loan_id_1c" => $debtor->loan_id_1c,
                    "customer_id_1c" => $debtor->customer_id_1c,
                    "start_date" => $loan->created_at->format('Y-m-d H:i:s'),
                    "end_date" => date("Y-m-d H:i:s", time())
                ]
            ]
        ];
        if ($last_open) {
            $arForPostPayments["data"][0]["start_date"] = $last_open;
            if (Carbon::now() < with(new Carbon($last_open))->addMinutes(7)) {
                $need_post = false;
            } else {
                $rowInfo = DebtorsInfo::where('debtor_id', $debtor_id)->first();
                $rowInfo->last_open = date('Y-m-d H:i:s', time());
                $rowInfo->save();
            }
        }

        if ($need_post) {
            $arForPostPayments["data"] = json_encode($arForPostPayments["data"]);
            $p = Utils\HelperUtil::SendPostByCurl(config('admin.sales_arm_url') . '/debtors/orders/upload',
                $arForPostPayments);
            \PC::debug($p);
        }

        if (!$last_open) {
            $row_debtor_info = new DebtorsInfo();
            $row_debtor_info->debtor_id = $debtor_id;
            $row_debtor_info->last_open = date("Y-m-d H:i:s", time());
            $row_debtor_info->save();
        }

        return 1;
    }

    /**
     * Изменение количества SMS для специалистов
     * @param Request $req
     */
    public function editSmsCount(Request $req)
    {
        $input = $req->input();

        if (!empty($input)) {
            parse_str($input['smseditdata'], $formData);
            foreach ($formData as $key => $val) {
                if (!empty($val)) {
                    if (strpos($key, 'sms_user_') !== false) {
                        $user_id = str_replace('sms_user_', '', $key);
                        $user_edit = User::find($user_id);
                        if (!is_null($user_edit)) {
                            $user_edit->sms_limit = $val;
                            $user_edit->sms_sent = 0;
                            $user_edit->save();
                        }
                    }
                }
            }

            die();
        }

        $currentUser = User::find(Auth::id());

        if (!$currentUser->hasRole('debtors_chief')) {
            echo 'Недостаточно прав.';
            die();
        }

        $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
        $usersDebtors = User::select('users.id_1c')
            ->whereIn('id', $arResponsibleUserIds)
            ->orderBy('name', 'asc');

        $arUsersDebtors = $usersDebtors->get()->toArray();
        $arOutput = [];

        $i = 0;
        foreach ($arUsersDebtors as $tmpUser_id_1c) {
            $tmpUser = User::where('id_1c', $tmpUser_id_1c)->first();
            if (is_null($tmpUser)) {
                continue;
            }
            $arOutput[$i]['name'] = $tmpUser['name'];
            $arOutput[$i]['id'] = $tmpUser['id'];
            $arOutput[$i]['sms_now'] = $tmpUser['sms_limit'] - $tmpUser['sms_sent'];
            $i++;
        }

        return view('debtors.editSmsCount', [
            'users' => $arOutput
        ]);
    }

    public function forgotten()
    {
        $user = User::find(Auth::id());

        $isChief = ($user->hasRole('debtors_chief')) ? true : false;
        return view('debtors.forgotten', [
            'isChief' => $isChief
        ]);
    }

    public function recommends()
    {
        $user = User::find(Auth::id());

        $isChief = ($user->hasRole('debtors_chief')) ? true : false;
        return view('debtors.recommends', [
            'isChief' => $isChief
        ]);
    }

    public function ajaxForgottenList(Request $req)
    {
        $debtors = $this->forgottenQuery($req);

        $collection = Datatables::of($debtors)
            ->editColumn('debtors_fixation_date', function ($item) {
                return (!is_null($item->debtors_fixation_date)) ? date('d.m.Y',
                    strtotime($item->debtors_fixation_date)) : '-';
            })
            ->addColumn('links', function ($item) {
                $html = '';
                $html .= HtmlHelper::Buttton(url('debtors/debtorcard/' . $item->debtors_id),
                    ['glyph' => 'eye-open', 'size' => 'xs', 'target' => '_blank']);
                return $html;
            }, 0)
            ->removeColumn('debtors_id')
            ->removeColumn('debtors_responsible_user_id_1c')
            ->setTotalRecords(1000)
            ->make();
        $collection->getData();
        return $collection;
    }

    /**
     * Запрос для "забытых должников"
     * @param Request $req
     */
    public function forgottenQuery(Request $req)
    {
        $input = $req->input();

        $currentUser = User::find(Auth::id());
        $today = date('Y-m-d 00:00:00', time());

        $arGoodResultIds = [0, 3, 6, 9, 10, 11, 12, 13, 17, 21, 22];

        $str_podr = false;

        if ($currentUser->hasRole('debtors_personal')) {
            $str_podr = '000000000007';
            $forgotten_date = date('Y-m-d 00:00:00', strtotime('-7 day', strtotime($today)));
        }

        if ($currentUser->hasRole('debtors_remote')) {
            $str_podr = '000000000006';
            $forgotten_date = date('Y-m-d 00:00:00', strtotime('-12 day', strtotime($today)));
        }

        if (!$str_podr) {
            return $this->backWithErr('Вы не привязаны к структурным подразделениям взыскания.');
        }

        $debtorColumns = [
            'debtors.fixation_date' => 'debtors_fixation_date',
            'debtors.passports.fio' => 'passports_fio',
            //'debtors.loan_id_1c' => 'debtors_loan_id_1c',
            //'debtors.qty_delays' => 'debtors_qty_delays',
            //'debtors.sum_indebt' => 'debtors_sum_indebt',
            //'debtors.od' => 'debtors_od',
            //'debtors.base' => 'debtors_base',
            //'debtors.customers.telephone' => 'customers_telephone',
            //'debtors.debt_groups.name' => 'debtors_debt_group_id',
            'debtors.id' => 'debtors_id',
            'debtors.users.name' => 'debtors_username',
            //'debtors.debtor_id_1c' => 'debtor_id_1c',
            'debtors.struct_subdivisions.name' => 'debtor_str_podr',
            //'debtors.uploaded' => 'uploaded',
            //'debtors.debt_group_id' => 'debtors_debt_group',
            'debtors.responsible_user_id_1c' => 'debtors_responsible_user_id_1c'
        ];

        foreach ($debtorColumns as $k => $v) {
            $cols[] = $k . ' as ' . $v;
        }

        $debtors = DebtorEvent::select($cols, DB::raw('MAX(debtor_events.created_at) as max_created_at'))
            ->leftJoin('debtors.debtors', 'debtors.id', '=', 'debtor_events.debtor_id')
            ->leftJoin('debtors.customers', 'debtors.customer_id_1c', '=', 'customers.id_1c')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passport_series', '=', 'passports.series');
                $join->on('debtors.passport_number', '=', 'passports.number');
            })
            ->leftJoin('debtors.users', 'debtors.responsible_user_id_1c', '=', 'users.id_1c')
            ->leftJoin('debtors.struct_subdivisions', 'debtors.str_podr', '=', 'struct_subdivisions.id_1c')
            ->groupBy('debtors.customer_id_1c')
            ->havingRaw('MAX(debtor_events.created_at) <= \'' . $forgotten_date . '\'');

        /* $debtors = Debtor::select($cols, DB::raw('MAX(debtor_events.created_at) as max_created_at'))
          ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
          ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
          ->leftJoin('debtors.customers', 'debtors.customers.id', '=', 'debtors.claims.customer_id')
          ->leftJoin('debtors.passports', function($join) {
          $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
          $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
          })
          ->leftJoin('debtors.users', 'debtors.users.id_1c', '=', 'debtors.debtors.responsible_user_id_1c')
          ->leftJoin('debtors.struct_subdivisions', 'debtors.struct_subdivisions.id_1c', '=', 'debtors.debtors.str_podr')
          ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debtors.debt_group_id')
          //->leftJoin('debtors.debtor_events', 'debtors.debtor_events.debtor_id', '=', 'debtors.debtors.id')
          ->leftJoin('debtors.debtor_events', function($oJoin) {
          $oJoin->on('debtors.debtor_events.debtor_id', '=', 'debtors.debtors.id');
          $oJoin->on('debtors.debtor_events.user_id_1c', '=', 'debtors.debtors.responsible_user_id_1c');
          })
          ->groupBy('debtors.id')
          //->groupBy('debtor_events.customer_id_1c')
          //->orderBy('debtor_events.created_at', 'desc')
          ->havingRaw('MAX(debtor_events.created_at) <= \'' . $forgotten_date . '\''); */

        $debtors->where('debtors.base', '<>', 'Архив ЗД');
        $debtors->where('debtors.is_debtor', 1);

        $debtors->where('debtors.str_podr', $str_podr);

        $debtors->whereIn('event_result_id', $arGoodResultIds);

        if (isset($input['search_field_users@id_1c']) && !empty($input['search_field_users@id_1c']) && $currentUser->hasRole('debtors_chief')) {
            $debtors->where('responsible_user_id_1c', $input['search_field_users@id_1c']);
        } else {
            if ($currentUser->hasRole('debtors_chief')) {
                $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
                $usersDebtors = User::select('users.id_1c')
                    ->whereIn('id', $arResponsibleUserIds);

                $arUsersDebtors = $usersDebtors->get()->toArray();
                $arIn = [];
                foreach ($arUsersDebtors as $tmpUser) {
                    if (strpos($tmpUser['id_1c'], 'Еричев') !== false) {
                        continue;
                    }
                    $arIn[] = $tmpUser['id_1c'];
                }

                $debtors->whereIn('debtors.responsible_user_id_1c', $arIn);
            } else {
                $debtors->where('debtors.responsible_user_id_1c', $currentUser->id_1c);
            }
        }

        return $debtors;
    }

    /**
     * Возвращает коллекцию по рекомендациям должников
     * @param Request $req
     * @return collection
     */
    public function ajaxRecommendsList(Request $req)
    {
        $input = $req->input();

        $currentUser = User::find(Auth::id());
        $today = date('Y-m-d 00:00:00', time());

        $str_podr = false;

        $isChief = $currentUser->hasRole('debtors_chief');

        if ($currentUser->hasRole('debtors_personal')) {
            $str_podr = ($isChief) ? false : '000000000007';
            $forgotten_date = date('Y-m-d 00:00:00', strtotime('-15 day', strtotime($today)));
        }

        if ($currentUser->hasRole('debtors_remote')) {
            $str_podr = ($isChief) ? false : '000000000006';
            $forgotten_date = date('Y-m-d 00:00:00', strtotime('-11 day', strtotime($today)));
        }

        if (!$str_podr && !$isChief) {
            return $this->backWithErr('Вы не привязаны к структурным подразделениям взыскания.');
        }

        $debtorColumns = [
            'debtors.fixation_date' => 'debtors_fixation_date',
            'debtors.passports.fio' => 'passports_fio',
            'debtors.loan_id_1c' => 'debtors_loan_id_1c',
            'debtors.qty_delays' => 'debtors_qty_delays',
            'debtors.sum_indebt' => 'debtors_sum_indebt',
            'debtors.od' => 'debtors_od',
            'debtors.base' => 'debtors_base',
            'debtors.customers.telephone' => 'customers_telephone',
            'debtors.debt_groups.name' => 'debtors_debt_group_id',
            'debtors.id' => 'debtors_id',
            'debtors.users.name' => 'debtors_username',
            'debtors.debtor_id_1c' => 'debtor_id_1c',
            'debtors.struct_subdivisions.name' => 'debtor_str_podr',
            'debtors.uploaded' => 'uploaded',
            'debtors.debt_group_id' => 'debtors_debt_group',
            'debtors.responsible_user_id_1c' => 'debtors_responsible_user_id_1c',
            'debtors.recommend_completed' => 'debtors_rec_completed',
        ];

        foreach ($debtorColumns as $k => $v) {
            $cols[] = $k . ' as ' . $v;
        }

        $debtors = Debtor::select($cols)
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.customers', 'debtors.customers.id', '=', 'debtors.claims.customer_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('debtors.users', 'debtors.users.id_1c', '=', 'debtors.debtors.responsible_user_id_1c')
            ->leftJoin('debtors.struct_subdivisions', 'debtors.struct_subdivisions.id_1c', '=',
                'debtors.debtors.str_podr')
            ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debtors.debt_group_id')
            ->leftJoin('debtors.debtor_events', 'debtors.debtor_events.debtor_id', '=', 'debtors.debtors.id')
            ->groupBy('debtors.id');

        $debtors->where('debtors.base', '<>', 'Архив ЗД');

        if (!$isChief) {
            $debtors->where('debtors.str_podr', $str_podr);
        }

        $debtors->whereNotNull('debtors.recommend_created_at');

        if (isset($input['search_field_users@id_1c']) && !empty($input['search_field_users@id_1c']) && $currentUser->hasRole('debtors_chief')) {
            $debtors->where('responsible_user_id_1c', $input['search_field_users@id_1c']);
        } else {
            if ($isChief) {
                $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
                $usersDebtors = User::select('users.id_1c')
                    ->whereIn('id', $arResponsibleUserIds);

                $arUsersDebtors = $usersDebtors->get()->toArray();
                $arIn = [];
                foreach ($arUsersDebtors as $tmpUser) {
                    if (strpos($tmpUser['id_1c'], 'Еричев') !== false) {
                        continue;
                    }
                    $arIn[] = $tmpUser['id_1c'];
                }

            } else {
                $debtors->where('debtors.responsible_user_id_1c', $currentUser->id_1c);
            }
        }

        $collection = Datatables::of($debtors)
            ->editColumn('debtors_fixation_date', function ($item) {
                return (!is_null($item->debtors_fixation_date)) ? date('d.m.Y',
                    strtotime($item->debtors_fixation_date)) : '-';
            })
            ->editColumn('debtors_od', function ($item) {
                return number_format($item->debtors_od / 100, 2, '.', '');
            })
            ->editColumn('debtors_sum_indebt', function ($item) {
                return number_format($item->debtors_sum_indebt / 100, 2, '.', '');
            })
            ->editColumn('debtors_rec_completed', function ($item) {
                return ($item->debtors_rec_completed == 1) ? 'Да' : 'Нет';
            })
            ->addColumn('links', function ($item) {
                $html = '';
                $html .= HtmlHelper::Buttton(url('debtors/debtorcard/' . $item->debtors_id),
                    ['glyph' => 'eye-open', 'size' => 'xs', 'target' => '_blank']);
                return $html;
            }, 0)
            ->removeColumn('debtors_id')
            ->removeColumn('debtor_id_1c')
            ->removeColumn('uploaded')
            ->removeColumn('debtors_debt_group')
            ->removeColumn('debtors_responsible_user_id_1c')
            ->setTotalRecords(1000)
            ->make();
        $collection->getData();
        return $collection;
    }

    /**
     * Подгружает договора в продажный АРМ для реплики
     * @param Request $request
     * @return int
     */
    public function uploadLoans(Request $request)
    {
        $input = $request->input();
        if (!isset($input['debtor_id'])) {
            return 0;
        }

        $debtor = Debtor::find($input['debtor_id']);
        if (is_null($debtor)) {
            return 0;
        }

        if (!\App\DebtorCardOpen::checkOpenCard($debtor->id)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,
                config('admin.sales_arm_url') . '/debtors/loans/upload?passport_series=' . $debtor->passport_series . '&passport_number=' . $debtor->passport_number);
            $answer_curl = curl_exec($ch);
            curl_close($ch);
        }
    }

    public function updateLoan(Request $request)
    {
        $input = $request->input();

        $debtor = Debtor::find($input['debtor_id']);
        if (!is_null($debtor) && isset($input['loan_id_1c']) && mb_strlen($input['loan_id_1c']) > 0 && isset($input['arm_loan_id']) && mb_strlen($input['arm_loan_id'])) {
            $update_history = new \App\DebtorUpdateHistory();
            $update_history->arm_loan_id = $input['arm_loan_id'];

            $update_history->save();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,
                config('admin.sales_arm_url') . '/debtors/loans/upload?loan_id_1c=' . $input['loan_id_1c'] . '&customer_id_1c=' . $debtor->customer_id_1c);
            $answer_curl = curl_exec($ch);
            curl_close($ch);
        }
    }

    public function getMultiSum(MultiSumRequest $request)
    {
        $customerId1c = $request['customer_id_1c'];
        $loanId1c = $request['loan_id_1c'];
        $date = $request['date'] ?? null;

        $result = $this->debtCardService->getMultiSum1c($customerId1c, $loanId1c, $date);
        return view('elements.debtors.multiloans_buttons', [
            'loans' => $result ?? []
        ]);
    }

    /**
     * Экспорт реестра отправленных писем и уведомлений
     * @param Request $req
     */
    public function exportPostRegistry(Request $req)
    {
        $currentUser = User::find(Auth::id());

        if (!$currentUser->hasRole('debtors_chief')) {
            echo 'Недостаточно прав.';
            die();
        }

        $mode = $req->get('mode', false); // uv - удаленное взыскание, lv - личное взыскание

        if (!$mode) {
            return;
        }

        $date = $req->get('date', false);
        $date = (!$date) ? date('Y-m-d', time()) : date('Y-m-d', strtotime($date));

        $notices = \App\NoticeNumbers::where('created_at', '>=', $date . ' 00:00:00')
            ->where('created_at', '<=', $date . ' 23:59:59');

        $number_postfix = '';

        if ($mode == 'uv') {
            $notices->where('str_podr', '000000000006');
            $number_postfix = 'УВ';
        }
        if ($mode == 'lv') {
            $notices->where('str_podr', '000000000007')
                ->whereIn('user_id_1c', [
                    'Ведущий специалист личного взыскания',
                    'Медведев В.В.',
                    'Кузнецов Д.С.',
                    'Иванов Н.С.                                  '
                ]);
            $number_postfix = 'ЛВ';
        }

        $cNotices = $notices->get();

        $html = '<table><tbody>';

        foreach ($cNotices as $notice) {

            $html .= '<tr>';

            $data = [];
            $data[0] = $notice->id . '/' . $number_postfix;
            $data[1] = date('d.m.Y', strtotime($notice->created_at));


            $debtor = \App\Debtor::where('debtor_id_1c', $notice->debtor_id_1c)->first();
            if (!is_null($debtor)) {
                $passport = \App\Passport::where('series', $debtor->passport_series)->where('number',
                    $debtor->passport_number)->first();
                if (!is_null($passport)) {
                    $data[2] = $passport->fio;
                    if ($notice->is_ur_address) {
                        $data[3] = \App\Passport::getFullAddress($passport);
                    } else {
                        $data[3] = \App\Passport::getFullAddress($passport, true);
                    }
                } else {
                    $data[2] = '-';
                    $data[3] = '-';
                }
            }

            $data[4] = trim($notice->user_id_1c);

            foreach ($data as $v) {
                $html .= '<td>' . $v . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $file = "post_registry_" . $mode . "_" . date("dmY", time()) . ".xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
            ->header("Content-type", "application/vnd.ms-excel")
            ->header("Content-Disposition", "attachment; filename=$file");
    }

    public function addNewPeaceClaim(Request $req)
    {
        $user = auth()->user();

        $repayment_type_id = $req->get('repayment_type_id', false);
        $debtor_id = $req->get('debtor_id', false);

        if (!$debtor_id) {
            return redirect()->back();
        }

        $debtor = Debtor::find($debtor_id);
        if (!$debtor) {
            return redirect()->back();
        }

        if ($repayment_type_id) {
            $input = $req->input();

            $input['start_at'] = date('Y-m-d', time());
            if ($repayment_type_id == 14) {
                $input['times'] = $input['times'] * 30;
            }

            $input['amount'] = $input['amount'] * 100;

            if (isset($input['prepaid']) && $input['prepaid'] == 1) {

            } else {
                $input['prepaid'] = 0;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, config('admin.sales_arm_url') . '/api/repayments/offers');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);

            $arJson = json_decode($result, true);

            if ($arJson) {
                if ($repayment_type_id == 14) {
                    $report = 'Предварительное согласие по договору ' . $debtor->loan_id_1c . ' на мировое соглашение сроком на ' . $input['times'] . ' дней, сумма: ' . $input['amount'] / 100 . ' руб. Действует до ' . date('d.m.Y',
                            strtotime($input['end_at']));
                } else {
                    $report = 'Предварительное согласие по договору ' . $debtor->loan_id_1c . ' на приостановку процентов сроком на ' . $input['times'] . ' дней, сумма: ' . $input['amount'] / 100 . ' руб. Действует до ' . date('d.m.Y',
                            strtotime($input['end_at']));
                }

                $event = new DebtorEvent();

                $event->event_type_id = 9;
                $event->debt_group_id = $debtor->debt_group_id;
                $event->event_result_id = ($repayment_type_id == 14) ? 19 : 18;
                $event->report = $report;
                $event->debtor_id = $debtor->id;
                $event->user_id = $user->id;
                $event->completed = 1;
                $event->last_user_id = $user->id;
                $event->debtor_id_1c = $debtor->debtor_id_1c;
                $event->user_id_1c = $user->id_1c;
                $event->refresh_date = date('Y-m-d H:i:s', time());
                $event->save();
            }
        }

        return redirect()->back();
    }

    public function setGroupPlanEvents(Request $req)
    {
        $date_plan = $req->get('search_field_debtor_events@date_plan', false);
        $debt_group_id = $req->get('search_field_debt_groups@id', false);
        $user_id_1c = $req->get('search_field_users@id_1c', false);
        $event_type_id = $req->get('search_field_debtors_event_types@id', false);

        $now_time = date('H:i:s', time());

        if (!$date_plan || !mb_strlen($date_plan)) {
            die('Не задан параметр "Дата план"');
        }
        if (!$debt_group_id || !mb_strlen($debt_group_id)) {
            die('Не задан параметр "Группа долга"');
        }
        if (!$user_id_1c || !mb_strlen($user_id_1c)) {
            die('Не задан параметр "Отвественный"');
        }
        if (!$event_type_id || !mb_strlen($event_type_id)) {
            die('Не задан параметр "Тип мероприятия"');
        }

        $date_plan = $date_plan . ' ' . $now_time;

        $planned_customers = [];

        $debtors = Debtor::where('responsible_user_id_1c', $user_id_1c)
            ->where('debt_group_id', $debt_group_id)
            ->where('base', '<>', 'Архив ЗД')
            ->where('is_debtor', 1)
            ->get();

        foreach ($debtors as $debtor) {
            if (in_array($debtor->customer_id_1c, $planned_customers)) {
                continue;
            }

            $responsible_user = User::where('id_1c', $debtor->responsible_user_id_1c)->first();

            if (is_null($responsible_user)) {
                continue;
            }

            $planned_customers[] = $debtor->customer_id_1c;

            $event = new DebtorEvent();

            $event->date = $date_plan;
            $event->customer_id_1c = $debtor->customer_id_1c;
            $event->loan_id_1c = $debtor->loan_id_1c;
            $event->event_type_id = $event_type_id;
            $event->debt_group_id = $debtor->debt_group_id;
            $event->debtor_id = $debtor->id;
            $event->user_id = $responsible_user->id;
            $event->completed = 0;
            $event->debtor_id_1c = $debtor->debtor_id_1c;
            $event->user_id_1c = $responsible_user->id_1c;
            $event->refresh_date = date('Y-m-d H:i:s', time());

            $event->save();
        }

        return redirect()->back();
    }

    public function sentRecurrentQuery(Request $req)
    {
        $user = auth()->user();

        $debtor_id = $req->get('debtor_id', false);
        $amount = $req->get('amount', false);

        if (!$debtor_id || !$amount) {
            Log::error('sentRecurrentQuery error', ['debtor_id' => $debtor_id, 'amount' => $amount]);
            return redirect()->back();
        }

        $debtor = Debtor::find($debtor_id);

        if (!$debtor) {
            Log::error('sentRecurrentQuery error', ['debtor_id' => $debtor, 'debtor' => $debtor]);
            return redirect()->back();
        }

        $postdata = [
            'customer_external_id' => $debtor->customer_id_1c,
            'loan_external_id' => $debtor->loan_id_1c,
            'amount' => $amount,
            'purpose_id' => 3,
            'is_recurrent' => 1,
            'details' => '{"is_debtor":true}'
        ];


        $url = 'http://192.168.35.69:8080/api/v1/payments';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'X-Requested-With: XMLHttpRequest'
        ));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            Log::error('sentRecurrentQuery error', ['httpcode' => $httpcode, 'curl_error' => curl_error($ch)]);
            echo 'Curl error: ' . curl_error($ch);
            var_dump($httpcode);
            die();
        }

        curl_close($ch);

        if ($httpcode == 200) {
            $recurrent_query = new \App\DebtorRecurrentQuery();
            $recurrent_query->debtor_id = $debtor->id;
            $recurrent_query->save();

            $debtorEvent = new DebtorEvent();
            $debtorEvent->customer_id_1c = $debtor->customer_id_1c;
            $debtorEvent->loan_id_1c = $debtor->loan_id_1c;
            $debtorEvent->event_type_id = 9;
            $debtorEvent->debt_group_id = $debtor->debt_group_id;
            $debtorEvent->event_result_id = 17;
            $debtorEvent->report = 'Отправлен запрос на безакцептное списание.';
            $debtorEvent->debtor_id = $debtor->id;
            $debtorEvent->user_id = $user->id;
            $debtorEvent->completed = 1;
            $debtorEvent->debtor_id_1c = $debtor->debtor_id_1c;
            $debtorEvent->user_id_1c = $user->id_1c;
            $debtorEvent->refresh_date = date('Y-m-d H:i:s', time());
            $debtorEvent->save();

            sleep(3);
        }

        return redirect()->back();
    }

    public function massRecurrentTask(Request $req)
    {
        $user = auth()->user();

        $is_leading_task = $req->get('type', false);

        if ($is_leading_task && $is_leading_task == 'ouv_chief' && ($user->id == 916 || $user->id == 69)) { // запуск по Ведущему личного взыскания Свиридовым
            $str_podr = '000000000007-1';
        } else {
            if ($is_leading_task && $is_leading_task == 'ouv_chief' && ($user->id == 3448 || $user->id == 69)) {
                $str_podr = '000000000006-1';
            } else {
                if ($user->hasRole('debtors_remote')) {
                    $str_podr = '000000000006';
                } else {
                    if ($user->hasRole('debtors_personal')) {
                        $str_podr = '000000000007';
                    } else {
                        $str_podr = null;
                    }
                }
            }
        }

        if (is_null($str_podr)) {
            return redirect()->back();
        }

        $start_flag = $req->get('start', false);

        $recurrent_task = \App\MassRecurrentTask::where('created_at', '>=', date('Y-m-d 00:00:00', time()))
            ->where('created_at', '<=', date('Y-m-d 23:59:59', time()))
            ->where('str_podr', $str_podr)
            //->orderBy('id', 'desc')
            ->first();

        $canStartToday = ($recurrent_task) ? false : true;
        //$canStartToday = true;

        if ($start_flag && $canStartToday) {
            $debtors = Debtor::where('is_debtor', 1);

            if ($str_podr == '000000000006') {
                $debtors->where('str_podr', '000000000006')
                    ->where('qty_delays', '>=', 21)
                    ->where('qty_delays', '<=', 135)
                    ->whereIn('debt_group_id', [4, 5, 6]);
            }

            if ($str_podr == '000000000007') {
                $debtors->where('str_podr', '000000000007')
                    ->where('qty_delays', '>=', 60)
                    ->where('qty_delays', '<=', 150)
                    ->whereIn('debt_group_id', [5, 6]);
            }

            if ($str_podr == '000000000007-1') {
                $debtors->where('str_podr', '000000000007')
                    ->where('responsible_user_id_1c', 'Ведущий специалист личного взыскания')
                    ->where('qty_delays', '>=', 60)
                    ->where('qty_delays', '<=', 150)
                    ->whereIn('debt_group_id', [5, 6])
                    ->whereIn('base', ['Б-3', 'Б-МС', 'Б-риски', 'Б-График']);
            }

            if ($str_podr == '000000000006-1') {
                $debtors->where('str_podr', '000000000006')
                    ->whereIn('responsible_user_id_1c', [
                        'Осипова Е. А.                                ',
                        'Петухова Е. И.                               '
                    ])
                    ->whereIn('base', ['Б-1', 'Б-МС', 'Б-риски', 'Б-График']);
            }

            $debtors = $debtors->get();

            if ($debtors) {
                $recurrent_task = new \App\MassRecurrentTask();

                $recurrent_task->user_id = $user->id;
                $recurrent_task->debtors_count = count($debtors);
                $recurrent_task->str_podr = $str_podr;
                $recurrent_task->save();

                return json_encode(['task_id' => $recurrent_task->id, 'debtors_count' => count($debtors)]);
            }
        }

        $completedTodayTask = ($canStartToday) ? 0 : $recurrent_task->completed;
        //$completedTodayTask = (is_null($recurrent_task)) ? 0 : $recurrent_task->completed;

        if ($str_podr == '000000000007-1') {
            $recurrent_type = 'olv_chief';
        } else {
            if ($str_podr == '000000000006-1') {
                $recurrent_type = 'ouv_chief';
            } else {
                $recurrent_type = 'usual';
            }
        }

        return view('debtors.mass_recurrents_task', [
            'canStartToday' => $canStartToday,
            'completed' => $completedTodayTask,
            'recurrent_type' => $recurrent_type
        ]);
    }

    public function massRecurrentQuery(Request $req)
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0);

        $task_id = $req->get('task_id', false);
        $recurrent_type = $req->get('recurrent_type', false);

        if (!$task_id) {
            return 0;
        }

        $recurrent_task = \App\MassRecurrentTask::find($task_id);

        $user = auth()->user();

        if ($recurrent_type && $recurrent_type == 'olv_chief') {
            $debtors = Debtor::where('is_debtor', 1)
                ->where('str_podr', '000000000007')
                ->where('responsible_user_id_1c', 'Ведущий специалист личного взыскания')
                ->where('qty_delays', '>=', 60)
                ->where('qty_delays', '<=', 150)
                ->whereIn('debt_group_id', [5, 6])
                ->whereIn('base', ['Б-3', 'Б-МС', 'Б-риски', 'Б-График'])
                ->get();
        } else {
            if ($recurrent_type && $recurrent_type == 'ouv_chief') {
                $debtors = Debtor::where('is_debtor', 1)
                    ->where('str_podr', '000000000006')
                    ->whereIn('responsible_user_id_1c', [
                        'Осипова Е. А.                                ',
                        'Петухова Е. И.                               '
                    ])
                    ->whereIn('base', ['Б-1', 'Б-МС', 'Б-риски', 'Б-График'])
                    ->get();
            } else {
                if ($user->hasRole('debtors_remote')) {
                    $debtors = Debtor::where('is_debtor', 1)
                        ->where('str_podr', '000000000006')
                        ->where('qty_delays', '>=', 22)
                        ->where('qty_delays', '<=', 69)
                        ->where('base', '<>', 'ХПД')
                        ->whereIn('debt_group_id', [4, 5, 6])
                        ->get();
                } else {
                    if ($user->hasRole('debtors_personal')) {
                        $debtors = Debtor::where('is_debtor', 1)
                            ->where('str_podr', '000000000007')
                            ->where('qty_delays', '>=', 60)
                            ->where('qty_delays', '<=', 150)
                            ->where('base', '<>', 'ХПД')
                            ->whereIn('debt_group_id', [5, 6])
                            ->get();
                    } else {

                    }
                }
            }
        }

        if (isset($debtors) && $debtors) {
            foreach ($debtors as $debtor) {
                $postdata = [
                    'customer_external_id' => $debtor->customer_id_1c,
                    'loan_external_id' => $debtor->loan_id_1c,
                    'amount' => $debtor->sum_indebt,
                    'purpose_id' => 3,
                    'is_recurrent' => 1,
                    'details' => '{"is_debtor":true,"is_mass_debtor":true}'
                ];

                $url = 'http://192.168.35.69:8080/api/v1/payments';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-Requested-With: XMLHttpRequest'
                ));
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $output = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if (curl_errno($ch)) {
                    Log::error('DebtorsController.massRecurrentQuery cURL error: ',
                        [curl_error($ch), $httpcode, $debtor]);
                }

                $recurrent_item = new \App\MassRecurrent();
                $recurrent_item->task_id = $task_id;
                $recurrent_item->debtor_id = $debtor->id;
                $recurrent_item->save();

                curl_close($ch);

                sleep(1);
            }
        }

        $recurrent_task->completed = 1;
        $recurrent_task->save();
    }

    public function getMassRecurrentStatus(Request $req)
    {
        $user = auth()->user();

        $recurrent_type = $req->get('recurrent_type', false);

        if ($recurrent_type && $recurrent_type == 'olv_chief') {
            $str_podr = '000000000007-1';
        } else {
            if ($recurrent_type && $recurrent_type == 'ouv_chief') {
                $str_podr = '000000000006-1';
            } else {
                if ($user->hasRole('debtors_remote')) {
                    $str_podr = '000000000006';
                } else {
                    if ($user->hasRole('debtors_personal')) {
                        $str_podr = '000000000007';
                    } else {
                        $str_podr = null;
                    }
                }
            }
        }

        $recurrent_task = \App\MassRecurrentTask::where('created_at', '>=', date('Y-m-d 00:00:00', time()))
            ->where('created_at', '<=', date('Y-m-d 23:59:59', time()))
            ->where('str_podr', $str_podr)
            //->orderBy('id', 'desc')
            ->first();

        if ($recurrent_task && $recurrent_task->completed == 0) {
            $recurrents_count = \App\MassRecurrent::where('task_id', $recurrent_task->id)->count();
            return json_encode([
                'status' => 'progress',
                'debtors_count' => $recurrent_task->debtors_count,
                'progress' => $recurrents_count
            ]);
        }

        return json_encode([
            'status' => 'completed'
        ]);
    }

    public function getCalcDataForCreditCard(Request $request)
    {
        $loan_id_1c = $request->get('loan_id_1c', false);
        $date = $request->get('debt_calc_date_cc', false);

        if (!$loan_id_1c || !$date) {
            return '<center>Ошибка передачи параметров.</center>';
        }

        $json_string = file_get_contents('http://192.168.35.54:8020/api/v1/loans/' . $loan_id_1c . '/schedule/' . date('d.m.Y',
                strtotime($date)));
        $arData = json_decode($json_string, true);

        return view('debtors.calcDataCreditCard', [
            'calculations' => $arData['data'],
            'date' => $date
        ]);
    }

    public function setSelfResponsible($debtor_id)
    {
        $debtor = Debtor::find($debtor_id);

        if (!is_null($debtor)) {
            $debtor->responsible_user_id_1c = auth()->user()->id_1c;
            $debtor->refresh_date = date('Y-m-d H:i:s', time());
            $debtor->fixation_date = date('Y-m-d 00:00:00', time());

            if (auth()->user()->hasRole('debtors_remote')) {
                $debtor->str_podr = '000000000006';
            }
            if (auth()->user()->hasRole('debtors_personal')) {
                $debtor->str_podr = '000000000007';
            }

            $debtor->save();
        }

        return redirect()->back();
    }

    public static function pledgeFormParams()
    {
        $arr = [
            'type_pledge' => [
                0 => 'Не выбрано',
                1 => 'Авто',
                2 => 'Недвижимость',
            ],
            'loantype_pledge' => [
                0 => 'Не выбрано',
                1 => 'Залоговый',
                2 => 'С обеспечением',
            ],
            'status_pledge' => [
                1 => 'На рассмотрении',
                2 => 'Одобрено',
                3 => 'Отказано',
            ],
            'realty_type' => [
                1 => 'Дом',
                2 => 'Квартира',
                3 => 'Коммерческая',
                4 => 'Производственная',
                5 => 'Земельный участок',
                6 => 'Другое',
            ],
            'car_limitation' => [
                0 => 'Нет',
                1 => 'Да',
            ],
            'realty_redevelopment' => [
                0 => 'Нет',
                1 => 'Да',
            ],
            'realty_limitation' => [
                0 => 'Нет',
                1 => 'Да',
            ],
            'car_brand' => [
                1 => 'Иномарки',
                2 => 'Отечественные',
                3 => 'AC',
                4 => 'Acura',
                5 => 'Alfa Romeo',
                6 => 'Alpina',
                7 => 'Aro',
                8 => 'Asia',
                9 => 'Aston Martin',
                10 => 'Audi',
                11 => 'Bajaj',
                12 => 'BAW',
                13 => 'Bentley',
                14 => 'BMW',
                15 => 'Brilliance',
                16 => 'Bufori',
                17 => 'Bugatti',
                18 => 'Buick',
                19 => 'BYD',
                20 => 'Cadillac',
                21 => 'Caterham',
                22 => 'Changan',
                23 => 'ChangFeng',
                24 => 'Chery',
                25 => 'Chevrolet',
                26 => 'Chrysler',
                27 => 'Citroen',
                28 => 'Dacia',
                29 => 'Dadi',
                30 => 'Daewoo',
                31 => 'Daihatsu',
                32 => 'Daimler',
                33 => 'Datsun',
                34 => 'Derways',
                35 => 'Dodge',
                36 => 'Dongfeng',
                37 => 'Doninvest',
                38 => 'DS',
                39 => 'DW Hower',
                40 => 'Eagle',
                41 => 'Ecomotors',
                42 => 'FAW',
                43 => 'Ferrari',
                44 => 'FIAT',
                45 => 'Ford',
                46 => 'Foton',
                47 => 'GAC',
                48 => 'Geely',
                49 => 'Genesis',
                50 => 'Geo',
                51 => 'GMC',
                52 => 'Great Wall',
                53 => 'Hafei',
                54 => 'Haima',
                55 => 'Haval',
                56 => 'Hawtai',
                57 => 'Honda',
                58 => 'Huanghai',
                59 => 'Hummer',
                60 => 'Hyundai',
                61 => 'Infiniti',
                62 => 'Iran Khodro',
                63 => 'Isuzu',
                64 => 'Iveco',
                65 => 'JAC',
                66 => 'Jaguar',
                67 => 'Jeep',
                68 => 'Jinbei',
                69 => 'JMC',
                70 => 'KIA',
                71 => 'Koenigsegg',
                72 => 'Lamborghini',
                73 => 'Lancia',
                74 => 'Land Rover',
                75 => 'Landwind',
                76 => 'LDV',
                77 => 'Lexus',
                78 => 'LIFAN',
                79 => 'Lincoln',
                80 => 'Lotus',
                81 => 'Luxgen',
                82 => 'Mahindra',
                83 => 'Marussia',
                84 => 'Maserati',
                85 => 'Maybach',
                86 => 'Mazda',
                87 => 'McLaren',
                88 => 'Mercedes-Benz',
                89 => 'Mercury',
                90 => 'Metrocab',
                91 => 'MG',
                92 => 'MINI',
                93 => 'Mitsubishi',
                94 => 'Mitsuoka',
                95 => 'Morgan',
                96 => 'Morris',
                97 => 'Nissan',
                98 => 'Noble',
                99 => 'Oldsmobile',
                101 => 'Opel',
                102 => 'Pagani',
                103 => 'Peugeot',
                104 => 'Plymouth',
                105 => 'Pontiac',
                106 => 'Porsche',
                107 => 'Proton',
                108 => 'PUCH',
                109 => 'Ravon',
                110 => 'Renault',
                111 => 'Rolls-Royce',
                112 => 'Ronart',
                113 => 'Rover',
                114 => 'Saab',
                115 => 'Saleen',
                116 => 'Saturn',
                117 => 'Scion',
                118 => 'SEAT',
                119 => 'Shuanghuan',
                120 => 'Skoda',
                121 => 'SMA',
                122 => 'Smart',
                123 => 'Spyker',
                124 => 'SsangYong',
                125 => 'Subaru',
                126 => 'Suzuki',
                127 => 'Talbot',
                128 => 'Tata',
                129 => 'Tesla',
                130 => 'Tianma',
                131 => 'Tianye',
                132 => 'Toyota',
                133 => 'Trabant',
                134 => 'Volkswagen',
                135 => 'Volvo',
                136 => 'Vortex',
                137 => 'Wartburg',
                138 => 'Westfield',
                139 => 'Wiesmann',
                140 => 'Xin Kai',
                141 => 'Zibar',
                142 => 'ZOTYE',
                143 => 'ZX',
                144 => 'ВАЗ (LADA)',
                145 => 'ВИС',
                146 => 'ГАЗ',
                147 => 'ЗАЗ',
                148 => 'ЗИЛ',
                149 => 'ИЖ',
                150 => 'ЛуАЗ',
                156 => 'Москвич',
                157 => 'РАФ',
                158 => 'СМЗ',
                159 => 'ТагАЗ',
                160 => 'УАЗ',
                161 => 'Другая',
            ],
        ];

        return $arr;
    }

}
