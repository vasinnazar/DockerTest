<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('repayments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('loan_id')->nullable()->index('FK_repayments_loans_idx');
            $table->unsignedInteger('repayment_type_id')->nullable()->index('FK_repayments_repayment_types_idx');
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->integer('time')->nullable()->default(0);
            $table->integer('fine')->nullable()->default(0)->comment('пеня в копейках, оплаченная при создании договора');
            $table->integer('exp_pc')->nullable()->default(0)->comment('сумма проср. процентов, оплаченных при заключении договора');
            $table->integer('pc')->nullable()->default(0)->comment('сумма процентов, оплаченных при заключении договора');
            $table->integer('req_money')->nullable()->default(0);
            $table->integer('paid_money')->nullable()->default(0)->comment('сумма взноса');
            $table->integer('discount')->nullable()->default(0);
            $table->integer('od')->nullable()->default(0)->comment('сумма основного долга, оплаченного при заключении договора');
            $table->string('id_1c', 45)->nullable()->index('id_1c');
            $table->integer('was_pc')->nullable()->default(0);
            $table->integer('was_exp_pc')->nullable()->default(0);
            $table->integer('was_od')->nullable()->default(0);
            $table->integer('was_fine')->nullable()->default(0);
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_repayments_subdivisions_idx');
            $table->unsignedInteger('user_id')->nullable()->index('FK_repayments_users_idx');
            $table->text('comment')->nullable();
            $table->integer('tax')->nullable()->default(0);
            $table->dateTime('last_payday')->nullable();
            $table->dateTime('claimed_for_remove')->nullable();
            $table->integer('was_tax')->nullable()->default(0);
            $table->text('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('repayments');
    }
}
