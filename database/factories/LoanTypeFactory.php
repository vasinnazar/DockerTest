<?php

use App\LoanType;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(LoanType::class, function (Faker $faker) {
    return [
        'additional_card_contract_id' => $faker->randomNumber(),
        'additional_card_contract_perm_id' => $faker->randomNumber(),
    ];
});
