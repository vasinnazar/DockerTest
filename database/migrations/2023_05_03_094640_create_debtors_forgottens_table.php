<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsForgottensTable extends Migration
{
    public function up(): void
    {
        Schema::create('debtors_forgottens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('debtor_id');
            $table->dateTime('forgotten_date');
            $table->foreign('debtor_id')->references('id')->on('debtors');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debtors_forgottens');
    }
}
