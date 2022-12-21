<?php

use App\Customer;
use Faker\Factory as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Customer::class, function () {
    $faker = Faker::create('ru_RU');
    return [
        'telephone' => $faker->phoneNumber,
        'id_1c' => $faker->swiftBicNumber,
        'balance' => 0,
    ];
});
