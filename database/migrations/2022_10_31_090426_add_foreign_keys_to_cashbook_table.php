<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToCashbookTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('cashbook', function (Blueprint $table) {
            $table->foreign(['loan_id'], 'FK_cashbook_loans')->references(['id'])->on('loans')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['order_id'], 'FK_cashbook_orders')->references(['id'])->on('orders')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('cashbook', function (Blueprint $table) {
            $table->dropForeign('FK_cashbook_loans');
            $table->dropForeign('FK_cashbook_orders');
        });
    }
}
