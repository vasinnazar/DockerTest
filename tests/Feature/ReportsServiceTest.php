<?php

namespace Tests\Feature;

use App\Customer;
use App\Debtor;
use App\Http\Controllers\DebtorsReportsController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\Request;
use App\MySoap;
use App\User;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class ReportsServiceTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    private MySoap $mySoap;
    private DebtorsReportsController $debtorsReportsController;
    private $user;
    private $customer;
    private $param;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->mySoap = app()->make(MySoap::class);
        $this->debtorsReportsController = app()->make(DebtorsReportsController::class);
    }
    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->customer = factory(Customer::class)->create();
        factory(Debtor::class, 'debtor')->create([
            'loan_id_1c' => '00002521042-001',
            'customer_id_1c' => $this->customer->id_1c,
        ]);
        $this->param = [
            'start_date' => now(),
            'end_date' => now(),
            'user_id' => [
                $this->user->id
            ]
        ];
    }
    public function testGetPaymentsForUsersSuccess():void
    {
        $testResponse1c = (object) [
            'result' => '1',
            'tab' => (object) [
                'num1' => (object) [
                    'date' => now()->format('Y-m-d'),
                    'doc_number' => 'Оплата пэйчер П02620673 от ' . now()->format('Y-m-d H:i:s'),
                    'loan_id_1c' => '00002521042-001 от 16 января 2023 г.',
                    'money' => random_int(1000, 5000),
                    'fio' => 'Плюс Татьяна Ивановна',
                    'responsible_user_id_1c' => 'Офис                                              ',
                    'customer_id_1c' => $this->customer->id_1c,
                ]
            ]
        ];
        $this->app->bind(
            MySoap::class,
            function () use ($testResponse1c){
                $mock = Mockery::mock(MySoap::class);
                $mock->shouldReceive('getPaymentsFrom1c')->andReturn($testResponse1c);
                return $mock;
            }
        );
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/ajax/debtors/userpayments', $this->param);
        $response->assertStatus(
            Response::HTTP_OK
        );
        $this->assertEquals((array) $testResponse1c->tab->num1, array_shift($response->json()['payments']));
    }

    public function testGetPaymentsForUsersError():void
    {
        $testResponse1c = (object) [
            'result' => '0',
            'tab' => (object) []
        ];
        $this->app->bind(
            MySoap::class,
            function () use ($testResponse1c){
                $mock = Mockery::mock(MySoap::class);
                $mock->shouldReceive('getPaymentsFrom1c')->andReturn($testResponse1c);
                return $mock;
            }
        );
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/ajax/debtors/userpayments', $this->param);

        $response->assertStatus(
            Response::HTTP_OK
        );
        $this->assertEmpty($response->json()['payments']);
    }
    public function testGetPaymentsForUsersErrorDate():void
    {
        $this->param['end_date'] = Carbon::yesterday();
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/ajax/debtors/userpayments', $this->param);

        $response->assertStatus(
            Response::HTTP_OK
        );
        $this->assertEquals($response->json()['result'], 0);

    }
    public function testGetPaymentsForUsersErrorBigPeriod():void
    {
        $this->param['end_date'] = now()->addMonth(2);
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/ajax/debtors/userpayments', $this->param);

        $response->assertStatus(
            Response::HTTP_OK
        );
        $this->assertEquals($response->json()['result'], 0);

    }

//    public function testDebtorsControllerGetPaymentsForDay()
//    {
//        $request = new Request([
//            'start_date' => $this->param['start_date'],
//            'end_date' => $this->param['end_date']
//        ]);
//        $response = $this->debtorsReportsController->getPaymentsForDay($this->mySoap, $request);
//        $this->assertEquals($response->status(), Response::HTTP_OK);
//        $this->assertNotEmpty($response->getOriginalContent()['payments']);
//    }

    public function testDebtorsControllerGetPaymentsForDayErrorDate()
    {
        $request = new Request([
            'start_date' => now(),
            'end_date' => Carbon::yesterday()
        ]);
        $response = $this->debtorsReportsController->getPaymentsForDay($this->mySoap, $request);
        $this->assertEquals($response['result'], 0);
    }

    public function testDebtorsControllerGetPaymentsForDayErrorBigPeriod()
    {
        $request = new Request([
            'start_date' => now(),
            'end_date' => now()->addMonth(2)
        ]);
        $response = $this->debtorsReportsController->getPaymentsForDay($this->mySoap, $request);
        $this->assertEquals($response['result'], 0);
    }
}
