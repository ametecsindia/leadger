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
    /** Attempts for temporary Sarvam problems (network, rate limit, server error); waiting for a slot is not one. */
    private const API_TRIES = 3;

    public $timeout = 900;
    /** Real errors (exceptions) end the job after this many; waiting for a free slot does not count. */
    public $maxExceptions = 3;

    /**
     * Instead of a fixed number of tries: with more than MAX_PARALLEL transcripts queued, the others wait for a free
     * slot (release) as long as needed. A fixed $tries counted those waits, so they failed after about a minute.
     */
    public function retryUntil()
    {
        return now()->addHours(6);
    }

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
        // Handed out again after its worker stopped mid-way (restart, crash): run it again
        if ($job && $job->isStalled()) {
            $job->status = 'QUEUED';
            $job->save();
        }
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

        // How many times Sarvam has already failed temporarily for this job (kept across releases)
        $apiTriesKey = 'sarvam-transcribe-tries:' . Tenancy::key() . ':' . $job->id;
        $apiTries = (int)Cache::get($apiTriesKey, 0);
        try {
            $result = (new TranscriptionBillingService())->processSingleJob($job, $apiTries + 1 < self::API_TRIES);
        } finally {
            $lock->release();
        }

        if (!empty($result['retry'])) {
            Cache::put($apiTriesKey, $apiTries + 1, now()->addHours(6));
            $this->release($this->backoff()[$apiTries] ?? 300);
            Log::info("[TranscribeRecordingJob] Job #{$job->id} retry in a moment (Sarvam attempt " . ($apiTries + 1) . ')');
            return;
        }
        Cache::forget($apiTriesKey);
        $state = $result['success'] ? 'completed' : (!empty($result['waiting_key']) ? 'waiting for a Sarvam key' : 'failed: ' . ($result['error'] ?? ''));
        // Seconds per step: queue_wait, conversation_upload, transcript, conversation_wait, analysis
        $timings = collect($result['timings'] ?? [])->map(fn ($s, $step) => "{$step} {$s}s")->implode(', ');
        Log::info("[TranscribeRecordingJob] Job #{$job->id} {$state}" . ($timings !== '' ? " ({$timings})" : ''));
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
