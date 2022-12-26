<?php

use App\Debtor;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Debtor::class, function (Faker $faker) {
    $ids1cCustomer = \App\Customer::all()->pluck('id_1c')->toArray();
    $nameUser = \App\User::all()->pluck('name')->toArray();
    $customer = \App\Customer::where('id_1c', $faker->randomElement($ids1cCustomer))->first();
    if (!is_null($customer)) {
        $passport = \App\Passport::where('customer_id',$customer->id)->first();
    }


    return [
        'customer_id_1c' => $faker->randomElement($ids1cCustomer),
        'is_debtor' => 1,
        'od' => $faker->numberBetween(200000, 3000000),
        'pc' => $faker->numberBetween(200000, 3000000),
        'exp_pc' => $faker->numberBetween(200000, 3000000),
        'fine' => $faker->numberBetween(200000, 3000000),
        'tax' => $faker->numberBetween(200000, 3000000),
        'base' => $faker->text(10),
        'responsible_user_id_1c' => $faker->randomElement($nameUser),
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
        'passport_series' => $passport->series ?? null,
        'passport_number' => $passport->number ?? null,
    ];
});
