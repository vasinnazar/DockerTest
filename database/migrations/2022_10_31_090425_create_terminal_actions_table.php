<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('terminal_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ActionID', 256)->nullable();
            $table->unsignedInteger('CreditID')->nullable()->index('FK_action_claim_idx');
            $table->unsignedInteger('ClientID')->nullable()->index('FK_action_customer_idx');
            $table->dateTime('DateIns')->nullable();
            $table->integer('ActionType')->nullable();
            $table->string('ActionText', 256)->nullable();
            $table->integer('Amount')->nullable();
            $table->integer('ExtInt')->nullable();
            $table->integer('Status')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('PayPointID')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('terminal_actions');
    }
}
