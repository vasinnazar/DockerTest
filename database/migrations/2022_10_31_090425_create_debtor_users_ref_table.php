<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorUsersRefTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtor_users_ref', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('master_user_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtor_users_ref');
    }
}
