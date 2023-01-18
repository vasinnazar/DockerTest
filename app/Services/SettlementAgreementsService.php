<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\Debtor;
use Carbon\Carbon;

class SettlementAgreementsService
{
    private $armClient;

    public function __construct(ArmClient $client)
    {
        $this->armClient = $client;
    }

    /**
     * Автоматическое мировое соглашение для должников стадии УПР
     * @return void
     */
    public function autoSettlementAgreements()
    {
        $debtors = Debtor::where('is_debtor', 1)
            ->where('str_podr', '000000000006')
            ->whereIn('debt_group_id', [2, 4, 5])
            ->where('qty_delays', 37)
            ->where('od', '>=', 500000)
            ->where('base', 'Б-1')
            ->get();

        foreach ($debtors as $debtor) {

            $ordersDebtor = $this->armClient->getOrdersByLoanId1c($debtor->loan_id_1c);
            $filteredOrders = $ordersDebtor->filter(function ($item) {
                return $item->type->plus === 1 && $item->money > 50000;
            });

            $amount = ($debtor->od + $debtor->pc + $debtor->exp_pc + $debtor->fine) * 0.3;

            if (empty($filteredOrders)) {
                $this->armClient->sendSettlementAgreements([
                    'repayment_type_id' => 14,
                    'times' => 60,
                    'amount' => (int)$amount,
                    'start_at' => Carbon::now()->format('Y-m-d'),
                    'end_at' => Carbon::now()->addDay(14)->format('Y-m-d'),
                    'loan_id_1c' => $debtor->loan_id_1c,
                    'prepaid' => 0,
                    'multiple' => 1
                ]);
            }
        }
    }

    public function sendPeaceClaims(Debtor $debtor)
    {
        if ($debtor->sum_indebt >= 500000 && $debtor->sum_indebt <= 1000000) {
            $options[] = [
                'repayment_type_id' => 14,
                'times' => 90,
                'amount' => 200000,
                'start_at' => Carbon::now()->format('Y-m-d'),
                'end_at' => Carbon::now()->addDay(60)->format('Y-m-d'),
                'loan_id_1c' => $debtor->loan_id_1c,
                'prepaid' => 0,
                'multiple' => 1
            ];
            $options[] = [
                'repayment_type_id' => 14,
                'times' => 60,
                'amount' => 300000,
                'start_at' => Carbon::now()->format('Y-m-d'),
                'end_at' => Carbon::now()->addDay(60)->format('Y-m-d'),
                'loan_id_1c' => $debtor->loan_id_1c,
                'prepaid' => 0,
                'multiple' => 1
            ];
        }

        if ($debtor->sum_indebt >= 1000100 && $debtor->sum_indebt <= 2000000) {
            $options[] = [
                'repayment_type_id' => 14,
                'times' => 150,
                'amount' => 200000,
                'start_at' => Carbon::now()->format('Y-m-d'),
                'end_at' => Carbon::now()->addDay(60)->format('Y-m-d'),
                'loan_id_1c' => $debtor->loan_id_1c,
                'prepaid' => 0,
                'multiple' => 1
            ];
            $options[] = [
                'repayment_type_id' => 14,
                'times' => 150,
                'amount' => 300000,
                'start_at' => Carbon::now()->format('Y-m-d'),
                'end_at' => Carbon::now()->addDay(60)->format('Y-m-d'),
                'loan_id_1c' => $debtor->loan_id_1c,
                'prepaid' => 0,
                'multiple' => 1
            ];
            $options[] = [
                'repayment_type_id' => 14,
                'times' => 90,
                'amount' => 500000,
                'start_at' => Carbon::now()->format('Y-m-d'),
                'end_at' => Carbon::now()->addDay(60)->format('Y-m-d'),
                'loan_id_1c' => $debtor->loan_id_1c,
                'prepaid' => 0,
                'multiple' => 1
            ];
        }

        if ($debtor->sum_indebt > 2000000) {
            $options[] = [
                'repayment_type_id' => 14,
                'times' => 300,
                'amount' => 300000,
                'start_at' => Carbon::now()->format('Y-m-d'),
                'end_at' => Carbon::now()->addDay(60)->format('Y-m-d'),
                'loan_id_1c' => $debtor->loan_id_1c,
                'prepaid' => 0,
                'multiple' => 1
            ];
            $options[] = [
                'repayment_type_id' => 14,
                'times' => 300,
                'amount' => 500000,
                'start_at' => Carbon::now()->format('Y-m-d'),
                'end_at' => Carbon::now()->addDay(60)->format('Y-m-d'),
                'loan_id_1c' => $debtor->loan_id_1c,
                'prepaid' => 0,
                'multiple' => 1
            ];
            $options[] = [
                'repayment_type_id' => 14,
                'times' => 300,
                'amount' => 700000,
                'start_at' => Carbon::now()->format('Y-m-d'),
                'end_at' => Carbon::now()->addDay(60)->format('Y-m-d'),
                'loan_id_1c' => $debtor->loan_id_1c,
                'prepaid' => 0,
                'multiple' => 1
            ];
        }

        if (isset($options)) {
            foreach ($options as $option) {
                $this->armClient->sendSettlementAgreements($option);
            }
        }
    }

}
