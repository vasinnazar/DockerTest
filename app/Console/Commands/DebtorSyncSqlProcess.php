<?php namespace App\Console\Commands;

use App\Jobs\DebtorSyncSqlImportJob;
use App\DebtorSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class DebtorSyncSqlProcess extends Command
{

    protected $signature = 'debtor-sync:execute-sql';
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
//        if (Queue::size('update_debtors') >= env('QUEUE_MAX_SIZE', 300)) {
//            return 0;
//        }

        $debtorsSync = DebtorSync::whereNull('deleted_at')->whereNull('in_process')->limit(300)->get();

        if ($debtorsSync->isEmpty()) {
            return 0;
        }

        foreach ($debtorsSync as $debtorSync) {
            $debtorSync->in_process = 1;
            $debtorSync->save();
            dispatch((new DebtorSyncSqlImportJob($debtorSync->id))->onQueue('update_debtors'));
        }

    }

}
