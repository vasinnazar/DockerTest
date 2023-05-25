<?php

use App\DebtorEvent;
use Carbon\Carbon;
use Faker\Generator as Faker;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(DebtorEvent::class, function (Faker $faker) {
    $ids1cCustomer = \App\Customer::all()->pluck('id_1c')->toArray();
    $idsUser = \App\User::all()->pluck('id')->toArray();
    $idsDebtor = \App\Debtor::all()->pluck('id')->toArray();
    return [
        'date'=> $faker->dateTimeBetween('-15 days'),
        'created_at'=> $faker->dateTimeBetween('-30 days'),
        'customer_id_1c' => $faker->randomElement($ids1cCustomer),
        'event_type_id' => $faker->numberBetween(0,25),
        'debt_group_id' => $faker->numberBetween(0,69),
        'event_result_id' => $faker->randomElement([0, 1, 6, 9, 10, 11, 12, 13, 22, 24, 27, 29]),
        'report' => $faker->text,
        'debtor_id' => $faker->randomElement($idsDebtor),
        'user_id' => $faker->randomElement($idsUser),
        'completed' => 0,
    ];
});
$factory->defineAs(DebtorEvent::class,'event_limit',function (Faker $faker){

    return [
        'event_type_id' => $faker->randomElement([
                DebtorEvent::SMS_EVENT,
                DebtorEvent::AUTOINFORMER_OMICRON_EVENT,
                DebtorEvent::WHATSAPP_EVENT,
                DebtorEvent::EMAIL_EVENT
            ]),
        'report' => $faker->text,
        'debt_group_id' => $faker->numberBetween(0,69),
        'event_result_id' => $faker->randomElement([0, 1, 6, 9, 10, 11, 12, 13, 22, 24, 27, 29]),
    ];
});
