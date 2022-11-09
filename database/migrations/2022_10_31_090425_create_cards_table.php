<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('card_number', 45)->nullable()->unique('card_number_UNIQUE');
            $table->string('secret_word', 45)->nullable();
            $table->unsignedTinyInteger('status')->nullable()->default(0);
            $table->unsignedInteger('customer_id')->nullable()->index('FK_cards_customers_idx');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['id'], 'id_UNIQUE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('cards');
    }
}
