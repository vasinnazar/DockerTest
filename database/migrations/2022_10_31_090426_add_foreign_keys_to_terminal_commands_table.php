<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToTerminalCommandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('terminal_commands', function (Blueprint $table) {
            $table->foreign(['PayPointID'], 'FK_terminal_terminal_command')->references(['id'])->on('terminals')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('terminal_commands', function (Blueprint $table) {
            $table->dropForeign('FK_terminal_terminal_command');
        });
    }
}
