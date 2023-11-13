<?php


use App\Debtor;
use App\DebtorEvent;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;

class DebtorEventControllerTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $debtors;
    private $events;
    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->debtors = factory(Debtor::class, 'debtor', 5)->create();
        $this->events = factory(DebtorEvent::class, 100)->create();
    }
    public function testDeleteEvents()
    {
        $debtorId = $this->debtors->pluck('id')->random();
        $event = $this->events->random();
        $response = $this->actingAs($this->user, 'web')
            ->delete('/ajax/debtors/' . $debtorId . '/events/' . $event->id);
        $response->assertStatus(
            Response::HTTP_OK
        );
        $this->assertDatabaseMissing('debtor_events', $event->toArray());
    }
}
