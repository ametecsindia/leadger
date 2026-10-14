<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use App\Models\DialerLog;
use App\Models\Recording;
use App\Services\CallRecordingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchPendingRecordingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recordings:fetch-pending {--hours=24 : Number of hours back to check} {--sweep : Also ask the provider about recent calls that have no recording row} {--sweep-minutes=60 : How far back the sweep looks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ask the call provider for recordings of connected calls that are still "Processing", and give up after a timeout.';

    /**
     * Execute the console command.
     *
     * @param CallRecordingService $recordingService
     * @return int
     */
    public function handle(CallRecordingService $recordingService)
    {
        $hours = (int)$this->option('hours') ?: 24;
        $since = Carbon::now()->subHours($hours);

        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $fullResponse = json_decode($settings->full_response ?? '{}', true);
        $crmId = $fullResponse['data']['crm_id'] ?? '';

        $attachedCount = 0;
        $expiredCount = 0;

        // Recordings waiting for the provider (row created when the call ended, no audio yet)
        $waiting = Recording::where('download_status', 'awaiting')
            ->where('created_at', '>=', $since)
            ->get();

        $this->info("Found {$waiting->count()} recordings awaiting the provider.");

        foreach ($waiting as $recording) {
            $log = $recording->dialer_log_id ? DialerLog::find($recording->dialer_log_id)
                : ($recording->call_log_id ? CallLog::find($recording->call_log_id) : null);

            $refId = $log->reference_id ?? null;
            if (!$refId && $log instanceof DialerLog) {
                $refId = $log->uuid;
            }
            if (!$refId && $log instanceof CallLog && !empty($log->lead_id) && !empty($crmId)) {
                $refId = $log->lead_id . '_M' . $log->id . '_' . $crmId . '_' . $log->created_by . '_' . ($log->verified ?? 1);
            }

            $providerData = $refId ? $this->queryProviderForRecording($refId, $log->vmn_used ?? null, $crmId) : null;
            if ($providerData && !empty($providerData['recording_url'])) {
                $res = $recordingService->processWebhook($providerData);
                if (($res['status'] ?? '') === 'success') {
                    $attachedCount++;
                    $this->info("Attached provider recording to recording #{$recording->id}");
                }
                continue;
            }

            // Retry window over: stop showing "Processing" forever
            if ($recording->created_at && $recording->created_at->lt(Carbon::now()->subMinutes(CallRecordingService::AWAITING_TIMEOUT_MINUTES))) {
                $recording->download_status = 'unavailable';
                $recording->save();
                $expiredCount++;
                Log::warning("[FetchPendingRecordings] Provider did not deliver a recording for recording #{$recording->id} (ref: {$refId}) within " . CallRecordingService::AWAITING_TIMEOUT_MINUTES . " minutes.");
            }
        }

        // Downloads whose queued job was lost (e.g. no queue worker was running): queue them again.
        // DownloadRecordingJob skips recordings that already have their file, so a duplicate job is harmless.
        $stuck = Recording::whereIn('download_status', ['pending', 'downloading'])
            ->whereNotNull('recording_url')
            ->where('created_at', '>=', $since)
            ->where('updated_at', '<=', Carbon::now()->subMinutes(3))
            ->get();
        foreach ($stuck as $recording) {
            $recording->touch();
            \App\Jobs\DownloadRecordingJob::dispatch($recording->id);
            $this->info("Re-queued stuck download for recording #{$recording->id}");
        }

        // Ready recordings that never got their automatic transcript (auto transcript on; none queued, running,
        // completed or failed for them yet). Failed transcripts are only retried by hand.
        $transcribed = 0;
        Recording::where('download_status', 'completed')->whereNotNull('file_path')
            ->where('created_at', '>=', $since)->orderByDesc('id')->limit(20)->get()
            ->each(function ($recording) use (&$transcribed) {
                if (CallRecordingService::autoTranscribe($recording)) {
                    $transcribed++;
                }
            });

        // Sweep: calls of the last hour with no recording row at all. The browser's call state is not
        // reliable (calls marked "no-answer" have turned out to be connected and recorded), so ask the provider.
        if ($this->option('sweep')) {
            // Retry downloads that failed (e.g. provider unreachable) so the local copy is recovered automatically
            $retry = Recording::where('download_status', 'failed')
                ->whereNull('file_path')->whereNotNull('recording_url')
                ->where('download_attempts', '<', 20)
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->where('updated_at', '<=', Carbon::now()->subMinutes(10))
                ->get();
            foreach ($retry as $recording) {
                $recording->download_status = 'pending';
                $recording->save();
                \App\Jobs\DownloadRecordingJob::dispatch($recording->id);
                $this->info("Retrying download for recording #{$recording->id}");
            }

            $sweepSince = Carbon::now()->subMinutes(max(1, (int)$this->option('sweep-minutes')));
            $candidates = DialerLog::whereNull('recording_id')->where('created_at', '>=', $sweepSince)
                ->where(fn ($q) => $q->whereNotNull('reference_id')->orWhereNotNull('uuid'))->get()
                ->concat(CallLog::whereNull('recording_id')->where('created_at', '>=', $sweepSince)->whereNotNull('reference_id')->get());

            foreach ($candidates as $log) {
                $refId = $log->reference_id ?: ($log->uuid ?? null);
                $providerData = $refId ? $this->queryProviderForRecording($refId, $log->vmn_used ?? null, $crmId) : null;
                if ($providerData && !empty($providerData['recording_url'])) {
                    $res = $recordingService->processWebhook($providerData);
                    if (($res['status'] ?? '') === 'success') {
                        $attachedCount++;
                        $this->info('Attached provider recording to ' . class_basename($log) . " #{$log->id}");
                    }
                }
            }
        }

        $this->info("Completed. Attached {$attachedCount}, marked {$expiredCount} unavailable, re-queued {$stuck->count()} downloads, queued {$transcribed} transcripts.");
        if ($attachedCount || $expiredCount || $stuck->count() || $transcribed) {
            Log::info("[FetchPendingRecordings] Attached: {$attachedCount}, unavailable: {$expiredCount}, re-queued downloads: {$stuck->count()}, transcripts queued: {$transcribed}");
        }

        return Command::SUCCESS;
    }

    /**
     * Optional provider fetcher callback for testing/custom provider adapters.
     *
     * @var callable|null
     */
    public static $providerFetcher = null;

    /**
     * Query provider gateway API for call recording details.
     */
    protected function queryProviderForRecording(string $refId, ?string $vmn, ?string $crmId): ?array
    {
        if (static::$providerFetcher && is_callable(static::$providerFetcher)) {
            return call_user_func(static::$providerFetcher, $refId, $vmn, $crmId);
        }

        $opts = [
            "ssl" => ["verify_peer" => false, "verify_peer_name" => false],
            "http" => [
                "method" => "GET",
                "header" => "Secret-Key: ffcab6c009f568588842b54f15de6efb\r\n",
                "timeout" => 5
            ]
        ];

        try {
            $vmnParam = $vmn ?: 'default';
            $url = "https://obd.ametecsindia.com/admin/click2call_data/{$refId}/{$vmnParam}";
            $context = stream_context_create($opts);
            $raw = @file_get_contents($url, false, $context);

            if ($raw) {
                $json = json_decode($raw, true);
                if (isset($json['status']) && $json['status'] == 200 && isset($json['data'])) {
                    $data = $json['data'];
                    $recUrl = CallRecordingService::validRecordingUrl($data['recording_url'] ?? null);
                    if ($recUrl) {
                        $respObj = is_array($data['response'] ?? null) ? $data['response'] : json_decode($data['response'] ?? '{}', true);
                        $payload = is_array($respObj) ? $respObj : [];
                        $payload['ref_id'] = $refId;
                        $payload['call_id'] = $refId;
                        $payload['recording_url'] = $recUrl;
                        $payload['status'] = 'connected';
                        // Real talk time only (provider duration / billsec / connected->end times in the response)
                        $talk = $data['duration'] ?? ($respObj['DURATION'] ?? ($respObj['BILLSEC'] ?? null));
                        if (is_numeric($talk)) {
                            $payload['talk_duration'] = (int)$talk;
                        }
                        return $payload;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("[FetchPendingRecordings] Gateway poll error for {$refId}: " . $e->getMessage());
        }

        return null;
    }
}
