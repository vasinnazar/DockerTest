<?php namespace App\Console\Commands;

use App\DebtorRegionTimezone;
use App\Jobs\DebtorSyncAboutImportJob;
use App\DebtorSyncAbout;
use App\Passport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PassportsUpdateTimeZone extends Command
{

    protected $signature = 'passports-update:time-zone';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'обновление тайм-зоны по региону';


    public function handle()
    {
        $timezones = DebtorRegionTimezone::get();
        foreach ($timezones as $tz) {
            try {
                if ($tz->id == 65) {
                    Passport::where('fact_address_region', 'like', $tz->root_word . '%')
                        ->where('fact_timezone', '!=', $tz->timezone)
                        ->orWhereNull('fact_timezone')
                        ->update(['fact_timezone' => $tz->timezone]);
                    continue;
                }
                Passport::where('fact_address_region', 'like', '%' . $tz->root_word . '%')
                    ->where('fact_timezone', '!=', $tz->timezone)
                    ->orWhereNull('fact_timezone')
                    ->update(['fact_timezone' => $tz->timezone]);

            } catch (Throwable $exception) {
                Log::error('Error update timezone : ' . $exception->getMessage());
            }
        }
    }

}
