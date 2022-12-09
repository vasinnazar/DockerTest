<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAmountOfAgreementFieldToDebtorEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtor_events', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->after('event_result_id')->nullable();
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
            $table->dropColumn('amount');
        });
    }
}
