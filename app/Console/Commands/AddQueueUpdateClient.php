<?php namespace App\Console\Commands;

use App\Jobs\UpdateAboutClientJob;
use App\Jobs\UpdateDebtorsJob;
use App\UpdateAboutClient;
use App\UpdateDebtors;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class AddQueueUpdateClient extends Command
{

    protected $signature = 'update-client:update-clients-queue';
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
        if (Queue::size('update_clients') >= env('QUEUE_MAX_SIZE', 300)) {
            return 0;
        }
        $aboutClients = UpdateAboutClient::whereNull('deleted_at')->limit(150)->get();
        if ($aboutClients->isEmpty()) {
            return 0;
        }

        foreach ($aboutClients as $aboutClient) {
            Log::info('Update clients :', ['customers' => $aboutClients->pluck('id')->toArray()]);
            dispatch(new UpdateAboutClientJob($aboutClient))->onQueue('update_clients');
        }

    }

}
