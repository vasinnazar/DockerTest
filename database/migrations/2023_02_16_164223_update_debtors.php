<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDebtors extends Migration
{
    public function up()
    {
        Schema::create('update_debtors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('sql_command');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('update_debtors');
    }
}
