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
        return $this->model::query()->findOrFail($id);
    }

    public function findByCustomerAndSmsId(string $customerId1C, int $smsId): DebtorEventSms
    {
        return DebtorEventSms::where('customer_id_1c', $customerId1C)
            ->where('sms_id', $smsId)
            ->latest()
            ->first();
    }
}
