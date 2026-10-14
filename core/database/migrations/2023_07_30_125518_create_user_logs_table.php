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
        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->string('scenario', 20);
            $table->bigInteger('user_id');
            $table->string('info', 100);
            $table->dateTime('login_date')->nullable();
            $table->dateTime('logout_date')->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->string('logged_in_duration', 20)->nullable();
            $table->string('user_agent_details', 1000)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_logs');
    }
};
