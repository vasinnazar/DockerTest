<?php

namespace Feature;

use App\Clients\PaysClient;
use App\Customer;
use App\Debtor;
use App\Jobs\WithoutAcceptJob;
use App\LoanType;
use App\Model\Status;
use App\Passport;
use App\Repositories\DebtorRepository;
use App\Repositories\MassRecurrentRepository;
use App\RoleUser;
use App\Services\MassRecurrentService;
use App\Subdivision;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RolesSeeder;
use Tests\TestCase;

class WithoutAcceptJobTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;
    private $user;
    private $debtors;
    private $strPodr = '000000000007';
    private $timezone = 'all';
    private $countDebtor = 10;

    public function setUp(): void
    {
        parent::setUp();
        factory(Subdivision::class)->create();
        $this->user = factory(User::class)->create();
        $this->seed(RolesSeeder::class);
        factory(RoleUser::class)->create([
            'user_id' => $this->user->id,
            'role_id' => 10,
        ]);
        factory(LoanType::class)->create();
        $this->debtors = factory(Debtor::class, 'debtor', $this->countDebtor)->create([
            'str_podr' => $this->strPodr,
            'qty_delays' => 100,
            'debt_group_id' => 5,
        ]);
        foreach ($this->debtors as $debtor) {
            $customer = factory(Customer::class)->create([
                'id_1c' => $debtor->customer_id_1c
            ]);
            factory(Passport::class)->create([
                'customer_id' => $customer->id
            ]);
        }
    }

    public function testWithoutAcceptSendSuccess(): void
    {
        Queue::fake();
        $responseCreateTask = $this->actingAs($this->user, 'web')
            ->post('/debtors/recurrent/massquerytask', [
                'timezone' => $this->timezone,
                'str_podr' => $this->strPodr,
                'start' => 1,
                'qty_delays_from' => 90,
                'qty_delays_to' => 110,
            ]);
        $responseCreateTask->assertStatus(
            Response::HTTP_OK
        );
        $this->assertDatabaseHas('debtors_mass_recurrents_tasks', [
            'user_id' => $this->user->id,
            'str_podr' => $this->strPodr,
            'timezone' => $this->timezone,
            'completed' => 0,
            'debtors_count' => $this->countDebtor,
        ]);
        $jsonResp = json_decode($responseCreateTask->getContent());
        $responseExecuteTask = $this->actingAs($this->user, 'web')
            ->post('/debtors/recurrent/massquery', [
                'task_id' => $jsonResp->task_id,
                'qty_delays_from' => 90,
                'qty_delays_to' => 110,
            ]);

        $responseExecuteTask->assertStatus(
            Response::HTTP_OK
        );
        $massRecRepo = app()->make(MassRecurrentRepository::class);
        $recurrent = $massRecRepo->getByStatus(Status::NEW_SEND);
        $this->assertEquals($recurrent->count(), $this->countDebtor);

        $this->artisan('send:without-accept');
        $recurrent = $massRecRepo->getByStatus(Status::IN_PROCESS);
        $this->assertEquals($recurrent->count(), $this->countDebtor);
        $this->app->bind(
            PaysClient::class,
            function () use ($jsonResp){
                $mockSendEmail = Mockery::mock(PaysClient::class);
                $mockSendEmail->shouldReceive('createPayment')->andReturn($jsonResp);
                return $mockSendEmail;
            }
        );
        $paysClient = app()->make(PaysClient::class);
        $debtorRepo = app()->make(DebtorRepository::class);
        Queue::assertPushed(WithoutAcceptJob::class, function ($job) use ($paysClient, $debtorRepo, $massRecRepo){
            $job->handle($paysClient, $debtorRepo, $massRecRepo);
            return $job;
        });
        $recurrent = $massRecRepo->getByStatus(Status::SUCCESS);
        $this->assertEquals($recurrent->count(), $this->countDebtor);
    }
    public function testWithoutAcceptSendErrorCreateTask(): void
    {
        $this->app->bind(
            MassRecurrentService::class,
            function () {
                $mockSendEmail = Mockery::mock(MassRecurrentService::class);
                $mockSendEmail->shouldReceive('checkStrPodrUser')->andReturn(true);
                $mockSendEmail->shouldReceive('createTask')->andReturn(false);
                return $mockSendEmail;
            }
        );
        $responseCreateTask = $this->actingAs($this->user, 'web')
            ->post('/debtors/recurrent/massquerytask', [
                'timezone' => $this->timezone,
                'str_podr' => $this->strPodr,
                'start' => 1,
                'qty_delays_from' => 90,
                'qty_delays_to' => 110,
            ]);
        $responseCreateTask->assertStatus(
            Response::HTTP_OK
        );
        $this->assertEquals(json_decode($responseCreateTask->getContent(), true)['status'],'fail');
    }
}