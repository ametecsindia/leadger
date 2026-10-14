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
        Schema::create('shifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',50);
            $table->string('start_time',20);
            $table->string('end_time',20);
            $table->timestamps();
        });
        DB::statement("insert into ".env('DB_TABLE_PREFIX')."shifts(name,start_time,end_time) values('Regular', '09:30', '18:30')");
        Schema::table('users', function (Blueprint $table) {
            $table->integer('shift_id')->default('1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
