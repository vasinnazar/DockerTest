<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('debtors')->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('login')->unique('users_email_unique');
            $table->string('password', 60);
            $table->rememberToken();
            $table->unsignedInteger('subdivision_id')->nullable()->index('FK_users_subdivisions_idx');
            $table->integer('group_id')->nullable()->default(1);
            $table->string('doc', 256)->nullable();
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('subdivision_change')->nullable();
            $table->date('ban_at')->nullable();
            $table->boolean('banned')->nullable()->default(false);
            $table->time('begin_time')->nullable()->default('00:00:00');
            $table->time('end_time')->nullable()->default('23:59:59');
            $table->string('id_1c', 45)->nullable()->index('IDX_id_1c');
            $table->unsignedInteger('customer_id')->index('FK_users_customers_idx');
            $table->dateTime('last_login');
            $table->date('birth_date')->nullable();
            $table->string('phone', 12)->nullable();
            $table->dateTime('employment_agree')->nullable();
            $table->string('employment_docs_track_number', 14)->nullable();
            $table->integer('master_user_id')->nullable();
            $table->integer('sms_limit')->nullable();
            $table->integer('sms_sent')->nullable();
            $table->string('position', 70)->nullable();
            $table->unsignedInteger('region_id')->nullable();
            $table->unsignedInteger('user_group_id')->nullable();
            $table->string('infinity_extension', 6)->nullable();
            $table->string('infinity_user_id', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('debtors')->dropIfExists('users');
    }
}
