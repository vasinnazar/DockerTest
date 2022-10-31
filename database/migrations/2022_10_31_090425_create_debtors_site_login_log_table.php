<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsSiteLoginLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_site_login_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('customer_id_1c', 45)->nullable()->index('idx_customer_id_1c');
            $table->string('str_podr', 45)->nullable();
            $table->integer('sum_loans_debt')->nullable();
            $table->integer('debt_loans_count')->nullable();
            $table->integer('debt_group_id')->nullable();
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
        Schema::connection('debtors')->dropIfExists('debtors_site_login_log');
    }
}
