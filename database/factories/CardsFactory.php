<?php

use App\Card;
use Faker\Factory as Faker;;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Card::class, function () {
    $faker = Faker::create('ru_RU');
    $idsCustomer = \App\Customer::all()->pluck('id')->toArray();
    return [
        'card_number'=>$faker->creditCardNumber(),
        'status'=>1,
        'customer_id' => $faker->randomElement($idsCustomer),
    ];
});
