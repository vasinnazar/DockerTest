<?php

namespace App\Repositories;

use App\Model\DebtorEventSms;

class DebtorEventSmsRepository
{
    private $model;

    public function __construct(DebtorEventSms $model)
    {
        $this->model = $model;
    }

    public function firstById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function findByCustomerAndSmsId(string $customerId1C, int $smsId)
    {
        return $this->model->where('customer_id_1c', $customerId1C)
            ->where('sms_id', $smsId)
            ->latest()
            ->first();
    }

    public function create(int $eventId, int $smsId, string $customerId1c, int $debtorId, string $debtorBase): DebtorEventSms
    {
        return DebtorEventSms::create([
            'event_id' => $eventId,
            'sms_id' => $smsId,
            'customer_id_1c' => $customerId1c,
            'debtor_id' => $debtorId,
            'debtor_base' => $debtorBase
        ]);
    }
}
