<?php

namespace Tests\Unit\Services;

use AdsourcesSeeder;
use App\about_client;
use App\Claim;
use App\Customer;
use App\Debtor;
use App\Loan;
use App\LoanType;
use App\Passport;
use App\Repositories\DebtorEventsRepository;
use App\Services\DebtorCardService;
use App\Subdivision;
use App\User;
use EducationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiveConditionSeeder;
use LoanGoalsSeeder;
use Tests\TestCase;

class DebtorCardServiceTest extends TestCase
{
    use RefreshDatabase;
    private DebtorCardService $debtorCardService;
    private $user;
    private $debtors;
    private $emailTemplateId;
    private $fakeCustomerId1c = 'q999999';
    private $fakePassportId = 9999;
    private $fakeCustomerId = 9999;
    private DebtorEventsRepository $debtorEventsRepository;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->debtorEventsRepository = app()->make(DebtorEventsRepository::class);
    }
    public function setUp(): void
    {
        parent::setUp();
        $this->debtorCardService = app()->make(DebtorCardService::class);
        $this->user = factory(User::class)->create();
        factory(LoanType::class)->create();
        factory(Subdivision::class)->create();
        $this->seed(AdsourcesSeeder::class);
        $this->seed(EducationSeeder::class);
        $this->seed(LiveConditionSeeder::class);
        $this->seed(LoanGoalsSeeder::class);
        $this->seed(\MaritalTypesSeeder::class);
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
            factory(about_client::class)->create([
                'customer_id' => $customer->id
            ]);
        }


    }
    public function testGetEqualContactsDebtorsWithoutCustomer()
    {
        $debtor = $this->debtors->first();
        $debtor->customer_id_1c = $this->fakeCustomerId1c;
        $debtorsEqualPhonesAndAddress = $this->debtorCardService->getEqualContactsDebtors($debtor);
        $this->assertTrue($debtorsEqualPhonesAndAddress->isEmpty());
    }
    public function testGetEqualContactsDebtorsWithoutPassport()
    {
        $debtor = $this->debtors->first();
        $debtor->passport_series = $this->fakePassportId;
        $debtor->passport_number = $this->fakePassportId;
        $debtorsEqualPhonesAndAddress = $this->debtorCardService->getEqualContactsDebtors($debtor);
        $this->assertTrue($debtorsEqualPhonesAndAddress->isEmpty());
    }
    public function testGetEqualContactsDebtorsWithoutAbout()
    {
        $debtor = $this->debtors->first();
        $debtor->customer->about_clients->first()->customer_id = $this->fakeCustomerId;
        $debtorsEqualPhonesAndAddress = $this->debtorCardService->getEqualContactsDebtors($debtor);
        $this->assertTrue($debtorsEqualPhonesAndAddress->isEmpty());
    }
}
