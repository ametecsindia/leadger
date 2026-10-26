<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transcript options (Call Recordings > Transcript): one saved result per recording and option
 * (summary / agent_customer / description), generated from the recording's saved transcript (call_ai_analyses).
 * A completed result is reused, never generated again. Each company has this table in its own database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transcript_results')) {
            return;
        }
        Schema::create('transcript_results', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->default(1);
            $t->unsignedTinyInteger('source_code');            // 0 campaign call, 2 manual dial call, 3 standalone recording
            $t->unsignedBigInteger('log_id');                  // the call / recording row of that source
            $t->string('option', 20);                          // summary | agent_customer | description
            $t->string('status', 20)->default('pending');      // pending | processing | completed | failed
            $t->longText('result')->nullable();                // JSON of the generated result
            $t->string('model', 60)->nullable();               // AI model used (null = built from the saved transcript)
            $t->unsignedInteger('usage_tokens')->nullable();   // tokens reported by the AI service
            $t->text('error_message')->nullable();
            $t->unsignedBigInteger('requested_by')->nullable();
            $t->unsignedBigInteger('transcription_job_id')->nullable();   // when the audio had to be transcribed first
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['source_code', 'log_id', 'option']);
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcript_results');
    }
};
