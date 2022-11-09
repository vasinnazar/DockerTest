<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubdivisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('subdivisions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 200);
            $table->string('name_id', 11);
            $table->string('address', 256);
            $table->string('peacejudge', 256)->nullable();
            $table->string('districtcourt', 256)->nullable();
            $table->string('director', 256)->nullable();
            $table->boolean('closed')->nullable()->default(false);
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->softDeletes();
            $table->boolean('is_terminal')->default(false)->comment('0 - обычное подразделение, 1 - терминал');
            $table->string('city', 256)->nullable();
            $table->integer('group_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->boolean('allow_use_new_cards')->nullable()->default(false);
            $table->string('schedule', 256)->nullable();
            $table->boolean('fiscal')->nullable()->default(false);
            $table->integer('is_lead')->nullable();
            $table->string('redirect_url')->nullable();
            $table->text('working_times')->nullable();
            $table->boolean('is_api')->default(false);
            $table->string('telephone', 12)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('subdivisions');
    }
}
