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
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
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
