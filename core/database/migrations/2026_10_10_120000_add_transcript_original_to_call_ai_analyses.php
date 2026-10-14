<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transcripts are shown in English letters (Sarvam "translit" mode). When an older transcript written in an
 * Indic script is re-transcribed, its original text is kept here for auditing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('call_ai_analyses', 'transcript_original')) {
            Schema::table('call_ai_analyses', function (Blueprint $table) {
                $table->longText('transcript_original')->nullable()->after('transcript');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('call_ai_analyses', 'transcript_original')) {
            Schema::table('call_ai_analyses', function (Blueprint $table) {
                $table->dropColumn('transcript_original');
            });
        }
    }
};
