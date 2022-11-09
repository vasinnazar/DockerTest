<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsTmpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_tmp', function (Blueprint $table) {
            $table->increments('id');
            $table->string('customer_id_1c', 45)->nullable()->index('IDX_customer_id_1c')->comment('Номер контрагента');
            $table->string('loan_id_1c', 45)->nullable()->index('IDX_loan_id_1c');
            $table->boolean('is_debtor')->nullable()->default(false);
            $table->integer('od')->nullable()->default(0);
            $table->integer('pc')->nullable()->default(0);
            $table->integer('exp_pc')->nullable()->default(0);
            $table->integer('fine')->nullable()->default(0);
            $table->integer('tax')->nullable()->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('last_doc_id_1c', 45)->nullable();
            $table->string('base', 45)->nullable();
            $table->string('responsible_user_id_1c', 45)->nullable()->index('IDX_responsible_user_id_1c');
            $table->dateTime('fixation_date')->nullable();
            $table->dateTime('refresh_date')->nullable();
            $table->integer('qty_delays')->nullable()->default(0);
            $table->integer('sum_indebt')->nullable()->default(0);
            $table->integer('debt_group_id')->nullable();
            $table->string('debtor_id_1c', 9)->nullable()->unique('debtor_id_1c_UNIQUE');
            $table->unsignedInteger('last_user_id')->nullable();
            $table->string('passport_series', 4)->nullable();
            $table->string('passport_number', 6)->nullable();
            $table->string('str_podr', 45)->nullable();
            $table->unsignedInteger('uploaded')->default(0);
            $table->integer('decommissioned')->nullable();
            $table->integer('non_interaction')->default(0);
            $table->integer('non_interaction_nf')->default(0);
            $table->integer('by_agent')->default(0);
            $table->integer('recall_personal_data')->default(0);
            $table->dateTime('recommend_created_at')->nullable();
            $table->text('recommend_text')->nullable();
            $table->integer('recommend_completed')->default(0);
            $table->integer('recommend_user_id')->nullable();
            $table->dateTime('date_restruct_agreement')->nullable();

            $table->index(['customer_id_1c'], 'IDX_debtors_customer_id_1c');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtors_tmp');
    }
}
