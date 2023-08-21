<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeleteCascadeEventEmail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtor_event_email', function (Blueprint $table) {
            $table->dropForeign('debtor_event_email_event_id_foreign');
            $table->foreign('event_id')->on('debtor_events')->references('id')->onDelete('cascade')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debtor_event_email', function (Blueprint $table) {
            $table->dropForeign('debtor_event_email_event_id_foreign');
            $table->foreign('event_id')->on('debtor_events')->references('id');
        });
    }
}
