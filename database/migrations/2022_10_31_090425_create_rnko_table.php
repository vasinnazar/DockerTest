<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRnkoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('rnko', function (Blueprint $table) {
            $table->increments('id');
            $table->string('card_number', 45)->nullable();
            $table->string('passport_series', 4)->nullable();
            $table->string('passport_number', 6)->nullable();
            $table->string('fio', 512)->nullable();
            $table->integer('status')->nullable()->default(0);
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('subdivision_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('comment', 512)->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('claim_date')->nullable();
            $table->integer('info')->nullable()->default(0);
            $table->integer('prev_user_id')->nullable();
            $table->string('prev_comment', 512)->nullable();
            $table->integer('prev_status')->nullable();
            $table->integer('check_user_id')->nullable();
            $table->dateTime('start_check')->nullable();
            $table->dateTime('end_check')->nullable();
            $table->integer('check_status')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('rnko');
    }
}
