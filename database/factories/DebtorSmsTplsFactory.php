<?php

use App\DebtorSmsTpls;
use Faker\Generator as Faker;

$factory->defineAs(DebtorSmsTpls::class,'sms', function (Faker $faker) {

    return [
        'recovery_type'=>$faker->text(5),
        'text_tpl'=>$faker->text(20),
        'sort'=>$faker->randomElement([1.0]),
        'is_excluded'=>$faker->randomElement([1,0]),
    ];
});
