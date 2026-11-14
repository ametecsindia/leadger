<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound call notifications in the existing notifications table (bell): one row per user and call (call_id), its
 * type moving inbound_ringing -> inbound_answered, or inbound_missed. Older rows (follow-up reminders) keep these
 * columns empty and are unchanged; the follow-up list joins notification_detail, so call rows never show there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('notifications', 'call_id')) {
            return;
        }
        Schema::table('notifications', function (Blueprint $t) {
            $t->string('type', 30)->nullable()->after('user_id');
            $t->string('call_id', 120)->nullable()->after('type');
            $t->string('title', 191)->nullable()->after('call_id');
            $t->string('body', 500)->nullable()->after('title');
            $t->text('data')->nullable()->after('body');
            $t->unique(['user_id', 'call_id'], 'notifications_user_call_unique');
            $t->index(['user_id', 'updated_at'], 'notifications_user_updated_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('notifications', 'call_id')) {
            return;
        }
        Schema::table('notifications', function (Blueprint $t) {
            $t->dropUnique('notifications_user_call_unique');
            $t->dropIndex('notifications_user_updated_index');
            $t->dropColumn(['type', 'call_id', 'title', 'body', 'data']);
        });
    }
};
