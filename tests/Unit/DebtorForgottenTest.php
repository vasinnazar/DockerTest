<?php

namespace Unit;

use App\Debtor;
use App\DebtorEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TestCase;

class DebtorForgottenTest extends TestCase
{
    public function test_debtors_are_marked_forgotten()
    {
        $debtors = factory(Debtor::class,'debtor_forgotten', 5)->create();

        foreach ($debtors as $debtor) {
            factory(DebtorEvent::class, 2)->create([
                'debtor_id' => $debtor->id,
                'customer_id_1c' => $debtor->customer_id_1c,
                'created_at' => Carbon::now()->subDay(40),
            ]);
        }

        $this->artisan('debtors:forgotten');

        foreach ($debtors as $debtor) {
            factory(DebtorEvent::class)->create([
                'debtor_id' => $debtor->id,
                'customer_id_1c' => $debtor->customer_id_1c,
                'created_at' => Carbon::now(),
            ]);
        }

        foreach ($debtors as $debtor) {
            $this->assertDatabaseHas('debtors', [
                'id' => $debtor->id,
                'forgotten_date' => null,
            ]);
        }
    }
}
