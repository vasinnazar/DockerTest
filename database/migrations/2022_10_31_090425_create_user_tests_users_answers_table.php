<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTestsUsersAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('user_tests_users_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedInteger('user_id')->nullable()->index('FK_ut_answer_ut_user_idx');
            $table->unsignedInteger('question_id')->nullable()->index('FK_ut_user_answer_ut_question_idx');
            $table->unsignedInteger('answer_id')->nullable()->index('FK_ut_user_answer_ut_answer_idx');
            $table->unsignedInteger('session_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('user_tests_users_answers');
    }
}
