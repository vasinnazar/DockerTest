<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('permission_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('permission_id')->index('permission_user_permission_id_foreign');
            $table->unsignedInteger('user_id')->index('permission_user_user_id_foreign');
            $table->dateTime('valid_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('permission_user');
    }
}
