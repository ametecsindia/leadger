<?php

namespace App\Console\Commands;

use App\Models\CallAiAnalysis;
use App\Services\SarvamAiService;
use App\Services\TranscriptionBillingService;
use Illuminate\Console\Command;

/**
 * Re-transcribe finished transcripts that are still written in an Indic script (Telugu, Hindi, ...) so they are
 * shown in English letters (Sarvam "translit" mode). Uses the normal billed transcription queue; the old text
 * is kept in call_ai_analyses.transcript_original. Recordings whose audio is gone are listed and left as they are.
 */
class RomanizeTranscriptsCommand extends Command
{
    protected $signature = 'leadger:transcripts-english-letters {--dry-run : Only list what would be re-transcribed}';
    protected $description = 'Re-transcribe Indic-script call transcripts in English letters (transliteration)';

    public function handle(): int
    {
        $billing = new TranscriptionBillingService();
        $keys = [];
        foreach (CallAiAnalysis::where('status', 'completed')->get() as $a) {
            if (!SarvamAiService::hasIndicScript($a->transcript)) continue;
            if (!preg_match('/^(call|dialer|rec)_(\d+)$/', (string)$a->recording_id, $m)) continue;
            $key = ['call' => 0, 'dialer' => 2, 'rec' => 3][$m[1]] . '_' . $m[2];
            $rec = $billing->getRecordingDetails((int)explode('_', $key)[0], (int)$m[2]);
            $issue = !$rec ? 'call record not found' : $rec['audio_issue'];
            $this->line(sprintf('%-12s %3ss  %s', $a->recording_id, $a->audio_duration_seconds, $issue ? "skipped: {$issue}" : 'will re-transcribe'));
            if (!$issue) $keys[] = $key;
        }

        if (empty($keys) || $this->option('dry-run')) {
            $this->info(count($keys) . ' transcript(s) to re-transcribe' . ($this->option('dry-run') ? ' (dry run).' : '.'));
            return self::SUCCESS;
        }

        $result = $billing->createTranscriptionJobs($keys, 1, null, true);
        $billing->dispatchBatch($result['batch_id']);
        $this->info("Queued {$result['total_jobs']} transcript(s) in batch {$result['batch_id']}; the queue worker processes them.");
        return self::SUCCESS;
    }
}
