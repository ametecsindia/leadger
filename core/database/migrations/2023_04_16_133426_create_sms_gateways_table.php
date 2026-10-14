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
        Schema::create('sms_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name',100);
            $table->string('method',10);
            $table->string('gateway_api_url',300);
            $table->string('mobile_number_param',50);
            $table->string('content_param',50);
            $table->tinyInteger('status')->default('1');
            $table->integer('created_by');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('sms_gateway_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_gateways');
    }
};
