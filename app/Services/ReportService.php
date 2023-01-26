<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\DebtorEventPromisePay;
use App\Passport;
use App\StrUtils;
use Carbon\Carbon;

class ReportService
{
    private $armClient;

    public function __construct(ArmClient $client)
    {
        $this->armClient = $client;
    }

    public function reportToExcelDebtors($debtors)
    {

        $headlines = [
            'Дата закрепления',
            'ФИО должника',
            'Код контрагента',
            'Номер договора',
            'Срок просрочки',
            'Сумма задолженности',
            'Сумма ОД',
            'База',
            'Тип договора',
            'Онлайн',
            'Телефон',
            'Группа долга',
            'ФИО специалиста',
            'Стр. подр.',
            'Юр. адрес',
            'Факт. адрес',
            'Большие деньги',
            'Залоговый займ',
            'Товарный займ',
            'Разница времени',
        ];

        $excel = \Excel::create('report');
        $sheet = $excel->sheet('page1');

        $activeSheet = $excel->getActiveSheet();

        $activeSheet->appendRow(1, $headlines);
        $row = 2;
        $arDebtGroups = \App\DebtGroup::getDebtGroups();
        foreach ($debtors as $debtor) {

            $isOnline = $debtor->debtor_is_online ? 'Да' : 'Нет';
            $passport = Passport::find($debtor->passport_id);
            $typeClaim = '';

            if ($debtor->debtor_is_bigmoney) {
                $typeClaim = 'Б.деньги';
            }
            if ($debtor->debtor_is_pledge) {
                $typeClaim = 'Залоговый';
            }
            if ($debtor->debtor_is_pos) {
                $typeClaim = 'Товарный';
            }
            $params = [
                Carbon::parse($debtor->debtors_fixation_date)->format('d.m.Y'),
                $debtor->passports_fio,
                $debtor->debtor_customer_id_1c,
                $debtor->debtors_loan_id_1c,
                $debtor->debtors_qty_delays,
                StrUtils::kopToRub($debtor->debtors_sum_indebt),
                StrUtils::kopToRub($debtor->debtors_od),
                $debtor->debtors_base,
                $typeClaim,
                $isOnline,
                $arDebtGroups[$debtor->debtors_debt_group],
                $debtor->debtors_username,
                $debtor->debtor_str_podr,
                Passport::getFullAddress($passport),
                Passport::getFullAddress($passport, true),
                $debtor->debtor_is_bigmoney,
                $debtor->debtor_is_pledge,
                $debtor->debtor_is_pos,
                $debtor->passports_fact_timezone,
            ];
            $activeSheet->appendRow($row, $params);
            $row++;
        }
        $excel->download();
    }

    public function reportOnAgreementWithDebtorstoEcxel($debtors)
    {
        $headlines = [
            'Дата закрепления',
            'ФИО должника',
            'Код контрагента',
            'Номер договора',
            'Срок просрочки',
            'Сумма задолженности',
            'Сумма ОД',
            'База',
            'Тип договора',
            'Онлайн',
            'Группа долга',
            'ФИО специалиста',
            'Дата договоренностей',
            'Сумма договоренностей',
            'Фактически поступившая сумма',
        ];


        $excel = \Excel::create('report');
        $sheet = $excel->sheet('page1');

        $activeSheet = $excel->getActiveSheet();

        $activeSheet->appendRow(1, $headlines);
        $row = 2;
        $arDebtGroups = \App\DebtGroup::getDebtGroups();
        foreach ($debtors as $debtor) {
            $isOnline = $debtor->debtor_is_online ? 'Да' : 'Нет';
            $typeClaim = '';

            if ($debtor->debtor_is_bigmoney) {
                $typeClaim = 'Б.деньги';
            }
            if ($debtor->debtor_is_pledge) {
                $typeClaim = 'Залоговый';
            }
            if ($debtor->debtor_is_pos) {
                $typeClaim = 'Товарный';
            }
            $sumFactDebtor = null;
            $arrangement = DebtorEventPromisePay::where('debtor_id', $debtor->debtors_id)->latest()->first();
            $loan = $this->armClient->getLoanById1c($debtor->debtors_loan_id_1c);

            if (!$loan->isEmpty()) {
                $ordersDebtor = $this->armClient->getOrdersById($loan->first()->id);

                $filteredOrders = $ordersDebtor->filter(function ($item) use ($arrangement) {
                    return $item->type->plus === 1
                        && Carbon::parse($arrangement->promise_date)
                            ->startOfDay()
                            ->lessThan(Carbon::parse($item->created_at)->endOfDay());
                });

                foreach ($filteredOrders as $order) {
                    $sumFactDebtor += $order->money;
                }
            }

            $params = [
                Carbon::parse($debtor->debtors_fixation_date)->format('d.m.Y'),
                $debtor->passports_fio,
                $debtor->debtor_customer_id_1c,
                $debtor->debtors_loan_id_1c,
                $debtor->debtors_qty_delays,
                StrUtils::kopToRub($debtor->debtors_sum_indebt),
                StrUtils::kopToRub($debtor->debtors_od),
                $debtor->debtors_base,
                $typeClaim,
                $isOnline,
                $arDebtGroups[$debtor->debtors_debt_group],
                $debtor->debtors_username,
                Carbon::parse($arrangement->promise_date)->format('d.m.Y'),
                StrUtils::kopToRub($arrangement->amount),
                $sumFactDebtor ? StrUtils::kopToRub($sumFactDebtor) : '',
            ];


            $activeSheet->appendRow($row, $params);
            $row++;
        }
        $excel->download();
    }
}
