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

    public function getSmsTpls(string $recoveryType, bool $isUbytki = false): Collection
    {
        $q = $this->model::query()->where('recovery_type', $recoveryType);
        if ($recoveryType == 'remote' && $isUbytki) {
            return $q->where('sort', 1)->get();
        }
        return $q->where('sort', null)->get();
    }

    public function getSmsForDebtorByUser(User $user, Debtor $debtor): \Illuminate\Support\Collection
    {
        $recoveryType = null;
        $isUbytki = null;
        if ($user->isDebtorsPersonal()) {
            $recoveryType = 'remote';
            $isUbytki = ($debtor->base == 'Архив убытки' || $debtor->base == 'Архив компании') ? true : false;
        }
        if ($user->isDebtorsRemote()) {
            $recoveryType = 'personal';
            $isUbytki = false;
        }
        if (is_null($recoveryType) && is_null($isUbytki)) {
            return collect();
        }

        $arDebtorFullName = explode(' ', $debtor->passport->first()->name);
        $sms = $this->getSmsTpls($recoveryType, $isUbytki)->map(function ($item) use (
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

        $isSendOnce = $this->checkSmsOnce($debtor, 21);
        $isFirstCondition = ($debtor->qty_delays != 80 && !in_array($debtor->base, [
                'Б-3',
                'Б-риски',
                'КБ-график',
                'Б-график'
            ])
        );
        $isSecondCondition = ($debtor->qty_delays != 20 && !in_array($debtor->base, ['Б-МС']));
        if ($isFirstCondition && $isSecondCondition && !$isSendOnce) {
            $sms = $sms->reject(function ($item) {
                return $item->id == 21;
            });
        }
        $isSendOnce = $this->checkSmsOnce($debtor, 45);
        $isFirstCondition = ($debtor->qty_delays != 95 && !in_array($debtor->base, [
                'Б-3',
                'Б-риски',
                'КБ-график',
                'Б-график'
            ])
        );
        $isSecondCondition = ($debtor->qty_delays != 25 && !in_array($debtor->base, ['Б-МС']));
        if ($isFirstCondition && $isSecondCondition && !$isSendOnce) {
            $sms = $sms->reject(function ($item) {
                return $item->id == 45;
            });
        }
        return $sms;
    }

    public function checkSmsOnce(Debtor $debtor, int $smsId): Boolean
    {
        $eventSms = DebtorEventSms::where('cusomer_id_1c', $debtor->customer_id_1c)
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
