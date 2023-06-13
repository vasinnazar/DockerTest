<?php

namespace App\Repositories;

use App\Message;
use Illuminate\Database\Eloquent\Model;

class MessageRepository
{
    private $model;

    public function __construct(Message $model)
    {
        $this->model = $model;
    }

    public function firstById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function getMessageByType(string $type): ?Message
    {
        return $this->model->where('type', $type)->first();
    }
}
