<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadgerClick2callLogsTable extends Migration
{
    public function up()
    {
        Schema::create('leadger_click2call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->string('reference_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('vmn');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leadger_click2call_logs');
    }
}
