<?php

namespace App\Console;

use App\Console\Commands\AddQueueUpdateClient;
use App\Console\Commands\AddQueueUpdateDebtor;
use App\Console\Commands\RepaymentOfferAutoPeace;
use App\Console\Commands\FilesUpdateClient;
use App\Console\Commands\UploadFilesUpdate;
use App\UpdateDebtors;
use App\UploadSqlFile;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use DB;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\Inspire',
        'App\Console\Commands\MysqlBackup',
        RepaymentOfferAutoPeace::class,
        FilesUpdateClient::class,
        UploadFilesUpdate::class,
        AddQueueUpdateClient::class,
        AddQueueUpdateDebtor::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        $schedule->command('repayment-offers:auto-peace');
        $schedule->command('update-client:get-files');
        $schedule->command('update-client:upload-files');
        $schedule->command('update-client:update-clients-queue');
        $schedule->command('update-client:update-debtors-queue');

    }

}
