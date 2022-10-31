<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('users', function (Blueprint $table) {
            $table->foreign(['subdivision_id'], 'FK_users_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('users', function (Blueprint $table) {
            $table->dropForeign('FK_users_subdivisions');
        });
    }
}
