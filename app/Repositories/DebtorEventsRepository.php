<?php

namespace App\Repositories;

use App\Debtor;
use App\DebtorEvent;
use App\User;
use Carbon\Carbon;

class DebtorEventsRepository
{
    private $model;

    public function __construct(DebtorEvent $model)
    {
        $this->model = $model;
    }

    public function firstById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function createEvent(
        Debtor $debt,
        User $user,
        string $report,
        int $eventTypeId,
        int $overdueReasonId,
        int $eventResultId,
        int $completed
    ): DebtorEvent {

        return DebtorEvent::create([
            'debtor_id' => $debt->id,
            'debtor_id_1c' => $debt->debtor_id_1c,
            'customer_id_1c' => $debt->customer_id_1c,
            'loan_id_1c' => $debt->loan_id_1c,
            'debt_group_id' => $debt->debt_group_id,
            'user_id' => $user->id,
            'user_id_1c' => $user->id_1c,
            'event_type_id' => $eventTypeId,
            'report' => $report,
            'refresh_date' => Carbon::now(),
            'overdue_reason_id' => $overdueReasonId,
            'event_result_id' => $eventResultId,
            'completed' => $completed,
        ]);
    }
}
