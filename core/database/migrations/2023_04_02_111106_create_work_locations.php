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
        Schema::create('work_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name',50);
            $table->string('lat',50);
            $table->string('long',50);
            $table->string('radius',50);
            $table->timestamps();
        });
        DB::Statement("insert into ".env('DB_TABLE_PREFIX')."work_locations(`name`,lat,`long`,radius) values ('No Work Location','17.374857365034558', '78.51531063486603','0')");
        Schema::table('users', function (Blueprint $table) {
            $table->integer('work_location_id')->default('1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_locations');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('work_location_id');
        });
    }
};
