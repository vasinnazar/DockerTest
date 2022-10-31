<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToDailyCashReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('daily_cash_reports', function (Blueprint $table) {
            $table->foreign(['subdivision_id'], 'FK_reports_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['user_id'], 'FK_reports_users')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('daily_cash_reports', function (Blueprint $table) {
            $table->dropForeign('FK_reports_subdivisions');
            $table->dropForeign('FK_reports_users');
        });
    }
}
