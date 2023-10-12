<?php

namespace Feature;

use App\about_client;
use App\Claim;
use App\Customer;
use App\Debtor;
use App\Jobs\WithoutAcceptJob;
use App\Loan;
use App\LoanType;
use App\MassRecurrentTask;
use App\Passport;
use App\Services\MassRecurrentService;
use App\Subdivision;
use App\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class WithoutAcceptJobTest extends TestCase
{
    private $user;
    private $debtors;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

    }

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        factory(LoanType::class)->create();
        factory(Subdivision::class)->create();
        $this->debtors = factory(Debtor::class, 'debtor', 5)->create();
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
        $this->app->bind(
            MassRecurrentService::class,
            function () {
                $mock = Mockery::mock(MassRecurrentService::class);
                $mock->shouldReceive('checkStrPodrUser')->andReturn(true);
                $mock->shouldReceive('createTask')->andReturn(
                    MassRecurrentTask::create([
                    'user_id' => $this->user->id,
                    'debtors_count' => 0,
                    'str_podr' => '000000000007',
                    'timezone' => 'all',
                    'completed' => 0
                ]));
                return $mock;
            }
        );
        $response = $this->actingAs($this->user, 'web')
            ->post('/debtors/recurrent/massquerytask', [
                'timezone' => 'all',
                'str_podr' => '000000000007',
                'start' => 1,

            ]);
        $response->assertStatus(
            Response::HTTP_OK
        );
        $response = $this->actingAs($this->user, 'web')
            ->post('/debtors/recurrent/massquery', [
                'task_id' => 1,
            ]);

       /* $this->artisan('send:without-accept');
        Queue::assertPushed(WithoutAcceptJob::class, fn ($job) => !is_null($job->delay));
        dd(6);*/
    }
}