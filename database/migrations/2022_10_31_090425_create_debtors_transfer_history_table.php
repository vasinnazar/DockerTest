<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsTransferHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_transfer_history', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('operation_user_id')->nullable()->index('idx_operation_user_id');
            $table->integer('row')->nullable();
            $table->string('debtor_id_1c', 9)->nullable()->index('idx_debtor_id_1c');
            $table->dateTime('transfer_time')->nullable();
            $table->string('responsible_user_id_1c_before', 45)->nullable();
            $table->string('responsible_user_id_1c_after', 45)->nullable();
            $table->string('base_before', 45)->nullable();
            $table->string('base_after', 45)->nullable();
            $table->string('str_podr_before', 45)->nullable();
            $table->string('str_podr_after', 45)->nullable();
            $table->dateTime('fixation_date_before')->nullable();
            $table->dateTime('fixation_date_after')->nullable();
            $table->integer('auto_transfer')->nullable()->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtors_transfer_history');
    }
}
