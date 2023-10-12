<?php

namespace Feature;

use App\about_client;
use App\Claim;
use App\Clients\PaysClient;
use App\Customer;
use App\Debtor;
use App\Jobs\Job;
use App\Jobs\WithoutAcceptJob;
use App\Loan;
use App\LoanType;
use App\MassRecurrentTask;
use App\Passport;
use App\Repositories\DebtorRepository;
use App\Repositories\MassRecurrentRepository;
use App\Role;
use App\RoleUser;
use App\Services\MassRecurrentService;
use App\Subdivision;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use RolesSeeder;
use Tests\TestCase;

class WithoutAcceptJobTest extends TestCase
{
    //use DatabaseTransactions;
    private $user;
    private $debtors;
    private $strPodr = '000000000007';
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

    }

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->seed(RolesSeeder::class);
        factory(RoleUser::class)->create([
            'user_id' => $this->user->id,
            'role_id' => 10,
        ]);
        factory(LoanType::class)->create();
        factory(Subdivision::class)->create();
        $this->debtors = factory(Debtor::class, 'debtor', 5)->create([
            'str_podr' => $this->strPodr,
            'qty_delays' => 100,
            'debt_group_id' => 5,
        ]);
        foreach ($this->debtors as $debtor) {
            $customer = factory(Customer::class)->create([
                'id_1c' => $debtor->customer_id_1c
            ]);
            $passport = factory(Passport::class)->create([
                'customer_id' => $customer->id
            ]);
        }

    }

    public function testWithoutAcceptSend(): void
    {
        Queue::fake();
        $timezone = 'all';
        $responseCreateTask = $this->actingAs($this->user, 'web')
            ->post('/debtors/recurrent/massquerytask', [
                'timezone' => $timezone,
                'str_podr' => $this->strPodr,
                'start' => 1,

            ]);
        $responseCreateTask->assertStatus(
            Response::HTTP_OK
        );
        $jsonResp = json_decode($responseCreateTask->getContent());
        $responseExecuteTask = $this->actingAs($this->user, 'web')
            ->post('/debtors/recurrent/massquery', [
                'task_id' => $jsonResp->task_id,
            ]);

        $responseExecuteTask->assertStatus(
            Response::HTTP_OK
        );

        $this->artisan('send:without-accept');
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
        $massRecRepo = app()->make(MassRecurrentRepository::class);
        Queue::assertPushed(WithoutAcceptJob::class, function ($job) use ($paysClient, $debtorRepo, $massRecRepo){
            $job->handle($paysClient, $debtorRepo, $massRecRepo);
            return $job;
        });
    }
}