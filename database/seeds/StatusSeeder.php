<?php

use App\Model\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            'Новый',
            'В процессе',
            'Отправлено',
            'Ошибка',
        ];

        foreach ($statuses as $status) {
            Status::query()->create(
                ['name' => $status]
            );
        }
    }
}
