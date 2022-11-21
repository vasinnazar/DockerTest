<?php


namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Auth;
use Illuminate\Http\Request;
use App\Region;
use App\Debtor;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Collection;

class TimezoneService
{
    public static function getRegionTime($region)
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

    public static function getDebtorsForTimezone($debtors, $timezone)
    {
        $timezone = $timezone === 'past' ? [5, -1] : [-2, -5];
        $regions = self::getTimezoneRegions($timezone);
        $debtorsWithTimezone = collect();
        foreach ($debtors as $debtor) {
            try {
                if ($debtor->customer()) {
                    $passports = $debtor->customer()->passports()->wherein('address_region', $regions)->get()->last();
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

    public static function getTimezoneRegions($timezone)
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