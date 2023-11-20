<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsQtyDelaysToDebtorsMassRecurrentsTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtors_mass_recurrents_tasks', function (Blueprint $table) {
            $table->Integer('qty_delays_from')->default(0)->after('str_podr');
            $table->Integer('qty_delays_to')->default(0)->after('qty_delays_from');
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
            $table->dropColumn('qty_delays_to');
            $table->dropColumn('qty_delays_from');
        });
    }
}
