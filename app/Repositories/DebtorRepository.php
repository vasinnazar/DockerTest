<?php

namespace App\Repositories;

use App\Customer;
use App\Debtor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DebtorRepository
{
    private $model;

    public function __construct(Debtor $model)
    {
        $this->model = $model;
    }
    public function getAll(): Collection
    {
        return $this->model::all();
    }
    public function firstById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }
    public function update(int $id, array $params = []): Model
    {
        $modelItem = $this->model->where('id', $id)->firstOrFail();

        return tap($modelItem)->update($params);
    }
    public function getDebtorsByPodrAndGroupAndBase(
        int $isDebtor,
        string $strPodr,
        array $groupsId,
        int $qtyDelays,
        string $base
    ): Collection
    {
        return $this->model
            ->where('is_debtor', $isDebtor)
            ->where('str_podr', $strPodr)
            ->whereIn('debt_group_id', $groupsId)
            ->where('qty_delays', $qtyDelays)
            ->where('base', $base)
            ->get();
    }
    public function getDebtorsWithEqualTelephone(string $phone): Collection
    {
        return $this->model
            ->leftJoin('customers', 'debtors.customer_id_1c', '=', 'customers.id_1c')
            ->where('telephone', $phone)
            ->get();
    }
    public function getDebtorsByCustomerId1c(array $customerId1c): Collection
    {
        return $this->model->whereIn('customer_id_1c', $customerId1c)->get();
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
