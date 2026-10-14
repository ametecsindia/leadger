<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Speaker-wise English conversation for a call transcript (Sarvam batch speech-to-text: translate + diarization).
 * transcript stays the original-language text; these columns hold the English version and its speaker turns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_ai_analyses', function (Blueprint $table) {
            if (!Schema::hasColumn('call_ai_analyses', 'transcript_english')) {
                $table->longText('transcript_english')->nullable()->after('transcript_original');
            }
            if (!Schema::hasColumn('call_ai_analyses', 'conversation')) {
                // [{"speaker":"S1","start":0.01,"end":6.81,"text":"..."}...] in spoken order
                $table->longText('conversation')->nullable()->after('transcript_english');
            }
            if (!Schema::hasColumn('call_ai_analyses', 'speaker_roles')) {
                // {"S1":"agent","S2":"customer"} once a user has confirmed who is who (never guessed)
                $table->string('speaker_roles', 191)->nullable()->after('conversation');
            }
            if (!Schema::hasColumn('call_ai_analyses', 'source_language')) {
                $table->string('source_language', 20)->nullable()->after('speaker_roles');
            }
            if (!Schema::hasColumn('call_ai_analyses', 'analysis_source')) {
                $table->string('analysis_source', 50)->nullable()->after('source_language');
            }
        });
    }

    public function down(): void
    {
        Schema::table('call_ai_analyses', function (Blueprint $table) {
            foreach (['transcript_english', 'conversation', 'speaker_roles', 'source_language', 'analysis_source'] as $col) {
                if (Schema::hasColumn('call_ai_analyses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
