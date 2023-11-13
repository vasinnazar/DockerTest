<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnDebtorIdToDebtorEventSmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtor_event_sms', function (Blueprint $table) {
            $table->unsignedInteger('debtor_id')->nullable()->after('customer_id_1c');
            $table->foreign('debtor_id')->on('debtors')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debtor_event_sms', function (Blueprint $table) {
            $table->dropForeign('debtor_event_sms_debtor_id_foreign');
            $table->dropColumn('debtor_id');
        });
    }
}