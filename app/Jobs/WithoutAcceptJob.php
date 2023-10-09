<?php

namespace App\Jobs;

use App\Clients\PaysClient;
use App\Debtor;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class WithoutAcceptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $debtorId;
    public function __construct(int $debtorId)
    {
        $this->debtorId = $debtorId;
    }

    public function handle(PaysClient $paysClient)
    {
        $debtor = Debtor::find($this->debtorId);
        if (!$debtor) {
            return;
        }
        try {
            $paysClient->createPayment(
                $debtor->customer_id_1c,
                $debtor->sum_indebt,
                $debtor->loan_id_1c,
                3,
                1,
                null,
                '{"is_debtor":true,"is_mass_debtor":true}'
            );
        } catch (\Exception $exception) {
            Log::error("Auto Payment Error", ['message' => $exception->getMessage(), 'debtorId' => $this->debtorId]);
        }
    }
}
