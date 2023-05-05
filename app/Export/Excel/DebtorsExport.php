<?php

namespace App\Export\Excel;

use App\Passport;
use App\StrUtils;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DebtorsExport implements FromCollection,WithHeadings
{
    private $debtors;

    public function __construct($debtors)
    {
        $this->debtors = $debtors;
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
    }

    public function collection()
    {

        $params = collect();
        $arDebtGroups = \App\DebtGroup::getDebtGroups();

        foreach ($this->debtors as $debtor) {

            $isOnline = $debtor->debtor_is_online ? 'Да' : 'Нет';
            $passport = Passport::find($debtor->passport_id);
            $typeClaim = '';
            try {
                $debtGroup = $arDebtGroups[$debtor->debtors_debt_group];
            } catch (\Exception $xception) {
                $debtGroup = '';
            }


            if ($debtor->debtor_is_bigmoney) {
                $typeClaim = 'Б.деньги';
            }
            if ($debtor->debtor_is_pledge) {
                $typeClaim = 'Залоговый';
            }
            if ($debtor->debtor_is_pos) {
                $typeClaim = 'Товарный';
            }
            $item = collect([
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
                $debtor->customers_telephone,
                $debtGroup,
                $debtor->debtors_username,
                $debtor->debtor_str_podr,
                Passport::getFullAddress($passport),
                Passport::getFullAddress($passport, true),
                $debtor->debtor_is_bigmoney,
                $debtor->debtor_is_pledge,
                $debtor->debtor_is_pos,
                $debtor->passports_fact_timezone,
            ]);
            $params->push($item);
        }
        return $params;
    }
}
