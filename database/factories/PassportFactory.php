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
        'zip' => '123123',
        'address_region' => 'Кемеровская обл',
        'address_city' => 'Кемерово г',
        'address_street' => 'Дарвина ул',
        'address_house' => '160а',
        'fact_zip' => '123123',
        'fact_address_region' => 'Кемеровская обл',
        'fact_address_city' => 'Кемерово г',
        'fact_address_street' => 'Дарвина ул',
        'fact_address_house' => '160а',
        'address_reg_date' => now(),
    ];
});
