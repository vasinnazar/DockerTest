<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPermissionRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('permission_role', function (Blueprint $table) {
            $table->foreign(['permission_id'], 'FK_permission_role')->references(['id'])->on('permissions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['role_id'], 'FK_role_permission')->references(['id'])->on('roles')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('permission_role', function (Blueprint $table) {
            $table->dropForeign('FK_permission_role');
            $table->dropForeign('FK_role_permission');
        });
    }
}
