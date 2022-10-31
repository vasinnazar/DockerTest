<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPassportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('passports', function (Blueprint $table) {
            $table->foreign(['customer_id'], 'FK_passport_customer')->references(['id'])->on('customers')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('passports', function (Blueprint $table) {
            $table->dropForeign('FK_passport_customer');
        });
    }
}
