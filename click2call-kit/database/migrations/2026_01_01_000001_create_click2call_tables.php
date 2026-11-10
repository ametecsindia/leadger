<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Click2Call kit tables (LeadGER equivalents in brackets):
 *  c2c_vouchers          vouchers added             [obd_credits_history]
 *  c2c_vmns              VMN numbers of the voucher [obd_did]
 *  c2c_vmn_history       numbers replaced by a newer voucher [obd_did_history]
 *  c2c_smart31_numbers   Smart31 / 1600 numbers     [smart31_vmn_numbers]
 *  c2c_calls             every Click2Call call       [dialer_logs + call_logs]
 *  c2c_daily_attempts    calls per number per day   [daily_phone_call_attempts]
 *  c2c_blocked_calls     calls refused by the rules [blocked_call_logs]
 *  users: c2c_channel, click_agent_number, c2c_default_number (same names as LeadGER)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('c2c_vouchers', function (Blueprint $t) {
            $t->id();
            $t->string('voucher_code', 100);
            $t->unsignedInteger('numbers')->default(0);
            $t->date('expires_at')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('c2c_vmns', function (Blueprint $t) {
            $t->id();
            $t->string('virtual_number', 20)->unique();      // 10 digits
            $t->boolean('click2call')->default(false);
            $t->boolean('webrtc')->default(false);
            $t->boolean('obd')->default(false);
            $t->boolean('tc_verified')->default(false);
            $t->json('team_ids')->nullable();                // which teams may use it; empty = only admins
            $t->date('expires_at')->nullable();
            $t->foreignId('voucher_id')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('c2c_vmn_history', function (Blueprint $t) {
            $t->id();
            $t->string('virtual_number', 20);
            $t->boolean('click2call')->default(false);
            $t->boolean('webrtc')->default(false);
            $t->boolean('obd')->default(false);
            $t->boolean('tc_verified')->default(false);
            $t->json('team_ids')->nullable();
            $t->date('expires_at')->nullable();
            $t->foreignId('voucher_id')->nullable();
            $t->timestamp('replaced_at')->nullable();
            $t->timestamps();
        });

        Schema::create('c2c_smart31_numbers', function (Blueprint $t) {
            $t->id();
            $t->string('vmn', 20);
            $t->date('usage_date');
            $t->boolean('c2c')->default(false);
            $t->boolean('webrtc')->default(false);
            $t->boolean('obd')->default(false);
            $t->boolean('verified')->default(false);
            $t->string('group_id', 50)->nullable();
            $t->string('group_name', 100)->nullable();
            $t->string('type', 50)->nullable();               // "1600" for 1600 numbers
            $t->json('assigned_groups')->nullable();          // team ids; empty = everyone
            $t->timestamps();
            $t->unique(['vmn', 'usage_date']);
        });

        Schema::create('c2c_calls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id');                         // agent
            $t->string('source', 20)->default('manual');      // manual | campaign
            $t->unsignedBigInteger('lead_id')->nullable();
            $t->unsignedBigInteger('campaign_id')->nullable();
            $t->string('customer_number', 20)->index();
            $t->string('agent_number', 20);
            $t->string('vmn', 20);
            $t->string('ref_id', 100)->unique();              // sent to the provider as ref_id
            $t->string('provider_call_id', 191)->nullable()->index();
            $t->string('call_state', 20)->default('initiated'); // initiated|ringing|connected|completed|busy|no-answer|failed
            $t->timestamp('started_at')->nullable();
            $t->timestamp('answered_at')->nullable();
            $t->timestamp('ended_at')->nullable();
            $t->unsignedInteger('talk_seconds')->default(0);
            $t->json('cdr')->nullable();                      // provider call record (no secrets)
            $t->string('error', 255)->nullable();
            $t->text('recording_url')->nullable();
            $t->string('recording_path')->nullable();
            $t->string('recording_status', 20)->nullable();   // awaiting|pending|completed|failed|unavailable
            $t->unsignedInteger('recording_attempts')->default(0);
            $t->string('outcome', 100)->nullable();           // agent's call outcome
            $t->text('notes')->nullable();
            $t->timestamp('follow_up_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });

        Schema::create('c2c_daily_attempts', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 20);
            $t->date('call_date');
            $t->unsignedInteger('attempt_count')->default(0);
            $t->timestamps();
            $t->unique(['phone', 'call_date']);
        });

        Schema::create('c2c_blocked_calls', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 20);
            $t->foreignId('user_id')->nullable();
            $t->string('reason', 30);                         // outside_hours | retry_limit | invalid_number
            $t->string('message', 255);
            $t->timestamp('blocked_at');
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'c2c_channel')) $t->boolean('c2c_channel')->default(false);
            if (!Schema::hasColumn('users', 'click_agent_number')) $t->string('click_agent_number', 30)->nullable();
            if (!Schema::hasColumn('users', 'c2c_default_number')) $t->string('c2c_default_number', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['c2c_channel', 'click_agent_number', 'c2c_default_number']);
        });
        foreach (['c2c_blocked_calls', 'c2c_daily_attempts', 'c2c_calls', 'c2c_smart31_numbers', 'c2c_vmn_history', 'c2c_vmns', 'c2c_vouchers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
