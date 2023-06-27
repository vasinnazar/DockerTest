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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mockery;
use RolesSeeder;
use Tests\TestCase;

class DebtorMassSmsControllerTest extends TestCase
{
    use RefreshDatabase;

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
        $this->debtors = factory(Debtor::class, 'debtor', 5)->create();
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
                'sendDate' => Carbon::now()->format('d.m.Y'),
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
                $mock->shouldReceive('getCustomerById1c')->andReturn(new Collection([
                    (object) ['id' => 1]
                ]));
                $mock->shouldReceive('getAbouts')->andReturn([
                    ['id' => 1,  'email' => "test_customer@mail.ru"]
                ]);
                $mock->shouldReceive('getUserById1c')->andReturn([
                    ["email_user" =>  ["email" => "test_user@mail.ru", "password" => "123456"]]]);
                return $mock;
            }
        );

        $this->app->bind(
            MessageService::class,
            function () {
                $mockSendEmail = Mockery::mock(MessageService::class);
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
                'sendDate' => Carbon::now()->format('d.m.Y'),
            ]);
        $result = $response->decodeResponseJson();

        foreach ($this->debtors as $debtor) {
            $this->assertDatabaseHas('debtor_event_email', [
                'debtor_id' => $debtor->id,
                'status' => (int) true
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
                $mock->shouldReceive('getCustomerById1c')->andReturn(new Collection([
                    (object) ['id' => 1]
                ]));
                $mock->shouldReceive('getAbouts')->andReturn([
                    ['id' => 1,  'email' => "test_customer@mail.ru"]
                ]);
                $mock->shouldReceive('getUserById1c')->andReturn([
                    ["email_user" =>  ["email" => "test_user@mail.ru", "password" => "123456"]]]);
                return $mock;
            }
        );

        $this->app->bind(
            MessageService::class,
            function () {
                $mockSendEmail = Mockery::mock(MessageService::class);
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
                'sendDate' => Carbon::now()->format('d.m.Y'),
            ]);
        $result = $response->decodeResponseJson();

        foreach ($this->debtors as $debtor) {
            $this->assertDatabaseHas('debtor_event_email', [
                'debtor_id' => $debtor->id,
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
                $mock->shouldReceive('getCustomerById1c')->andReturn(new Collection([
                    (object) ['id' => 1]
                ]));
                $mock->shouldReceive('getAbouts')->andReturn([
                    ['id' => 1,  'email' => "test_customer@mail.ru"]
                ]);
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
                'sendDate' => Carbon::now()->format('d.m.Y'),
            ]);
        $result = $response->decodeResponseJson();
        $this->assertEquals(0, (int)$result['cnt']);
    }
    public function testSendMassMessageEmailErrorExcLimit()
    {
        $isSms = 0;
        $this->debtorEventsRepository->createEvent($this->debtors->first(),$this->user,'test', $this->eventTypeId, 0);
        $this->debtorEventsRepository->createEvent($this->debtors->first(),$this->user,'test', $this->eventTypeId, 0);
        $response = $this->actingAs($this->user, 'web')
            ->post('/ajax/debtors/massmessage/send', [
                'isSms' => $isSms,
                'responsibleUserId' => $this->user->id,
                'debtorsIds' => [$this->debtors->first()->id],
                'templateId' => Arr::random($this->emailTemplateId),
                'sendDate' => Carbon::now()->format('d.m.Y'),
            ]);
        $result = $response->decodeResponseJson();
        $this->assertEquals(0, (int)$result['cnt']);
    }
}
