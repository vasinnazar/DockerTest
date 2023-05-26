<?php

namespace Tests\Feature;

use App\Customer;
use App\Debtor;
use App\DebtorSmsTpls;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\RequestOptions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DebtorMassSmsControllerTest extends TestCase
{
    use DatabaseTransactions;

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
                'smsId' => $sms->id,
                'smsDate' => Carbon::now()->format('d.m.Y'),
            ]);

        $result = $response->decodeResponseJson();
        $this->assertEquals($debtors->count(), (int)$result['cnt']);
        $this->assertEquals("success", $result['error']);
    }
}
