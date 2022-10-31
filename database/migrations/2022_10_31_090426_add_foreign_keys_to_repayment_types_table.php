<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToRepaymentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('repayment_types', function (Blueprint $table) {
            $table->foreign(['contract_form_id'], 'FK_rtypes_contrforms')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('repayment_types', function (Blueprint $table) {
            $table->dropForeign('FK_rtypes_contrforms');
        });
    }
}
