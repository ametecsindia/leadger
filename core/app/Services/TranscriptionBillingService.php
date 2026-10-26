<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Jobs\TranscribeRecordingJob;
use App\Models\TranscriptionJob;
use App\Models\CompanyAiSetting;
use App\Models\CallAiAnalysis;
use App\Models\AiUsageLog;
use App\Models\CallLog;
use App\Models\DialerLog;
use App\Models\Recording;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TranscriptionBillingService
{
    /**
     * Get provider pricing configuration (single company-wide AI settings row).
     *
     * @param int $companyId kept for backwards compatibility; settings are company-wide
     * @return array
     */
    public function getProviderPricing(int $companyId = 1): array
    {
        $settings = CompanyAiSetting::company();

        $provider = $settings->provider ?? 'Sarvam AI';
        $unitRate = isset($settings->price_per_unit) && (float)$settings->price_per_unit > 0
            ? (float)$settings->price_per_unit
            : 0.1500;
        $pricingUnit = $settings->pricing_unit ?? 'minute';
        $currency = $settings->currency ?? 'INR';
        $billingMode = $settings->billing_mode ?? 'Usage Based';

        return [
            'provider' => $provider,
            'unit_rate' => $unitRate,
            'unit_rate_formatted' => '₹' . number_format($unitRate, 2) . ' / ' . $pricingUnit,
            'pricing_unit' => $pricingUnit,
            'currency' => $currency,
            'billing_mode' => $billingMode,
            'transcript_enabled' => $settings->autoTranscriptEnabled(),
        ];
    }

    /**
     * Resolve a Recordings-page row (source code + log id) to its call details and REAL audio file.
     *
     * @param int $sourceCode 0=Campaign Calling (call_logs), 2=Dialing (dialer_logs), 3=standalone recordings
     * @param int $logId
     * @return array|null  includes 'audio_path' (absolute, existing file or null) and 'audio_issue' (reason when unusable)
     */
    public function getRecordingDetails(int $sourceCode, int $logId): ?array
    {
        $recording = null;
        $callLog = null;
        $dialerLog = null;

        if ($sourceCode == 0) {
            $callLog = CallLog::find($logId);
            if (!$callLog) return null;
            $recording = $callLog->recording_id ? Recording::find($callLog->recording_id) : null;
            $seconds = CallRecordingService::callRecordSeconds($callLog);
            $phone = $callLog->phonenumber;
            $userId = $callLog->created_by;
            $leadName = optional($callLog->lead)->name;
            $campaignId = $callLog->campaign_id ?? optional($callLog->lead)->campaign_id;
            $createdAt = $callLog->created_at;
        } elseif ($sourceCode == 2) {
            $dialerLog = DialerLog::find($logId);
            if (!$dialerLog) return null;
            $recording = $dialerLog->recording_id ? Recording::find($dialerLog->recording_id) : null;
            $seconds = CallRecordingService::callRecordSeconds($dialerLog);
            $phone = $dialerLog->phone_number;
            $userId = $dialerLog->created_by;
            $leadName = optional($dialerLog->lead)->name;
            $campaignId = $dialerLog->campaign_id;
            $createdAt = $dialerLog->created_at;
        } else {
            $recording = Recording::find($logId);
            if (!$recording) return null;
            $seconds = 0;
            $phone = $recording->phone_number;
            $userId = $recording->user_id;
            $leadName = $recording->customer_name;
            $campaignId = $recording->campaign_id;
            $createdAt = $recording->call_date ?: $recording->created_at;
        }

        if ($seconds <= 0 && $recording) {
            $seconds = (int)$recording->duration;
        }

        // Only a real audio file that exists in storage can be transcribed
        $audioPath = null;
        if ($recording) {
            $audioPath = CallRecordingService::realAudioPath($recording->file_path);
        }
        if (!$audioPath && $dialerLog) {
            $audioPath = CallRecordingService::realAudioPath($dialerLog->file_path);
        }
        if (!$audioPath && $callLog && !empty($callLog->recording)) {
            // Older campaign recordings saved by the provider cron: recordings_upload/{crm_id}/{campaign_id}/{file}
            $uploadBase = rtrim((string)env('recordings_upload'), '/\\');
            $name = basename($callLog->recording);
            $crmId = '';
            $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
            if ($settings && !empty($settings->full_response)) {
                $crmId = json_decode($settings->full_response, true)['data']['crm_id'] ?? '';
            }
            foreach (array_filter([
                $uploadBase ? "{$uploadBase}/{$crmId}/{$campaignId}/{$name}" : null,
                $uploadBase ? "{$uploadBase}/{$campaignId}/{$name}" : null,
                $uploadBase ? "{$uploadBase}/{$name}" : null,
                \App\Support\Tenancy::path(storage_path('app/recordings')) . DIRECTORY_SEPARATOR . $name,
            ]) as $candidate) {
                if ($audioPath = CallRecordingService::realAudioPath($candidate)) {
                    break;
                }
            }
        }
        if ($audioPath && $seconds <= 0) {
            $seconds = CallRecordingService::probeAudioDuration($audioPath);
        }

        $audioIssue = null;
        if (!$audioPath) {
            $status = $recording->download_status ?? null;
            $audioIssue = in_array($status, ['awaiting', 'pending', 'downloading'], true)
                ? 'Recording is still processing - the audio file has not arrived from the provider yet.'
                : 'No recording audio file is stored for this call.';
        } elseif ($seconds <= 0) {
            $audioIssue = 'The recording has no audio (duration is 0 seconds).';
        }

        $user = $userId ? \App\Models\User::find($userId) : null;
        $campaign = $campaignId ? \App\Models\Campaign::find($campaignId) : null;
        $keys = CallAiAnalysis::keysFor($sourceCode, $logId);

        return [
            'key' => "{$sourceCode}_{$logId}",
            'source_code' => $sourceCode,
            'source_type' => $sourceCode == 0 ? 'Campaign Calling' : ($sourceCode == 2 ? 'Dialing' : 'Direct Telephony'),
            'log_id' => $logId,
            'call_id' => $logId,
            'recording_id' => $keys['recording_id'],
            'recording_file' => $audioPath,
            'audio_path' => $audioPath,
            'audio_issue' => $audioIssue,
            'customer_name' => $leadName ?: ($phone ?: 'Call #' . $logId),
            'agent_name' => $user->name ?? ($recording->agent_name ?? 'Telecaller'),
            'phonenumber' => $phone,
            'campaign_name' => $campaign->name ?? '—',
            'campaign_id' => $campaignId,
            'duration' => max(0, (int)$seconds),
            'created_at' => $createdAt,
        ];
    }

    /**
     * Turn request input (["0_12", ...] or [{id, source_code}, ...]) into "sourceCode_logId" keys.
     */
    public static function normalizeKeys($rawKeys): array
    {
        if (is_string($rawKeys)) {
            $decoded = json_decode($rawKeys, true);
            $rawKeys = is_array($decoded) ? $decoded : explode(',', $rawKeys);
        }
        $keys = [];
        foreach ((array)$rawKeys as $item) {
            if (is_array($item)) {
                $sc = $item['source_code'] ?? ($item['source'] ?? 0);
                $lid = $item['id'] ?? ($item['log_id'] ?? null);
                if ($lid !== null && $lid !== '') {
                    $keys[] = (int)$sc . '_' . (int)$lid;
                }
            } elseif (is_string($item) || is_numeric($item)) {
                $str = (string)$item;
                $keys[] = (strpos($str, '_') !== false) ? $str : "0_{$str}";
            }
        }
        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * Calculate cost preview estimate for selected recordings.
     *
     * @param array $recordingKeys Array of 'sourceCode_logId' strings e.g. ['0_12', '2_34']
     * @param int $companyId
     * @return array
     */
    public function calculateEstimate(array $recordingKeys, int $companyId = 1): array
    {
        $pricing = $this->getProviderPricing($companyId);
        $unitRate = $pricing['unit_rate'];

        $breakdown = [];
        $skipped = [];
        $totalSeconds = 0;
        $validCount = 0;

        foreach ($recordingKeys as $key) {
            $parts = explode('_', (string)$key);
            if (count($parts) !== 2) continue;

            $sourceCode = (int)$parts[0];
            $logId = (int)$parts[1];

            $rec = $this->getRecordingDetails($sourceCode, $logId);
            if (!$rec) continue;

            if ($rec['audio_issue']) {
                $skipped[] = ['key' => $rec['key'], 'call_id' => $logId, 'reason' => $rec['audio_issue']];
                continue;
            }

            $existingAnalysis = CallAiAnalysis::findFor($sourceCode, $logId);
            $hasExistingTranscript = $existingAnalysis && $existingAnalysis->status === 'completed' && !empty($existingAnalysis->transcript);

            $durationSec = (int)$rec['duration'];
            $billableMinutes = round($durationSec / 60, 2);
            $estCost = round($billableMinutes * $unitRate, 4);

            $totalSeconds += $durationSec;
            $validCount++;

            $breakdown[] = [
                'key' => $rec['key'],
                'id' => $logId,
                'call_id' => $rec['call_id'],
                'source_type' => $rec['source_type'],
                'customer_name' => $rec['customer_name'],
                'agent_name' => $rec['agent_name'],
                'called_by' => $rec['agent_name'],
                'phonenumber' => $rec['phonenumber'],
                'duration_seconds' => $durationSec,
                'duration_formatted' => Helper::formatDuration($durationSec),
                'formatted_duration' => Helper::formatDuration($durationSec),
                'billable_minutes' => $billableMinutes,
                'estimated_cost' => $estCost,
                'estimated_cost_formatted' => '₹' . number_format($estCost, 2),
                'already_transcribed' => $hasExistingTranscript,
            ];
        }

        $totalBillableMinutes = round($totalSeconds / 60, 2);
        $totalEstimatedCost = round($totalBillableMinutes * $unitRate, 2);

        return [
            'status' => 'success',
            'recording_count' => $validCount,
            'total_duration_seconds' => $totalSeconds,
            'total_duration_formatted' => $this->formatDuration($totalSeconds),
            'formatted_duration' => $this->formatDuration($totalSeconds),
            'total_billable_minutes' => $totalBillableMinutes,
            'billable_minutes' => $totalBillableMinutes,
            'provider' => $pricing['provider'],
            'unit_rate' => $unitRate,
            'price_per_unit' => $unitRate,
            'unit_rate_formatted' => $pricing['unit_rate_formatted'],
            'pricing_unit' => $pricing['pricing_unit'],
            'currency' => $pricing['currency'],
            'currency_symbol' => '₹',
            'total_estimated_cost' => $totalEstimatedCost,
            'estimated_cost' => $totalEstimatedCost,
            'total_estimated_cost_formatted' => '₹' . number_format($totalEstimatedCost, 2),
            'formatted_estimated_cost' => number_format($totalEstimatedCost, 2),
            'breakdown' => $breakdown,
            'recordings' => $breakdown,
            'skipped' => $skipped,
        ];
    }

    /**
     * Create QUEUED transcription jobs for the selected recordings.
     *
     * @param array $recordingKeys
     * @param int $clientId
     * @param int|null $userId user who requested it (null for automatic transcripts)
     * @param bool $manual true when an admin/manager clicked "Transcribe" - allowed even when the
     *                     automatic AI Transcript setting is off
     * @return array batch_id, jobs, skipped (rows that cannot be transcribed and why)
     * @throws \Exception when nothing can be queued
     */
    public function createTranscriptionJobs(array $recordingKeys, int $clientId, ?int $userId, bool $manual = false): array
    {
        $pricing = $this->getProviderPricing($clientId);
        // Automatic transcripts follow the company's Transcription settings (License Info tab)
        if (!$manual && !SarvamKeyService::autoTranscribeOn()) {
            throw new \Exception('Automatic transcription is off for this company.');
        }

        // No valid company key: Sarvam is never called
        if (!(new SarvamAiService())->isConfigured()) {
            throw new \Exception(SarvamKeyService::notSetUpMessage());
        }

        $unitRate = $pricing['unit_rate'];
        $batchId = 'trb_' . uniqid() . '_' . time();
        $createdJobs = [];
        $skipped = [];

        foreach ($recordingKeys as $key) {
            $parts = explode('_', (string)$key);
            if (count($parts) !== 2) continue;

            $sourceCode = (int)$parts[0];
            $logId = (int)$parts[1];

            $rec = $this->getRecordingDetails($sourceCode, $logId);
            if (!$rec) {
                $skipped[] = ['key' => $key, 'call_id' => $logId, 'reason' => 'Call record not found.'];
                continue;
            }
            if ($rec['audio_issue']) {
                $skipped[] = ['key' => $key, 'call_id' => $logId, 'reason' => $rec['audio_issue']];
                continue;
            }

            // Do not queue twice while a job is already running for this recording. A job whose worker stopped
            // (stalled) does not count: it is marked failed and a new one may start.
            $active = CallAiAnalysis::findFor($sourceCode, $logId);
            if ($active && in_array($active->status, ['pending', 'processing', 'waiting_key'], true)) {
                $running = TranscriptionJob::where('call_id', $logId)->where('source_code', $sourceCode)
                    ->whereIn('status', ['QUEUED', 'PROCESSING', SarvamKeyService::WAITING_KEY])->get();
                foreach ($running->filter(fn ($j) => $j->isStalled()) as $dead) {
                    $dead->status = 'FAILED';
                    $dead->error_message = 'Transcription stopped (the background worker was restarted). It was started again.';
                    $dead->completed_at = now();
                    $dead->save();
                }
                if ($running->contains(fn ($j) => !$j->isStalled() && $j->status !== 'FAILED')) {
                    $skipped[] = ['key' => $key, 'call_id' => $logId, 'reason' => 'A transcript is already being processed for this recording.'];
                    continue;
                }
            }

            $durationSec = (int)$rec['duration'];
            $billableMinutes = round($durationSec / 60, 2);
            $estCost = round($billableMinutes * $unitRate, 4);

            $job = new TranscriptionJob();
            $job->batch_id = $batchId;
            $job->client_id = $clientId;
            $job->requested_by = $userId ?: 0;
            $job->recording_id = $rec['recording_id'];
            $job->call_id = $rec['call_id'];
            $job->source_code = $rec['source_code'];
            $job->customer_name = $rec['customer_name'];
            $job->agent_name = $rec['agent_name'];
            $job->phonenumber = $rec['phonenumber'];
            $job->recording_file = $rec['audio_path'];
            $job->duration_seconds = $durationSec;
            $job->billable_minutes = $billableMinutes;
            $job->provider = $pricing['provider'];
            $job->unit_rate = $unitRate;
            $job->estimated_cost = $estCost;
            $job->actual_cost = 0.0000;
            $job->currency = $pricing['currency'];
            $job->status = 'QUEUED';
            $job->save();

            // Row shows "Queued" immediately
            $analysis = CallAiAnalysis::firstOrNewFor($sourceCode, $logId);
            $analysis->company_id = $clientId;
            $analysis->status = 'pending';
            $analysis->error_message = null;
            $analysis->save();

            $createdJobs[] = $job;
        }

        if (empty($createdJobs)) {
            $reason = $skipped[0]['reason'] ?? 'No valid recordings selected.';
            throw new \Exception(count($skipped) > 1 ? 'None of the selected recordings can be transcribed. ' . $reason : $reason);
        }

        return [
            'batch_id' => $batchId,
            'total_jobs' => count($createdJobs),
            'jobs' => $createdJobs,
            'skipped' => $skipped,
            'unit_rate' => $unitRate,
            'currency' => $pricing['currency'],
        ];
    }

    /**
     * Send every queued job of a batch to the background queue worker.
     */
    public function dispatchBatch(string $batchId): void
    {
        TranscriptionJob::where('batch_id', $batchId)->where('status', 'QUEUED')->pluck('id')
            ->each(fn ($id) => TranscribeRecordingJob::dispatch((int)$id));
    }

    /**
     * Queue a transcript for one Recording row (used for automatic transcripts after download).
     */
    public function queueRecording(Recording $recording, ?int $userId = null, bool $manual = false): ?string
    {
        if ($recording->call_log_id) {
            $key = '0_' . $recording->call_log_id;
        } elseif ($recording->dialer_log_id) {
            $key = '2_' . $recording->dialer_log_id;
        } else {
            $key = '3_' . $recording->id;
        }

        [$sc, $id] = array_map('intval', explode('_', $key));
        $existing = CallAiAnalysis::findFor($sc, $id);
        if ($existing && in_array($existing->status, ['completed', 'pending', 'processing', 'waiting_key'], true)) {
            return null;
        }

        $batch = $this->createTranscriptionJobs([$key], 1, $userId, $manual);
        $this->dispatchBatch($batch['batch_id']);
        Log::info("[Transcription] Queued transcript batch {$batch['batch_id']} for recording #{$recording->id}");
        return $batch['batch_id'];
    }

    /**
     * Process a single transcription job through Sarvam AI and finalize client billing.
     *
     * @param TranscriptionJob $job
     * @return array
     */
    public function processSingleJob(TranscriptionJob $job, bool $mayRetry = false): array
    {
        // No valid key for this company (removed / invalid): the transcript waits for a key, nothing is sent
        $service = new SarvamAiService();
        if (!$service->isConfigured()) {
            SarvamKeyService::markWaiting($job);
            return ['success' => false, 'waiting_key' => true, 'job_id' => $job->id, 'error' => 'Waiting for a valid Sarvam API key'];
        }

        $queuedSeconds = $job->created_at ? max(0, now()->getTimestamp() - $job->created_at->getTimestamp()) : null;
        $job->status = 'PROCESSING';
        $job->save();

        // Heartbeat: updated_at moves while Sarvam is working, so a job whose worker died is recognised as stalled
        // (TranscriptionJob::isStalled) instead of showing "processing" forever
        $lastBeat = time();
        $service->onProgress = function () use ($job, &$lastBeat) {
            if (time() - $lastBeat >= 20) {
                $lastBeat = time();
                $job->touch();
            }
        };

        $sourceCode = (int)$job->source_code;
        $logId = $sourceCode == 0 ? (int)$job->call_id : (int)preg_replace('/\D/', '', (string)$job->recording_id);
        if ($sourceCode != 0 && $logId <= 0) {
            $logId = (int)$job->call_id;
        }

        $analysis = CallAiAnalysis::firstOrNewFor($sourceCode, $logId);
        $analysis->company_id = $job->client_id;
        $analysis->status = 'processing';
        $analysis->error_message = null;
        $analysis->save();

        $meta = [
            'recording_file' => $job->recording_file,
            'company_id' => $job->client_id,
            'agent_name' => $job->agent_name,
            'customer_name' => $job->customer_name,
            'phonenumber' => $job->phonenumber,
            'duration' => $job->duration_seconds,
        ];

        try {
            $processResult = $service->processCallTranscription($logId, $sourceCode, $meta);
        } catch (\Throwable $e) {
            $processResult = ['status' => 'failed', 'error' => 'Transcription error: ' . $e->getMessage()];
        }

        // Sarvam rejected the key (401/403): key marked invalid, this and the queued transcripts wait for a new key
        if ($processResult['status'] !== 'success' && $service->authFailed()) {
            SarvamKeyService::markInvalid('Sarvam rejected the key during a transcription');
            SarvamKeyService::markWaiting($job);
            return ['success' => false, 'waiting_key' => true, 'job_id' => $job->id, 'error' => 'Waiting for a valid Sarvam API key'];
        }
        // A temporary problem (network, rate limit, server error): queued again, the job retries with back-off
        if ($processResult['status'] !== 'success' && $mayRetry && $service->lastFailureTransient()) {
            $job->status = 'QUEUED';
            $job->error_message = 'Retrying: ' . mb_substr((string)($processResult['error'] ?? ''), 0, 180);
            $job->save();
            $analysis->status = 'pending';
            $analysis->error_message = null;
            $analysis->save();
            return ['success' => false, 'retry' => true, 'job_id' => $job->id, 'error' => $processResult['error'] ?? ''];
        }

        if ($processResult['status'] === 'success') {
            $analysis = $processResult['analysis'];
            $actualDuration = max(1, (int)($analysis->audio_duration_seconds ?: $job->duration_seconds));
            $actualBillableMinutes = round($actualDuration / 60, 2);
            $actualCost = round($actualBillableMinutes * $job->unit_rate, 4);

            $job->status = 'COMPLETED';
            $job->provider_reference = $analysis->sarvam_request_id;
            $job->duration_seconds = $actualDuration;
            $job->billable_minutes = $actualBillableMinutes;
            $job->actual_cost = $actualCost;
            $job->completed_at = now();
            $job->billed_at = now();
            $job->error_message = null;
            $job->save();

            $analysis->ai_cost = $actualCost;
            $analysis->status = 'completed';
            $analysis->save();

            AiUsageLog::updateOrCreate(
                [
                    'company_id' => $job->client_id,
                    'recording_id' => $job->recording_id,
                ],
                [
                    'call_id' => $job->call_id,
                    'provider' => $job->provider,
                    'service' => 'Speech-to-Text & AI Transcript',
                    'audio_duration_seconds' => $actualDuration,
                    'usage_amount' => $actualCost,
                    'currency' => $job->currency,
                    'status' => 'completed',
                ]
            );

            // transcript options waiting for this transcript are generated from it now
            TranscriptOptionService::afterTranscript($sourceCode, $logId, true, null, $job->id);

            return [
                'success' => true,
                'job_id' => $job->id,
                'call_id' => $logId,
                'source_code' => $sourceCode,
                'customer_name' => $job->customer_name,
                'agent_name' => $job->agent_name,
                'phonenumber' => $job->phonenumber,
                'actual_cost' => $actualCost,
                'actual_cost_formatted' => '₹' . number_format($actualCost, 2),
                'billable_minutes' => $actualBillableMinutes,
                'timings' => ['queue_wait' => $queuedSeconds] + ($processResult['timings'] ?? []),
            ];
        }

        // Failure - the client is NOT charged
        $errorMsg = $processResult['error'] ?? 'Sarvam AI transcription failed.';

        $job->status = 'FAILED';
        $job->actual_cost = 0.0000;
        $job->error_message = $errorMsg;
        $job->completed_at = now();
        $job->billed_at = null;
        $job->save();

        $analysis->status = 'failed';
        $analysis->error_message = $errorMsg;
        $analysis->save();
        // transcript options waiting for this transcript fail with the same reason (a completed one is kept)
        TranscriptOptionService::afterTranscript($sourceCode, $logId, false, $errorMsg, $job->id);

        return [
            'success' => false,
            'job_id' => $job->id,
            'call_id' => $logId,
            'source_code' => $sourceCode,
            'customer_name' => $job->customer_name,
            'agent_name' => $job->agent_name,
            'phonenumber' => $job->phonenumber,
            'error' => $errorMsg,
            'timings' => ['queue_wait' => $queuedSeconds] + ($processResult['timings'] ?? []),
        ];
    }

    /**
     * Process an entire batch of transcription jobs synchronously.
     *
     * @param string $batchId
     * @return array
     */
    public function processBatch(string $batchId): array
    {
        $jobs = TranscriptionJob::where('batch_id', $batchId)
            ->whereIn('status', ['QUEUED', 'PENDING_CONFIRMATION'])
            ->get();

        $completed = 0;
        $failed = 0;
        $totalActualCost = 0.0000;
        $totalActualSeconds = 0;
        $results = [];

        foreach ($jobs as $job) {
            $res = $this->processSingleJob($job);
            if ($res['success']) {
                $completed++;
                $totalActualCost += (float)$res['actual_cost'];
                $totalActualSeconds += (int)$job->duration_seconds;
            } else {
                $failed++;
            }
            $results[] = $res;
        }

        return [
            'batch_id' => $batchId,
            'total' => count($jobs),
            'completed' => $completed,
            'failed' => $failed,
            'total_actual_cost' => round($totalActualCost, 2),
            'total_actual_cost_formatted' => '₹' . number_format($totalActualCost, 2),
            'total_billable_minutes' => round($totalActualSeconds / 60, 2),
            'results' => $results,
        ];
    }

    /**
     * Format seconds into human readable duration e.g. "38 min 42 sec".
     *
     * @param int $seconds
     * @return string
     */
    public function formatDuration(int $seconds): string
    {
        $m = floor($seconds / 60);
        $s = $seconds % 60;

        if ($m > 0) {
            return "{$m} min " . ($s > 0 ? "{$s} sec" : "");
        }
        return "{$s} sec";
    }

    /**
     * Get real-time status and billing totals for a batch.
     *
     * @param string $batchId
     * @return array
     */
    public function getBatchStatus(string $batchId): array
    {
        $jobs = TranscriptionJob::where('batch_id', $batchId)->get();
        if ($jobs->isEmpty()) {
            return [
                'status' => 'not_found',
                'batch_id' => $batchId,
                'total' => 0,
                'completed' => 0,
                'failed' => 0,
                'processing' => 0,
                'queued' => 0,
                'in_progress' => 0,
                'total_actual_cost' => 0.00,
                'total_actual_cost_formatted' => '₹0.00',
                'is_finished' => true,
            ];
        }

        // A job whose worker stopped (stalled) counts as failed, so the page stops waiting for it and offers Retry
        $stalledIds = $jobs->filter(fn ($j) => $j->isStalled())->pluck('id')->all();
        $total = $jobs->count();
        $completed = $jobs->where('status', 'COMPLETED')->count();
        $failed = $jobs->where('status', 'FAILED')->count() + count($stalledIds);
        $processing = $jobs->where('status', 'PROCESSING')->count() - count($stalledIds);
        $queued = $jobs->whereIn('status', ['QUEUED', 'PENDING_CONFIRMATION'])->count();
        $totalCost = (float)$jobs->where('status', 'COMPLETED')->sum('actual_cost');
        $inProgress = $processing + $queued;

        // A job still QUEUED after 45s means no queue worker is picking jobs up
        $stalled = $jobs->whereIn('status', ['QUEUED'])->filter(fn ($j) => $j->created_at && $j->created_at->lt(now()->subSeconds(45)))->count() > 0;

        return [
            'status' => 'success',
            'batch_id' => $batchId,
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'processing' => $processing,
            'queued' => $queued,
            'in_progress' => $inProgress,
            'queue_stalled' => $stalled,
            'total_actual_cost' => round($totalCost, 2),
            'total_actual_cost_formatted' => '₹' . number_format($totalCost, 2),
            'is_finished' => ($completed + $failed) >= $total,
            'results' => $jobs->map(function ($j) use ($stalledIds) {
                $sc = (int)$j->source_code;
                $logId = $sc == 0 ? (int)$j->call_id : (int)preg_replace('/\D/', '', (string)$j->recording_id);
                $stalled = in_array($j->id, $stalledIds, true);
                return [
                    'call_id' => $logId,
                    'source_code' => $sc,
                    'customer_name' => $j->customer_name,
                    'agent_name' => $j->agent_name,
                    'phonenumber' => $j->phonenumber,
                    'status' => $stalled ? 'FAILED' : $j->status,
                    'success' => ($j->status === 'COMPLETED'),
                    'error' => $stalled ? 'Transcription stopped because the background worker was restarted. Press Retry to run it again.' : $j->error_message,
                    'actual_cost' => $j->actual_cost,
                    'actual_cost_formatted' => '₹' . number_format($j->actual_cost, 2),
                ];
            })->values()->toArray(),
        ];
    }
}
