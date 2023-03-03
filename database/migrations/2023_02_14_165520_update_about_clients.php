<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAboutClients extends Migration
{
    public function up()
    {
        Schema::create('debtor_sync_about', function (Blueprint $table) {
            $table->increments('id');
            $table->string('debtor_id_1c');
            $table->string('customer_id_1c');
            $table->string('telephone')->nullable();
            $table->string('telephonehome')->nullable();
            $table->string('telephoneorganiz')->nullable();
            $table->string('telephonerodstv')->nullable();
            $table->string('anothertelephone')->nullable();
            $table->string('zip')->nullable();
            $table->string('address_region')->nullable();
            $table->string('address_district')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_house')->nullable();
            $table->string('address_building')->nullable();
            $table->string('address_apartment')->nullable();
            $table->string('address_city1')->nullable();
            $table->string('fact_zip')->nullable();
            $table->string('fact_address_region')->nullable();
            $table->string('fact_address_district')->nullable();
            $table->string('fact_address_city')->nullable();
            $table->string('fact_address_street')->nullable();
            $table->string('fact_address_house')->nullable();
            $table->string('fact_address_building')->nullable();
            $table->string('fact_address_apartment')->nullable();
            $table->string('fact_address_city1')->nullable();
            $table->integer('file_id');
            $table->boolean('in_process')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->index(['deleted_at', 'file_id']);
            $table->index(['deleted_at', 'in_process']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('debtor_sync_about');
    }
}
