<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToOrderTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('order_types', function (Blueprint $table) {
            $table->foreign(['contract_form_id'], 'FK_ordertypes_contractsforms')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('order_types', function (Blueprint $table) {
            $table->dropForeign('FK_ordertypes_contractsforms');
        });
    }
}
