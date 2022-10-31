<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToWorkTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('work_times', function (Blueprint $table) {
            $table->foreign(['subdivision_id'], 'FK_worktimes_subdivisions')->references(['id'])->on('subdivisions')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['user_id'], 'FK_worktimes_users')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('work_times', function (Blueprint $table) {
            $table->dropForeign('FK_worktimes_subdivisions');
            $table->dropForeign('FK_worktimes_users');
        });
    }
}
