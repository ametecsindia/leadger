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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name',200)->distinct();
            $table->string('description',200)->nullable();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('team_id')->nullable();
            $table->string('fcm_key',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teams');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('team_id');
            $table->dropColumn('fcm_key');
        });
    }
};
