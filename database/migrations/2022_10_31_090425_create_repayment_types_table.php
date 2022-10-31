<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRepaymentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('repayment_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256)->nullable();
            $table->integer('od_money')->nullable()->comment('сумма основной задолженности');
            $table->integer('percents_money')->nullable()->comment('сумма процентов в копейках');
            $table->integer('exp_percents_money')->nullable()->comment('сумма просроченных процентов в копейках');
            $table->integer('fine_money')->nullable()->comment('сумма пени в копейках');
            $table->integer('freeze_days')->nullable()->comment('количество дней заморозки');
            $table->text('condition')->nullable()->comment('условие');
            $table->decimal('exp_percent', 5)->nullable()->comment('проценты просрочки');
            $table->decimal('fine_percent', 5)->nullable()->comment('проценты пени');
            $table->integer('contract_form_id')->nullable()->index('FK_rtypes_contrforms_idx')->comment('форма договора');
            $table->string('text_id', 45)->nullable();
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->boolean('add_after_freeze')->nullable()->default(false);
            $table->boolean('mandatory_percents')->nullable()->default(true)->comment('для заключения договора необходимо обязательно оплатить проценты');
            $table->text('payments_order')->nullable();
            $table->integer('default_time')->nullable()->default(0);
            $table->decimal('percent', 5)->nullable();
            $table->integer('perm_contract_form_id')->nullable();
            $table->decimal('pc_after_exp', 5)->nullable()->comment('основной процент после просрочки договора');
            $table->integer('card_contract_form_id')->nullable();
            $table->integer('card_perm_contract_form_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('repayment_types');
    }
}
