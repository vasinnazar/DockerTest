<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtor_events', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('date')->nullable()->index('idx_date');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('customer_id_1c', 45)->nullable()->index('idx_customer_id_1c');
            $table->string('loan_id_1c', 45)->nullable();
            $table->integer('event_type_id')->nullable();
            $table->integer('debt_group_id')->nullable();
            $table->integer('overdue_reason_id')->nullable();
            $table->integer('event_result_id')->nullable();
            $table->text('report')->nullable();
            $table->unsignedInteger('debtor_id')->nullable()->index('FK_debtor_events_debtors_idx');
            $table->unsignedInteger('user_id')->nullable()->index('FK_debtor_events_users_idx');
            $table->integer('completed')->default(0);
            $table->string('id_1c', 10)->nullable()->unique('id_1c_UNIQUE');
            $table->unsignedInteger('last_user_id')->nullable();
            $table->string('debtor_id_1c', 9)->nullable()->index('idv_debtor_id_1c');
            $table->string('user_id_1c', 70)->nullable()->index('idx_user_id_1c');
            $table->dateTime('refresh_date')->nullable();

            $table->index(['debtor_id'], 'idx_debtor_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtor_events');
    }
}
