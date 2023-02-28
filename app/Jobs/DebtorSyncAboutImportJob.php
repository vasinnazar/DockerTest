<?php

namespace App\Jobs;

use App\Customer;
use App\DebtorsOtherPhones;
use App\Passport;
use App\DebtorSyncAbout;
use App\UploadSqlFile;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DebtorSyncAboutImportJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $aboutSyncId;

    public function __construct(int $aboutSyncId)
    {
        $this->aboutSyncId = $aboutSyncId;
    }

    public function handle()
    {
        $aboutSync = DebtorSyncAbout::find($this->aboutSyncId);
        $customer = Customer::where('id_1c', $aboutSync->customer_id_1c)->first();

        if (is_null($customer)) {
            Log::error('Update Client Info error customer not found : ', [
                'customer_id_1c' => $aboutSync->customer_id_1c
            ]);

            $aboutSync->deleted_at = Carbon::now();
            $aboutSync->save();
            $processCount = DebtorSyncAbout::whereNull('deleted_at')->where('file_id', $aboutSync->file_id)->count();

            if ($processCount == 0) {
                UploadSqlFile::where('id',$aboutSync->file_id)->update(['completed' => 1, 'in_process' => 0]);
            }
            return 0;
        }

        if (!is_null($aboutSync->telephone) && $customer->telephone != $aboutSync->telephone) {
            $customer->telephone = $aboutSync->telephone;
            $customer->save();
        }

        if (!is_null($aboutSync->telephonehome)) {
            DebtorsOtherPhones::addRecord(
                $aboutSync->debtor_id_1c,
                preg_replace("/[^0-9]/", '', $aboutSync->telephonehome),
                1
            );
        }

        if (!is_null($aboutSync->telephoneorganiz)) {
            DebtorsOtherPhones::addRecord(
                $aboutSync->debtor_id_1c,
                preg_replace("/[^0-9]/", '', $aboutSync->telephoneorganiz),
                2
            );
        }

        if (!is_null($aboutSync->telephonerodstv)) {
            DebtorsOtherPhones::addRecord(
                $aboutSync->debtor_id_1c,
                preg_replace("/[^0-9]/", '', $aboutSync->telephonerodstv),
                3
            );
        }
        if (!is_null($aboutSync->anothertelephone)) {
            DebtorsOtherPhones::addRecord(
                $aboutSync->debtor_id_1c,
                preg_replace("/[^0-9]/", '', $aboutSync->anothertelephone),
                4
            );
        }

        $passport = $customer->getLastPassport();

        if (is_null($passport)) {
            Log::error('Unable to find passport', ['customer_id_1c' => $customer->id_1c]);
        } else {

            try {
                $passport->zip = $aboutSync->zip;
                $passport->address_region = $aboutSync->address_region;
                $passport->address_district = $aboutSync->address_district;
                $passport->address_city = $aboutSync->address_city;
                $passport->address_street = $aboutSync->address_street;
                $passport->address_house = $aboutSync->address_house;
                $passport->address_building = $aboutSync->address_building;
                $passport->address_apartment = $aboutSync->address_apartment;
                $passport->address_city1 = $aboutSync->address_city1;
                $passport->fact_zip = $aboutSync->fact_zip;
                $passport->fact_address_region = $aboutSync->fact_address_region;
                $passport->fact_address_district = $aboutSync->fact_address_district;
                $passport->fact_address_city = $aboutSync->fact_address_city;
                $passport->fact_address_street = $aboutSync->fact_address_street;
                $passport->fact_address_house = $aboutSync->fact_address_house;
                $passport->fact_address_building = $aboutSync->fact_address_building;
                $passport->fact_address_apartment = $aboutSync->fact_address_apartment;
                $passport->fact_address_city1 = $aboutSync->fact_address_city1;
                $passport->save();

            } catch (\Exception $e) {
                Log::error('Error update info client : ', [$e->getMessage()]);
            }
        }

        $aboutSync->deleted_at = Carbon::now();
        $aboutSync->save();
        $processCount = DebtorSyncAbout::whereNull('deleted_at')->where('file_id', $aboutSync->file_id)->count();

        if ($processCount == 0) {
            UploadSqlFile::where('id',$aboutSync->file_id)->update(['completed' => 1, 'in_process' => 0]);
        }
    }
}
