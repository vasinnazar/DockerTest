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
            $table->string('customer_id_1c');
            $table->unsignedInteger('event_id')->nullable();
            $table->string('message');
            $table->boolean('status')->comment('Статус отправки email');
            $table->index('customer_id_1c')->on('customers')->references('id_1c');
            $table->foreign('event_id')->on('debtor_events')->references('id');
            $table->softDeletes();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debtor_event_email');
    }
}
