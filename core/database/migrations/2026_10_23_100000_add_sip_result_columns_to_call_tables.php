<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WebRTC calls: the true result of the SIP call, so a call that never reached the customer can be traced.
 * sip_code / sip_reason = the final SIP response (or the app's reason: no audio, ring timeout, network);
 * dialed_number = the number exactly as sent to the gateway; answer_time = when the customer really answered
 * (campaign calls; manual calls already have it).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['call_logs', 'dialer_logs'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'sip_code')) $t->unsignedSmallInteger('sip_code')->nullable();
                if (!Schema::hasColumn($table, 'sip_reason')) $t->string('sip_reason', 120)->nullable();
                if (!Schema::hasColumn($table, 'dialed_number')) $t->string('dialed_number', 30)->nullable();
                if (!Schema::hasColumn($table, 'answer_time')) $t->dateTime('answer_time')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['call_logs', 'dialer_logs'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['sip_code', 'sip_reason', 'dialed_number'] as $c) {
                    if (Schema::hasColumn($table, $c)) $t->dropColumn($c);
                }
                if ($table === 'call_logs' && Schema::hasColumn($table, 'answer_time')) $t->dropColumn('answer_time');
            });
        }
    }
};
