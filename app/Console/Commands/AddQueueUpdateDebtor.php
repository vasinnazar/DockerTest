<?php namespace App\Console\Commands;

use App\Jobs\UpdateDebtorsJob;
use App\UpdateDebtors;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class AddQueueUpdateDebtor extends Command
{

    protected $signature = 'update-client:update-debtors-queue';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (Queue::size('update_debtors') >= env('QUEUE_MAX_SIZE', 300)) {
            return 0;
        }

        $debtors = UpdateDebtors::whereNull('deleted_at')->limit(150)->get();

        if ($debtors->isEmpty()) {
            return 0;
        }

        foreach ($debtors as $debtor) {
            dispatch(new UpdateDebtorsJob($debtor))->onQueue('update_debtors');
        }

    }

}
