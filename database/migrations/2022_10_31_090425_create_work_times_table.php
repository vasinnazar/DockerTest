<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('work_times', function (Blueprint $table) {
            $table->increments('id');
            $table->text('comment')->nullable();
            $table->unsignedInteger('user_id')->index('FK_worktimes_users_idx');
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_worktimes_subdivisions_idx');
            $table->dateTime('date_start')->nullable();
            $table->dateTime('date_end')->nullable();
            $table->tinyInteger('evaluation')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->text('review')->nullable();
            $table->text('reason')->nullable();
            $table->string('id_1c', 45)->nullable();

            $table->unique(['id'], 'id_UNIQUE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('work_times');
    }
}
