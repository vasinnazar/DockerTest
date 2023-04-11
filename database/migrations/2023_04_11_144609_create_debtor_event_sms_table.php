<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorEventSmsTable extends Migration
{
    public function up(): void
    {
        Schema::create('debtor_event_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('event_id');
            $table->integer('sms_id');
            $table->integer('customer_id');
            $table->foreign('event_id')->references('id')->on('debtor_event');
            $table->foreign('sms_id')->references('id')->on('debtor_sms_tpls');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debtor_event_sms');
    }
}
