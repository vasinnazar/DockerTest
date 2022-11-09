<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToAboutClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->table('about_clients', function (Blueprint $table) {
            $table->foreign(['adsource'], 'FK_about_adsource')->references(['id'])->on('adsources')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['obrasovanie'], 'FK_about_education')->references(['id'])->on('education_levels')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['goal'], 'FK_about_loangoal')->references(['id'])->on('loan_goals')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['customer_id'], 'FK_about_customer')->references(['id'])->on('customers')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign(['zhusl'], 'FK_about_livecond')->references(['id'])->on('live_conditions')->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->foreign(['marital_type_id'], 'FK_about_maritaltype')->references(['id'])->on('marital_types')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->table('about_clients', function (Blueprint $table) {
            $table->dropForeign('FK_about_adsource');
            $table->dropForeign('FK_about_education');
            $table->dropForeign('FK_about_loangoal');
            $table->dropForeign('FK_about_customer');
            $table->dropForeign('FK_about_livecond');
            $table->dropForeign('FK_about_maritaltype');
        });
    }
}
