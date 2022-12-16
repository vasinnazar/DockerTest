<?php

namespace App\Services;

use App\Debtor;
use App\DebtorEvent;
use App\DebtorUsersRef;
use App\Exceptions\DebtorException;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DebtorEventService
{

    const LIMIT_PER_DAY = 2;
    const LIMIT_PER_WEEK = 4;
    const LIMIT_PER_MONTH = 16;

    /**
     * @param Debtor $debtor
     * @param int $typeEvent
     * @return void
     * @throws DebtorException
     */
    public function checkLimitEvent(Debtor $debtor)
    {
        $stringFormatDate = 'Y-m-d H:i:s';
        $countEventDay = DebtorEvent::where('debtor_id_1c', $debtor->debtor_id_1c)
            ->where('created_at', '>=', Carbon::now()->startOfDay())
            ->where('created_at', '<=', Carbon::now()->endOfDay())
            ->whereIn('event_type_id', [
                DebtorEvent::SMS_EVENT,
                DebtorEvent::AUTOINFORMER_OMICRON_EVENT,
                DebtorEvent::WHATSAPP_EVENT,
                DebtorEvent::EMAIL_EVENT
            ])
            ->count();

        if ($countEventDay >= DebtorEventService::LIMIT_PER_DAY) {
            throw new DebtorException('limit_per_day');
        }

        $startWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->format($stringFormatDate);
        $endWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->format($stringFormatDate);
        $countEventWeek = DebtorEvent::where('debtor_id_1c', $debtor->debtor_id_1c)
            ->where('created_at', '>=', $startWeek)
            ->where('created_at', '<=', $endWeek)
            ->whereIn('event_type_id', [
                DebtorEvent::SMS_EVENT,
                DebtorEvent::AUTOINFORMER_OMICRON_EVENT,
                DebtorEvent::WHATSAPP_EVENT,
                DebtorEvent::EMAIL_EVENT
            ])
            ->count();

        if ($countEventWeek >= DebtorEventService::LIMIT_PER_WEEK) {
            throw new DebtorException('limit_per_week');
        }
        $startMonth = Carbon::now()->startOfMonth()->format($stringFormatDate);
        $endMonth = Carbon::now()->endOfMonth()->format($stringFormatDate);

        $countEventMonth = DebtorEvent::where('debtor_id_1c', $debtor->debtor_id_1c)
            ->where('created_at', '>=', $startMonth)
            ->where('created_at', '<=', $endMonth)
            ->whereIn('event_type_id', [
                DebtorEvent::SMS_EVENT,
                DebtorEvent::AUTOINFORMER_OMICRON_EVENT,
                DebtorEvent::WHATSAPP_EVENT,
                DebtorEvent::EMAIL_EVENT
            ])
            ->count();

        if ($countEventMonth >= DebtorEventService::LIMIT_PER_MONTH) {
            throw new DebtorException('limit_per_month');
        }
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
        $usersId = array_merge([$user->id], json_decode(DebtorUsersRef::getDebtorSlaveUsers($user->id), true));
        foreach ($dates as $intk => $intv) {
            $data = collect(DebtorEvent::select(DB::raw('count(*) as num, event_type_id'))
                ->whereIn('user_id', $usersId)
                ->whereBetween('date', $intv)
                ->where('completed', 0)
                ->groupBy('event_type_id')
                ->get());
            if($user->hasRole('missed_calls')){
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
                        $data = $data->merge(collect($missedCallsEvent));
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
        return $res;
    }
}
