<?php

namespace App\Export\Excel;

use App\Clients\ArmClient;
use App\DebtorEventPromisePay;
use App\StrUtils;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DebtorsOnAgreementExport implements FromCollection,WithHeadings
{
    private $debtors;
    private $armClient;

    public function __construct(ArmClient $armClient,$debtors)
    {
        $this->debtors = $debtors;
        $this->armClient = $armClient;
    }

    public function headings(): array
    {
        return [
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
    }

    public function collection()
    {
        $arDebtGroups = \App\DebtGroup::getDebtGroups();
        foreach ($this->debtors as $debtor) {
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

                $sumFactDebtor = $filteredOrders->sum('money');
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
        }
        return collect($params);
    }
}
