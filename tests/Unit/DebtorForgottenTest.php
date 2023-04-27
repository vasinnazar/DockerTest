<?php

namespace Unit;

use App\Debtor;
use App\DebtorEvent;
use App\Role;
use App\RoleUser;
use App\Services\DebtorService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DebtorForgottenTest extends TestCase
{
    use DatabaseTransactions;
    public function test_get_forgotten_debtors_by_id_1c()
    {
        $user = factory(User::class)->create();

        $role = factory(Role::class)->create();
        RoleUser::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        $debtors = factory(Debtor::class, 'debtor_forgotten')->create([
            'forgotten_date' => now(),
            'str_podr' => $user->isDebtorsPersonal() ? '000000000007' : '000000000006',
            'responsible_user_id_1c' => $user->id_1c
        ]);

        $result = app(DebtorService::class)->getForgottenById1c($user, $user->id_1c);
        $this->assertCount(1, $result);
        $this->assertEquals($debtors->first()->id, $result->first()->id);
    }

    public function test_debtors_are_marked_forgotten()
    {
        $debtors = factory(Debtor::class, 'debtor_forgotten', 5)->create();

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
