<?php

namespace App\Repositories;

use App\MassRecurrent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MassRecurrentRepository
{
    private $model;

    public function __construct(MassRecurrent $model)
    {
        $this->model = $model;
    }
    public function firstById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }
    public function store(array $params): Model
    {
        return $this->model->create($params);
    }
    public function update(int $id, array $params = []): Model
    {
        $modelItem = $this->model->where('id', $id)->firstOrFail();

        return tap($modelItem)->update($params);
    }
    public function updateByTask(int $taskId, array $params = []): Model
    {
        $modelItem = $this->model->where('task_id', $taskId);

        return tap($modelItem)->update($params);
    }
    public function getByStatus(int $status): Collection
    {
        return $this->model->where('status_id', $status)->get();
    }
}
