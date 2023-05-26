<?php

use App\Debtor;
use Faker\Generator as Faker;

$factory->defineAs(Debtor::class,'debtor', function (Faker $faker) {

    return [
        'customer_id_1c' => $faker->text(9),
        'is_debtor' => 1,
        'od' => $faker->numberBetween(200000, 3000000),
        'pc' => $faker->numberBetween(200000, 3000000),
        'exp_pc' => $faker->numberBetween(200000, 3000000),
        'fine' => $faker->numberBetween(200000, 3000000),
        'tax' => $faker->numberBetween(200000, 3000000),
        'base' => $faker->text(10),
        'responsible_user_id_1c' => $faker->name(),
        'fixation_date' => $faker->dateTimeBetween('-30 days'),
        'qty_delays' => $faker->randomNumber(2),
        'sum_indebt' => $faker->numberBetween(200000, 3000000),
        'overpayments' => 0,
        'str_podr' => '00000000000010',
        'uploaded' => 1,
        'decommissioned' => 0,
        'non_interaction' => 0,
        'by_agent' => 0,
        'non_interaction_nf' => 0,
        'recommend_completed' => 0,
        'is_bigmoney' => 0,
        'is_pledge' => 0,
        'is_pos' => 0,
        'sale_status' => 0,
        'passport_series' => $faker->randomNumber(4),
        'passport_number' => $faker->randomNumber(6),
    ];
});
$factory->defineAs(Debtor::class,'debtor_forgotten', function (Faker $faker) {

    return [
        'customer_id_1c' => $faker->text(9),
        'is_debtor' => 1,
        'od' => $faker->numberBetween(200000, 3000000),
        'pc' => $faker->numberBetween(200000, 3000000),
        'exp_pc' => $faker->numberBetween(200000, 3000000),
        'fine' => $faker->numberBetween(200000, 3000000),
        'tax' => $faker->numberBetween(200000, 3000000),
        'base' => $faker->text(10),
        'responsible_user_id_1c' => $faker->name(),
        'fixation_date' => $faker->dateTimeBetween('-30 days'),
        'qty_delays' => $faker->randomNumber(2),
        'sum_indebt' => $faker->numberBetween(200000, 3000000),
        'overpayments' => 0,
        'str_podr' => $faker->randomElement(['000000000007', '000000000006']),
        'uploaded' => 1,
        'decommissioned' => 0,
        'non_interaction' => 0,
        'by_agent' => 0,
        'non_interaction_nf' => 0,
        'recommend_completed' => 0,
        'is_bigmoney' => 0,
        'is_pledge' => 0,
        'is_pos' => 0,
        'sale_status' => 0,
        'passport_series' => $faker->randomNumber(4),
        'passport_number' => $faker->randomNumber(6),
        'created_at' => $faker->dateTimeBetween('-30 days'),
    ];
});
