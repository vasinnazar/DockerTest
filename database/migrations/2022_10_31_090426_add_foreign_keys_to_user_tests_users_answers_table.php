<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToUserTestsUsersAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('user_tests_users_answers', function (Blueprint $table) {
            $table->foreign(['user_id'], 'FK_ut_answer_ut_user')->references(['id'])->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['question_id'], 'FK_ut_user_answer_ut_question')->references(['id'])->on('user_test_questions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['answer_id'], 'FK_ut_user_answer_ut_answer')->references(['id'])->on('user_test_answers')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('user_tests_users_answers', function (Blueprint $table) {
            $table->dropForeign('FK_ut_answer_ut_user');
            $table->dropForeign('FK_ut_user_answer_ut_question');
            $table->dropForeign('FK_ut_user_answer_ut_answer');
        });
    }
}
