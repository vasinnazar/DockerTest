<?php

use Illuminate\Database\Seeder;

class EducationSeeder extends Seeder
{
    public function run()
    {
        $arrEducation = [
            1 => 'Нет',
            2 => 'Среднее неполное',
            3 => 'Среднее',
            4 => 'Средне-специальное',
            5 => 'Высшее неоконченное',
            6 => 'Высшее',
        ];

        foreach ($arrEducation as $educationId => $nameEducation) {
            \App\EducationLevel::create([
                'id' => $educationId,
                'name' => $nameEducation,
            ]);
        }
    }
}
