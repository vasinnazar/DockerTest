<?php


namespace Tests\Feature;


use App\Debtor;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiEventsControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testWithoutAcceptEvent()
    {

        $user = factory(User::class)->create(['id' => 1545]);
        $debtor = factory(Debtor::class, 'debtor')->create();

        $this->withoutMiddleware();
        $response = $this->actingAs($user)
            ->withSession(['foo' => 'bar'])
            ->post('/api/debtors/events/without-accept', [
                'customer_id_1c' => $debtor->customer_id_1c,
                'loan_id_1c' => $debtor->loan_id_1c,
                'amount' => 123,
                'card_number' => '1234-2345-3456-4567'
            ]);
        $this->assertEquals(1 , $response->decodeResponseJson());
        $this->assertDatabaseHas('debtor_events', [
            'refresh_date' => now()->format('Y-m-d H:i:s'),
            'created_at' => now()->format('Y-m-d H:i:s'),
            'date' => '0000-00-00 00:00:00',
            'customer_id_1c' => $debtor->customer_id_1c,
            'loan_id_1c' => $debtor->loan_id_1c,
            'event_result_id' => 28,
            'event_type_id'=> 21,
            'completed' => 0,
            'debtor_id' => $debtor->id,
            'debtor_id_1c' => $debtor->id_1c,
            'user_id' => 1545,
            'overdue_reason_id' => 0,
            'completed' => 1,

        ]);
    }
}
