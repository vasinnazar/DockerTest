<?php

namespace Services;

use App\Debtor;
use App\DebtorEvent;
use App\Services\DebtorEventService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DebtorEventServiceTest extends TestCase
{
    use DatabaseTransactions;
    public function testCheckLimitEventByCustomerId1c()
    {
        $debtors = factory(Debtor::class, 'debtor')->create();
        factory(DebtorEvent::class, 10)->create([
            'customer_id_1c' => $debtors->first()->customer_id_1c,
            'debtor_id' => $debtors->first()->id,
            'event_type_id' => random_int(1, 11),
        ]);

        $result = app(DebtorEventService::class)->checkLimitEventByCustomerId1c($debtors->first()->customer_id_1c);
        $this->assertEquals(null,$result);

        factory(DebtorEvent::class, 'event_limit',2)->create([
            'customer_id_1c' => $debtors->first()->customer_id_1c,
            'debtor_id' => $debtors->first()->id,
            'created_at' => Carbon::now()
        ]);

        try {
            app(DebtorEventService::class)->checkLimitEventByCustomerId1c($debtors->first()->customer_id_1c);
        }catch (\Throwable $exception) {
            $this->assertEquals('Превышен лимит за день', $exception->errorMessage);
        }
    }

    public function testGetCountEventsByDate()
    {
        $debtors = factory(Debtor::class, 'debtor')->create();
        factory(DebtorEvent::class, 'event_limit',2)->create([
            'customer_id_1c' => $debtors->first()->customer_id_1c,
            'debtor_id' => $debtors->first()->id,
            'created_at' => Carbon::now()
        ]);
        $res = app(DebtorEventService::class)->getCountEventsByDate(
            Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay(),
            $debtors->first()->customer_id_1c,
            [
                DebtorEvent::SMS_EVENT,
                DebtorEvent::AUTOINFORMER_OMICRON_EVENT,
                DebtorEvent::WHATSAPP_EVENT,
                DebtorEvent::EMAIL_EVENT
            ]
        );
        $this->assertEquals(2,$res);
    }
}
