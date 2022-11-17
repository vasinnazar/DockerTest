<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnIsExcluded extends Migration
{
    public function up()
    {
        Schema::table('debtors_sms_tpls', function (Blueprint $table) {

        });
    }

    public function down()
    {
        Schema::table('debtors_sms_tpls', function (Blueprint $table) {
            //
        });
    }
}
