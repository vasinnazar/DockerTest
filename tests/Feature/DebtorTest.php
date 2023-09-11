<?php

namespace Tests\Feature;

use App\Claim;
use App\Customer;
use App\Debtor;
use App\DebtorEvent;
use App\Loan;
use App\LoanType;
use App\Passport;
use App\Subdivision;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class DebtorTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    private $user;
    private $debtor;
    private $debotEvent;
    private $loan;
    private $claim;
    private $passport;
    private $customer;
    public function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->customer = factory(Customer::class)->create();
        $this->claim = factory(Claim::class)->create();
        $this->loan = factory(Loan::class)->create([
            'claim_id' => $this->claim->id,
            'loantype_id' => factory(LoanType::class)->create()->id,
            'subdivision_id' => factory(Subdivision::class)->create()->id
        ]);
        $this->passport = factory(Passport::class)->create([
            'customer_id' => $this->customer->id
        ]);
        $this->debtor = factory(Debtor::class, 'debtor')->create([
            'kratnost' => 1,
            'loan_id_1c' => $this->loan->id_1c,
            'passport_series' => $this->passport->series,
            'passport_number' => $this->passport->number
        ]);
        $this->debtorEvent = factory(DebtorEvent::class)->create([
            'created_at' => now(),
            'completed' => 0,
            'user_id' => $this->user->id,
        ]);

        $this->withoutMiddleware();
    }

    public function testAjaxDebtorEventsList()
    {
        $response = $this->actingAs($this->user)->json('GET', 'ajax/debtorevents/list', [
            'search_field_debtors@kratnost' => 1,
            'search_field_debtor_events@date_from' => '2020-12-12'
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'de_username' => $this->user->name
        ]);
    }

    protected function connectionsToTransact()
    {
        return ['debtors'];
    }
}
