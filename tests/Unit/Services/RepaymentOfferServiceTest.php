<?php

namespace Tests\Unit\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorBlockProlongation;
use App\Repositories\DebtorEventsRepository;
use App\Services\RepaymentOfferService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RepaymentOfferServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->debtors = factory(Debtor::class, 'debtor', 10)->create([
            'responsible_user_id_1c' => $this->user->id_1c,
            'str_podr' => '000000000006',
            'base' => 'Б-1',
            'debt_group_id' => 2,
            'qty_delays' => 36
        ]);

    }
    public function testAutoPeaceUPRWithRepOffers()
    {
        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getOffers')->andReturn(collect([(object) array(
                    'id' => 1,
                    'end_at' => Carbon::now()->addDay(2),
                    'status' => 1,
                )]));
                $mock->shouldReceive('sendRepaymentOffer');
                return $mock;
            }
        );
        $repaymentOfferService = app(RepaymentOfferService::class);
        $repaymentOfferService->autoPeaceForUPR();
        foreach ($this->debtors as $debtor) {
            $this->assertDatabaseMissing('debtor_events', [
                'debtor_id' => $debtor->id,
            ]);
        }
    }
    public function testAutoPeaceUPRWithDepBlockProlongation()
    {
        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getOffers')->andReturn(collect());
                $mock->shouldReceive('sendRepaymentOffer');
                return $mock;
            }
        );
        foreach ($this->debtors as $debtor) {
            factory(DebtorBlockProlongation::class)->create([
                'debtor_id' => $debtor->id,
                'loan_id_1c' => $debtor->loan_id_1c,
            ]);
        }
        $repaymentOfferService = app(RepaymentOfferService::class);
        $repaymentOfferService->autoPeaceForUPR();
        foreach ($this->debtors as $debtor) {
            $this->assertDatabaseMissing('debtor_events', [
                'debtor_id' => $debtor->id,
            ]);
        }
    }
    public function testAutoPeaceUPRFirstCondition()
    {
        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getOffers')->andReturn(collect());
                $mock->shouldReceive('sendRepaymentOffer');
                return $mock;
            }
        );
        $repaymentOfferService = app(RepaymentOfferService::class);
        $repaymentOfferService->autoPeaceForUPR();
        foreach ($this->debtors as $debtor) {
            if ($debtor->sum_indebt >= 500000 && $debtor->sum_indebt <= 1000000) {
                $report = '(Автоматическое) Предварительное согласие по договору ' .
                    $debtor->loan_id_1c . ' на мировое соглашение сроком на ' .
                    30 . ' дней, сумма: ' .
                    (int)($debtor->sum_indebt * 0.5) / 100 . ' руб. Действует до ' .
                    Carbon::now()->addDay(10)->format('d.m.Y');
                $this->assertDatabaseHas('debtor_events', [
                    'debtor_id' => $debtor->id,
                    'report' => $report
                ]);
            }
            if ($debtor->sum_indebt > 1000000) {
                $report = '(Автоматическое) Предварительное согласие по договору ' .
                    $debtor->loan_id_1c . ' на мировое соглашение сроком на ' .
                    60 . ' дней, сумма: ' .
                    (int)($debtor->sum_indebt * 0.4) / 100 . ' руб. Действует до ' .
                    Carbon::now()->addDay(10)->format('d.m.Y');
                $this->assertDatabaseHas('debtor_events', [
                    'debtor_id' => $debtor->id,
                    'report' => $report
                ]);
            }
            if ($debtor->sum_indebt < 500000) {
                $this->assertDatabaseMissing('debtor_events', [
                    'debtor_id' => $debtor->id,
                ]);
            }
        }
    }
    public function testAutoPeaceUPRExeption()
    {
        $this->app->bind(
            ArmClient::class,
            function () {
                $mock = Mockery::mock(ArmClient::class);
                $mock->shouldReceive('getOffers')->andReturn(collect());
                $mock->shouldReceive('sendRepaymentOffer');
                return $mock;
            }
        );
        $this->app->bind(
            DebtorEventsRepository::class,
            function () {
                $mock = Mockery::mock(DebtorEventsRepository::class);
                $mock->shouldReceive('createEvent')->andThrow(
                    new \Exception('test'),
                );
                return $mock;
            }
        );
        $repaymentOfferService = app(RepaymentOfferService::class);
        $repaymentOfferService->autoPeaceForUPR();
        $contents = Storage::disk('storage')->get('logs/laravel.log');
        foreach ($this->debtors as $debtor) {
            $this->assertDatabaseMissing('debtor_events', [
                'debtor_id' => $debtor->id,
            ]);
            $isLogText = (bool) strpos($contents, 'Create event auto-peace {"debtorId":'.$debtor->id.',"messages":"test"}');
            $this->assertTrue($isLogText);
        }
    }
}
