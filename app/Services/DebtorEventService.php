<?php

namespace App\Services;

use App\Debtor;
use App\DebtorEvent;
use App\DebtorEventPromisePay;
use App\DebtorUsersRef;
use App\Exceptions\DebtorException;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;


class DebtorEventService
{

    const LIMIT_PER_DAY = 2;
    const LIMIT_PER_WEEK = 4;
    const LIMIT_PER_MONTH = 16;

    /**
     * @throws DebtorException
     */
    public function checkLimitEventByCustomerId1c(string $customerId1c):void
    {
        $arrayEventsLimit = [
            DebtorEvent::SMS_EVENT,
            DebtorEvent::AUTOINFORMER_OMICRON_EVENT,
            DebtorEvent::WHATSAPP_EVENT,
            DebtorEvent::EMAIL_EVENT
        ];
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $countEventDay = $this->getCountEventsByDate($startDate, $endDate, $customerId1c, $arrayEventsLimit);

        if ($countEventDay >= DebtorEventService::LIMIT_PER_DAY) {
            throw new DebtorException('limit_per_day');
        }

        $startWeek = Carbon::now()->startOfWeek();
        $endWeek = Carbon::now()->endOfWeek();
        $countEventWeek = $this->getCountEventsByDate($startWeek, $endWeek, $customerId1c, $arrayEventsLimit);

        if ($countEventWeek >= DebtorEventService::LIMIT_PER_WEEK) {
            throw new DebtorException('limit_per_week');
        }

        $startMonth = Carbon::now()->startOfMonth();
        $endMonth = Carbon::now()->endOfMonth();
        $countEventMonth = $this->getCountEventsByDate($startMonth, $endMonth, $customerId1c, $arrayEventsLimit);

        if ($countEventMonth >= DebtorEventService::LIMIT_PER_MONTH) {
            throw new DebtorException('limit_per_month');
        }
    }

    public function getCountEventsByDate(Carbon $startDate, Carbon $endDate, string $customerId1c, array $eventsType)
    {
        return DebtorEvent::where('customer_id_1c', $customerId1c)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->whereIn('event_type_id', $eventsType)
            ->count();
    }

    public function getPlannedForUser($user, $firstDate, $daysNum = 10)
    {
        $res = [];
        $totalTypes = [];
        $totalDays = [];
        $tableData = [];
        $total = 0;
        $dates = [];
        $cols = [];
        for ($i = 0; $i < $daysNum; $i++) {
            $date = $firstDate->copy()->addDays($i);
            $intname = $date->format('d.m.y');
            $dates[$intname] = [
                $date->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $date->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            ];
            $cols[] = $intname;
            $totalDays[$intname] = 0;
        }
        $usersId = array_merge([$user->id], $user->subordinatedUsers->pluck('id')->toArray());
        foreach ($dates as $intk => $intv) {
            $data = DebtorEvent::select(DB::raw('count(*) as num, event_type_id'))
                ->whereIn('user_id', $usersId)
                ->whereBetween('date', $intv)
                ->where('completed', 0)
                ->groupBy('event_type_id')
                ->get();
            if ($user->hasRole('missed_calls')) {
                $missedCallsUsersId = User::where('banned', 0)
                    ->where('user_group_id', $user->user_group_id)
                    ->get()
                    ->pluck('id')
                    ->toArray();
                $missedCallsEvent = DebtorEvent::select(DB::raw('count(*) as num, event_type_id'))
                    ->whereIn('user_id', $missedCallsUsersId)
                    ->whereBetween('date', $intv)
                    ->where('completed', 0)
                    ->where('event_type_id', 4)
                    ->get();
                foreach ($missedCallsEvent as $mce) {
                    if ($mce->num != 0 && !is_null($mce->event_type_id)) {
                        $data = $data->merge($missedCallsEvent);
                    }
                }
            }
            foreach ($data as $item) {
                $tableData[$item->event_type_id][$intk] = $item->num;

                if (!array_key_exists($item->event_type_id, $totalTypes)) {
                    $totalTypes[$item->event_type_id] = 0;
                }
                if (!array_key_exists($intk, $totalDays)) {
                    $totalDays[$intk] = 0;
                }
            }

            $amountOfAgreement = DebtorEventPromisePay::whereIn('user_id', $usersId)
                ->whereBetween('promise_date', $intv)
                ->sum('amount');

            $amount[$intk] = (is_null($amountOfAgreement)) ? 0 : $amountOfAgreement;

        }
        foreach ($tableData as $tdk => $tdv) {
            foreach ($cols as $col) {
                if (!array_key_exists($col, $tableData[$tdk])) {
                    $tableData[$tdk][$col] = 0;
                }
                $totalDays[$col] += $tableData[$tdk][$col];
                $totalTypes[$tdk] += $tableData[$tdk][$col];
                $total += $tableData[$tdk][$col];
            }
        }
        $res['data'] = $tableData;
        $res['cols'] = $cols;
        $res['total_types'] = $totalTypes;
        $res['total_days'] = $totalDays;
        $res['total'] = $total;
        $res['totalDayAmount'] = $amount;
        return $res;
    }

    public function getDebtorEventsForCustomer($debtors)
    {
        $arDebtorIds = [];
        foreach ($debtors as $debtor) {
            $arDebtorIds[] = $debtor->debtor_id_1c;
        }

        return DebtorEvent::select(DB::raw('*, debtor_events.id as id, debtor_events.created_at as de_created_at, debtors_events_promise_pays.promise_date as promise_date, debtors_events_promise_pays.amount as promise_amount'))
            ->leftJoin('users', 'users.id', '=', 'debtor_events.user_id')
            ->leftJoin('customers', 'customers.id_1c', '=', 'debtor_events.customer_id_1c')
            ->leftJoin('debtors_events_promise_pays', 'debtors_events_promise_pays.event_id', '=', 'debtor_events.id')
            ->whereIn('debtor_id_1c', $arDebtorIds)
            ->orderBy('de_created_at', 'desc')
            ->get();
    }

    public function getEventsForExport(Request $req)
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
        return $debtorEvents->get();
    }
}
