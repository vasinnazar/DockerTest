<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressDoublesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('address_doubles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('debtor_fio', 512)->nullable();
            $table->string('debtor_address', 512)->nullable();
            $table->string('debtor_telephone', 12)->nullable();
            $table->integer('debtor_overdue')->nullable();
            $table->string('customer_fio', 512)->nullable();
            $table->string('customer_address', 512)->nullable();
            $table->string('customer_telephone', 12)->nullable();
            $table->text('comment')->nullable();
            $table->dateTime('date')->nullable();
            $table->string('responsible_user_id_1c', 256)->nullable();
            $table->boolean('is_debtor')->nullable()->default(false);
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
        Schema::connection('debtors')->dropIfExists('address_doubles');
    }
}
