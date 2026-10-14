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
        Schema::create('obd_campaign_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('obd_campaign_id');
            $table->string('mobile_number', 20);
            $table->tinyInteger('status')->default('0');
            $table->timestamp('call_initiated_time')->nullable();
            $table->timestamp('call_answered_time')->nullable();
            $table->timestamp('call_end_time')->nullable();
            $table->string('call_duration', 50)->nullable();
            $table->string('hangup_cause', 50)->nullable();
            $table->string('disconnected_by', 50)->nullable();
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
        Schema::dropIfExists('obd_campaign_detail');
    }
};
