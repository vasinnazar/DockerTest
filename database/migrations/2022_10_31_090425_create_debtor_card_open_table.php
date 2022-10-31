<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorCardOpenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtor_card_open', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('debtor_id')->nullable()->index('idx_debtor_id');
            $table->dateTime('date_opened')->nullable()->index('idx_date_opened');
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
        Schema::connection('debtors')->dropIfExists('debtor_card_open');
    }
}
