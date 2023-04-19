<?php

namespace App\Repositories;

use App\Debtor;
use App\DebtorSmsTpls;
use App\Model\DebtorEventSms;
use App\StrUtils;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Boolean;

class DebtorSmsRepository
{
    private $model;

    public function __construct(DebtorSmsTpls $model)
    {
        $this->model = $model;
    }

    public function firstById(int $id): Model
    {
        return $this->model::query()->findOrFail($id);
    }

    public function getSms(string $recoveryType, bool $isUbytki = false): Collection
    {
        return $this->model::query()
            ->where('recovery_type', $recoveryType)
            ->bySort($recoveryType, $isUbytki)
            ->get();
    }


    public function checkSmsOnce(Debtor $debtor, int $smsId): Boolean
    {
        $eventSms = DebtorEventSms::where('customer_id_1c', $debtor->customer_id_1c)
            ->where('sms_id', $smsId)
            ->latest()
            ->first();
        if (!$eventSms) {
            return true;
        }
        if ($debtor->base != $eventSms->debtor_base) {
            $eventSms->delete();
            return true;
        }
        return false;
    }
}
