<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticeNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('notice_numbers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('debtor_id_1c', 9)->nullable()->index('idx_debtor_id_1c');
            $table->string('str_podr', 45)->nullable();
            $table->string('user_id_1c', 45)->nullable()->index('idx_user_id_1c');
            $table->integer('is_ur_address')->nullable();
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
        Schema::connection('debtors')->dropIfExists('notice_numbers');
    }
}
