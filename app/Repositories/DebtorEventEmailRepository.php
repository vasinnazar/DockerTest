<?php

namespace App\Repositories;

use App\Model\DebtorEventEmail;
use Carbon\Carbon;

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

    public function findByDebtorId(string $debtorId)
    {
        return $this->model->where('debtor_id', $debtorId)->get();
    }
    public function create(int $debtorId, string $message, bool $status, string $dateSend): DebtorEventEmail
    {
        return DebtorEventEmail::create([
            'debtor_id' => $debtorId,
            'message' => $message,
            'status' => $status,
            'date_sent' => Carbon::parse($dateSend)->format('Y-m-d'),
        ]);
    }
}
