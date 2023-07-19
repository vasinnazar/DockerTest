<?php

namespace App\Repositories;

use App\Debtor;
use Illuminate\Support\Collection;

class DebtorRepository
{
    private $model;

    public function __construct(Debtor $model)
    {
        $this->model = $model;
    }
    public function firstById(int $id)
    {
        return $this->model->findOrFail($id);
    }
    public function getDebtorsWithEqualPhone(string $phone, string $customerId1c): Collection
    {
        return $this->model
            ->leftJoin('customers', 'debtors.customer_id_1c', '=', 'customers.id_1c')
            ->leftJoin('about_clients', 'customers.id', '=', 'about_clients.customer_id')
            ->where('customer_id_1c', '!=', $customerId1c)
            ->where(function ($query) use ($phone) {
                $query->where('telephone', $phone)
                    ->orWhere('telephonehome', $phone)
                    ->orWhere('telephoneorganiz', $phone)
                    ->orWhere('telephonerodstv', $phone)
                    ->orWhere('anothertelephone', $phone);
            })
            ->get();
    }
    public function getDebtorsWithEqualAddressRegister(object $passport): Collection
    {
        return $this->model
            ->leftJoin('passports', function ($join) {
                $join->on('debtors.passport_series', '=', 'passports.series');
                $join->on('debtors.passport_number', '=', 'passports.number');
            })
            ->where('zip', $passport->zip)
            ->where('address_region', $passport->address_region)
            ->where('address_district', $passport->address_district)
            ->where('address_city', $passport->address_city)
            ->where('address_street', $passport->address_street)
            ->where('address_house', $passport->address_house)
            ->where('address_building', $passport->address_building)
            ->where('address_apartment', $passport->address_apartment)
            ->where('address_city1', $passport->address_city1)
            ->where('passports.id', '<>', $passport->id)
            ->get();
    }
    public function getDebtorsWithEqualAddressFact(object $passport): Collection
    {
        return $this->model
            ->leftJoin('passports', function ($join) {
                $join->on('debtors.passport_series', '=', 'passports.series');
                $join->on('debtors.passport_number', '=', 'passports.number');
            })
            ->where('fact_zip', $passport->zip)
            ->where('fact_address_region', $passport->address_region)
            ->where('fact_address_district', $passport->address_district)
            ->where('fact_address_city', $passport->address_city)
            ->where('fact_address_street', $passport->address_street)
            ->where('fact_address_house', $passport->address_house)
            ->where('fact_address_building', $passport->address_building)
            ->where('fact_address_apartment', $passport->address_apartment)
            ->where('fact_address_city1', $passport->address_city1)
            ->where('passports.id', '<>', $passport->id)
            ->get();
    }



}
