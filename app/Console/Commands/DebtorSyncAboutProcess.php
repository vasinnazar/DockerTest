<?php namespace App\Console\Commands;

use App\Jobs\DebtorSyncAboutImportJob;
use App\DebtorSyncAbout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class DebtorSyncAboutProcess extends Command
{

    protected $signature = 'debtor-sync:execute-about';
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
//        if (Queue::size('update_clients') >= env('QUEUE_MAX_SIZE', 300)) {
//            return 0;
//        }
        $aboutsSyncIds = DebtorSyncAbout::whereNull('deleted_at')->whereNull('in_process')->get()->pluck('id');

        if ($aboutsSyncIds->isEmpty()) {
            return 0;
        }

        foreach ($aboutsSyncIds as $aboutSyncId) {
            DebtorSyncAbout::where('id', $aboutSyncId)->update(['in_process' => 1]);
            dispatch((new DebtorSyncAboutImportJob($aboutSyncId))->onQueue('debtor-sync-about'));
        }

    }

}
