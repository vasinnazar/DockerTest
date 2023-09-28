<?php

namespace Tests\Unit\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\Services\RepaymentOfferService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
        $this->debtors = factory(Debtor::class, 'debtor', 5)->create([
            'responsible_user_id_1c' => $this->user->id_1c,
            'str_podr' => '000000000006',
            'base' => 'Б-1',
            'debt_group_id' => 2,
            'qty_delays' => 36
        ]);

    }
    public function testGetEqualContactsDebtorsWithoutCustomer()
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
    }

}
