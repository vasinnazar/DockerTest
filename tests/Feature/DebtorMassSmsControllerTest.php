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
use App\Role;
use App\Subdivision;
use App\User;
use Carbon\Carbon;
use EmailsMessagesSeeder;
use GuzzleHttp\RequestOptions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mockery;
use RolesSeeder;
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
        $user = factory(User::class)->create();
        $debtors = factory(Debtor::class, 'debtor', 1)->create();
        $customers = collect();
        foreach ($debtors as $debtor) {
            $customers->push(factory(Customer::class)->create([
                'id_1c' => $debtor->customer_id_1c
            ]));
        }
        factory(Passport::class)->create();
        factory(Claim::class)->create();
        factory(LoanType::class)->create();
        factory(Subdivision::class)->create();
        factory(Loan::class)->create([
            'id_1c' => $debtors->pluck('loan_id_1c')->first()
        ]);
        $this->seed(RolesSeeder::class);
        $this->seed(EmailsMessagesSeeder::class);
        $emailTemplateId = EmailMessage::all()->pluck('id')->toArray();


        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getCustomerById1c')->andReturn(new Collection([
                    (object) ['id' => 1]
                ]));
                return $mock;
            }
        );

        $this->withoutMiddleware();
        $response = $this->actingAs($user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $user->id,
                'debtorsIds' => $debtors->pluck('id')->toArray(),
                'templateId' => Arr::random($emailTemplateId),
                'sendDate' => Carbon::now()->format('d.m.Y'),
            ]);
    }
}
