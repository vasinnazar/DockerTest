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
    protected $description = 'Добавление заданий в очередь из временной таблицы';

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

        $debtorsSyncIds = DebtorSync::whereNull('deleted_at')->whereNull('in_process')->get()->pluck('id');

        if ($debtorsSyncIds->isEmpty()) {
            return 0;
        }

        foreach ($debtorsSyncIds as $debtorSyncId) {
            DebtorSync::where('id', $debtorSyncId)->update(['in_process' => 1]);
            dispatch((new DebtorSyncSqlImportJob($debtorSyncId))->onQueue('debtor-sync-sql'));
        }

    }

}
