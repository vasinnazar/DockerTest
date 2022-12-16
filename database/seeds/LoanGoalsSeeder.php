<?php

use Illuminate\Database\Seeder;

class LoanGoalsSeeder extends Seeder
{
    public function run()
    {
        $arr = [
            'На лекарства',
            'На одежду',
            'На питание',
            'На оплату жилья (аренду)',
            'На оплату кредитов',
            'На оплату обучения',
            'На оплату отдыха',
            'На подарки',
            'На ремонт',
            'Другое',
        ];

        foreach ($arr as $item){
            \App\LoanGoal::create([
                'name'=>$item,
            ]);
        }
    }
}
