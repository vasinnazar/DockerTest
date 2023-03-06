<?php namespace App\Console\Commands;

use App\Jobs\DebtorSyncAboutImportJob;
use App\DebtorSyncAbout;
use Illuminate\Console\Command;

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
