<?php

use App\Debtor;
use App\DebtorBlockProlongation;
use Faker\Generator as Faker;

$factory->define(DebtorBlockProlongation::class, function (Faker $faker)
{
    $debtorIds = Debtor::all()->pluck('id')->toArray();
    $debtorId = $faker->randomElement($debtorIds);
    return [
        'debtor_id' => $debtorId,
        'loan_id_1c' => Debtor::find($debtorId)->loan_id_1c,
        'block_till_date' => $faker->date('Y-m-d H:m:s'),
    ];
});
