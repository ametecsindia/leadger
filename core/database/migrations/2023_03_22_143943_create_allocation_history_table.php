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
        Schema::create('allocation_history', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id');
            $table->string('user_type', 20)->nullable();
            $table->integer('from_user')->nullable();
            $table->integer('to_user')->nullable();
            $table->string('allocation_type', 30)->nullable();
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
        Schema::dropIfExists('allocation_history');
    }
};
