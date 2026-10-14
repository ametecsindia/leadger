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
        Schema::create('image_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('file_name',50);
            $table->string('file_path',200);
            $table->string('file_extension',30)->nullable();
            $table->integer('lead_id')->nullable();
            $table->integer('campaign_id')->nullable();
            $table->string('lat',20)->nullable();
            $table->string('long',20)->nullable();
            $table->string('address',200)->nullable();
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
        Schema::dropIfExists('image_uploads');
    }
};
