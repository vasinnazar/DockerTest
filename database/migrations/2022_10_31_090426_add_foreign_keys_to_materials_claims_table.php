<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToMaterialsClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('materials_claims', function (Blueprint $table) {
            $table->foreign(['subdivision_id'], 'FK_matclaims_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['user_id'], 'FK_matclaims_users')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('materials_claims', function (Blueprint $table) {
            $table->dropForeign('FK_matclaims_subdivisions');
            $table->dropForeign('FK_matclaims_users');
        });
    }
}
