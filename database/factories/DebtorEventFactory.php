<?php

use App\DebtorEvent;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(DebtorEvent::class, function (Faker $faker) {
    $ids1cCustomer = \App\Customer::all()->pluck('id_1c')->toArray();
    $idsUser = \App\User::all()->pluck('id')->toArray();
    $idsDebtor = \App\Debtor::all()->pluck('id')->toArray();
    return [
        'date'=> $faker->dateTimeBetween('- 15 days'),
        'customer_id_1c' => $faker->randomElement($ids1cCustomer),
        'event_type_id' => $faker->numberBetween(0,25),
        'debt_group_id' => $faker->numberBetween(0,69),
        'event_result_id' => $faker->numberBetween(0,30),
        'report' => $faker->text,
        'debtor_id' => $faker->randomElement($idsDebtor),
        'user_id' => $faker->randomElement($idsUser),
        'completed' => 0,
    ];
});
