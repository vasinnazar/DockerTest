<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('creator_id')->nullable()->index('FK_customers_users_idx');
            $table->string('telephone', 11)->index('IDX_telephone');
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->string('id_1c', 45)->nullable()->index('IDX_id_1c');
            $table->integer('pin')->nullable()->comment('пин код для входа с терминала');
            $table->integer('balance')->default(0)->comment('сумма денег у клиента в копейках (для терминала)');
            $table->string('snils', 15)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('customers');
    }
}
