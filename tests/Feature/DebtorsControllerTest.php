<?php

namespace Tests\Feature;

use App\Claim;
use App\Clients\ArmClient;
use App\Customer;
use App\Debtor;
use App\DebtorSmsTpls;
use App\EmailMessage;
use App\Loan;
use App\LoanType;
use App\Passport;
use App\Repositories\DebtorEventsRepository;
use App\Services\MessageService;
use App\Subdivision;
use App\User;
use Carbon\Carbon;
use EmailsMessagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mockery;
use RolesSeeder;
use Tests\TestCase;

class DebtorsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $debtors;
    private $emailTemplateId;
    private $fakeDebtorId = 999999;
    private DebtorEventsRepository $debtorEventsRepository;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->debtorEventsRepository = app()->make(DebtorEventsRepository::class);
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
                'series' => $debtor->passport_series,
                'number' => $debtor->passport_number,
                'customer_id' => $customer->id
            ]);
            $claim = factory(Claim::class)->create([
                'customer_id' => $customer->id,
                'passport_id' => $passport->id
            ]);
            factory(Loan::class)->create([
                'id_1c' => $debtor->loan_id_1c,
                'claim_id' => $claim->id
            ]);
        }

        $this->seed(RolesSeeder::class);
        $this->seed(EmailsMessagesSeeder::class);
        $this->emailTemplateId = EmailMessage::all()->pluck('id')->toArray();
    }

    public function testSearchEqualContactsDebtorsExists()
    {
        $debtorsId = $this->debtors->pluck('id')->random();
        $this->withoutMiddleware();
        $response = $this->actingAs($this->user, 'web')
            ->post('/ajax/debtors/searchEqualContacts', ['debtor_id' => $debtorsId]);
        $response->assertStatus(
            Response::HTTP_OK
        );
    }
    public function testSearchEqualContactsDebtorsNotExists()
    {
        $this->withoutMiddleware();
        $response = $this->actingAs($this->user, 'web')
            ->post('/ajax/debtors/searchEqualContacts', ['debtor_id' => $this->fakeDebtorId]);
        $response->assertStatus(
            Response::HTTP_OK
        );
    }

}
