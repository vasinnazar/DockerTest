<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('permission_role', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('role_id')->nullable()->index('FK_role_permission_idx');
            $table->unsignedInteger('permission_id')->nullable()->index('FK_permission_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('permission_role');
    }
}
