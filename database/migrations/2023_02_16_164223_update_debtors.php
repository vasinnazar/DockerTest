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
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->index(['deleted_at', 'file_id']);
            $table->index(['deleted_at', 'in_process']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('debtor_sync_sql');
    }
}
