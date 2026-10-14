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
        Schema::table('imap_emails', function (Blueprint $table) {
            $table->tinyInteger('cat_id')->default('0');
            $table->integer('parent_id')->nullable();
            $table->tinyInteger('status')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imap_emails', function (Blueprint $table) {
            $table->dropColumn('cat_id');
            $table->dropColumn('parent_id');
            $table->dropColumn('status');
        });
    }
};
