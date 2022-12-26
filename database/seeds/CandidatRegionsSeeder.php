<?php

use Illuminate\Database\Seeder;

class CandidatRegionsSeeder extends Seeder
{
    public function run()
    {
        $arrRegions = [
            9 => 'Региональный центр Барнаул',
            10 => 'Региональный центр Кемерово-Север',
            12 => 'Региональный центр Ханты-Мансийск',
            13 => 'Региональный центр Томск',
            14 => 'Региональный центр Кемерово-Юг',
            15 => 'Республика Тыва',
            16 => 'Региональный центр Красноярск',
            17 => 'Региональный центр Новосибирск',
            18 => 'Региональный центр Омск',
            19 => 'Региональный центр Уфа',
            20 => 'Региональный центр Чита',
            21 => 'Региональный центр Бурятия',
            23 => 'Региональный центр Краснодар',
            27 => 'Региональный центр Якутск',

        ];

        foreach ($arrRegions as $regionsId => $nameRegions) {
            $visible = 1;
            if (in_array($regionsId, [12, 19, 20], true)) {
                $visible = 0;
            }
            \App\CandidateRegion::create([
                'id' => $regionsId,
                'name' => $nameRegions,
                'visible' => $visible,
            ]);
        }
    }
}
