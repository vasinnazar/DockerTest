<?php


namespace App\Services;

use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TimezoneService
{
    /**
     * @param $region
     * @return Carbon|null
     */
    public static function getRegionTime($region): ?Carbon
    {
        if(is_null($region)){
            return null;
        }
        $offset = config('timezones.' . $region);
        if (is_null($offset)) {
            return null;
        }
        return Carbon::now()->addHour($offset);
    }

    /**
     * @param $debtors
     * @param $timezone
     * @return Collection
     */
    public static function getDebtorsForTimezone($debtors, $timezone): Collection
    {
        $timezone = $timezone === 'east' ? [5, -1] : [-2, -5];
        $regions = self::getTimezoneRegions($timezone);
        $debtorsWithTimezone = collect();
        foreach ($debtors as $debtor) {
            try {
                if ($debtor->customer()) {
                    $passports = $debtor->customer()
                        ->passports()
                        ->wherein('address_region', $regions)
                        ->get()
                        ->last();
                }
            } catch (\Exception $exc) {
                Log::error('TimezoneService.getDebtorsForTimezone Не найден паспорт', ['debtor' => $debtor->id, 'exception' => $exc]);
            }

            if (isset($passports) && ($debtor->passport_series == $passports->series && $debtor->passport_number == $passports->number)) {
                $debtorsWithTimezone->push($debtor);
            }
        }
        return $debtorsWithTimezone;
    }

    /**
     * @param $timezone
     * @return array|null
     */
    public static function getTimezoneRegions($timezone): ?array
    {
        if(is_null($timezone)){
            return null;
        }
        $regions = config('timezones');
        foreach ($regions as $key => $value) {
            if ($value <= $timezone[0] && $value >= $timezone[1]) {
                $regionsForTimezone[] = $key;
            }
        }

        if (is_null($regionsForTimezone)) {
            return null;
        }

        return $regionsForTimezone;
    }
}