<?php

use App\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $arrRoles = [
            '1'  => [
                'name' => 'admin',
                'description' => "Администратор, поддержка"
            ],
            '2'  => [
                'name' => 'spec',
                'description' => 'Специалист на точке'
            ],
            '3'  => [
                'name' => 'seb_terminal',
                'description' => 'Проверка работающая с заявками с терминалов'
            ],
            '4' => [
                'name' => 'udvzisk',
                'description' => 'Отдел удаленного взыскания'
            ],
            '5' => [
                'name' => 'tester',
                'description' => 'Тестирование'
            ],
            '6' => [
                'name' => 'ruk',
                'description' => 'Руководитель'
            ],
            '7' => [
                'name' => 'rnko',
                'description' => 'Открытие РНКО'
            ],
            '8' => [
                'name' => 'debtors',
                'description' => 'Работают с должниками'
            ],
            '9' => [
                'name' => 'debtors_remote',
                'description' => 'Специалист удаленного взыскания'
            ],
            '10' => [
                'name' => 'debtors_personal',
                'description' => 'Специалист личного взыскания'
            ],
            '11' => [
                'name' => 'debtors_chief',
                'description' => 'Начальник отдела взыскания'
            ],
            '12' => [
                'name' => 'DepHR',
                'description' => 'Департамент HR'
            ],
            '13' => [
                'name' => 'GraphSales',
                'description' => 'Доступ к странице графиков продаж'
            ],
            '14' => [
                'name' => 'DepHR_headman',
                'description' => 'Руководители для кандидатов'
            ],
            '15' => [
                'name' => 'DepHR_director',
                'description' => 'Руководитель HR'
            ],
            '16' => [
                'name' => 'not_blockable_user',
                'description' => 'Не банить по отсутствию 7 дней'
            ],
            '17' => [
                'name' => 'missed_calls',
                'description' => 'Пропущенные звонки отделения'
            ],
            '24' => [
                'name' => 'autoinformator',
                'description' => 'Автоинформатор'
            ]
        ];
        foreach ($arrRoles as $key => $role) {
            Role::updateOrCreate([
                'id' => $key,
                'name' => $role['name'],
                'description' => $role['description'],
            ]);
        }
    }
}
