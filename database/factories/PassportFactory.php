<?php

use App\Passport;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Passport::class, function (Faker $faker) {
    $idsCustomer = \App\Customer::all()->pluck('id')->toArray();
    return [
        'birth_date' => $faker->dateTimeInInterval('-50 years','-20 years')->format('Y-m-d'),
        'birth_city' => $faker->address,
        'series' => $faker->randomNumber(4,true),
        'number' => $faker->randomNumber(6,true),
        'issued' => $faker->text,
        'issued_date' => $faker->dateTimeInInterval('-32 years','-18 years')->format('Y-m-d'),
        'customer_id' => $faker->randomElement($idsCustomer),
        'fio' => $faker->name,
    ];
});
