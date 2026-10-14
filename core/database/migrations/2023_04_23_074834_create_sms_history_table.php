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
        Schema::create('sms_history', function (Blueprint $table) {
            $table->id();
            $table->integer('sms_gateway_id');
            $table->integer('lead_id')->nullable();
            $table->string('phone_number', 20);
            $table->string('sms_content', 300);
            $table->string('response', 1000);
            $table->integer('sent_by');
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
        Schema::dropIfExists('sms_history');
    }
};
