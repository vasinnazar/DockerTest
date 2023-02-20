<?php

namespace App\Jobs;

use App\Customer;
use App\DebtorsOtherPhones;
use App\Passport;
use App\UpdateAboutClient;
use App\UploadSqlFile;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAboutClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $aboutClient;

    public function __construct(UpdateAboutClient $aboutClient)
    {
        $this->aboutClient = $aboutClient;
    }

    public function handle()
    {
        $customer = Customer::where('id_1c', $this->aboutClient->customer_id_1c)->first();
        if (is_null($customer)) {
            Log::error('Update Client Info error customer not found : ', [
                'customer_id_1c' => $this->aboutClient->customer_id_1c
            ]);
            return 0;
        }

        if (!is_null($this->aboutClient->telephone) && $customer->telephone != $this->aboutClient->telephone) {
            $customer->telephone = $this->aboutClient->telephone;
            $customer->save();
        }

        if (!is_null($this->aboutClient->telephonehome)) {
            DebtorsOtherPhones::addRecord(
                $this->aboutClient->debtor_id_1c,
                preg_replace("/[^0-9]/", '', $this->aboutClient->telephonehome),
                1
            );
        }

        if (!is_null($this->aboutClient->telephoneorganiz)) {
            DebtorsOtherPhones::addRecord(
                $this->aboutClient->debtor_id_1c,
                preg_replace("/[^0-9]/", '', $this->aboutClient->telephoneorganiz),
                2
            );
        }

        if (!is_null($this->aboutClient->telephonerodstv)) {
            DebtorsOtherPhones::addRecord(
                $this->aboutClient->debtor_id_1c,
                preg_replace("/[^0-9]/", '', $this->aboutClient->telephonerodstv),
                3
            );
        }
        if (!is_null($this->aboutClient->anothertelephone)) {
            DebtorsOtherPhones::addRecord(
                $this->aboutClient->debtor_id_1c,
                preg_replace("/[^0-9]/", '', $this->aboutClient->anothertelephone),
                4
            );
        }

        $passport = $customer->getLastPassport();

        if (is_null($passport)) {
            Log::error('Unable to find passport', ['customer_id_1c' => $customer->id_1c]);
            return 0;
        }

        try {

            $passport->zip = $this->aboutClient->zip;
            $passport->address_region = $this->aboutClient->address_region;
            $passport->address_district = $this->aboutClient->address_district;
            $passport->address_city = $this->aboutClient->address_city;
            $passport->address_street = $this->aboutClient->address_street;
            $passport->address_house = $this->aboutClient->address_house;
            $passport->address_building = $this->aboutClient->address_building;
            $passport->address_apartment = $this->aboutClient->address_apartment;
            $passport->address_city1 = $this->aboutClient->address_city1;
            $passport->fact_zip = $this->aboutClient->fact_zip;
            $passport->fact_address_region = $this->aboutClient->fact_address_region;
            $passport->fact_address_district = $this->aboutClient->fact_address_district;
            $passport->fact_address_city = $this->aboutClient->fact_address_city;
            $passport->fact_address_street = $this->aboutClient->fact_address_street;
            $passport->fact_address_house = $this->aboutClient->fact_address_house;
            $passport->fact_address_building = $this->aboutClient->fact_address_building;
            $passport->fact_address_apartment = $this->aboutClient->fact_address_apartment;
            $passport->fact_address_city1 = $this->aboutClient->fact_address_city1;
            $passport->save();

            UpdateAboutClient::whereId($this->aboutClient->id)->update('deleted_at', Carbon::now());
            $process = UpdateAboutClient::whereNull('deleted_at')->where('file_id')->get();
            if ($process->isEmpty()) {
                UploadSqlFile::find($this->aboutClient->file_id)->update(['completed' =>1,'in_process' => 0]);
            }
        } catch (\Exception $e) {
            Log::error('Error update info client : ', [$e->getMessage()]);
        }

    }
}
