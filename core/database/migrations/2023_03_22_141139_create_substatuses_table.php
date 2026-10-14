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
        Schema::create('substatuses', function (Blueprint $table) {
            $table->id();
            $table->string('name',200)->distinct();
            $table->string('description',200)->nullable();
            $table->integer('status_id');
            $table->integer('seq_order')->nullable();
            $table->tinyInteger('status')->nullable();
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
        Schema::dropIfExists('substatuses');
    }
};
