<?php


namespace Tests\Feature;

use App\Clients\ArmClient;
use App\Customer;
use App\Debtor;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class ApiDebtorsControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testOnSubdivision()
    {
        $user = factory(User::class)->create();

        $debtor = factory(Debtor::class, 'debtor')->create([
            'responsible_user_id_1c' => $user->id_1c,
        ]);
        $customers = (factory(Customer::class)->create([
            'id_1c' => $debtor->customer_id_1c
        ]));

        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getUserById1c')->andReturn([]);
                return $mock;
            }
        );

        $this->withoutMiddleware();
        $response = $this->actingAs($user)
            ->withSession(['foo' => 'bar'])
            ->post('/api/debtors/msg/on-subdivision', [
                'user_id_1c' => 'foo-bar',
                'customer_id_1c' => $debtor->customer_id_1c,
                'loan_id_1c' => $debtor->loan_id_1c,
                'is_debtor_personal' => true
            ]);
        $pfxLoan = 'pn';

        $this->assertDatabaseHas('messages', [
            'recepient_id' => $user->id,
            'type' => $pfxLoan . $debtor->loan_id_1c,
            'message_type' => $pfxLoan
        ]);
    }

    public function testOnSite()
    {
        $user = factory(User::class)->create();
        $debtor = factory(Debtor::class, 'debtor')->create([
            'responsible_user_id_1c' => $user->id_1c,
            'str_podr' => '000000000006',
        ]);


        $this->withoutMiddleware();
        $response = $this->actingAs($user)
            ->withSession(['foo' => 'bar'])
            ->post('/api/debtors/onsite', [
                'customer_id_1c' => $debtor->customer_id_1c,
            ]);
        $pfxLoan = 'sn';

        $this->assertDatabaseHas('messages', [
            'recepient_id' => $user->id,
            'type' => $pfxLoan . $debtor->loan_id_1c,
            'message_type' => $pfxLoan
        ]);

        $this->assertDatabaseHas('debtor_events', [
            'refresh_date' => now()->format('Y-m-d H:i:s'),
            'created_at' => now()->format('Y-m-d H:i:s'),
            'date' => "0000-00-00 00:00:00",
            'customer_id_1c' => $debtor->customer_id_1c,
            'loan_id_1c' => $debtor->loan_id_1c,
            'event_result_id' => 17,
            'event_type_id'=> 9,
            'completed' => 1,
            'debtor_id' => $debtor->id,
            'debtor_id_1c' => $debtor->id_1c,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('debtor_events', [
            'refresh_date' => now()->format('Y-m-d H:i:s'),
            'created_at' => now()->format('Y-m-d H:i:s'),
            'date' =>now()->format('Y-m-d H:i:s'),
            'customer_id_1c' => $debtor->customer_id_1c,
            'loan_id_1c' => $debtor->loan_id_1c,
            'event_result_id' => null,
            'event_type_id'=> 6,
            'completed' => 0,
            'debtor_id' => $debtor->id,
            'debtor_id_1c' => $debtor->id_1c,
            'user_id' => $user->id,
        ]);
    }
}
