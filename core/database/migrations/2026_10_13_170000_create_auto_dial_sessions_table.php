<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auto Dialer (Calling Workspace, Campaign Queue): one row per auto dialing session, and a flag on the
 * campaign call logs placed by it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('auto_dial_sessions')) {
            Schema::create('auto_dial_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('campaign_id');
                $table->string('dialer_type', 20);                 // click2call | webrtc
                $table->string('status', 20)->index();            // running | paused | ended
                $table->unsignedInteger('calls_made')->default(0);
                $table->unsignedTinyInteger('failures_in_row')->default(0);
                $table->text('dialed_lead_ids')->nullable();       // JSON: leads already dialed in this session
                $table->text('skipped')->nullable();               // JSON: [{lead_id, reason, at}]
                $table->unsignedBigInteger('current_lead_id')->nullable();
                $table->boolean('pending_outcome')->default(false); // the current lead's call still needs its outcome
                $table->timestamp('last_heartbeat_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->string('end_reason', 40)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('call_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('call_logs', 'is_auto_dial')) {
                $table->boolean('is_auto_dial')->default(false)->index();
            }
            if (!Schema::hasColumn('call_logs', 'auto_dial_session_id')) {
                $table->unsignedBigInteger('auto_dial_session_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            if (Schema::hasColumn('call_logs', 'auto_dial_session_id')) $table->dropColumn('auto_dial_session_id');
            if (Schema::hasColumn('call_logs', 'is_auto_dial')) {
                $table->dropIndex(['is_auto_dial']);
                $table->dropColumn('is_auto_dial');
            }
        });
        Schema::dropIfExists('auto_dial_sessions');
    }
};
