<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsTransferCalendarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_transfer_calendar', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('transfer_date')->nullable();
            $table->string('user_id_1c', 45)->nullable();
            $table->integer('sort')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtors_transfer_calendar');
    }
}
