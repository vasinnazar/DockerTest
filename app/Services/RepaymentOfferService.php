<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorBlockProlongation;
use App\DebtorEvent;
use App\DebtorsEventType;
use App\Repositories\DebtorEventsRepository;
use App\Repositories\DebtorRepository;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RepaymentOfferService
{

    private $armClient;
    private $debtorEventsRepository;
    private $debtorRepository;
    const REPAYMENT_TYPE_PEACE = 14;
    const STATUS_CLOSE_OFFER = 4;

    public function __construct(
        ArmClient $client,
        DebtorEventsRepository $debtorEventsRepository,
        DebtorRepository $debtorRepository
    )
    {
        $this->armClient = $client;
        $this->debtorEventsRepository = $debtorEventsRepository;
        $this->debtorRepository = $debtorRepository;
    }

    /**
     * Автоматическое мировое соглашение для должников стадии УПР
     * @return void
     */
    public function autoPeaceForUPR()
    {
        $debtors = $this->debtorRepository
            ->getDebtorsByPodrAndGroupAndBase(1, '000000000006', [2, 4, 5, 8], 36, 'Б-1');

        foreach ($debtors as $debtor) {
            $repaymentOffers = $this->armClient->getOffers($debtor->loan_id_1c);
            $repaymentOffersFiltered = $repaymentOffers->filter(function ($item) {
                return Carbon::now()->lessThan(Carbon::parse($item->end_at)) && $item->status != 4;
            });

            if (!$repaymentOffersFiltered->isEmpty()) {
                continue;
            }
            $debtorProlangation = DebtorBlockProlongation::where('loan_id_1c', $debtor->loan_id_1c)->first();
            if (!is_null($debtorProlangation)) {
                continue;
            }
            $amount = $debtor->sum_indebt;
            $periodOffer = 10;
            if ($amount >= 500000 && $amount <= 1000000) {
                $payment = (int)($amount * 0.5);
                $this->sendPeaceForUPR($debtor, $payment, $periodOffer, 30);
            }
            if ($amount > 1000000) {
                $payment = (int)($amount * 0.4);
                $this->sendPeaceForUPR($debtor, $payment, $periodOffer, 60);
            }
        }
    }

    public function sendPeaceForUPR(Debtor $debtor, int $amount, int $periodOffer, int $times)
    {
        Log::info(
            'Repayment Offer Auto Peace SEND:',
            ['debtorID' => $debtor->id, 'loanId1c' => $debtor->loan_id_1c]
        );
        $this->armClient->sendRepaymentOffer(
            self::REPAYMENT_TYPE_PEACE,
            $times,
            $amount,
            $debtor->loan_id_1c,
            Carbon::now()->addDay($periodOffer),
            Carbon::now(),
            0,
            1
        );
        try {
            $user = User::where('banned', 0)->where('id_1c', $debtor->responsible_user_id_1c)->first();
            $report = '(Автоматическое) Предварительное согласие по договору ' .
                $debtor->loan_id_1c . ' на мировое соглашение сроком на ' .
                $times . ' дней, сумма: ' .
                $amount / 100 . ' руб. Действует до ' .
                Carbon::now()->addDay($periodOffer)->format('d.m.Y');
            $this->debtorEventsRepository->createEvent(
                $debtor,
                $user,
                $report,
                DebtorsEventType::TYPE_INFORMATION,
                null,
                DebtorsEventType::RESULT_TYPE_CONSENT_TO_PEACE,
                DebtorEvent::COMPLETED
            );
        } catch (\Exception $e) {
            Log::error('Create event auto-peace', ['debtorId' => $debtor->id, 'messages' => $e->getMessage()]);
        }
    }

    public function sendPeaceForUDR(Debtor $debtor)
    {
        if ($debtor->sum_indebt >= 500000 && $debtor->sum_indebt <= 1000000) {
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                60,
                200000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                30,
                300000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
        }

        if ($debtor->sum_indebt >= 1000100 && $debtor->sum_indebt <= 2000000) {
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                120,
                300000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                90,
                400000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                60,
                600000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
        }

        if ($debtor->sum_indebt > 2000100 && $debtor->sum_indebt <= 4000000) {
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                150,
                400000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                150,
                600000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                150,
                800000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
        }

        if ($debtor->sum_indebt > 4000100) {
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                240,
                500000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                270,
                700000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                150,
                1000000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
        }
    }

    public function closeOfferIfExist(Debtor $debtor)
    {
        $offers = $this->armClient->getOffers($debtor->loan_id_1c);

        if (!$offers->isEmpty()) {
            foreach ($offers as $offer) {
                $this->armClient->updateOffer($offer->id, [
                    'status' => self::STATUS_CLOSE_OFFER,
                ]);
            }
        }
    }

}
