<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePassportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('passports', function (Blueprint $table) {
            $table->increments('id');
            $table->date('birth_date');
            $table->string('birth_city', 128);
            $table->string('series', 4);
            $table->string('number', 6);
            $table->string('issued', 256);
            $table->date('issued_date');
            $table->string('subdivision_code', 7);
            $table->string('zip', 6)->nullable();
            $table->string('fact_zip', 6)->nullable();
            $table->string('address_region', 128);
            $table->string('address_district', 128)->nullable();
            $table->string('address_city', 128);
            $table->string('address_street', 128);
            $table->string('address_house', 10);
            $table->string('address_building', 10)->nullable();
            $table->string('address_apartment', 50)->nullable();
            $table->date('address_reg_date');
            $table->string('fact_address_region', 128)->nullable();
            $table->string('fact_address_district', 128)->nullable();
            $table->string('fact_address_city', 128)->nullable();
            $table->string('fact_address_street', 128)->nullable();
            $table->string('fact_address_house', 10)->nullable();
            $table->string('fact_address_building', 10)->nullable();
            $table->string('fact_address_apartment', 50)->nullable();
            $table->unsignedInteger('customer_id')->index('FK_customer_id_idx');
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->string('fio', 512);
            $table->string('address_city1', 128)->nullable();
            $table->string('fact_address_city1', 128)->nullable();
            $table->integer('fact_timezone')->nullable();

            $table->index([DB::raw("fio(255)")], 'fio');
            $table->index(['series', 'number'], 'series_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('passports');
    }
}
