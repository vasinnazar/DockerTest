<?php

use App\Subdivision;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Subdivision::class, function (Faker $faker) {
    $address = $faker->address;
    return [
        'name' => $address,
        'name_id'=>$faker->numberBetween(0, 500),
        'address'=>$address,
        'peacejudge' => 'Мировой судья судебного участка ' . $address,
        'districtcourt' => 'районный суд ' . $address,
        'director' => $faker->name,
        'closed'=> 0,
        'is_terminal'=> 1,
        'city' => $address,
        'is_api' => 0,
    ];
});
