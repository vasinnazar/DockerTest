<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('terminals', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('HardwareID', 256)->nullable();
            $table->string('pay_point_id', 45)->nullable();
            $table->string('description', 256)->nullable();
            $table->string('password', 256)->nullable();
            $table->boolean('is_locked')->nullable()->default(false);
            $table->integer('bill_count')->nullable();
            $table->integer('bill_cash')->nullable();
            $table->integer('dispenser_count')->nullable();
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->unsignedInteger('user_id')->nullable()->index('FK_terminals_users_idx');
            $table->string('address', 256)->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->string('DispenserStatus', 3)->nullable();
            $table->string('stWebcamStatus', 45)->nullable();
            $table->string('stValidatorStatus', 45)->nullable();
            $table->string('stPrinterStatus', 45)->nullable();
            $table->string('stScannerStatus', 45)->nullable();
            $table->dateTime('last_disconnect_sms')->nullable();
            $table->dateTime('last_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('terminals');
    }
}
