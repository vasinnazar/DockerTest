<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorBlockProlongation;
use App\DebtorEvent;
use App\DebtorsEventType;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RepaymentOfferService
{

    private $armClient;
    const REPAYMENT_TYPE_PEACE = 14;
    const STATUS_CLOSE_OFFER = 4;

    public function __construct(ArmClient $client)
    {
        $this->armClient = $client;
    }

    /**
     * Автоматическое мировое соглашение для должников стадии УПР
     * @return void
     */
    public function autoPeaceForUPR()
    {
        $debtors = Debtor::where('is_debtor', 1)
            ->where('str_podr', '000000000006')
            ->whereIn('debt_group_id', [2, 4, 5])
            ->where('qty_delays', 36)
            ->where('base', 'Б-1')
            ->get();

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
            $amount = $debtor->od + $debtor->pc + $debtor->exp_pc + $debtor->fine;
            if ($amount < 500000) {
                continue;
            }

            $amount = (int)($amount * 0.3);

            Log::info('Repayment Offer Auto Peace SEND:',
                ['debtorID' => $debtor->id, 'loanId1c' => $debtor->loan_id_1c]);
            $result = $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                60,
                $amount,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(14),
                Carbon::now(),
                0,
                1
            );

            if (!$result) {
                continue;
            }

            try {
                $user = User::where('banned', 0)->where('id_1c', $debtor->responsible_user_id_1c)->first();
                $event = new DebtorEvent();
                $event->customer_id_1c = $debtor->customer_id_1c;
                $event->event_type_id = DebtorsEventType::TYPE_INFORMATION;
                $event->debt_group_id = $debtor->debt_group_id;
                $event->event_result_id = DebtorsEventType::RESULT_TYPE_CONSENT_TO_PEACE;
                $event->report = '(Автоматическое) Предварительное согласие по договору ' .
                    $debtor->loan_id_1c . ' на мировое соглашение сроком на ' .
                    60 . ' дней, сумма: ' .
                    $amount / 100 . ' руб. Действует до ' .
                    Carbon::now()->addDay(14)->format('d.m.Y');
                $event->debtor_id = $debtor->id;
                $event->user_id = $user->id;
                $event->completed = 1;
                $event->last_user_id = $user->id;
                $event->debtor_id_1c = $debtor->debtor_id_1c;
                $event->user_id_1c = $user->id_1c;
                $event->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
                $event->save();
            } catch (\Exception $e) {
                Log::error('Create event auto-peace', ['debtorId' => $debtor->id, 'messages' => $e->getMessage()]);
            }

        }
    }

    public function sendPeaceForUDR(Debtor $debtor)
    {
        if ($debtor->sum_indebt >= 500000 && $debtor->sum_indebt <= 1000000) {
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                90,
                200000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                60,
                300000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
        }

        if ($debtor->sum_indebt >= 1000100 && $debtor->sum_indebt <= 2000000) {
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                150,
                200000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                150,
                300000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                90,
                500000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
        }

        if ($debtor->sum_indebt > 2000000) {
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                300,
                300000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                300,
                500000,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(60)
            );
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                300,
                700000,
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
