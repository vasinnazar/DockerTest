<?php

use App\Claim;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Claim::class, function (Faker $faker) {
    $idsCustomer = \App\Customer::all()->pluck('id')->toArray();
    $idsUser = \App\User::all()->pluck('id')->toArray();
    $idsClient = \App\about_client::all()->pluck('id')->toArray();
    $idsSubdivision = \App\Subdivision::all()->pluck('id')->toArray();
    $idsPassport = \App\Passport::all()->pluck('id')->toArray();

    return [
        'customer_id' => $faker->randomElement($idsCustomer),
        'srok' =>$faker->numberBetween(10, 31),
        'summa' =>$faker->numberBetween(2000, 30000),
        'date' =>$faker->dateTime(),
        'user_id' => $faker->randomElement($idsUser),
        'status' => 0,
        'about_client_id' => $faker->randomElement($idsClient),
        'subdivision_id' => $faker->randomElement($idsSubdivision),
        'passport_id' => $faker->randomElement($idsPassport),
        'max_money' => 0,
        'uki'=>0,
    ];
});
