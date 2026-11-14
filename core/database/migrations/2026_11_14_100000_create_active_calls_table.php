<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calls in progress right now (Dashboard "On Call Now"): a row when BOTH sides have answered, closed (ended_at) on
 * hang-up / call end, and by active-calls:cleanup when no event or heartbeat arrives any more. Each company has this
 * table in its own database. call_key is one per call: call:<call_logs id>, dialer:<dialer_logs id>, inbound:<call_id>.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('active_calls')) {
            return;
        }
        Schema::create('active_calls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->default(1);
            $t->string('call_key', 120)->unique();
            $t->string('log_table', 20)->nullable();          // call_logs | dialer_logs | inbound_calls
            $t->unsignedBigInteger('call_log_id')->nullable();
            $t->string('direction', 10);                       // outbound | inbound
            $t->string('channel', 20);                         // click2call | webrtc | inbound
            $t->string('provider_call_id', 191)->nullable()->index();
            $t->unsignedBigInteger('agent_user_id')->nullable();
            $t->string('agent_number', 30)->nullable();
            $t->string('customer_number', 30)->nullable();
            $t->unsignedBigInteger('lead_id')->nullable();
            $t->unsignedBigInteger('campaign_id')->nullable();
            $t->string('vmn', 30)->nullable();
            $t->timestamp('answered_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->timestamp('ended_at')->nullable();
            $t->string('end_reason', 40)->nullable();
            $t->unsignedInteger('duration_seconds')->nullable();
            $t->timestamps();
            $t->index(['company_id', 'ended_at']);
            $t->index(['agent_user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_calls');
    }
};
