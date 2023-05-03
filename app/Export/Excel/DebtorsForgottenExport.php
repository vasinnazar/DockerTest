<?php

namespace App\Export\Excel;

use App\DebtGroup;
use App\Debtor;
use App\User;
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
        foreach ($this->debtors as $debtor) {

            $nameDebtorGroup = DebtGroup::where('id', $debtor->debt_group_id)->first();
            $item = collect([
                Carbon::parse($debtor->fixation_date)->format('d.m.Y'),
                $debtor->passport->fio,
                $debtor->loan_id_1c,
                $debtor->qty_delays,
                $debtor->sum_indebt / 100,
                $debtor->od / 100,
                $debtor->base,
                $debtor->customer->telephone,
                $nameDebtorGroup ? $nameDebtorGroup->name : '',
                (User::where('id_1c', $debtor->responsible_user_id_1c)->first())->name,
                $debtor->str_podr
            ]);
            $collectdebtor->push($item);
        }
        return $collectdebtor;
    }
}
