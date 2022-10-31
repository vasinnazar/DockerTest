<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoantypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('loantypes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256)->nullable();
            $table->integer('money')->nullable();
            $table->integer('time')->nullable();
            $table->decimal('percent', 5)->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->unsignedInteger('contract_form_id')->nullable();
            $table->unsignedInteger('card_contract_form_id')->nullable();
            $table->boolean('basic')->default(false);
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));            $table->unsignedTinyInteger('status')->nullable()->default(0);
            $table->string('id_1c', 45)->nullable();
            $table->boolean('show_in_terminal')->default(false)->comment('показывать в терминале');
            $table->string('docs', 256)->nullable()->comment('список документов необходимых для выдачи (для терминала)');
            $table->decimal('exp_pc', 5)->default(5)->comment('просроченные проценты');
            $table->decimal('exp_pc_perm', 5)->default(2)->comment('просроченные проценты для постоянных клиентов');
            $table->decimal('fine_pc', 5)->default(20)->comment('пеня');
            $table->decimal('fine_pc_perm', 5)->default(20)->comment('пеня для постоянных клиентов');
            $table->decimal('pc_after_exp', 5)->nullable()->comment('основной процент после просрочки договора');
            $table->boolean('special_pc')->default(false)->comment('брать ли срочный процент из кредитника (0 или 1)');
            $table->unsignedInteger('additional_contract_id')->nullable()->comment('доп уведомление или соглашение');
            $table->unsignedInteger('additional_contract_perm_id')->nullable()->comment('доп уведомление или соглашение для постоянных клиентов или пенсионеров');
            $table->integer('perm_contract_form_id')->nullable();
            $table->integer('perm_card_contract_form_id')->nullable();
            $table->unsignedInteger('additional_card_contract_id');
            $table->unsignedInteger('additional_card_contract_perm_id');
            $table->integer('terminal_promo_discount')->nullable()->default(0);
            $table->boolean('has_special_pc_for_dop')->default(false);
            $table->integer('min_time')->nullable()->default(1);
            $table->integer('min_money')->nullable()->default(0);
            $table->text('data')->nullable();

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
        Schema::connection('debtors')->dropIfExists('loantypes');
    }
}
