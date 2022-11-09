<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsOtherPhonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_other_phones', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('debtor_id_1c', 9)->nullable()->index('IDX_debtor_id_1c');
            $table->string('phone', 11)->nullable()->index('IDX_phone');
            $table->integer('type')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtors_other_phones');
    }
}
