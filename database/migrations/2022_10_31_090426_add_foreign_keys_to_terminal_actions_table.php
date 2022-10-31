<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToTerminalActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('terminal_actions', function (Blueprint $table) {
            $table->foreign(['ClientID'], 'FK_action_customer')->references(['id'])->on('customers')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('terminal_actions', function (Blueprint $table) {
            $table->dropForeign('FK_action_customer');
        });
    }
}
