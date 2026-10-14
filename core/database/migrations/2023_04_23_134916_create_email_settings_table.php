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
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name',100);
            $table->string('driver',20);
            $table->string('host',100)->nullable();
            $table->string('port',20)->nullable();
            $table->string('username',100)->nullable();
            $table->string('password',50)->nullable();
            $table->string('encryption',20)->nullable();
            $table->string('from_email',100);
            $table->tinyInteger('status')->default('1');
            $table->integer('created_by');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_setting_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_settings');
    }
};
