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
        Schema::create('firebase_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('fcm_key', 255);
            $table->string('mobile_number', 20)->nullable();
            $table->string('lead_id', 20)->nullable();
            $table->text('payload');
            $table->text('response');
            $table->string('type', 20)->nullable();
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
        Schema::dropIfExists('firebase_logs');
    }
};
