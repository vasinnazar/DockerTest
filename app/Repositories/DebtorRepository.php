<?php

namespace App\Repositories;

use App\Debtor;

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

}
