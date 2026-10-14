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
        Schema::create('audit_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('lead_id');
            $table->integer('campaign_id');
            $table->string('flag', 30);
            $table->string('role', 30);
            $table->text('remark');
            $table->integer('audited_by');
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
        Schema::dropIfExists('audit_reports');
    }
};
