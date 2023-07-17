<?php

namespace App\Repositories;

use App\Debtor;
use Arcanedev\Support\Bases\Model;

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

    public function getDebtorsWithEqualPhone(string $phone)
    {
        return $this->model
            ->leftJoin('customers', 'debtors.customer_id_1c', '=', 'customers.id_1c')
            ->leftJoin('about_clients', 'customers.id', '=', 'about_clients.customer_id')
            ->where('telephone', $phone)
            ->orWhere('telephonehome', $phone)
            ->orWhere('telephoneorganiz', $phone)
            ->orWhere('telephonerodstv', $phone)
            ->orWhere('anothertelephone', $phone)
            ->get();
    }

}
