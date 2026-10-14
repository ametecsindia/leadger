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
        Schema::create('incoming_vmn_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('call_id', 50);
            $table->string('dni', 50);
            $table->string('a_party_no', 50);
            $table->timestamp('a_start_time')->nullable();
            $table->string('a_dial_status', 50);
            $table->string('cli', 50);
            $table->string('recording_url', 500);
            $table->integer('duration')->default('0');
            $table->string('response', 4000);
            $table->string('file_path', 500)->nullable();
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
        Schema::dropIfExists('incoming_vmn_logs');
    }
};
