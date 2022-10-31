<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorSmsTplsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtor_sms_tpls', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('recovery_type', 8)->nullable();
            $table->text('text_tpl')->nullable();
            $table->integer('sort')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtor_sms_tpls');
    }
}
