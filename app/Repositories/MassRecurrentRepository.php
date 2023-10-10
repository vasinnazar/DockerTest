<?php

namespace App\Repositories;

use App\MassRecurrent;
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
    public function update(int $id, array $params = []): Model
    {
        $modelItem = $this->model->where('id', $id)->firstOrFail();

        return tap($modelItem)->update($params);
    }
    /**
     * Получаем всех должников для безакцептного списания со статусом
     * для отправки в очередь в платежный контур
     */
    public function getWithoutAcceptDebtors(int $status)
    {
        return MassRecurrent::where('status_id', $status)->get();
    }
}
