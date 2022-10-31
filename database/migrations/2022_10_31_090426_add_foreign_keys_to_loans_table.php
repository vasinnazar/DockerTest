<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('loans', function (Blueprint $table) {
            $table->foreign(['order_id'], 'FK_loans_orders')->references(['id'])->on('orders')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['subdivision_id'], 'FK_loans_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('NO ACTION');
            $table->foreign(['promocode_id'], 'FK_loans_promocodes')->references(['id'])->on('promocodes')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['user_id'], 'FK_loans_users')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('loans', function (Blueprint $table) {
            $table->dropForeign('FK_loans_orders');
            $table->dropForeign('FK_loans_subdivisions');
            $table->dropForeign('FK_loans_promocodes');
            $table->dropForeign('FK_loans_users');
        });
    }
}
