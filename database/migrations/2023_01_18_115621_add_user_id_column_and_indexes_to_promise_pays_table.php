<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserIdColumnAndIndexesToPromisePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtors_events_promise_pays', function (Blueprint $table) {
            $table->integer('user_id')->nullable();
            $table->index('promise_date');
            $table->index('event_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debtors_events_promise_pays', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropIndex(['promise_date', 'event_id', 'created_at']);
        });
    }
}
