<?php

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $arrPermission = [
            ['name' => 'select.orders.all.ever', 'description' => 'Запрашивать все ордеры за все время'],
            ['name' => 'open.loans.all.ever', 'description' => 'Открывать список кредитников (погасить займ)'],
            ['name' => 'create.claims.all.ever', 'description' => 'Возможность создавать заявку'],
            ['name' => 'open.quiz_departments.all.ever', 'description' => 'Доступ к окну опросника о взаимодействии'],
            ['name' => 'open.adminpanel.all.ever', 'description' => 'Доступ к админ панели'],
            ['name' => 'open.quiz_departments_report.all.ever', 'description' => 'Доступ к отчету о взаимодействии'],
            ['name' => 'open.rnko.all.ever', 'description' => 'Открытие РНКО'],
            ['name' => 'open.debtors.all.ever', 'description' => ""],
            ['name' => 'open.debtor_transfer.all.ever', 'description' => 'Доступ к актам приема-передачи должников'],
            ['name' => 'select.candidate_list.all.ever', 'description' => 'Доступ к странице кандидатов'],
            ['name' => 'show.phones', 'description' => 'Просмотр номеров телефонов в списках'],
            ['name' => 'show.passports', 'description' => 'Просмотр паспортных данных в списках'],
            ['name' => 'show.addresses', 'description' => 'Просмотр адресов'],
            ['name' => 'show.birthdays', 'description' => 'Просмотр дат рождения'],
        ];
        foreach ($arrPermission as $perm)
        {
            \App\Permission::create([
                'name' => $perm['name'],
                'description' => $perm['description'],
            ]);
        }

    }
}
