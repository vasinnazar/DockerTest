<?php

namespace App\Repositories;

use App\Model\DebtorEventEmail;

class DebtorEventEmailRepository
{
    private $model;

    public function __construct(DebtorEventEmail $model)
    {
        $this->model = $model;
    }

    public function firstById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function findByCustomerId1c(string $customerId1c)
    {
        return $this->model->where('customer_id_1c', $customerId1c)->get();
    }
    public function create(string $customerId1c, string $message, bool $status, int $eventId = null): DebtorEventEmail
    {
        return DebtorEventEmail::create([
            'customer_id_1c' => $customerId1c,
            'message' => $message,
            'status' => $status,
            'event_id' => $eventId,
        ]);
    }
}
