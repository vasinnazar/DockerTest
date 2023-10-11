<?php

namespace Feature;

use App\about_client;
use App\Claim;
use App\Customer;
use App\Debtor;
use App\Jobs\WithoutAcceptJob;
use App\Loan;
use App\LoanType;
use App\Passport;
use App\Subdivision;
use App\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
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
        $response = $this->actingAs($this->user, 'web')
            ->post('/debtors/recurrent/query', );
        $response->assertStatus(
            Response::HTTP_OK
        );
        $this->artisan('send:without-accept');
        Queue::assertPushed(WithoutAcceptJob::class, fn ($job) => !is_null($job->delay));
        dd(6);
    }
}