<?php

namespace App\Console\Commands;

use App\Jobs\WithoutAcceptJob;
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
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        WithoutAcceptJob::dispatch(1234);
    }
}
