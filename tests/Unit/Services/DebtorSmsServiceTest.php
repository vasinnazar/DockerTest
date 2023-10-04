<?php

namespace Services;

use App\Customer;
use App\Debtor;
use App\DebtorEvent;
use App\DebtorSmsTpls;
use App\Model\DebtorEventSms;
use App\Services\DebtorSmsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DebtorSmsServiceTest extends TestCase
{
    use DatabaseTransactions;
    public function testHasSmsMustBeSentOnce()
    {
        $debtor = factory(Debtor::class,'debtor')->create([
            'base' => 'КБ-График',
            'qty_delays' => 95,
        ]);
        $sms = factory(DebtorSmsTpls::class,'sms')->create([
            'id' => 20
        ]);
        $res = app(DebtorSmsService::class)->hasSmsMustBeSentOnce($debtor,$sms->id);
        self::assertEquals(true,$res);
        $sms->delete();

        $debtor = factory(Debtor::class,'debtor')->create([
            'base' => 'КБ-График',
            'qty_delays' => 95,
        ]);
        $sms = factory(DebtorSmsTpls::class,'sms')->create([
            'id' => 21
        ]);
        $res = app(DebtorSmsService::class)->hasSmsMustBeSentOnce($debtor,$sms->id);
        self::assertEquals(true,$res);
        $sms->delete();

        $debtor = factory(Debtor::class,'debtor')->create([
            'base' => 'Б-МС',
            'qty_delays' => 20,
        ]);
        $sms = factory(DebtorSmsTpls::class,'sms')->create([
            'id' => 21
        ]);
        $res = app(DebtorSmsService::class)->hasSmsMustBeSentOnce($debtor,$sms->id);
        self::assertEquals(true,$res);
        $sms->delete();

        $debtor = factory(Debtor::class,'debtor')->create([
            'base' => 'Б-риски',
            'qty_delays' => 103,
        ]);
        $sms = factory(DebtorSmsTpls::class,'sms')->create([
            'id' => 45
        ]);
        $res = app(DebtorSmsService::class)->hasSmsMustBeSentOnce($debtor,$sms->id);
        self::assertEquals(true,$res);
        $sms->delete();

        $debtor = factory(Debtor::class,'debtor')->create([
            'base' => 'Б-риски',
            'qty_delays' => 101,
        ]);
        $sms = factory(DebtorSmsTpls::class,'sms')->create([
            'id' => 45
        ]);
        $res = app(DebtorSmsService::class)->hasSmsMustBeSentOnce($debtor,$sms->id);
        self::assertEquals(false,$res);
        $sms->delete();

        $debtor = factory(Debtor::class,'debtor')->create([
            'base' => 'Б-МС',
            'qty_delays' => 25,
        ]);
        $sms = factory(DebtorSmsTpls::class,'sms')->create([
            'id' => 45
        ]);
        $res = app(DebtorSmsService::class)->hasSmsMustBeSentOnce($debtor,$sms->id);
        self::assertEquals(true,$res);
        $sms->delete();

        $debtor = factory(Debtor::class,'debtor')->create([
            'base' => 'Б-риски',
            'qty_delays' => 105,
        ]);
        $sms = factory(DebtorSmsTpls::class,'sms')->create([
            'id' => 45
        ]);
        $event = factory(DebtorEvent::class)->create();
        $customer = factory(Customer::class)->create([
            'id_1c'=>$debtor->customer_id_1c,
        ]);
        $this->debtorEventSmsRepository->create(
            $event->id,
            $sms->id,
            $customer->id_1c,
            $debtor->id,
            $debtor->base
        );
        $res = app(DebtorSmsService::class)->hasSmsMustBeSentOnce($debtor,$sms->id);
        self::assertEquals(false,$res);

        $debtor->base = 'З-МС';
        $debtor->save();
        $res = app(DebtorSmsService::class)->hasSmsMustBeSentOnce($debtor, $sms->id);
        self::assertEquals(true, $res);

    }
}
