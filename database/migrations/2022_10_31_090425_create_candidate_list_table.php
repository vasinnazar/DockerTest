<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCandidateListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('candidate_list', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('fio', 45);
            $table->string('city', 50);
            $table->string('tel_candidate', 11);
            $table->dateTime('call_date');
            $table->dateTime('interview_date');
            $table->boolean('reach')->nullable();
            $table->integer('interview_result')->nullable()->default(0);
            $table->integer('decision')->nullable();
            $table->dateTime('approval_date')->nullable();
            $table->string('comment', 500)->nullable();
            $table->dateTime('training')->nullable();
            $table->integer('result')->nullable();
            $table->integer('region')->nullable();
            $table->string('mentor', 50)->nullable();
            $table->string('headman', 50)->nullable();
            $table->string('responsible', 50)->nullable();
            $table->string('comment_ruk', 500)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('candidate_list');
    }
}
