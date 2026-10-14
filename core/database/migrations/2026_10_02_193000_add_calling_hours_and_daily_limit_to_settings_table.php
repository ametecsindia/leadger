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
            if (!Schema::hasColumn('settings', 'calling_hours_from')) {
                $table->string('calling_hours_from', 10)->default('09:30')->nullable()->after('active_call_channel');
            }
            if (!Schema::hasColumn('settings', 'calling_hours_to')) {
                $table->string('calling_hours_to', 10)->default('19:30')->nullable()->after('calling_hours_from');
            }
            if (!Schema::hasColumn('settings', 'daily_call_limit')) {
                $table->integer('daily_call_limit')->default(100)->nullable()->after('calling_hours_to');
            }
            if (!Schema::hasColumn('settings', 'auto_recording')) {
                $table->tinyInteger('auto_recording')->default(1)->nullable()->after('daily_call_limit');
            }
            if (!Schema::hasColumn('settings', 'dialer_delay')) {
                $table->integer('dialer_delay')->default(5)->nullable()->after('auto_recording');
            }
            if (!Schema::hasColumn('settings', 'wrapup_time')) {
                $table->integer('wrapup_time')->default(30)->nullable()->after('dialer_delay');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['calling_hours_from', 'calling_hours_to', 'daily_call_limit', 'auto_recording', 'dialer_delay', 'wrapup_time'] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
