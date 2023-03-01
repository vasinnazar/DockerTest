<?php

namespace App\Console;

use App\Console\Commands\DebtorSyncAboutProcess;
use App\Console\Commands\DebtorSyncFilesDownload;
use App\Console\Commands\DebtorSyncSqlProcess;
use App\Console\Commands\Inspire;
use App\Console\Commands\MysqlBackup;
use App\Console\Commands\RepaymentOfferAutoPeace;
use App\Console\Commands\DebtorSyncFilesImport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Inspire::class,
        MysqlBackup::class,
        RepaymentOfferAutoPeace::class,
        DebtorSyncFilesImport::class,
        DebtorSyncAboutProcess::class,
        DebtorSyncSqlProcess::class,
        DebtorSyncFilesDownload::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        $schedule->command('repayment-offers:auto-peace');
        $schedule->command('debtor-sync:download')->withoutOverlapping();
        $schedule->command('debtor-sync:import')->withoutOverlapping();
        $schedule->command('debtor-sync:execute-sql')->withoutOverlapping();
        $schedule->command('debtor-sync:execute-about')->withoutOverlapping();

    }

}
