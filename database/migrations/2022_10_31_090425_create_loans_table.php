<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('loans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('money')->nullable();
            $table->integer('time')->nullable();
            $table->unsignedInteger('claim_id')->index('IDX_claim_id');
            $table->unsignedInteger('loantype_id');
            $table->unsignedInteger('card_id')->nullable()->index('FK_loans_cards_idx');
            $table->boolean('closed')->default(false);
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->unsignedInteger('order_id')->nullable()->index('FK_loans_orders_idx');
            $table->softDeletes();
            $table->unsignedInteger('subdivision_id')->index('FK_loans_subdivisions_idx');
            $table->string('id_1c', 45)->nullable()->index('id_1c');
            $table->boolean('enrolled')->default(false);
            $table->boolean('in_cash')->nullable();
            $table->unsignedInteger('user_id')->index('FK_loans_users_idx');
            $table->unsignedInteger('promocode_id')->nullable()->index('FK_loans_promocodes_idx');
            $table->integer('fine')->default(0);
            $table->dateTime('last_payday');
            $table->decimal('special_percent', 5)->nullable()->comment('спец процент для акции гарантия низкой ставки');
            $table->dateTime('claimed_for_remove')->nullable();
            $table->boolean('on_balance')->default(false)->comment('перечислено ли на баланс пользователя');
            $table->boolean('uki')->nullable()->default(false);
            $table->boolean('cc_call')->nullable()->default(false);
            $table->integer('tranche_number')->nullable();
            $table->string('first_loan_id_1c', 45)->nullable();
            $table->dateTime('first_loan_date')->nullable();
            $table->string('true_id_1c', 45)->nullable();

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
        Schema::connection('debtors')->dropIfExists('loans');
    }
}
