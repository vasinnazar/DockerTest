<?php

namespace App\Console\Commands;

use App\Jobs\WithoutAcceptJob;
use App\MassRecurrent;
use App\Model\Status;
use App\Repositories\MassRecurrentRepository;
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
        MassRecurrent::where('status_id', Status::NEW_SEND)->chunkById(100, function ($debtorsMassRecurrent) {
            foreach ($debtorsMassRecurrent as $debtorMassRec) {
                $debtorMassRec->fill([
                    'status_id' => Status::IN_PROCESS,
                ])->save();
                WithoutAcceptJob::dispatch($debtorMassRec->id, $debtorMassRec->debtor_id)->onQueue('without_accept');
            }
        });
    }
}
