<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialsClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('materials_claims', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index('FK_matclaims_users_idx');
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_matclaims_subdivisions_idx');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->text('data')->nullable();
            $table->text('comment')->nullable();
            $table->dateTime('claim_date')->nullable()->comment('дата  исполнения');
            $table->tinyInteger('status')->nullable()->default(0);
            $table->integer('sfp_old')->nullable();
            $table->integer('sfp_new')->nullable();
            $table->integer('sfp_claim')->nullable();
            $table->string('id_1c', 20)->nullable();

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
        Schema::connection('debtors')->dropIfExists('materials_claims');
    }
}
