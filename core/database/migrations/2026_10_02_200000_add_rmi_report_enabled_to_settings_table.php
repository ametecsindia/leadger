<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'rmi_report_enabled')) {
                $table->tinyInteger('rmi_report_enabled')->default(1)->nullable()->after('wrapup_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'rmi_report_enabled')) {
                $table->dropColumn('rmi_report_enabled');
            }
        });
    }
};
