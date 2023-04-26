<?php

namespace App\Console\Commands;

use App\Debtor;
use App\DebtorEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DebtorsForgotten extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtors:forgotten';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Помечает должников,с которыми не было долгого взаимодействия';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $arGoodResultIds = [0, 1, 6, 9, 10, 11, 12, 13, 22, 24, 27, 29];
        $forgottenDate = Carbon::now()->startOfDay()->subDays(12)->format('Y-m-d 00:00:00');

        $debtors = Debtor::where('is_debtor', 1)->get();
        foreach ($debtors as $debtor) {
            $event = DebtorEvent::where('customer_id_1c', $debtor->customer_id_1c)
                ->whereIn('event_result_id', $arGoodResultIds)
                ->latest()
                ->first();

            if ($event && $event->created_at->lte($forgottenDate)) {
                $debtor->forgotten_date = Carbon::now();
                $debtor->save();
            }
        }
    }

}
