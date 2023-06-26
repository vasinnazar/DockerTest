<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDebtorEventEmail extends Migration
{
    public function up(): void
    {
        Schema::create('debtor_event_email', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('debtor_id');
            $table->string('message');
            $table->boolean('status')->comment('Статус отправки email');
            $table->date('date_sent');
            $table->foreign('debtor_id')->on('debtors')->references('id');
            $table->softDeletes();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debtor_event_email');
    }
}
