<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToUserTestAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('user_test_answers', function (Blueprint $table) {
            $table->foreign(['question_id'], 'FK_ut_answer_ut_question')->references(['id'])->on('user_test_questions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('user_test_answers', function (Blueprint $table) {
            $table->dropForeign('FK_ut_answer_ut_question');
        });
    }
}
