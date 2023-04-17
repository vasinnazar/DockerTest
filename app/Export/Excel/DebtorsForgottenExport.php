<?php

namespace App\Export\Excel;

use App\DebtGroup;
use App\Debtor;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DebtorsForgottenExport implements FromCollection, WithHeadings
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
            'ФИО',
            'Договор',
            'Дней просрочки',
            'Задолженность',
            'Осн. долг',
            'База',
            'Телефон',
            'Группа долга',
            'Ответственный',
            'Структурное подразделение'
        ];
    }

    public function collection()
    {
        $collectdebtor = collect();
        foreach ($this->debtors as $debtorItem) {

            $debtor = Debtor::find($debtorItem['debtors_id']);
            $nameDebtorGroup = DebtGroup::where('id', $debtor->debt_group_id)->first();
            $item = collect([
                Carbon::parse($debtorItem['debtors_fixation_date'])->format('d.m.Y'),
                $debtorItem['passports_fio'],
                $debtor->loan_id_1c,
                $debtor->qty_delays,
                $debtor->sum_indebt / 100,
                $debtor->od / 100,
                $debtor->base,
                $debtor->customer->telephone,
                $nameDebtorGroup ? $nameDebtorGroup->name : '',
                $debtorItem['debtors_username'],
                $debtorItem['debtor_str_podr']
            ]);
            $collectdebtor->push($item);
        }
        return $collectdebtor;
    }
}
