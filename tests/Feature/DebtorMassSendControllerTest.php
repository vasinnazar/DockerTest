<?php

namespace Tests\Feature;

use App\about_client;
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
use App\Services\MailerService;
use App\Subdivision;
use App\User;
use Carbon\Carbon;
use EmailsMessagesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mockery;
use RolesSeeder;
use Tests\TestCase;

class DebtorMassSendControllerTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $debtors;
    private $emailTemplateId;
    private $eventTypeId = 24;
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
        $this->debtors = factory(Debtor::class, 'debtor', 50)->create();
        foreach ($this->debtors as $debtor) {
            $customer = factory(Customer::class)->create([
                'id_1c' => $debtor->customer_id_1c
            ]);
            $passport = factory(Passport::class)->create([
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

        $this->seed(RolesSeeder::class);
        $this->seed(EmailsMessagesSeeder::class);
        $this->emailTemplateId = EmailMessage::all()->pluck('id')->toArray();
    }

    public function testSendMassMessageSms()
    {
        $isSms = 1;
        $sms = factory(DebtorSmsTpls::class, 'sms')->create();
        $this->withoutMiddleware();
        $response = $this->actingAs($this->user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $this->user->id,
                'debtorsIds' => $this->debtors->pluck('id')->toArray(),
                'templateId' => $sms->id,
                'dateSmsTemplate' => Carbon::now()->format('d.m.Y'),
            ]);

        $result = $response->decodeResponseJson();
        $this->assertEquals($this->debtors->count(), (int)$result['cnt']);
        $this->assertEquals("success", $result['error']);
    }

    public function testSendMassMessageEmailSuccessSending()
    {
        $isSms = 0;
        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getUserById1c')->andReturn([
                    ["email_user" =>  ["email" => "test_user@mail.ru", "password" => "123456"]]]);
                return $mock;
            }
        );

        $this->app->bind(
            MailerService::class,
            function () {
                $mockSendEmail = Mockery::mock(MailerService::class);
                $mockSendEmail->shouldReceive('sendEmailMessage')->andReturn(true);
                return $mockSendEmail;
            }
        );

        $this->withoutMiddleware();
        $response = $this->actingAs($this->user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $this->user->id,
                'debtorsIds' => $this->debtors->pluck('id')->toArray(),
                'templateId' => Arr::random($this->emailTemplateId),
                'dateSmsTemplate' => Carbon::now(),
            ]);
        $result = $response->decodeResponseJson();
        foreach ($this->debtors as $debtor) {
            $this->assertDatabaseHas('debtor_event_email', [
                'customer_id_1c' => $debtor->customer_id_1c,
                'status' => (int) true,
            ]);
            $this->assertDatabaseHas('debtor_events', [
                'debtor_id' => $debtor->id,
                'event_type_id' => $this->eventTypeId
            ]);
        }
        $this->assertEquals($this->debtors->count(), (int)$result['cnt']);
    }

    public function testSendMassMessageEmailErrorSending()
    {
        $isSms = 0;
        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getUserById1c')->andReturn([
                    ["email_user" =>  ["email" => "test_user@mail.ru", "password" => "123456"]]]);
                return $mock;
            }
        );

        $this->app->bind(
            MailerService::class,
            function () {
                $mockSendEmail = Mockery::mock(MailerService::class);
                $mockSendEmail->shouldReceive('sendEmailMessage')->andReturn(false);
                return $mockSendEmail;
            }
        );
        $response = $this->actingAs($this->user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $this->user->id,
                'debtorsIds' => $this->debtors->pluck('id')->toArray(),
                'templateId' => Arr::random($this->emailTemplateId),
                'dateSmsTemplate' => Carbon::now(),
            ]);
        $result = $response->decodeResponseJson();

        foreach ($this->debtors as $debtor) {
            $this->assertDatabaseHas('debtor_event_email', [
                'customer_id_1c' => $debtor->customer_id_1c,
                'status' => (int) false
            ]);
            $this->assertDatabaseMissing('debtor_events', [
                'debtor_id' => $debtor->id,
                'event_type_id' => $this->eventTypeId
            ]);
        }
        $this->assertEquals(0, (int)$result['cnt']);
    }

    public function testSendMassMessageEmailErrorGetEmailFromArm()
    {
        $isSms = 0;
        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getUserById1c')->andReturn([
                    ["email_user" =>  []]]);
                return $mock;
            }
        );

        $response = $this->actingAs($this->user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $this->user->id,
                'debtorsIds' => $this->debtors->pluck('id')->toArray(),
                'templateId' => Arr::random($this->emailTemplateId),
                'dateSmsTemplate' => Carbon::now(),
            ]);
        $result = $response->decodeResponseJson();
        $this->assertEquals($result['error'], 'Не удалось определить данные ответственного');
    }

    public function testSendMassMessageEmailErrorExcLimit()
    {
        $isSms = 0;
        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getUserById1c')->andReturn([
                    ["email_user" =>  ["email" => "test_user@mail.ru", "password" => "123456"]]]);
                return $mock;
            }
        );
        $this->debtorEventsRepository->createEvent($this->debtors->first(),$this->user,'test', $this->eventTypeId, 0);
        $this->debtorEventsRepository->createEvent($this->debtors->first(),$this->user,'test', $this->eventTypeId, 0);
        $response = $this->actingAs($this->user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $this->user->id,
                'debtorsIds' => [$this->debtors->first()->id],
                'templateId' => Arr::random($this->emailTemplateId),
                'dateSmsTemplate' => Carbon::now()->format('d.m.Y'),
            ]);
        $result = $response->decodeResponseJson();
        $this->assertEquals(0, (int)$result['cnt']);
    }

    public function testAjaxList()
    {
        factory(Debtor::class, 'debtor')->create([
            'uploaded' => 1,
            'non_interaction' => 0,
            'non_interaction_nf' => 0,
            'by_agent' => 0,
            'recall_personal_data' => 0,
            'recommend_completed' => 0,
            'is_bigmoney' => 0,
            'is_pledge' => 0,
            'is_pos' => 0,
            'od' => 450000
        ]);
        $filters = 'sum_from=4500&sum_to=4500';
        $response = $this->get('/ajax/debtormasssms/list?' . $filters);
        $this->assertEquals(450000, array_get($response->decodeResponseJson(), 'data.0.debtors_od'));
    }

}
