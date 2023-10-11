<?php

namespace App\Jobs;

use App\Clients\PaysClient;
use App\Debtor;
use App\Model\Status;
use App\Repositories\DebtorRepository;
use App\Repositories\MassRecurrentRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class WithoutAcceptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    private int $debtorId;
    private int $massRecurrentId;
    public function __construct(int $massRecurrentId, int $debtorId)
    {
        $this->massRecurrentId = $massRecurrentId;
        $this->debtorId = $debtorId;
    }

    public function handle(PaysClient $paysClient, DebtorRepository $debtorRepository, MassRecurrentRepository $massRecurrentRepository)
    {
        $debtor = $debtorRepository->firstById($this->debtorId);
        $payment = $massRecurrentRepository->firstById($this->massRecurrentId);
        try {
            $paysClient->createPayment(
                $debtor->customer_id_1c,
                $payment->sum_indebt,
                $debtor->loan_id_1c,
                3,
                1,
                null,
                '{"is_debtor":true,"is_mass_debtor":true}'
            );
            $massRecurrentRepository->update($this->massRecurrentId, [
                'status_id' => Status::SUCCESS
            ]);
        } catch (\Exception $exception) {
            Log::error("Without Accept Job Error", [
                'message' => $exception->getMessage(),
                'debtorId' => $this->debtorId,
                'massRecurrentId' => $this->massRecurrentId
            ]);
            $massRecurrentRepository->update($this->massRecurrentId, [
                'status_id' => Status::FAILED
            ]);
        }
    }
    public function failed(\Throwable $exception): void
    {
        Log::error("Without Accept Job Error", [
            'message' => $exception->getMessage(),
            'debtorId' => $this->debtorId,
            'massRecurrentId' => $this->massRecurrentId
        ]);
        $massRecurrentRepository = app(MassRecurrentRepository::class);
        $massRecurrentRepository->update($this->massRecurrentId, [
            'status_id' => Status::FAILED
        ]);
    }
}
