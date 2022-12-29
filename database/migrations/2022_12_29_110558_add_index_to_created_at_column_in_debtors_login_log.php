<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToCreatedAtColumnInDebtorsLoginLog extends Migration
{
    public function up()
    {
        Schema::table('debtors_site_login_log', function (Blueprint $table) {
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::table('debtors_site_login_log', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
}
