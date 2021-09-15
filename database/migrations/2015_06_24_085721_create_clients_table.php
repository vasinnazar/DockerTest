<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	  Schema::create('clients', function(Blueprint $table) {
            $table->increments('id');
            $table->string('fio')->nullable();
            $table->integer('number_ean')->nullable();
            $table->string('photo')->nullable();
            $table->integer('id_sodatel')->nullable();
             $table->string('telephone')->nullable();
            $table->timestamps();
              });
    }
	

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('clients');
	}

}
