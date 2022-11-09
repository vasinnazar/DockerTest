<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemoveRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('remove_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('doc_id')->nullable();
            $table->integer('doc_type')->nullable()->comment('тип документа по MySoap');
            $table->text('comment')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedInteger('requester_id')->nullable()->index('FK_removes_requesters_idx')->comment('ид пользователя сделавшего запрос');
            $table->integer('status')->nullable()->comment('статус заявки');
            $table->unsignedInteger('user_id')->nullable()->index('FK_removes_users_idx')->comment('ид админа выполнившего запрос');
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
        Schema::connection('debtors')->dropIfExists('remove_requests');
    }
}
