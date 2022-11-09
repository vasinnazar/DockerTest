<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('claims', function (Blueprint $table) {
            $table->foreign(['customer_id'], 'FK_claim_customer')->references(['id'])->on('customers')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['promocode_id'], 'FK_claims_promocodes')->references(['id'])->on('promocodes')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['user_id'], 'FK_claim_user')->references(['id'])->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['subdivision_id'], 'FK_claims_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('claims', function (Blueprint $table) {
            $table->dropForeign('FK_claim_customer');
            $table->dropForeign('FK_claims_promocodes');
            $table->dropForeign('FK_claim_user');
            $table->dropForeign('FK_claims_subdivisions');
        });
    }
}
