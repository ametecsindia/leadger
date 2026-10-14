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
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('session_timeout')->default(120); // minutes
        });
    }
    
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('session_timeout');
        });
    }
    
};
