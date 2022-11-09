<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsLossCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_loss_calls', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('debtor_id_1c', 9)->nullable()->index('IDX_debtor_id_1c');
            $table->string('customer_telephone', 11)->nullable();
            $table->integer('responsible_user_id')->nullable()->index('IDX_responsible_user_id');
            $table->string('internal_phone', 6)->nullable();
            $table->text('text')->nullable();
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
        Schema::connection('debtors')->dropIfExists('debtors_loss_calls');
    }
}
