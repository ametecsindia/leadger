<?php

namespace App\Jobs;

use App\Models\TranscriptionJob;
use App\Services\SarvamKeyService;
use App\Services\TranscriptionBillingService;
use App\Support\Tenancy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Runs one transcription job (Queued -> Processing -> Completed/Failed) on the queue worker,
 * so the Recordings page never waits on the speech-to-text API.
 *
 * The payload holds only the transcription job id and the company (a tenant host or database name - never a key or
 * a password). The worker switches to that company's database and reads that company's own Sarvam key there.
 * Temporary Sarvam problems are retried (3 attempts, growing back-off); at most MAX_PARALLEL transcripts of a
 * company run at the same time, so Sarvam's rate limits are not hit.
 */
class TranscribeRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_PARALLEL = 2;

    public $tries = 3;
    public $timeout = 900;

    public $transcriptionJobId;
    public $company;

    public function __construct(int $transcriptionJobId)
    {
        $this->transcriptionJobId = $transcriptionJobId;
        $this->company = Tenancy::context();
    }

    /** Seconds before attempt 2 and 3. */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle()
    {
        Tenancy::apply($this->company);

        $job = TranscriptionJob::find($this->transcriptionJobId);
        if (!$job || !in_array($job->status, ['QUEUED', 'PENDING_CONFIRMATION'], true)) {
            return;
        }

        // Concurrency limit per company: wait for a free slot instead of calling Sarvam in parallel
        $lock = null;
        for ($slot = 1; $slot <= self::MAX_PARALLEL && !$lock; $slot++) {
            $candidate = Cache::lock('sarvam-transcribe:' . Tenancy::key() . ':' . $slot, $this->timeout + 60);
            if ($candidate->get()) $lock = $candidate;
        }
        if (!$lock) {
            $this->release(20);
            return;
        }

        try {
            $result = (new TranscriptionBillingService())->processSingleJob($job, $this->attempts() < $this->tries);
        } finally {
            $lock->release();
        }

        if (!empty($result['retry'])) {
            $this->release($this->backoff()[$this->attempts() - 1] ?? 300);
            Log::info("[TranscribeRecordingJob] Job #{$job->id} retry in a moment (attempt {$this->attempts()})");
            return;
        }
        $state = $result['success'] ? 'completed' : (!empty($result['waiting_key']) ? 'waiting for a Sarvam key' : 'failed: ' . ($result['error'] ?? ''));
        Log::info("[TranscribeRecordingJob] Job #{$job->id} {$state}");
    }

    public function failed(\Throwable $e)
    {
        try {
            Tenancy::apply($this->company);
        } catch (\Throwable $ignored) {
            return;
        }
        $job = TranscriptionJob::find($this->transcriptionJobId);
        if ($job && in_array($job->status, ['QUEUED', 'PROCESSING'], true)) {
            $job->status = 'FAILED';
            $job->error_message = 'Transcription stopped unexpectedly: ' . mb_substr($e->getMessage(), 0, 200);
            $job->save();
            [$sc, $logId] = SarvamKeyService::analysisKey($job);
            if ($analysis = \App\Models\CallAiAnalysis::findFor($sc, $logId)) {
                $analysis->status = 'failed';
                $analysis->error_message = $job->error_message;
                $analysis->save();
            }
        }
    }
}
