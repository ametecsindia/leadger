<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('obd_did', function (Blueprint $table) {
            $table->tinyInteger('tc_verified')->after('click2call');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('obd_did', function (Blueprint $table) {
            $table->dropColumn('tc_verified');
        });
    }
};
