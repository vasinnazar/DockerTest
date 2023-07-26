<?php

namespace App\Repositories;

use App\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CustomerRepository
{
    private $model;

    public function __construct(Customer $model)
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
}
