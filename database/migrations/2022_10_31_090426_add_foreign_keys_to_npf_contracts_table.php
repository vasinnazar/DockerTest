<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToNpfContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('npf_contracts', function (Blueprint $table) {
            $table->foreign(['npf_fond_id'], 'FK_npf_contracts_npf_fonds')->references(['id'])->on('npf_fonds')->onUpdate('CASCADE');
            $table->foreign(['passport_id'], 'FK_npf_contracts_passports')->references(['id'])->on('passports')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('npf_contracts', function (Blueprint $table) {
            $table->dropForeign('FK_npf_contracts_npf_fonds');
            $table->dropForeign('FK_npf_contracts_passports');
        });
    }
}
