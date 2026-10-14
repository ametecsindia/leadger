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
        Schema::create('obd_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string("name", 100);
            $table->timestamp("schedule_start")->nullable();
            $table->timestamp("schedule_end")->nullable();
            $table->string("vi_campaign_id", 20)->nullable();
            $table->string("did", 20);
            $table->tinyInteger('status')->default('0');
            $table->integer('credits')->default('0');
            $table->string("audio_file", 20);
            $table->integer('created_by');
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
        Schema::dropIfExists('obd_campaigns');
    }
};
