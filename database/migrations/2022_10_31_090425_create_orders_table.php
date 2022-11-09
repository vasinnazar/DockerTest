<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('type')->index('FK_orders_ordertypes_idx');
            $table->string('number', 12);
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->unsignedInteger('user_id')->nullable()->index('FK_orders_users_idx');
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_orders_subdivisions_idx');
            $table->unsignedInteger('customer_id')->nullable()->index('FK_orders_customers_idx');
            $table->integer('money')->nullable()->comment('!!В КОПЕЙКАХ!!');
            $table->unsignedInteger('passport_id')->nullable()->index('FK_orders_passports_idx');
            $table->text('reason')->nullable();
            $table->unsignedInteger('repayment_id')->nullable()->index('FK_orders_repayments_idx');
            $table->integer('purpose')->nullable();
            $table->unsignedInteger('loan_id')->nullable()->index('FK_orders_loans_idx');
            $table->boolean('used')->default(true);
            $table->unsignedInteger('peace_pay_id')->nullable()->index('FK_orders_peacepays_idx');
            $table->dateTime('claimed_for_remove')->nullable();
            $table->text('comment')->nullable();
            $table->string('fio', 512)->nullable();
            $table->text('passport_data')->nullable();
            $table->boolean('sync')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('orders');
    }
}
