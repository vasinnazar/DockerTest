<?php

namespace App\Services;

use App\ContractForm;
use App\CourtOrder;
use App\Debtor;
use App\DebtorEvent;
use App\Http\Controllers\ContractEditorController;
use App\Loan;
use App\StrUtils;
use App\Utils\PdfUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PdfService
{

    public function getCourtOrder(Debtor $debtor)
    {
        $contractForm = ContractForm::where('text_id', CourtOrder::TEXT_ID_FORM_ORDER)->first();
        $html = $this->replaceValuesCourtOrder($contractForm->template, $debtor);

        DebtorEvent::create([
            'debtor_id' => $debtor->id,
            'debtor_id_1c' => $debtor->debtor_id_1c,
            'customer_id_1c' => $debtor->customer_id_1c,
            'loan_id_1c' => $debtor->loan_id_1c,
            'debt_group_id' => $debtor->debt_group_id,
            'user_id' => Auth::user()->id,
            'last_user_id' => Auth::user()->id,
            'user_id_1c' => Auth::user()->id_1c,
            'event_type_id' => 25,
            'report' => 'Отправлена копия заявления',
            'refresh_date' => Carbon::now(),
            'overdue_reason_id' => 0,
            'event_result_id' => 30,
            'completed' => 1,
        ]);

        CourtOrder::create([
            'debtor_id'=>$debtor->id,
            'is_printed'=>1,
        ]);

        return PdfUtil::getPdfFromPrintServer($html);
    }

    public function replaceValuesCourtOrder($html, $debtor)
    {
        $loan = Loan::where('id_1c', $debtor->loan_id_1c)->first();
        $arraySumDebtor = $loan->getDebtFrom1cWithoutRepayment();
        $passportDebtor = $debtor->customer()->getLastPassport();
        $duty = $this->getStateDuty($arraySumDebtor->money / 100);

        $full_address = $passportDebtor->address_region . ',';

        $full_address .= $passportDebtor->address_district != '' ?
            $passportDebtor->address_district . ',' . $passportDebtor->address_city . ',' :
            $passportDebtor->address_city . ',';

        $full_address .= $passportDebtor->address_street . ',' . $passportDebtor->address_house . ',';

        $full_address .= $passportDebtor->address_building != '' ?
            $passportDebtor->address_building . ',' . $passportDebtor->address_apartment . ',' :
            $passportDebtor->address_apartment;

        $html = str_replace('{{#fio}}', $passportDebtor->fio, $html);
        $html = str_replace('{{#birth_address}}', $passportDebtor->birth_city, $html);
        $html = str_replace('{{#birth_day}}', Carbon::parse($passportDebtor->birth_date)->format('d.m.Y'), $html);
        $html = str_replace('{{#series}}', $passportDebtor->series, $html);
        $html = str_replace('{{#number}}', $passportDebtor->number, $html);
        $html = str_replace('{{#number}}', $passportDebtor->number, $html);
        $html = str_replace('{{#address_residential}}', $full_address, $html);
        $html = str_replace('{{#amounts_recovery}}', ($arraySumDebtor->money / 100), $html);
        $html = str_replace('{{#loan_id_1c}}', $debtor->loan_id_1c, $html);
        $html = str_replace('{{#loan_amount}}', ($arraySumDebtor->od / 100), $html);
        $html = str_replace('{{#loan_amount_symbol}}', StrUtils::num2str($arraySumDebtor->od / 100), $html);
        $html = str_replace('{{#loan_term}}', $loan->time, $html);
        $html = str_replace('{{#basic_percent}}', ($arraySumDebtor->pc / 100) . ' ', $html);
        $html = str_replace('{{#basic_percent_symbol}}', StrUtils::num2str($arraySumDebtor->pc / 100), $html);

        $html = str_replace('{{#date_start_contract}}', $loan->created_at->format('d.m.Y'), $html);
        $html = str_replace('{{#date_now}}', Carbon::now()->format('d.m.Y'), $html);
        $html = str_replace('{{#date_start_contractM}}', StrUtils::dateToStr($loan->created_at->toDateTimeString()),
            $html);
        $html = str_replace('{{#date_end_contractM}}',
            StrUtils::dateToStr($loan->created_at->addDay($loan->time)->toDateTimeString()), $html);
        $html = str_replace('{{#date_end_contract}}',
            Carbon::now()->subDays($debtor->qty_delays)->format('d.m.Y'),
            $html);
        $html = str_replace('{{#date_end_contract_dop}}',
            Carbon::now()->format('d.m.Y'), $html);
        $html = str_replace('{{#exp_percent}}', ($arraySumDebtor->exp_pc / 100), $html);
        $html = str_replace('{{#qty_delays}}', $debtor->qty_delays, $html);
        $html = str_replace('{{#duty}}', $duty, $html);
        $html = str_replace('{{#all_amount}} ', $duty + ($arraySumDebtor->money / 100), $html);
        $html = str_replace('{{#fine}} ', $arraySumDebtor->fine / 100, $html);

        return $html;
    }

    /**
     * Расчет гос. пошлины:
     * до 20 000 рублей — 4% цены иска, но не менее 400 рублей. Итоговую сумму делим на 2.
     * от 20 001 рубля до 100 000 рублей — 800 рублей + 3% от суммы превышающий 20 000, итоговую сумму делим на 2.
     * от 100 001 рубля до 200 000 рублей — 3200 рублей + 2% от суммы превышающей 100 000, итоговую сумму делим на 2.
     * @param int $amount
     * @return float|int|void
     */
    public function getStateDuty(int $amount)
    {
        $duty = null;
        if ($amount < 20000) {
            $duty = ($amount / 100) * 4;
            $duty = ($duty < 400) ? 400 / 2 : $duty / 2;
        }

        if ($amount >= 20001 && $amount <= 100000) {
            $duty = ($amount / 100) * 3;
            $duty = (800 + $duty) /2 ;
        }

        if ($amount >= 100001 && $amount <= 200000) {
            $duty = ($amount / 100) * 2;
            $duty = (3200 + $duty) / 2 ;
        }

        return $duty;
    }
}
