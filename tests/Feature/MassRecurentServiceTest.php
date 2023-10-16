<?php

namespace Tests\Feature;

use App\Clients\PaysClient;
use App\Customer;
use App\Debtor;
use App\Jobs\WithoutAcceptJob;
use App\LoanType;
use App\Model\Status;
use App\Passport;
use App\Repositories\DebtorRepository;
use App\Repositories\MassRecurrentRepository;
use App\RoleUser;
use App\Services\MassRecurrentService;
use App\Subdivision;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RolesSeeder;
use Tests\TestCase;

class MassRecurentServiceTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testWithoutAcceptSendWithoutStrPodr(): void
    {
        $responseCreateTask = $this->from('/debtors/index')
            ->post('/debtors/recurrent/massquerytask', [
                'start' => 1,
                'qty_delays_from' => 90,
                'qty_delays_to' => 110,
            ]);
        $responseCreateTask->assertStatus(
        Response::HTTP_FOUND
        );
        $responseCreateTask->assertRedirect('/debtors/index');
    }
    public function testWithoutAcceptSendStrPodrIsFake(): void
    {
        $responseCreateTask = $this->from('/debtors/index')
            ->post('/debtors/recurrent/massquerytask', [
                'str_podr' => 'fake',
            ]);
        $responseCreateTask->assertStatus(
            Response::HTTP_FOUND
        );
        $responseCreateTask->assertRedirect('/debtors/index');
    }
    public function testWithoutAcceptDontTaskId(): void
    {
        $responseExecuteTask = $this->post('/debtors/recurrent/massquery', [
                'qty_delays_from' => 90,
                'qty_delays_to' => 110,
            ]);
        $responseExecuteTask->assertStatus(
            Response::HTTP_OK
        );
        $this->assertFalse(json_decode($responseExecuteTask->getContent(), true));
    }
    public function testWithoutAcceptFakeTaskId(): void
    {
        $responseExecuteTask = $this->post('/debtors/recurrent/massquery', [
                'task_id' => 9999,
            ]);
        $responseExecuteTask->assertStatus(
            Response::HTTP_OK
        );
        $this->assertTrue(json_decode($responseExecuteTask->getContent(), true));
    }
}