<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('order_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('text_id', 45)->nullable()->unique('text_id_UNIQUE')->comment('уникальный текстовый идентификатор для поиска');
            $table->string('name', 256)->nullable()->comment('название');
            $table->string('invoice', 45)->nullable()->comment('номер счёта');
            $table->boolean('plus')->nullable()->default(false);
            $table->integer('contract_form_id')->nullable()->index('FK_ordertypes_contractsforms_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('order_types');
    }
}
