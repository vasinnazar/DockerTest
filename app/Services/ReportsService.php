<?php

namespace App\Services;

use App\Debtor;
use App\MySoap;
use App\User;
use Carbon\Carbon;

class ReportsService
{
    private MySoap $mySoap;

    public function __construct(MySoap $mySoap)
    {
        $this->mySoap = $mySoap;
    }

    public function getPaymentsForUsers(Carbon $startDate, Carbon $endDate, array $userIds): array
    {
        $users = User::whereIn('id', $userIds)->get();
        $result = ['result' => 1, 'payments' => []];
        foreach ($users as $user) {
            $res1c = $this->mySoap->getPaymentsFrom1c($startDate, $endDate, $user->id_1c);
            if ((int)$res1c->result !== 1) {
                return $result;
            }
             foreach ($res1c->tab as $payment) {
                 if (!isset($payment->loan_id_1c)) {
                     continue;
                 }
                 $arLoanId1c = explode(' ', $payment->loan_id_1c);
                 if ($arLoanId1c[0] === 'Продление') {
                     $loanId1c = str_replace('№', '', $arLoanId1c[1]);
                 } else {
                     $loanId1c = $arLoanId1c[0];
                 }

                 $debtor = Debtor::where('customer_id_1c', $payment->customer_id_1c)
                     ->where('loan_id_1c', $loanId1c)
                     ->first();
                 if (!$debtor) {
                     continue;
                 }
                 $payment->debtor_id = $debtor->id;
                 $result['payments'][] = $payment;
             }
        }
        return $result;
    }

}
