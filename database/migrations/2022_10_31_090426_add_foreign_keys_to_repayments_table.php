<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('repayments', function (Blueprint $table) {
            $table->foreign(['loan_id'], 'FK_repayments_loans')->references(['id'])->on('loans')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['subdivision_id'], 'FK_repayments_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['repayment_type_id'], 'FK_repayments_repayment_types')->references(['id'])->on('repayment_types')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['user_id'], 'FK_repayments_users')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('repayments', function (Blueprint $table) {
            $table->dropForeign('FK_repayments_loans');
            $table->dropForeign('FK_repayments_subdivisions');
            $table->dropForeign('FK_repayments_repayment_types');
            $table->dropForeign('FK_repayments_users');
        });
    }
}
