<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDebtorsProcessedToMassRecurrentTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtors_mass_recurrents_tasks', function (Blueprint $table) {
            $table->integer('debtors_processed')->after('debtors_count')->default(0);
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
            $table->dropColumn('debtors_processed');
        });
    }
}
