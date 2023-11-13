<?php

namespace Services;

use App\Customer;
use App\Debtor;
use App\Services\SynchronizeService;
use App\Subdivision;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;


class SynchronizeServiceTest extends TestCase
{
 use DatabaseTransactions;
    public function testSynchronizeDebtor()
    {
        $customer = factory(Customer::class)->create();
        $debtor = factory(Debtor::class, 'debtor')->create([
            'loan_id_1c' => '00002521042-001',
            'customer_id_1c' => $customer->id_1c,
            'passport_series' => 6920,
            'passport_number' => 922164,
        ]);
        factory(Subdivision::class)->create([
            "name" => "Онлайн",
            "name_id" => "П00000055",
            "address" => "Онлайн",
            "director" => "Ларионова Ольга Сергеевна",
            "closed" => 0,
            "city" => "г Новосибирск",
            "is_lead" => 1,
            "working_times" => null,
        ]);
        factory(User::class)->create([
            'id_1c'=> 'Лутошкина Ольга Александровна                     '
        ]);
        factory(User::class)->create([
            'id_1c'=> 'Онлайн                                       '
        ]);
        $result = app(SynchronizeService::class)->synchronizeDebtor($debtor);

        $this->assertEquals(true,$result);
        $this->assertDatabaseHas('claims',[
            'customer_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('loans',[
            'id_1c' => $debtor->loan_id_1c,
        ]);
        $this->assertDatabaseHas('passports',[
            'series' => $debtor->passport_series,
            'number' => $debtor->passport_number,
        ]);

        $customer = factory(Customer::class)->create();
        $debtor = factory(Debtor::class, 'debtor')->create([
            'loan_id_1c' => '123456',
            'customer_id_1c' => $customer->id_1c,
            'passport_series' => 6920,
            'passport_number' => 922164,
        ]);

        try {
            $result = app(SynchronizeService::class)->synchronizeDebtor($debtor);
        }catch (\Throwable $exception) {
            $this->assertEquals('Не удалось получить информацию', $exception->errorMessage);
        }
    }
}
