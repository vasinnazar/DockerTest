<?php

use App\about_client;
use Faker\Factory as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(about_client::class, function () {
    $faker = Faker::create('ru_RU');
    $idsCustomer = \App\Customer::all()->pluck('id')->toArray();
    $idAdsource = \App\AdSource::all()->pluck('id')->toArray();
    $idsEducation = \App\EducationLevel::all()->pluck('id')->toArray();
    $idsLiveCond = \App\LiveCondition::all()->pluck('id')->toArray();
    $idsLoanGoal = \App\LoanGoal::all()->pluck('id')->toArray();
    $idsMaritalType = \App\MaritalType::all()->pluck('id')->toArray();

    return [
        'customer_id' => $faker->randomElement($idsCustomer),
        'sex' => $faker->numberBetween(0, 10),
        'goal' => $faker->randomElement($idsLoanGoal),
        'zhusl' => $faker->randomElement($idsLiveCond),
        'avto' => $faker->numberBetween(0, 1),
        'organizacia' => $faker->company(),
        'innorganizacia' => $faker->randomNumber(7, true),
        'dolznost' => $faker->sentence(1),
        'fiorukovoditel' => $faker->name,
        'adresorganiz' => $faker->address,
        'telephoneorganiz' => $faker->phoneNumber,
        'dohod' => $faker->randomNumber(6, true),
        'dopdohod' => $faker->randomNumber(6, true),
        'stazlet' => $faker->numberBetween(1, 40),
        'adsource' => $faker->randomElement($idAdsource),
        'obrasovanie' => $faker->randomElement($idsEducation),
        'pensioner' => $faker->numberBetween(0, 1),
        'postclient' => $faker->numberBetween(0, 1),
        'armia' => $faker->numberBetween(0, 1),
        'poruchitelstvo' => $faker->numberBetween(0, 1),
        'zarplatcard' => $faker->numberBetween(0, 1),
        'alco' => $faker->numberBetween(0, 1),
        'drugs' => $faker->numberBetween(0, 1),
        'stupid' => $faker->numberBetween(0, 1),
        'badspeak' => $faker->numberBetween(0, 1),
        'pressure' => $faker->numberBetween(0, 1),
        'dirty' => $faker->numberBetween(0, 1),
        'smell' => $faker->numberBetween(0, 1),
        'badbehaviour' => $faker->numberBetween(0, 1),
        'soldier' => $faker->numberBetween(0, 1),
        'other' => $faker->numberBetween(0, 1),
        'watch' => $faker->numberBetween(0, 1),
        'anothertelephone' => $faker->phoneNumber,
        'marital_type_id' => $faker->randomElement($idsMaritalType),
        'dohod_husband' => $faker->phoneNumber,
        'pension' => $faker->phoneNumber,
    ];
});
