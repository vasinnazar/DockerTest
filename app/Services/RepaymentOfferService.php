<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorBlockProlongation;
use Carbon\Carbon;

class RepaymentOfferService
{

    private $armClient;
    const CLOSE_OFFER = 4;

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
            if (is_null($debtorProlangation)) {

                $loansDebtor = $this->armClient->getLoanById1c($debtor->loan_id_1c);
                if (!empty($loansDebtor->first())) {

                    $ordersDebtor = $this->armClient->getOrdersById($loansDebtor->first()->id);
                    $filteredOrders = $ordersDebtor->filter(function ($item) {
                        return $item->type->plus === 1 && $item->money > 50000;
                    });

                    $amount = (int)(($debtor->od + $debtor->pc + $debtor->exp_pc + $debtor->fine) * 0.3);

                    if (empty($filteredOrders)) {
                        $this->armClient->sendRepaymentOffer(14, 60, $amount, $debtor->loan_id_1c,
                            Carbon::now()->addDay(14));
                    }
                }
            }
        }
    }

    public function sendPeaceForUDR(Debtor $debtor)
    {
        if ($debtor->sum_indebt >= 500000 && $debtor->sum_indebt <= 1000000) {
            $this->armClient->sendRepaymentOffer(14, 90, 200000, $debtor->loan_id_1c, Carbon::now()->addDay(60));
            $this->armClient->sendRepaymentOffer(14, 60, 300000, $debtor->loan_id_1c, Carbon::now()->addDay(60));
        }

        if ($debtor->sum_indebt >= 1000100 && $debtor->sum_indebt <= 2000000) {
            $this->armClient->sendRepaymentOffer(14, 150, 200000, $debtor->loan_id_1c, Carbon::now()->addDay(60));
            $this->armClient->sendRepaymentOffer(14, 150, 300000, $debtor->loan_id_1c, Carbon::now()->addDay(60));
            $this->armClient->sendRepaymentOffer(14, 90, 500000, $debtor->loan_id_1c, Carbon::now()->addDay(60));
        }

        if ($debtor->sum_indebt > 2000000) {
            $this->armClient->sendRepaymentOffer(14, 300, 300000, $debtor->loan_id_1c, Carbon::now()->addDay(60));
            $this->armClient->sendRepaymentOffer(14, 300, 500000, $debtor->loan_id_1c, Carbon::now()->addDay(60));
            $this->armClient->sendRepaymentOffer(14, 300, 700000, $debtor->loan_id_1c, Carbon::now()->addDay(60));
        }
    }

    public function closeOfferIfExist(Debtor $debtor)
    {
        $offers = $this->armClient->getOffers($debtor->loan_id_1c);

        if($offers->count()) {
            foreach ($offers as $offer) {
                $this->armClient->updateOffer($offer->id, [
                    'status' => RepaymentOfferService::CLOSE_OFFER,
                ]);
            }
        }
    }

}
