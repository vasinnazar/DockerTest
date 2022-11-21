<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnIsExcluded extends Migration
{
    public function up()
    {
        Schema::table('debtor_sms_tpls', function (Blueprint $table) {
            $table->integer('is_excluded')->nullable();
        });
    }

    public function down()
    {
        Schema::table('debtor_sms_tpls', function (Blueprint $table) {
            $table->dropColumn('is_excluded');
        });
    }
}
