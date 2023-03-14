<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourtOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('court_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('debtor_id')->unsigned();
            $table->foreign('debtor_id')->references('id')->on('debtors');
            $table->integer('is_printed');
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    public function down()
    {
        Schema::dropIfExists('court_orders');
    }
}
