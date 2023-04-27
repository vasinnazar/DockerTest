<?php

use App\Role;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Role::class, function (Faker $faker) {
    return [
        'id'=>$faker->randomElement([12,13]),
        'name'=>$faker->randomElement(['debtor_remote','debtor_personal']),
        'description'=> $faker->text
    ];
});
