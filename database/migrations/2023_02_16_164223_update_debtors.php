<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDebtors extends Migration
{
    public function up()
    {
        Schema::create('debtor_sync_sql', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('sql_command');
            $table->integer('file_id');
            $table->boolean('in_process')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->index(['deleted_at', 'file_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('debtor_sync_sql');
    }
}
