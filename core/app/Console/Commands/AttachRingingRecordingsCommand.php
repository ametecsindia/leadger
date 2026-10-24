<?php

namespace App\Console\Commands;

use App\Http\Controllers\Dashboard\RecordingsController;
use App\Models\CallLog;
use App\Models\DialerLog;
use App\Services\CallRecordingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * One-time catch-up: unanswered calls (no-answer / busy / failed / cancelled) whose stored provider record has a
 * recording link (the ringing, e.g. agent answered and the customer did not) but no recording row yet, from before
 * such recordings were kept. Uses the provider record already saved on the call; nothing is invented, existing
 * recordings are not touched. --dry-run only counts.
 */
class AttachRingingRecordingsCommand extends Command
{
    protected $signature = 'recordings:attach-ringing {--days=30 : How far back to look} {--dry-run : Only count, change nothing}';
    protected $description = 'Keep the ringing recording of unanswered calls that the provider recorded';

    public function handle(CallRecordingService $service): int
    {
        $since = Carbon::now()->subDays(max(1, (int)$this->option('days')));
        $found = $attached = 0;
        foreach ([DialerLog::class, CallLog::class] as $model) {
            $model::whereNull('recording_id')->whereIn('call_state', RecordingsController::NOT_ANSWERED_STATES)
                ->where('created_at', '>=', $since)->whereNotNull('click_response')
                ->orderBy('id')->chunkById(200, function ($logs) use ($service, &$found, &$attached) {
                    foreach ($logs as $log) {
                        $payload = is_array($log->click_response) ? $log->click_response : json_decode((string)$log->click_response, true);
                        if (!is_array($payload) || !CallRecordingService::validRecordingUrl($payload['recording_url'] ?? $payload['RecordVoice'] ?? null)) {
                            continue;
                        }
                        $found++;
                        if ($this->option('dry-run')) {
                            continue;
                        }
                        $payload['ref_id'] = $payload['ref_id'] ?? $payload['REF_ID'] ?? $log->reference_id;
                        if ((($service->processWebhook($payload)['status'] ?? '') === 'success') && $log->fresh()->recording_id) {
                            $attached++;
                        }
                    }
                });
        }
        $this->info($this->option('dry-run')
            ? "Unanswered calls with a ringing recording to keep: {$found}."
            : "Ringing recordings kept: {$attached} of {$found} unanswered calls (downloads queued).");
        return self::SUCCESS;
    }
}
