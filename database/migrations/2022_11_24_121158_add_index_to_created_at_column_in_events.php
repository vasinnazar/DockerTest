<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToCreatedAtColumnInEvents extends Migration
{
    public function up()
    {
        Schema::table('debtor_events', function (Blueprint $table) {
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::table('debtor_events', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
}
