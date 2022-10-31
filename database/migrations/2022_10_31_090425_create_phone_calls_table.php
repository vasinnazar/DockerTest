<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhoneCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('phone_calls', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('last_date_call')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('call_result')->nullable();
            $table->text('comment')->nullable();
            $table->string('fio', 512)->nullable();
            $table->string('telephone', 15)->nullable();
            $table->tinyInteger('phone_call_type')->nullable();
            $table->string('customer_id_1c', 45)->nullable();
            $table->unsignedInteger('user_id')->nullable()->index('FK_phone_calls_users_idx');
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_phone_calls_subdivisions_idx');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('phone_calls');
    }
}
