<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorBlockProlongation;
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
            ->where('qty_delays', 37)
            ->where('od', '>=', 500000)
            ->where('base', 'Б-1')
            ->get();

        foreach ($debtors as $debtor) {

            $debtorProlangation = DebtorBlockProlongation::where('loan_id_1c', $debtor->loan_id_1c)->first();
            if(!is_null($debtorProlangation)) {
                continue;
            }
            $loansDebtor = $this->armClient->getLoanById1c($debtor->loan_id_1c);
            if($loansDebtor->isEmpty()) {
                continue;
            }
            $ordersDebtor = $this->armClient->getOrdersById($loansDebtor->first()->id);
            $filteredOrders = $ordersDebtor->filter(function ($item) {
                return $item->type->plus === 1 && $item->money > 50000;
            });
            if(!$filteredOrders->isEmpty()) {
                continue;
            }
            $amount = (int)(($debtor->od + $debtor->pc + $debtor->exp_pc + $debtor->fine) * 0.3);
            Log::info('RepaymentOffer Auto Peace SEND:',['debtorID'=>$debtor->id,'loanId1c'=>$debtor->loan_id_1c]);
            $this->armClient->sendRepaymentOffer(
                self::REPAYMENT_TYPE_PEACE,
                60,
                $amount,
                $debtor->loan_id_1c,
                Carbon::now()->addDay(14),
                Carbon::now(),
                0,
                0
            );

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

        if(!$offers->isEmpty()) {
            foreach ($offers as $offer) {
                $this->armClient->updateOffer($offer->id, [
                    'status' => self::STATUS_CLOSE_OFFER,
                ]);
            }
        }
    }

}
