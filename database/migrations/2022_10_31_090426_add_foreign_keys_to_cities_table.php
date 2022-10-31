<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('cities', function (Blueprint $table) {
            $table->foreign(['region_id'], 'FK_regions_cities')->references(['id'])->on('regions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('cities', function (Blueprint $table) {
            $table->dropForeign('FK_regions_cities');
        });
    }
}
