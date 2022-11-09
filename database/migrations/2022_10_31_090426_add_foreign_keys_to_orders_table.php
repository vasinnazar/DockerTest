<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('orders', function (Blueprint $table) {
            $table->foreign(['customer_id'], 'FK_orders_customers')->references(['id'])->on('customers')->onUpdate('CASCADE')->onDelete('NO ACTION');
            $table->foreign(['passport_id'], 'FK_orders_passports')->references(['id'])->on('passports')->onUpdate('CASCADE')->onDelete('NO ACTION');
            $table->foreign(['repayment_id'], 'FK_orders_repayments')->references(['id'])->on('repayments')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['user_id'], 'FK_orders_users')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('NO ACTION');
            $table->foreign(['type'], 'FK_orders_ordertypes')->references(['id'])->on('order_types')->onUpdate('CASCADE');
            $table->foreign(['peace_pay_id'], 'FK_orders_peacepays')->references(['id'])->on('peace_pays')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['subdivision_id'], 'FK_orders_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('orders', function (Blueprint $table) {
            $table->dropForeign('FK_orders_customers');
            $table->dropForeign('FK_orders_passports');
            $table->dropForeign('FK_orders_repayments');
            $table->dropForeign('FK_orders_users');
            $table->dropForeign('FK_orders_ordertypes');
            $table->dropForeign('FK_orders_peacepays');
            $table->dropForeign('FK_orders_subdivisions');
        });
    }
}
