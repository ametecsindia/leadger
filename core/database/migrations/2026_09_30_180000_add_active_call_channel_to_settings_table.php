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
        if (!Schema::hasColumn('settings', 'active_call_channel')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('active_call_channel', 50)->default('webrtc')->after('session_timeout');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('settings', 'active_call_channel')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('active_call_channel');
            });
        }
    }
};
