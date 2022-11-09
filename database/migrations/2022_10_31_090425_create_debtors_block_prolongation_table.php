<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsBlockProlongationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('debtors_block_prolongation', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('debtor_id')->nullable()->index('idx_debtor_id');
            $table->string('loan_id_1c', 45)->nullable()->index('idx_loan_id_1c');
            $table->timestamp('block_till_date')->nullable()->index('idx_block_till_date');
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('debtors_block_prolongation');
    }
}
