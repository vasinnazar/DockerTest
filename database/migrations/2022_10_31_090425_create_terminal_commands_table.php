<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalCommandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('terminal_commands', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('PayPointID')->nullable()->index('FK_terminal_terminal_command_idx');
            $table->boolean('Sync')->nullable()->default(false);
            $table->string('ANSWER', 512)->nullable();
            $table->boolean('isExecuted')->nullable()->default(false);
            $table->boolean('Success')->nullable()->default(false);
            $table->dateTime('DataExec')->nullable();
            $table->string('name', 45);
            $table->string('params', 512)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['id'], 'id_UNIQUE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('terminal_commands');
    }
}
