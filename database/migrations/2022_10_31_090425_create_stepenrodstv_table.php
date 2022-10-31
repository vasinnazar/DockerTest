<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStepenrodstvTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('stepenrodstv', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name', 45)->nullable();

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
        Schema::connection('debtors')->dropIfExists('stepenrodstv');
    }
}
