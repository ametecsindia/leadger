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
        Schema::create('notification_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('notification_id');
            $table->integer('lead_id')->nullable();
            $table->string('message',500)->nullable();
            $table->timestamp('follow_up_on');
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
        Schema::dropIfExists('notification_detail');
    }
};
