<?php

namespace App\Console;

use App\Console\Commands\DebtorSyncAboutProcess;
use App\Console\Commands\DebtorSyncSqlProcess;
use App\Console\Commands\Inspire;
use App\Console\Commands\MysqlBackup;
use App\Console\Commands\RepaymentOfferAutoPeace;
use App\Console\Commands\DebtorSyncFilesImport;
use App\Console\Commands\PassportsUpdateTimeZone;
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
        PassportsUpdateTimeZone::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            //в должниках проверять ордера и добавлять сообщение об оплате
            if (config('app.version_type') == 'debtors') {
//                \App\Debtor::checkForPaymentInArm();
                /**
                 * Воскрешаем реплику если та лежит
                 */
//                $slave_data = DB::select(DB::raw('show slave status'));
//                if ($slave_data[0]->Slave_SQL_Running == 'No') {
//                    $master_data = DB::connection('arm115')->select(DB::raw('show master status'));
//                    DB::select(DB::raw('STOP SLAVE'));
//                    DB::select(DB::raw("CHANGE MASTER TO MASTER_LOG_FILE='" . $master_data[0]->File . "', MASTER_LOG_POS=" . $master_data[0]->Position . ";"));
//                    DB::select(DB::raw('START SLAVE'));
//                }
            } else {
                \App\Order::syncOrders();
//            \App\Loan::syncLoans();
                \App\Scorista::checkStatuses();
                \App\MysqlThread::addToStat();

                /**
                 * обрабатываем входящие смски
                 */
                $now = Carbon::now();
                if ($now->hour >= 8 && $now->hour <= 22) {
                    \App\SmsInbox::uploadFromGoIpDb();
                    \App\SmsInbox::handleInbox('2017-05-24');
                }
            }
        })->everyMinute();
        $schedule->call(function () {
            if (config('app.version_type') != 'debtors') {
                // подгружать ордера с киви и банка чтобы они появлялись в реплике арма для должников
                $now = Carbon::now();
                if ($now->hour >= 8 && $now->hour <= 22) {
                    \App\Synchronizer::updateOrders(Carbon::today()->format('Y-m-d H:i:s'), null, null, '7494');
                    \App\Synchronizer::updateOrders(Carbon::today()->format('Y-m-d H:i:s'), null, null, '000000012');
                }
            }
        })->hourly();
        /**
         * каждый день в полночь
         */
        $schedule->call(function () {
            if (config('app.version_type') != 'debtors') {
                /**
                 * выводить сообщение о сверке карт
                 */
                if (Carbon::now()->day == Carbon::now()->daysInMonth) {
                    \App\Message::createEndMonthSfpMessage();
                }
                /**
                 * Скопировать логи из локальной папки на ФТП
                 */
                $filename = 'laravel-' . Carbon::now()->subDay()->format('Y-m-d') . '.log';
                $file = File::get(storage_path() . '/logs/' . $filename);
                Storage::disk('ftp222')->put('logs/' . $filename, $file);
            }
            Log::info("Hello from cron. Changing owner...");
            \App\Utils\HelperUtil::startShellProcess('chown -R apache:apache /var/www/armff.ru/storage/logs/laravel-' . Carbon::today()->format('Y-m-d') . '.log');
        })->daily();
        $schedule->call(function () {
            if (config('app.version_type') != 'debtors') {
                /**
                 * Сделать бэкап базы
                 */
//                \App\Utils\MysqlBackupUtil::createBackup();
            }
        })->dailyAt('01:00');
        $schedule->call(function () {
            \App\Utils\MysqlBackupUtil::clearDumpsFolder();
        })->dailyAt('02:00');
        $schedule->call(function () {
            if (config('app.version_type') == 'debtors') {
                /**
                 * Сделать бэкап базы
                 */
//                \App\Utils\MysqlBackupUtil::createBackup(null, 'armf');
//                \App\Utils\MysqlBackupUtil::createBackup(null, 'debtors');
            }
        })->dailyAt('23:00');
        $schedule->call(function () {

//            if (Carbon::now()->day == 5) {
//                //каждый месяц запускаем отправку в телепорт заявок с нулевыми статусами
//                $start_date = Carbon::today()->subMonth()->day(1)->format('Y-m-d H:i:s');
//                $end_date = Carbon::today()->day(1)->format('Y-m-d H:i:s');
//                \App\Claim::resendTeleportClaimsWithNullStatus($start_date, $end_date);
//            }
        })->dailyAt('08:40');
        $schedule->call(function () {
            if (config('app.version_type') != 'debtors') {
                //загружает номенклатуры, склады и статьи затрат
                \App\Nomenclature::uploadFrom1c();
                \App\SubdivisionStore::uploadFrom1c();
                \App\Expenditure::uploadFrom1c();
            }
        })->dailyAt('07:00');

        $schedule->call(function () {
            $crCon = new \App\Http\Controllers\CronController();
            $crCon->checkSqlFileForUpdate();
        })->everyMinute();

        $schedule->call(function () {
            if (config('app.version_type') == 'debtors') {
                //делает загрузку данных по пустым контрагентам по выгруженным с ночи файлам
                $uploader = new \App\Utils\DebtorsInfoUploader();
                $date = Carbon::now()->format('dmY');
                $uploader->uploadByFilenames([
                    'rassrochka_' . $date . '.txt',
                    'mirovoe_' . $date . '.txt',
                    'zayavlenie_penya_' . $date . '.txt',
                    'dopnik_' . $date . '.txt',
                    'cred_' . $date . '.txt',
                    'factadress_' . $date . '.txt',
                    'uradress_' . $date . '.txt',
                    'passport_' . $date . '.txt',
                    'zayavka_' . $date . '.txt'
                ]);
                //заполняет пустые поля с пользователем и ответственным
                $now = Carbon::now()->format('Y-m-d H:i:s');
                try {
                    DB::statement("UPDATE debtors.debtor_events as d1 SET user_id=(SELECT id FROM debtors.users WHERE id_1c=d1.user_id_1c), updated_at='" . $now . "' where user_id is null limit 10000;");
                    DB::statement("UPDATE debtors.debtor_events as d1 SET debtor_id=(SELECT id FROM debtors.debtors WHERE debtor_id_1c=d1.debtor_id_1c), updated_at='" . $now . "' where debtor_id is null limit 10000;");
                } catch (\Exception $ex) {

                }
            }
        })->dailyAt('08:00');

        $schedule->call(function () {
            $c = new \App\Http\Controllers\CronController();
            $c->getOmicronTask();
        })->everyThirtyMinutes();

        $schedule->call(function () {
            \Illuminate\Support\Facades\DB::table('debtors.debtors')
                ->where('closed_at', '<=', date('Y-m-d', time()) . ' 00:00:00')
                ->update([
                    'od_after_closing' => 0
                ]);
        })->dailyAt('07:00');

        $schedule->command('passports-update:time-zone');
        $schedule->command('repayment-offers:auto-peace');
        $schedule->command('debtor-sync:import')->withoutOverlapping();
        $schedule->command('debtor-sync:execute-sql')->withoutOverlapping();
        $schedule->command('debtor-sync:execute-about')->withoutOverlapping();

    }

}
