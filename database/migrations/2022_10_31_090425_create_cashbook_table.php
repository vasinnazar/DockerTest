<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashbookTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('cashbook', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->unsignedInteger('action')->nullable();
            $table->integer('balance')->nullable();
            $table->unsignedInteger('subdivision_id')->nullable();
            $table->integer('money')->nullable();
            $table->unsignedInteger('loan_id')->nullable()->index('FK_cashbook_loans_idx');
            $table->softDeletes();
            $table->unsignedInteger('order_id')->nullable()->index('FK_cashbook_orders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('cashbook');
    }
}
