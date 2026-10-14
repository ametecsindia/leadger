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
        Schema::create('dialer_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('phone_number', 30)->nullable();
            $table->integer('status_id')->nullable();
            $table->text('feedback')->nullable();
            $table->text('comments')->nullable();
            $table->integer('time_taken');
            $table->integer('created_by');
            $table->integer('campaign_id')->nullable();
            $table->string('call_type', 30)->nullable();
            $table->string('uuid', 200)->nullable();
            $table->string('vmn_used', 30)->nullable();
            $table->text('file_path')->nullable();
            $table->string('click_response', 2000)->nullable();
            $table->string('click_recording_url', 2000)->nullable();
            $table->string('duration',50)->nullable();
            $table->tinyInteger('verified');
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
        Schema::dropIfExists('dialer_logs');
    }
};
