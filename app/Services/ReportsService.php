<?php

namespace App\Services;

use App\MySoap;
use App\User;
use Carbon\Carbon;

class ReportsService
{
    public function getPaymentsForUsers(Carbon $startDate, Carbon $endDate, array $userIds): array
    {
        $users = User::whereIn('id', $userIds)->get();
        $result = ['result' => 1, 'payments' => []];
        foreach ($users as $user) {
            $xml = \App\MySoap::createXML([
                'type' => 'GetDebtorPayment',
                'start_date' => $startDate->startOfDay()->format('YmdHis'),
                'end_date' => $endDate->endOfDay()->format('YmdHis'),
                'debtor_id_1c' => $user->id_1c
            ]);
            $res1c = MySoap::sendXML(
                $xml,
                false,
                'Main',
                config('1c.exchange_arm')
            );
            if ((int)$res1c->result !== 1) {
                return $result;
            }
            $obj = json_decode(json_encode($res1c));
            foreach ($obj->tab as $payment) {
                if (!isset($payment->loan_id_1c)) {
                    continue;
                }
                $arLoanId1c = explode(' ', $payment->loan_id_1c);
                if ($arLoanId1c[0] === 'Продление') {
                    $loanId1c = str_replace('№', '', $arLoanId1c[1]);
                } else {
                    $loanId1c = $arLoanId1c[0];
                }
                $debtor = \App\Debtor::where('customer_id_1c', $payment->customer_id_1c)
                    ->where('loan_id_1c', $loanId1c)
                    ->first();
                if ($debtor) {
                    $payment->debtor_id = $debtor->id;
                }
                $result['payments'][] = $payment;
            }
        }
        return $result;
    }

}
