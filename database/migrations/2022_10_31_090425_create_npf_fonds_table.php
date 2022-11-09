<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNpfFondsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('npf_fonds', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256);
            $table->integer('contract_form_id')->nullable()->index('FK_npf_fonds_contracts_forms_idx');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->integer('claim_from_npf_id')->nullable()->index('FK_npf_fonds_claim_npf_idx');
            $table->integer('claim_from_pfr_id')->nullable()->index('FK_npf_fonds_claim_pfr_idx');
            $table->integer('pd_agreement_id')->nullable()->index('FK_npf_fonds_pd_idx');
            $table->integer('anketa_id')->nullable()->index('FK_npf_fonds_anketa_idx');
            $table->string('id_1c', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('npf_fonds');
    }
}
