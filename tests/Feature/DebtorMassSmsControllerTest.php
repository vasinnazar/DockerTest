<?php

namespace Tests\Feature;

use App\Claim;
use App\Customer;
use App\Debtor;
use App\DebtorSmsTpls;
use App\EmailMessage;
use App\Loan;
use App\LoanType;
use App\Role;
use App\Subdivision;
use App\User;
use Carbon\Carbon;
use EmailsMessagesSeeder;
use GuzzleHttp\RequestOptions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class DebtorMassSmsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testSendMassSms()
    {
        $debtors = factory(Debtor::class, 'debtor', 5)->create();
        $user = factory(User::class)->create();
        $sms = factory(DebtorSmsTpls::class, 'sms')->create();
        $customers = collect();
        foreach ($debtors as $debtor) {
            $customers->push(factory(Customer::class)->create([
                'id_1c' => $debtor->customer_id_1c
            ]));
        }


        $this->withoutMiddleware();
        $response = $this->actingAs($user, 'web')
            ->withSession(['foo' => 'bar'])
            ->post('/ajax/debtors/masssms/send', [
                'responsibleUserId' => $user->id,
                'debtorsIds' => $debtors->pluck('id')->toArray(),
                'templateId' => $sms->id,
                'sendDate' => Carbon::now()->format('d.m.Y'),
            ]);

        $result = $response->decodeResponseJson();
        $this->assertEquals($debtors->count(), (int)$result['cnt']);
        $this->assertEquals("success", $result['error']);
    }

    public function testSendMassMessageSms()
    {
        $isSms = 1;
        $debtors = factory(Debtor::class, 'debtor', 5)->create();
        $user = factory(User::class)->create();
        $sms = factory(DebtorSmsTpls::class, 'sms')->create();
        $customers = collect();
        foreach ($debtors as $debtor) {
            $customers->push(factory(Customer::class)->create([
                'id_1c' => $debtor->customer_id_1c
            ]));
        }


        $this->withoutMiddleware();
        $response = $this->actingAs($user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $user->id,
                'debtorsIds' => $debtors->pluck('id')->toArray(),
                'templateId' => $sms->id,
                'sendDate' => Carbon::now()->format('d.m.Y'),
            ]);

        $result = $response->decodeResponseJson();
        $this->assertEquals($debtors->count(), (int)$result['cnt']);
        $this->assertEquals("success", $result['error']);
    }

    public function testSendMassMessageEmail()
    {
        $isSms = 0;
        $this->seed();
        $debtors = factory(Debtor::class, 'debtor', 5)->create();
        $user = factory(User::class)->create();
        factory(Claim::class)->create();
        factory(LoanType::class)->create();
        factory(Subdivision::class)->create();
        factory(Loan::class)->create();
        $customers = collect();
        foreach ($debtors as $debtor) {
            $customers->push(factory(Customer::class)->create([
                'id_1c' => $debtor->customer_id_1c
            ]));
        }

        $emailTemplateId = EmailMessage::all()->pluck('id')->toArray();
        $this->withoutMiddleware();
        $response = $this->actingAs($user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $user->id,
                'debtorsIds' => $debtors->pluck('id')->toArray(),
                'templateId' => $emailTemplateId[1],
                'sendDate' => Carbon::now()->format('d.m.Y'),
            ]);

        $result = $response->decodeResponseJson();
        $this->assertEquals($debtors->count(), (int)$result['cnt']);
        $this->assertEquals("success", $result['error']);
    }
}
