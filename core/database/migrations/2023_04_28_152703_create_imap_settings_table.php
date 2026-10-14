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
        Schema::create('imap_settings', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->string("username", 100);
            $table->string("host", 100);
            $table->string("password", 100);
            $table->string("encryption", 10)->nullable();
            $table->string("folder", 50);
            $table->tinyInteger('status')->default('1');
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
        Schema::dropIfExists('imap_settings');
    }
};
