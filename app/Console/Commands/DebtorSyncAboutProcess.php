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
    protected $description = 'Display an inspiring quote';

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
        $aboutsSync = DebtorSyncAbout::whereNull('deleted_at')->whereNull('in_process')->limit(300)->get();
        if ($aboutsSync->isEmpty()) {
            return 0;
        }
        Log::info('Update clients :', ['customers' => $aboutsSync->pluck('id')->toArray()]);
        foreach ($aboutsSync as $aboutSync) {
            $aboutSync->in_process = 1;
            $aboutSync->save();
            dispatch((new DebtorSyncAboutImportJob($aboutSync->id))->onQueue('update_clients'));
        }

    }

}
