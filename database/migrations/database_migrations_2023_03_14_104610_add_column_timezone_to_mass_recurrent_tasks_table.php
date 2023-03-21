<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTimezoneToMassRecurrentTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtors_mass_recurrents_tasks', function (Blueprint $table) {
            $table->string('timezone')->after('str_podr')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debtors_mass_recurrents_tasks', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
}
