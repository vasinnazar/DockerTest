<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('quiz_departments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('fio_ruk', 512)->nullable()->comment('ФИО Вашего руководителя');
            $table->string('fio_star_spec', 512)->nullable()->comment('ФИО вашего старшего специалиста ');
            $table->boolean('to_friends')->nullable()->comment('Порекомендуете ли Вы нашу компанию своим друзьям в качестве работодателя');
            $table->string('to_friends_comment', 512)->nullable();
            $table->boolean('workplace')->nullable()->comment('Устраивает ли вас ваше рабочее место');
            $table->string('workplace_comment', 512)->nullable();
            $table->boolean('ruk1')->nullable()->comment('Довольны ли вы взаимодействием со своим руководителем');
            $table->string('ruk1_comment', 512)->nullable();
            $table->boolean('ruk2')->nullable()->comment('У вас хороший руководитель');
            $table->string('ruk2_comment', 512)->nullable();
            $table->boolean('motivation')->nullable()->comment('Устраивает ли вас система мотивации');
            $table->string('motivation_comment', 512)->nullable();
            $table->boolean('vzisk')->nullable()->comment('Довольны ли вы взаимодействием с отделом взыскания');
            $table->string('vzisk_comment', 512)->nullable();
            $table->boolean('ovk')->nullable()->comment('Довольны ли вы взаимодействием с отделом ОВК');
            $table->string('ovk_comment', 512)->nullable();
            $table->boolean('proverka')->nullable()->comment('Довольны ли вы взаимодействием с отделом проверки');
            $table->string('proverka_comment', 512)->nullable();
            $table->boolean('kadri')->nullable()->comment('Довольны ли вы взаимодействием с отделом кадров');
            $table->string('kadri_comment', 512)->nullable();
            $table->boolean('sales')->nullable()->comment('Довольны ли вы взаимодействием с отделом продаж');
            $table->string('sales_comment', 512)->nullable();
            $table->boolean('it')->nullable()->comment('Довольны ли вы взаимодействием с отделом IT');
            $table->string('it_comment', 512)->nullable();
            $table->boolean('buh')->nullable()->comment('Довольны ли вы взаимодействием с бухгалтерией');
            $table->string('buh_comment', 512)->nullable();
            $table->unsignedInteger('user_id')->nullable()->index('FK_quizdept_users_idx');
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_quizdept_subdivisions_idx');
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
        Schema::connection('debtors')->dropIfExists('quiz_departments');
    }
}
