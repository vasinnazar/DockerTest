<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->text('text')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->unsignedInteger('user_id')->nullable()->index('FK_messages_users_idx');
            $table->string('caption', 512)->nullable();
            $table->string('type', 18)->nullable()->index('IDX_type');
            $table->unsignedInteger('recepient_id')->nullable()->index('IDX_recepient_id');
            $table->string('message_type', 45)->nullable()->index('IDX_message_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('messages');
    }
}
