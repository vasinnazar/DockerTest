<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToUserTestQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('user_test_questions', function (Blueprint $table) {
            $table->foreign(['user_test_id'], 'FK_ut_question_ut_test')->references(['id'])->on('user_tests')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('user_test_questions', function (Blueprint $table) {
            $table->dropForeign('FK_ut_question_ut_test');
        });
    }
}
