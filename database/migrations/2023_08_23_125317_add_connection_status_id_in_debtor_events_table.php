<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddConnectionStatusIdInDebtorEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtor_events', function (Blueprint $table) {
            $table->unsignedBigInteger('connection_status_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debtor_events', function (Blueprint $table) {
            $table->dropColumn('connection_status_id');
        });
    }
}
