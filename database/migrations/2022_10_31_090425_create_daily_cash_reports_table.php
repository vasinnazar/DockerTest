<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyCashReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('daily_cash_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('data')->nullable();
            $table->unsignedInteger('user_id')->nullable()->index('FK_reports_users_idx');
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_reports_subdivisions_idx');
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->integer('start_balance')->nullable();
            $table->integer('end_balance')->nullable();
            $table->boolean('matches')->nullable()->default(false);
            $table->string('id_1c', 15);
            $table->boolean('edit_enabled')->nullable()->default(false);
            $table->integer('report_start_balance')->nullable()->default(0);
            $table->integer('report_end_balance')->nullable()->default(0);

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
        Schema::connection('debtors')->dropIfExists('daily_cash_reports');
    }
}
