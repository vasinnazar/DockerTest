<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeacePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('peace_pays', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->unsignedInteger('repayment_id')->nullable()->index('FK_peace_pays_repayments_idx');
            $table->integer('exp_pc')->nullable()->default(0);
            $table->integer('fine')->nullable()->default(0);
            $table->integer('money')->nullable()->default(0);
            $table->boolean('closed')->nullable()->default(false);
            $table->date('end_date')->nullable();
            $table->integer('total')->nullable();
            $table->date('last_payday')->nullable();
            $table->integer('last_payment_fine_left')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('peace_pays');
    }
}
