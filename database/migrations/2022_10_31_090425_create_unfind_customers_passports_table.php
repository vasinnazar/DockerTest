<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnfindCustomersPassportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('unfind_customers_passports', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->text('debtor_id_1c')->nullable();
            $table->text('customer_id_1c')->nullable();
            $table->string('series', 4)->nullable();
            $table->string('number', 6)->nullable();
            $table->text('fio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('unfind_customers_passports');
    }
}
