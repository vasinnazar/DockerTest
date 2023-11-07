<?php

use App\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $arrRoles = [
            [
                'name' => 'admin',
                'description' => "Администратор, поддержка"
            ],
            [
                'name' => 'spec',
                'description' => 'Специалист на точке'
            ],
            [
                'name' => 'seb_terminal',
                'description' => 'Проверка работающая с заявками с терминалов'
            ],
            [
                'name' => 'udvzisk',
                'description' => 'Отдел удаленного взыскания'
            ],
            [
                'name' => 'tester',
                'description' => 'Тестирование'
            ],
            [
                'name' => 'ruk',
                'description' => 'Руководитель'
            ],
            [
                'name' => 'rnko',
                'description' => 'Открытие РНКО'
            ],
            [
                'name' => 'debtors',
                'description' => 'Работают с должниками'
            ],
            [
                'name' => 'debtors_remote',
                'description' => 'Специалист удаленного взыскания'
            ],
            [
                'name' => 'debtors_personal',
                'description' => 'Специалист личного взыскания'
            ],
            [
                'name' => 'debtors_chief',
                'description' => 'Начальник отдела взыскания'
            ],
            [
                'name' => 'DepHR',
                'description' => 'Департамент HR'
            ],
            [
                'name' => 'GraphSales',
                'description' => 'Доступ к странице графиков продаж'
            ],
            [
                'name' => 'DepHR_headman',
                'description' => 'Руководители для кандидатов'
            ],
            [
                'name' => 'DepHR_director',
                'description' => 'Руководитель HR'
            ],
            [
                'name' => 'not_blockable_user',
                'description' => 'Не банить по отсутствию 7 дней'
            ],
            [
                'name' => 'missed_calls',
                'description' => 'Пропущенные звонки отделения'
            ],
        ];
        for ($i = 0; $i < count($arrRoles); $i++) {
            $test = Role::updateOrCreate([
                'id' => $i + 1,
                'name' => $arrRoles[$i]['name'],
                'description' => $arrRoles[$i]['description'],
            ]);
        }
    }
}
