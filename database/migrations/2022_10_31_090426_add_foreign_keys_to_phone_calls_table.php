<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPhoneCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('phone_calls', function (Blueprint $table) {
            $table->foreign(['subdivision_id'], 'FK_phone_calls_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['user_id'], 'FK_phone_calls_users')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('phone_calls', function (Blueprint $table) {
            $table->dropForeign('FK_phone_calls_subdivisions');
            $table->dropForeign('FK_phone_calls_users');
        });
    }
}
