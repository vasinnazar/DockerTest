<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPeacePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('peace_pays', function (Blueprint $table) {
            $table->foreign(['repayment_id'], 'FK_peace_pays_repayments')->references(['id'])->on('repayments')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('peace_pays', function (Blueprint $table) {
            $table->dropForeign('FK_peace_pays_repayments');
        });
    }
}
