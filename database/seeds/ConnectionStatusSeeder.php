<?php

use App\Model\ConnectionStatus;
use Illuminate\Database\Seeder;

class ConnectionStatusSeeder extends Seeder
{
    public function run()
    {
        $connectionStatuses = [
            'АО',
            'В сети не зарегистрирован',
            'Временно не доступен',
            'Длительный вызов',
            'Есть контакт',
            'Занят',
            'Не правильно набран номер',
            'Номер изменился',
            'Номер не обслуживается',
            'Номер не существует',
            'Ожидание вызова (вторая линия)',
            'Сброс во время вызова',
            'Сброс во время диалога',
            'Сброс после представления',
            'Сброс клиент не слышит',
            'Тишина'
        ];

        foreach ($connectionStatuses as $status) {
            ConnectionStatus::query()->firstOrCreate(
                ['name' => $status]
            );
        }
    }
}
