<?php

namespace App\Console\Commands;

use App\Clients\PaysClient;
use App\Jobs\WithoutAcceptJob;
use App\Model\Status;
use App\Repositories\DebtorRepository;
use App\Repositories\MassRecurrentRepository;
use App\Services\MassRecurrentService;
use Illuminate\Console\Command;

class SendWithoutAccept extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:without-accept';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправка безакцепта в очередь на списание';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MassRecurrentRepository $massRecurrentRepository)
    {
        parent::__construct();
        $this->massRecurrentRepository = $massRecurrentRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $debtorsMassRecurrents = $this->massRecurrentRepository->getByStatus(Status::NEW_SEND);
        foreach ($debtorsMassRecurrents as $debtorMassRec) {
            $debtorMassRec->fill([
                'status_id' => Status::IN_PROCESS,
            ])->save();
            WithoutAcceptJob::dispatch($debtorMassRec->id, $debtorMassRec->debtor_id);
        }
    }
}
