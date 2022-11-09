<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAboutClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('about_clients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->index('FK_about_customer_idx');
            $table->boolean('sex')->nullable();
            $table->integer('goal')->nullable()->index('FK_about_loangoal_idx');
            $table->integer('zhusl')->nullable()->index('FK_about_livecond_idx');
            $table->string('deti', 512)->nullable();
            $table->string('fiosuprugi', 100)->nullable();
            $table->string('fioizmena', 100)->nullable();
            $table->integer('avto')->nullable();
            $table->string('telephonehome', 50)->nullable()->default('нет');
            $table->string('organizacia', 100)->nullable();
            $table->string('innorganizacia', 20)->nullable();
            $table->string('dolznost', 50)->nullable();
            $table->string('vidtruda', 20)->nullable();
            $table->string('fiorukovoditel', 50)->nullable();
            $table->string('adresorganiz', 100)->nullable();
            $table->string('telephoneorganiz', 30)->nullable();
            $table->string('credit', 50)->nullable();
            $table->integer('dohod')->nullable()->default(0);
            $table->integer('dopdohod')->nullable()->default(0);
            $table->string('stazlet', 10)->nullable()->default('0');
            $table->integer('adsource')->nullable()->index('FK_about_adsource_idx');
            $table->string('pensionnoeudost', 50)->nullable();
            $table->string('telephonerodstv', 512)->nullable();
            $table->integer('obrasovanie')->nullable()->index('FK_about_education_idx');
            $table->boolean('pensioner')->nullable()->default(false);
            $table->boolean('postclient')->nullable()->default(false);
            $table->boolean('armia')->nullable()->default(false);
            $table->boolean('poruchitelstvo')->nullable()->default(false);
            $table->boolean('zarplatcard')->nullable()->default(false);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->boolean('alco')->nullable()->default(false);
            $table->boolean('drugs')->nullable()->default(false);
            $table->boolean('stupid')->nullable()->default(false);
            $table->boolean('badspeak')->nullable()->default(false);
            $table->boolean('pressure')->nullable()->default(false);
            $table->boolean('dirty')->nullable()->default(false);
            $table->boolean('smell')->nullable()->default(false);
            $table->boolean('badbehaviour')->nullable()->default(false);
            $table->boolean('soldier')->nullable()->default(false);
            $table->boolean('other')->nullable()->default(false);
            $table->boolean('watch')->nullable()->default(false);
            $table->unsignedInteger('stepenrodstv')->nullable()->index('FK_about_stepenrodstv_idx');
            $table->string('anothertelephone', 512)->nullable();
            $table->unsignedInteger('marital_type_id')->nullable()->index('FK_about_maritaltype_idx');
            $table->string('recomend_phone_1', 300)->nullable();
            $table->string('recomend_phone_2', 300)->nullable();
            $table->string('recomend_phone_3', 300)->nullable();
            $table->string('recomend_fio_1', 300)->nullable();
            $table->string('recomend_fio_2', 300)->nullable();
            $table->string('recomend_fio_3', 300)->nullable();
            $table->string('other_mfo', 300)->nullable();
            $table->string('other_mfo_why', 500)->nullable();
            $table->string('email', 256)->nullable();
            $table->integer('dohod_husband')->nullable();
            $table->integer('pension')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('about_clients');
    }
}
