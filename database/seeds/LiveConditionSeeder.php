<?php

use Illuminate\Database\Seeder;
use App\LiveCondition;

class LiveConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $liveConditions = [
            1 => 'Арендуемое',
            2 => 'Долевая собственность',
            3 => 'Живут с родителями',
            4 => 'Муниципальное',
            5 => 'Собственное',
        ];

        foreach ($liveConditions as $id => $name) {
            LiveCondition::create([
                'name' => $name,
            ]);
        }
    }
}
