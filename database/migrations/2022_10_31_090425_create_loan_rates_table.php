<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('loan_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->decimal('pc', 5)->nullable();
            $table->decimal('exp_pc', 5)->nullable();
            $table->decimal('fine', 5)->nullable();
            $table->dateTime('start_date')->nullable();
            $table->string('loantype_id_1c', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('loan_rates');
    }
}
