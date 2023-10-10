<?php

use App\Model\Status;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColumnToDebtorsMassRecurents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debtors_mass_recurrents', function (Blueprint $table) {
            $table->unsignedInteger('status_id')->after('debtor_id')->default(Status::SUCCESS);
            $table->foreign('status_id')->on('statuses')->references('id')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debtors_mass_recurrents', function (Blueprint $table) {
            $table->dropForeign('debtors_mass_recurrents_status_id_foreign');
            $table->dropColumn('status_id');
        });
    }
}
