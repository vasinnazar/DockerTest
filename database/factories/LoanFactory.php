<?php

use App\Claim;
use App\Loan;
use App\LoanType;
use App\Subdivision;
use App\User;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Loan::class, function (Faker $faker) {
    $idsUser = User::all()->pluck('id')->toArray();
    $idsClaim = Claim::all()->pluck('id')->toArray();
    $idsSubdivision = Subdivision::all()->pluck('id')->toArray();
    $idsLoantype = LoanType::all()->pluck('id')->toArray();
    return [
        'claim_id' => $faker->randomElement($idsClaim),
        'loantype_id' => $faker->randomElement($idsLoantype),
        'subdivision_id' => $faker->randomElement($idsSubdivision),
        'user_id' => $faker->randomElement($idsUser),
        'last_payday' =>$faker->dateTimeBetween('-10 days'),
        'id_1c' =>$faker->swiftBicNumber,
    ];
});
