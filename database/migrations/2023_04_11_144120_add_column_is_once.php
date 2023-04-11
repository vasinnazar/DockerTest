<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnIsOnce extends Migration
{
    public function up(): void
    {
        Schema::table('debtor_sms_tpls', function (Blueprint $table) {
            $table->boolean('is_once')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('debtors_sms_tpl', function (Blueprint $table) {
            $table->dropColumn('is_once');
        });
    }
}
