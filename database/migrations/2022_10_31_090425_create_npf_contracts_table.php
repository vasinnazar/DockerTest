<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNpfContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('npf_contracts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('passport_id')->index('FK_npf_contracts_passports_idx');
            $table->unsignedInteger('npf_fond_id')->index('FK_npf_contracts_npf_fonds_idx');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('subdivision_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('claimed_for_remove')->nullable();
            $table->string('id_1c', 45)->nullable();
            $table->string('old_fio', 512)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('npf_contracts');
    }
}
