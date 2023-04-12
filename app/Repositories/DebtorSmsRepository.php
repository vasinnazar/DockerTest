<?php

namespace App\Repositories;

use App\Debtor;
use App\DebtorSmsTpls;
use App\StrUtils;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DebtorSmsRepository
{
    private $model;

    public function __construct(DebtorSmsTpls $model)
    {
        $this->model = $model;
    }

    public function firstById(int $id):Model
    {
        return $this->model::query()->findOrFail($id);
    }
    public function getSmsTpls(string $recoveryType, bool $isUbytki = false): Collection
    {
        $q = $this->model::query()->where('recovery_type', $recoveryType);
        if ($recoveryType == 'remote' && $isUbytki) {
            $q->where('sort', 1);
        } else {
            $q->where('sort', null);
        }
        return $q->get();
    }

    public function getSmsForDebtorByUser(User $user, Debtor $debtor): \Illuminate\Support\Collection
    {
        $recoveryType = null;
        $isUbytki = null;
        if ($user->isDebtorsRemote()) {
            $recoveryType = 'remote';
            $isUbytki = ($debtor->base == 'Архив убытки' || $debtor->base == 'Архив компании') ? true : false;
        }
        if ($user->isDebtorsPersonal()) {
            $recoveryType = 'personal';
            $isUbytki = false;
        }
        if (is_null($recoveryType) && is_null($isUbytki)) {
            return collect();
        }

        $arDebtorFullName = explode(' ', $debtor->passport->first()->name);
        return $this->getSmsTpls($recoveryType, $isUbytki)->map(function ($item) use (
            $user,
            $debtor,
            $arDebtorFullName
        ) {
            $item->text_tpl = str_replace(
                [
                    '##spec_phone##',
                    '##sms_till_date##',
                    '##sms_loan_info##',
                    '##sms_debtor_name##'
                ],
                [
                    (mb_strlen($user->phone) < 6) ? '88003014344' : $user->phone,
                    Carbon::today()->format('d.m.Y'),
                    $debtor->loan_id_1c . ' от ' . StrUtils::dateToStr($debtor->loan->created_at),
                    ($arDebtorFullName[1] ?? '') . ' ' . ($arDebtorFullName[2] ?? '')
                ],
                $item->text_tpl
            );
            return $item;
        });
    }
}
