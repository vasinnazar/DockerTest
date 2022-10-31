<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoantypesConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('loantypes_conditions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('loantype_id')->index('FK_loantype_id_idx');
            $table->unsignedInteger('condition_id')->index('FK_condition_id_idx');

            $table->unique(['id'], 'id_UNIQUE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('loantypes_conditions');
    }
}
