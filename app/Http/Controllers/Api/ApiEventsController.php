<?php

namespace App\Http\Controllers\Api;

use App\Debtor;
use App\DebtorEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WithoutAcceptEventRequest;
use Illuminate\Support\Facades\Log;

class ApiEventsController extends Controller
{
    public function withoutAcceptEvent(WithoutAcceptEventRequest $req)
    {
        $input = $req->validated();

        Log::info('FromSellingARMController withoutAcceptEvent: input ', [$input]);

        if ($input['customer_id_1c'] && $input['loan_id_1c'] && $input['amount']) {
            $debtor = Debtor::where('customer_id_1c', $input['customer_id_1c'])
                ->where('loan_id_1c', $input['loan_id_1c'])
                ->first();

            if ($debtor) {
                $report = 'Безакцептное списание по договору ' . $input['loan_id_1c'] . ' на сумму '
                    . number_format($input['amount'] / 100, 2, '.', '')
                    . ' руб. c карты ' . $input['card_number'] ?? '';

                DebtorEvent::create([
                    'date' => '0000-00-00 00:00:00',
                    'customer_id_1c' => $debtor->customer_id_1c,
                    'debtor_id' => $debtor->id,
                    'debtor_id_1c' => $debtor->debtor_id_1c,
                    'loan_id_1c' => $debtor->loan_id_1c,
                    'refresh_date' => now(),
                    'event_type_id' => 21,
                    'event_result_id' => 28,
                    'overdue_reason_id' => 0,
                    'report' => $report,
                    'user_id' => 1545,
                    'user_id_1c' => 'Офис                                              ',
                    'completed' => 1
                ]);
                return 1;
            }
        }
        return 0;
    }
}
