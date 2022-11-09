<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPermissionUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('permission_user', function (Blueprint $table) {
            $table->foreign(['permission_id'])->references(['id'])->on('permissions');
            $table->foreign(['user_id'])->references(['id'])->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('permission_user', function (Blueprint $table) {
            $table->dropForeign('permission_user_permission_id_foreign');
            $table->dropForeign('permission_user_user_id_foreign');
        });
    }
}
