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
        Schema::table('users', function (Blueprint $table) {
            $table->string('click_username',30)->nullable();
            $table->string('click_password',30)->nullable();
            $table->string('click_vmn',30)->nullable();
            $table->string('click_agent_number',30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('click_username');
            $table->dropColumn('click_password');
            $table->dropColumn('click_vmn');
            $table->dropColumn('click_agent_number');
        });
    }
};
