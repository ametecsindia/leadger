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
        Schema::create('tracking_history', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('lead_id');
            $table->string('type',10)->nullable();
            $table->float('distance',8,2)->nullable();
            $table->float('duration',8,2)->nullable();
            $table->string('lat',20)->nullable();
            $table->string('long',20)->nullable();
            $table->string('address',200)->nullable();
            $table->timestamp('tracked_at')->nullable();
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
        Schema::dropIfExists('tracking_history');
    }
};
