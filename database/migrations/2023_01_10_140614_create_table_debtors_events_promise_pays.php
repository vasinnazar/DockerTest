<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDebtorsEventsPromisePays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debtors_events_promise_pays', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('debtor_id');
            $table->integer('event_id');
            $table->integer('amount')->default(0);
            $table->dateTime('promise_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('debtors_events_promise_pays');
    }
}
