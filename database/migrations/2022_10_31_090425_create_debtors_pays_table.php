<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_pays', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('loan_id_1c', 45)->nullable();
            $table->integer('repayment_type_id')->nullable();
            $table->integer('sum')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('notified')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtors_pays');
    }
}
