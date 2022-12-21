<?php

use Illuminate\Database\Seeder;

class MaritalTypesSeeder extends Seeder
{
    public function run()
    {
        $arr = [
            'Холост\Не замужем',
            'Женат\Замужем',
            'Гражданский брак',
            'Вдова\Вдовец',
            'Разведен(а)',
        ];
        foreach ($arr as $item){
            \App\MaritalType::create([
                'name'=>$item,
            ]);
        }
    }
}
