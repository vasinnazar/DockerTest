<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('claims', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->nullable()->index('FK_claim_customer');
            $table->integer('srok')->nullable();
            $table->integer('summa')->nullable();
            $table->dateTime('date')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->unsignedInteger('user_id')->nullable()->index('FK_claim_user');
            $table->unsignedTinyInteger('status')->nullable()->default(0);
            $table->softDeletes();
            $table->unsignedInteger('passport_id')->nullable()->index('FK_claim_passport_idx');
            $table->unsignedInteger('about_client_id')->nullable();
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_claims_subdivisions_idx');
            $table->string('id_1c', 64)->nullable()->unique('id_1c_UNIQUE');
            $table->unsignedInteger('promocode_id')->nullable()->index('FK_claims_promocodes_idx');
            $table->string('seb_phone', 15)->nullable()->comment('телефон проверяющего сэб');
            $table->decimal('special_percent', 5)->nullable()->comment('спец процент для акции гарантия низкой ставки');
            $table->dateTime('claimed_for_remove')->nullable();
            $table->unsignedInteger('max_money')->nullable()->comment('максимальная сумма для выдачи');
            $table->unsignedInteger('terminal_loantype_id')->nullable();
            $table->string('terminal_guid', 256)->nullable();
            $table->boolean('uki')->default(false);
            $table->dateTime('timestart')->nullable();
            $table->string('id_teleport', 50)->nullable();
            $table->string('agrid', 50)->nullable();
            $table->string('scorista_status', 50)->nullable();
            $table->boolean('scorista_decision')->nullable();
            $table->string('teleport_status', 45)->nullable();

            $table->index(['id_1c'], 'id_1c');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('claims');
    }
}
