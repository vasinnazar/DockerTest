<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_payments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('date')->nullable();
            $table->string('responsible_user_id_1c', 45)->nullable();
            $table->integer('money')->nullable();
            $table->string('customer_id_1c', 45)->nullable();
            $table->text('loan_data')->nullable();
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
        Schema::connection('debtors')->dropIfExists('debtors_payments');
    }
}
