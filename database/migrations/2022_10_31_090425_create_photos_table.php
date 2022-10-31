<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePhotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('photos', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('path', 512);
            $table->unsignedInteger('claim_id')->nullable()->index('zaimid_index');
            $table->boolean('is_main')->default(false);
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('description', 256)->nullable();

            $table->index([DB::raw("path(255)")], 'IDX_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('photos');
    }
}
