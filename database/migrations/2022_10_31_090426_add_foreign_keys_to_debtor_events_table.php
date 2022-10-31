<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToDebtorEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('debtor_events', function (Blueprint $table) {
            $table->foreign(['debtor_id'], 'FK_debtor_events_debtors')->references(['id'])->on('debtors')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['user_id'], 'FK_debtor_events_users')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('debtor_events', function (Blueprint $table) {
            $table->dropForeign('FK_debtor_events_debtors');
            $table->dropForeign('FK_debtor_events_users');
        });
    }
}
