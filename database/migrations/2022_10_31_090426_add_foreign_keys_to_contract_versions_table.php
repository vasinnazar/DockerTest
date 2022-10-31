<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToContractVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('contract_versions', function (Blueprint $table) {
            $table->foreign(['contract_form_id'], 'FK_contract_form_id')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['new_contract_form_id'], 'FK_new_contract_form_id')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('contract_versions', function (Blueprint $table) {
            $table->dropForeign('FK_contract_form_id');
            $table->dropForeign('FK_new_contract_form_id');
        });
    }
}
