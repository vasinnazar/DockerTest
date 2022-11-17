<?php

namespace App\Services;

use App\Debtor;
use App\DebtorEvent;
use App\Exceptions\DebtorException;
use Carbon\Carbon;

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
    public function checkLimitEvent(Debtor $debtor,int $typeEvent)
    {
        $stringFormatDate = 'Y-m-d H:i:s';
        $countEventDay = DebtorEvent::where('debtor_id_1c', $debtor->debtor_id_1c)
            ->where('created_at', '>=', Carbon::now()->startOfDay())
            ->where('created_at', '<=', Carbon::now()->endOfDay())
            ->where('event_type_id', $typeEvent)
            ->count();

        if ($countEventDay >= DebtorEventService::LIMIT_PER_DAY) {
            throw new DebtorException('limit_per_day');
        }

        $startWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->format($stringFormatDate);
        $endWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->format($stringFormatDate);
        $countEventWeek = DebtorEvent::where('debtor_id_1c', $debtor->debtor_id_1c)
            ->where('created_at', '>=', $startWeek)
            ->where('created_at', '<=', $endWeek)
            ->where('event_type_id', $typeEvent)
            ->count();

        if ($countEventWeek >= DebtorEventService::LIMIT_PER_WEEK) {
            throw new DebtorException('limit_per_week');
        }
        $startMonth = Carbon::now()->startOfMonth()->format($stringFormatDate);
        $endMonth = Carbon::now()->endOfMonth()->format($stringFormatDate);

        $countEventMonth = DebtorEvent::where('debtor_id_1c', $debtor->debtor_id_1c)
            ->where('created_at', '>=', $startMonth)
            ->where('created_at', '<=', $endMonth)
            ->where('event_type_id', $typeEvent)
            ->count();

        if ($countEventMonth >= DebtorEventService::LIMIT_PER_MONTH) {
            throw new DebtorException('limit_per_month');
        }
  }
}
