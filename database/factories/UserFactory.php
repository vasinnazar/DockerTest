<?php

use App\User;
use Faker\Factory as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(User::class, function () {
    $faker = Faker::create('ru_RU');
    $name = $faker->name;
    return [
        'name' => $name,
        'login' => $name,
        'password' => \Illuminate\Support\Facades\Hash::make(12356),
        'remember_token' => str_random(10),
        'group_id' => 1,
        'doc' => null,
        'banned' => 0,
        'begin_time' => '00:00:00',
        'end_time' => '00:00:01',
        'customer_id' => 0,
        'last_login' => $faker->date('Y-m-d H:m:s'),
        'employment_agree' => $faker->date('Y-m-d H:m:s'),
        'employment_docs_track_number' => 'testfill',
        'id_1c'=> $faker->name
    ];
});
