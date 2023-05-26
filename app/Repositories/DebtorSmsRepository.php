<?php

namespace App\Repositories;

use App\DebtorSmsTpls;
use App\Model\DebtorEventSms;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DebtorSmsRepository
{
    private $model;

    public function __construct(DebtorSmsTpls $model)
    {
        $this->model = $model;
    }

    public function firstById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function getSms(string $recoveryType, bool $isUbytki = false): Collection
    {
        return $this->model
            ->where('recovery_type', $recoveryType)
            ->bySort($recoveryType, $isUbytki)
            ->get();
    }
}
