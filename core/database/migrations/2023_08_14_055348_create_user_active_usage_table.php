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
        Schema::create('user_active_usage', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
            $table->integer('duration')->default('0');
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
        Schema::dropIfExists('user_active_usage');
    }
};
