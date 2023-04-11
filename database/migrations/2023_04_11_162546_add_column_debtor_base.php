<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnDebtorBase extends Migration
{
    public function up(): void
    {
        Schema::table('debtor_events', function (Blueprint $table) {
            $table->string('debtor_base')->after('debtor_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('debtor_events', function (Blueprint $table) {
            $table->dropColumn('debtor_base');
        });
    }
}
