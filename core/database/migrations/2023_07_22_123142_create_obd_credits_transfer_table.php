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
        Schema::create('obd_credits_transfer', function (Blueprint $table) {
            $table->id();
            $table->integer('from_user');
            $table->integer('to_user');
            $table->integer('credits');
            $table->string('reason', 200)->nullable();
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
        Schema::dropIfExists('obd_credits_transfer');
    }
};
