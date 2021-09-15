<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateZaimsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        Schema::create('zaims', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id')->nullable();
            $table->integer('srok')->nullable();
              $table->integer('summa')->nullable();
            $table->date('data')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->integer('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('zaims');
    }

}
