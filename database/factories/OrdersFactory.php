<?php

use App\Order;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Order::class, function (Faker $faker) {
    $idsCustomer = \App\Customer::all()->pluck('id')->toArray();
    $idsUser = \App\User::all()->pluck('id')->toArray();
    $idsSubdivision = \App\Subdivision::all()->pluck('id')->toArray();
    $idsOrdersType = \App\OrderType::all()->pluck('id')->toArray();
    return [
        'type' => $faker->randomElement($idsOrdersType),
        'number' => $faker->randomNumber(5,true),
        'subdivision_id' => $faker->randomElement($idsSubdivision),
        'customer_id' => $faker->randomElement($idsCustomer),
        'user_id' => $faker->randomElement($idsUser),
        'money' =>$faker->numberBetween(200000, 3000000),
        'sync'=> 1,
    ];
});
