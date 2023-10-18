<?php

use App\Role;
use App\User;
use App\RoleUser;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(RoleUser::class, function (Faker $faker) {
    $idsRole = Role::all()->pluck('id')->toArray();
    $idsUser = User::all()->pluck('id')->toArray();
    return [
        'user_id'=>$faker->randomElement($idsUser),
        'role_id'=>$faker->randomElement($idsRole),
    ];
});
