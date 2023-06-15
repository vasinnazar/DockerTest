<?php

namespace Tests\Feature;

use App\Customer;
use App\Debtor;
use App\MySoap;
use App\Services\ReportsService;
use App\User;
use Illuminate\Http\Response;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;


class ReportsServiceTest extends TestCase
{
    public function testGetPaymentsForUsers():void
    {
        $user = factory(User::class)->create();
        $debtor = factory(Debtor::class,'debtor')->create();
        $param = [
            'start_date' => now(),
            'end_date' => now(),
            'user_id' => [
                $user->id
            ]
        ];
        $this->app->bind(
            MySoap::class,
            function () {
                $mock = Mockery::mock(MySoap::class);
                $mock->shouldReceive('sendXML')->andReturn();
                return $mock;
            }
        );
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/ajax/debtors/userpayments', $param);
        $response->assertStatus(
            Response::HTTP_OK
        );
    }
}
