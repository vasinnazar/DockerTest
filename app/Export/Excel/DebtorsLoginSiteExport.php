<?php

namespace App\Export\Excel;

use App\Passport;
use App\StrUtils;
use App\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DebtorsLoginSiteExport implements FromCollection, WithHeadings
{
    private $debtors;

    public function __construct($debtors)
    {
        $this->debtors = $debtors;
    }

    public function headings(): array
    {
        return [
            'ФИО',
            'Код контрагента',
            'Ответственный',
            'Общая сумма задолженности',
            'Кол-во договоров',
            'Группа долга'
        ];
    }

    public function collection()
    {
        $params = collect();
        $debtGroups = \App\DebtGroup::get()->toArray();

        foreach ($this->debtors as $debtor) {
            $customer = \App\Customer::where('id_1c', $debtor->customer_id_1c)->first();
            if (is_null($customer)) {
                continue;
            }

            $passport = \App\Passport::where('customer_id', $customer->id)->first();
            if (is_null($passport)) {
                continue;
            }

            $debt = \App\Debtor::where('customer_id_1c', $customer->id_1c)->where('is_debtor', 1)->first();
            if (is_null($debt)) {
                continue;
            }
            $responsible = User::where('id_1c', $debt->responsible_user_id_1c)->first();

            $item = collect([
                $passport->fio,
                $customer->id_1c,
                (!is_null($responsible) ? $responsible->name : ''),
                number_format($debtor->sum_loans_debt / 100, 2, '.', ''),
                $debtor->debt_loans_count,
                (isset($debtGroups[$debtor->debt_group_id]) ? $debtGroups[$debtor->debt_group_id]['name'] : '-')
            ]);
            $params->push($item);
        }
        return $params;
    }
}
