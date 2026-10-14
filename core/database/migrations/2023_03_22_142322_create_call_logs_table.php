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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phonenumber',20);
            $table->string('call_type',30);
            $table->integer('lead_id')->nullable();
            $table->integer('duration')->nullable();
            $table->string('recording',300)->nullable();
            $table->integer('current_status')->nullable();
            $table->integer('next_status')->nullable();
            $table->string('remarks',500)->nullable();
            $table->timestamp('follow_up_on')->nullable();
            $table->integer('reminder')->nullable();
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
        Schema::dropIfExists('call_logs');
    }
};
