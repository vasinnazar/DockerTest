<?php

namespace App\Http\Controllers;

use App\Clients\ArmClient;
use App\DebtGroup;
use App\Debtor;
use App\DebtorEventPromisePay;
use App\DebtorsEventType;
use App\DebtorUsersRef;
use App\Passport;
use App\Services\DebtorService;
use App\StrUtils;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebtorExportController extends Controller
{
    private $armClient;
    public function __construct(ArmClient $client )
    {
        $this->armClient = $client;
    }

    public function exportForgotten(Request $req, DebtorService $service)
    {
        $id1c = $req->get('search_field_users@id_1c') !== '' ? $req->get('search_field_users@id_1c') : null;
        $collectDebtors =  $service->getForgottenById1c($id1c);

        $excel = \Excel::create('Забытые должники');
        $excel->sheet('Лист 1');
        $activeSheet = $excel->getActiveSheet();

        $lineNumber = 1;
        $activeSheet->appendRow($lineNumber, [
            'Дата закрепления',
            'ФИО',
            'Договор',
            'Дней просрочки',
            'Задолженность',
            'Осн. долг',
            'База',
            'Телефон',
            'Группа долга',
            'Ответственный',
            'Структурное подразделение'
        ]);
        foreach ($collectDebtors as $item) {
            $debtor = Debtor::find($item['debtors_id']);
            $nameDebtorGroup = DebtGroup::where('id',$debtor->debt_group_id)->first();
            $lineNumber++;
            $activeSheet->appendRow($lineNumber, [
                Carbon::parse($item['debtors_fixation_date'])->format('d.m.Y'),
                $item['passports_fio'],
                $debtor->loan_id_1c,
                $debtor->qty_delays,
                $debtor->sum_indebt / 100,
                $debtor->od / 100,
                $debtor->base,
                ($debtor->customer())->telephone,
                $nameDebtorGroup ? $nameDebtorGroup->name : '',
                $item['debtors_username'],
                $item['debtor_str_podr']
            ]);
        }
        $excel->download();
    }

    public function exportInExcelDebtors(Request $req, DebtorService $service)
    {
        $debtors = $service->getDebtors($req, true)->sortBy('passports_fio');
        if ($req->get('search_field_debtors_events_promise_pays@promise_date') !== '') {
            $this->exportOnAgreement($debtors);
            exit();
        }

        $headlines = [
            'Дата закрепления',
            'ФИО должника',
            'Код контрагента',
            'Номер договора',
            'Срок просрочки',
            'Сумма задолженности',
            'Сумма ОД',
            'База',
            'Тип договора',
            'Онлайн',
            'Телефон',
            'Группа долга',
            'ФИО специалиста',
            'Стр. подр.',
            'Юр. адрес',
            'Факт. адрес',
            'Большие деньги',
            'Залоговый займ',
            'Товарный займ',
            'Разница времени',
        ];

        $excel = \Excel::create('report');
        $excel->sheet('page1');

        $activeSheet = $excel->getActiveSheet();

        $activeSheet->appendRow(1, $headlines);
        $row = 2;

        $arDebtGroups = \App\DebtGroup::getDebtGroups();

        foreach ($debtors as $debtor) {

            $isOnline = $debtor->debtor_is_online ? 'Да' : 'Нет';
            $passport = Passport::find($debtor->passport_id);
            $typeClaim = '';
            try{
                $debtGroup = $arDebtGroups[$debtor->debtors_debt_group];
            }catch (\Exception $xception){
                $debtGroup = '';
            }


            if ($debtor->debtor_is_bigmoney) {
                $typeClaim = 'Б.деньги';
            }
            if ($debtor->debtor_is_pledge) {
                $typeClaim = 'Залоговый';
            }
            if ($debtor->debtor_is_pos) {
                $typeClaim = 'Товарный';
            }
            $params = [
                Carbon::parse($debtor->debtors_fixation_date)->format('d.m.Y'),
                $debtor->passports_fio,
                $debtor->debtor_customer_id_1c,
                $debtor->debtors_loan_id_1c,
                $debtor->debtors_qty_delays,
                StrUtils::kopToRub($debtor->debtors_sum_indebt),
                StrUtils::kopToRub($debtor->debtors_od),
                $debtor->debtors_base,
                $typeClaim,
                $isOnline,
                $debtor->customers_telephone,
                $debtGroup,
                $debtor->debtors_username,
                $debtor->debtor_str_podr,
                Passport::getFullAddress($passport),
                Passport::getFullAddress($passport, true),
                $debtor->debtor_is_bigmoney,
                $debtor->debtor_is_pledge,
                $debtor->debtor_is_pos,
                $debtor->passports_fact_timezone,
            ];
            $activeSheet->appendRow($row, $params);
            $row++;
        }
        $excel->download();
        exit();
    }

    public function exportOnAgreement($debtors)
    {
        $headlines = [
            'Дата закрепления',
            'ФИО должника',
            'Код контрагента',
            'Номер договора',
            'Срок просрочки',
            'Сумма задолженности',
            'Сумма ОД',
            'База',
            'Тип договора',
            'Онлайн',
            'Группа долга',
            'ФИО специалиста',
            'Дата договоренностей',
            'Сумма договоренностей',
            'Фактически поступившая сумма',
        ];


        $excel = \Excel::create('report');
        $excel->sheet('Лист 1');

        $activeSheet = $excel->getActiveSheet();

        $activeSheet->appendRow(1, $headlines);
        $row = 2;
        $arDebtGroups = \App\DebtGroup::getDebtGroups();
        foreach ($debtors as $debtor) {
            $isOnline = $debtor->debtor_is_online ? 'Да' : 'Нет';
            $typeClaim = '';

            if ($debtor->debtor_is_bigmoney) {
                $typeClaim = 'Б.деньги';
            }
            if ($debtor->debtor_is_pledge) {
                $typeClaim = 'Залоговый';
            }
            if ($debtor->debtor_is_pos) {
                $typeClaim = 'Товарный';
            }
            $sumFactDebtor = null;
            $arrangement = DebtorEventPromisePay::where('debtor_id', $debtor->debtors_id)->latest()->first();
            $loan = $this->armClient->getLoanById1c($debtor->debtors_loan_id_1c);

            if (!$loan->isEmpty()) {
                $ordersDebtor = $this->armClient->getOrdersById($loan->first()->id);
                $filteredOrders = $ordersDebtor->filter(function ($item) use ($arrangement) {
                    return $item->type->plus === 1
                        && Carbon::parse($arrangement->promise_date)
                            ->startOfDay()
                            ->lessThan(Carbon::parse($item->created_at)->endOfDay());
                });

                $sumFactDebtor = $filteredOrders->sum('money');
            }

            $params = [
                Carbon::parse($debtor->debtors_fixation_date)->format('d.m.Y'),
                $debtor->passports_fio,
                $debtor->debtor_customer_id_1c,
                $debtor->debtors_loan_id_1c,
                $debtor->debtors_qty_delays,
                StrUtils::kopToRub($debtor->debtors_sum_indebt),
                StrUtils::kopToRub($debtor->debtors_od),
                $debtor->debtors_base,
                $typeClaim,
                $isOnline,
                $arDebtGroups[$debtor->debtors_debt_group],
                $debtor->debtors_username,
                Carbon::parse($arrangement->promise_date)->format('d.m.Y'),
                StrUtils::kopToRub($arrangement->amount),
                $sumFactDebtor ? StrUtils::kopToRub($sumFactDebtor) : '',
            ];


            $activeSheet->appendRow($row, $params);
            $row++;
        }
        $excel->download();
    }


    public function exportEvents(Request $req)
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
        $totalEvents = $debtorEvents->get();

        $headlines = [
            'Дата план',
            'Тип мероприятия',
            'ФИО должника',
            'Дата факт',
            'Ответственный',
        ];

        $excel = \Excel::create('report');
        $excel->sheet('Лист 1');

        $activeSheet = $excel->getActiveSheet();

        $activeSheet->appendRow(1, $headlines);
        $row = 2;
        foreach ($totalEvents as $event) {
            $params = [
                Carbon::parse($event->de_date)->format('d.m.Y'),
                (DebtorsEventType::where('id',$event->de_type_id)->first())->name,
                $event->passports_fio,
                Carbon::parse($event->de_created_at)->format('d.m.Y'),
                $event->de_username,
            ];
            $activeSheet->appendRow($row, $params);
            $row++;
        }
        $excel->download();
        exit();
    }
}
