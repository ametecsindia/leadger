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
        Schema::create('tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('device_id',100)->nullable();
            $table->string('lat',20)->nullable();
            $table->string('long',20)->nullable();
            $table->string('address',200)->nullable();
            $table->string('logout_lat',20)->nullable();
            $table->string('logout_long',20)->nullable();
            $table->string('logout_address',200)->nullable();
            $table->integer('created_by')->nullable();
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
        Schema::dropIfExists('tracking_logs');
    }
};
