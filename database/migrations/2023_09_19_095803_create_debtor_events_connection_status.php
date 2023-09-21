<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDebtorEventsConnectionStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debtor_events_connection_status', function (Blueprint $table) {
            $table->unsignedInteger('debtor_event_id');
            $table->unsignedInteger('connection_status_id');

            $table->foreign(['debtor_event_id'])->references(['id'])->on('debtor_events')->onDelete('cascade');
            $table->foreign(['connection_status_id'])->references(['id'])->on('connection_statuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debtor_events_connection_status');
    }
}
