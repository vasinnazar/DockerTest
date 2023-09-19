<?php

namespace App\Repositories;

use App\Model\ConnectionStatus;
use Illuminate\Support\Collection;

class ConnectionStatusRepository
{
    private $model;

    public function __construct(ConnectionStatus $model)
    {
        $this->model = $model;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }
}
