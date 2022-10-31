<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToNpfFondsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('npf_fonds', function (Blueprint $table) {
            $table->foreign(['anketa_id'], 'FK_npf_fonds_anketa')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['claim_from_pfr_id'], 'FK_npf_fonds_claim_pfr')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['pd_agreement_id'], 'FK_npf_fonds_pd')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['claim_from_npf_id'], 'FK_npf_fonds_claim_npf')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['contract_form_id'], 'FK_npf_fonds_contracts_forms')->references(['id'])->on('contracts_forms')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('npf_fonds', function (Blueprint $table) {
            $table->dropForeign('FK_npf_fonds_anketa');
            $table->dropForeign('FK_npf_fonds_claim_pfr');
            $table->dropForeign('FK_npf_fonds_pd');
            $table->dropForeign('FK_npf_fonds_claim_npf');
            $table->dropForeign('FK_npf_fonds_contracts_forms');
        });
    }
}
