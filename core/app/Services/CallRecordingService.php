<?php

namespace App\Services;

use App\Jobs\DownloadRecordingJob;
use App\Models\CallLog;
use App\Models\DialerLog;
use App\Models\Recording;
use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallRecordingService
{
    /**
     * Process incoming telephony webhook / callback.
     *
     * @param array $payload
     * @param string|null $rawInput
     * @param Request|null $request
     * @return array
     */
    public function processWebhook(array $payload, ?string $rawInput = null, ?Request $request = null): array
    {
        try {
            $logContext = [
            'ip' => $request ? $request->ip() : 'internal',
            'payload' => $payload,
        ];
        Log::info('[TelephonyWebhook] Incoming webhook received', $logContext);

        // 1. Validate Secret / Authentication if provided or configured
        $configuredSecret = env('TELEPHONY_WEBHOOK_SECRET', 'ffcab6c009f568588842b54f15de6efb');
        $headerSecret = $request ? ($request->header('Secret-Key') ?? $request->header('X-Webhook-Secret') ?? $request->header('X-Api-Key')) : null;
        $querySecret = $request ? ($request->query('secret') ?? $request->input('secret')) : null;
        $providedSecret = $headerSecret ?: $querySecret;

        if (!empty($providedSecret) && !hash_equals($configuredSecret, (string)$providedSecret)) {
            Log::warning('[TelephonyWebhook] Unauthorized webhook attempt: secret mismatch', ['ip' => $request ? $request->ip() : 'unknown']);
            return [
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthorized: Invalid webhook secret'
            ];
        }

        // 2. Extract fields (case-insensitive & cross-gateway normalization)
        $refId = $payload['ref_id'] ?? $payload['REF_ID'] ?? $payload['reference_id'] ?? $payload['refId'] ?? null;
        $providerCallId = $payload['call_id'] ?? $payload['CALL_ID'] ?? $payload['CallId'] ?? $payload['uniqueid'] ?? $payload['unique_id'] ?? $payload['uuid'] ?? $refId;
        $phone = $payload['b_party_no'] ?? $payload['B_PARTY_NO'] ?? $payload['customer_number'] ?? $payload['phone_number'] ?? $payload['mobile'] ?? null;
        $recordingUrl = self::validRecordingUrl($payload['recording_url'] ?? $payload['RecordVoice'] ?? $payload['record_voice'] ?? $payload['record_url'] ?? $payload['audio_url'] ?? $payload['file_url'] ?? null);

        // Dial statuses
        $aDialStatus = strtolower(trim((string)($payload['A_DIAL_STATUS'] ?? $payload['a_dial_status'] ?? '')));
        $bDialStatus = strtolower(trim((string)($payload['B_DIAL_STATUS'] ?? $payload['b_dial_status'] ?? '')));
        $overallStatus = strtolower(trim((string)($payload['status'] ?? $payload['call_status'] ?? $payload['dial_status'] ?? $payload['disposition'] ?? '')));

        // Talk time and outcome from the provider record (customer leg = B party)
        $facts = self::providerCallFacts($payload);
        $duration = $facts['talk'];

        // Clean phone number
        if ($phone) {
            $phone = preg_replace('/[^0-9]/', '', (string)$phone);
            if (strlen($phone) == 12 && str_starts_with($phone, '91')) {
                $phone = substr($phone, 2);
            }
        }

        // 3. Connected = the CUSTOMER answered. An agent leg that answered while the customer did not
        //    (busy / no answer) is not a connected call and gets no recording row.
        if ($facts['outcome'] !== null) {
            $isConnected = $facts['outcome'] === 'completed';
            $isExplicitFailure = !$isConnected;
        } else {
            $isConnected = str_contains($overallStatus, 'connect') || str_contains($overallStatus, 'answer')
                || str_contains($overallStatus, 'success') || str_contains($overallStatus, 'completed');
            $isExplicitFailure = (bool)Helper::callStateFromDialStatus($overallStatus) && !$isConnected;
        }

        if ($isExplicitFailure && !$isConnected) {
            Log::info("[TelephonyWebhook] Call not connected (outcome: {$facts['outcome']}, B: {$bDialStatus}, A: {$aDialStatus}), no recording for call id: {$providerCallId}");
            $this->updateCallLogResponseOnly($refId, $providerCallId, $phone, $payload, $rawInput);

            return [
                'status' => 'ignored',
                'code' => 200,
                'message' => 'Call was busy, not answered, or failed. Recording not created.'
            ];
        }

        // Must have talk duration > 0 seconds UNLESS recording URL exists
        if ($duration <= 0 && empty($recordingUrl)) {
            Log::info("[TelephonyWebhook] Call has 0 talk time and no recording URL, skipping recording creation for call id: {$providerCallId}");
            $this->updateCallLogResponseOnly($refId, $providerCallId, $phone, $payload, $rawInput);

            return [
                'status' => 'ignored',
                'code' => 200,
                'message' => 'Zero talk time. Recording not created.'
            ];
        }

        // 4. Find matched call-log row (DialerLog or CallLog) - unique call ID first, in both tables
        [$dialerLog, $callLog] = self::findCallRecord($refId, $providerCallId, $phone);

        // If no provider call ID was given, fallback to unique reference ID or generated ID
        $effectiveCallId = $providerCallId ?: ($refId ?: ('call_' . ($dialerLog ? $dialerLog->id : ($callLog ? $callLog->id : uniqid()))));

        // 5. Update Call-Log row immediately
        $rawJson = is_string($rawInput) && !empty($rawInput) ? $rawInput : json_encode($payload);
        if ($dialerLog) {
            $dialerLog->click_recording_url = $recordingUrl ?: self::validRecordingUrl($dialerLog->click_recording_url);
            $dialerLog->click_response = $rawJson;
            if ($duration > 0) {
                $dialerLog->duration = Helper::formatDuration($duration);
                $dialerLog->time_taken = $duration;
            }
            $dialerLog->provider_call_id = $effectiveCallId;
            $dialerLog->call_state = 'completed';
            $dialerLog->answer_time = $dialerLog->answer_time ?: Carbon::now();   // the provider reports the customer leg connected
            if (empty($dialerLog->end_time)) {
                $dialerLog->end_time = Carbon::now();
            }
            $dialerLog->save();
        }

        if ($callLog) {
            $callLog->click_recording_url = $recordingUrl ?: self::validRecordingUrl($callLog->click_recording_url);
            $callLog->click_response = $rawJson;
            if ($duration > 0) {
                $callLog->duration = $duration;
            }
            $callLog->provider_call_id = $effectiveCallId;
            $callLog->call_state = 'completed';
            $callLog->answer_time = $callLog->answer_time ?: Carbon::now();   // the provider reports the customer leg connected
            if (empty($callLog->end_time)) {
                $callLog->end_time = Carbon::now();
            }
            $callLog->save();
        }

        // 6. Idempotent Recording record creation (Unique key on provider_call_id)
        // The row may already exist in "awaiting" state (created when the call ended), linked via recording_id.
        // The matched call's own recording wins; a provider-id match is used only if it is not another call's.
        $recording = null;
        if ($dialerLog && $dialerLog->recording_id) {
            $recording = Recording::find($dialerLog->recording_id);
        } elseif ($callLog && $callLog->recording_id) {
            $recording = Recording::find($callLog->recording_id);
        }
        if (!$recording) {
            $recording = self::ownRecordingByProviderId($effectiveCallId, $dialerLog ?: $callLog);
        }

        if ($recording) {
            if ($recording->download_status === 'deleted') {
                // An Admin deleted this recording: never download the audio again
                return ['status' => 'ignored', 'code' => 200, 'is_duplicate' => true, 'recording' => $recording, 'message' => 'Recording was deleted by an administrator.'];
            }
            Log::info("[TelephonyWebhook] Callback for existing recording #{$recording->id} (call ID: {$effectiveCallId}).");
            $changed = false;
            // The provider's call-end talk time is authoritative over our own estimates
            if ($duration > 0 && (int)$recording->duration !== $duration) {
                $recording->duration = $duration;
                $changed = true;
            }
            // Attach the real provider URL and (re)download unless we already hold the file
            if (!empty($recordingUrl) && $recording->download_status !== 'completed') {
                $recording->recording_url = $recordingUrl;
                $recording->download_status = 'pending';
                $changed = true;
            }
            if ($changed) {
                $recording->raw_payload = $rawJson;
                $recording->save();
            }
            if ($recording->download_status === 'pending' && !empty($recording->recording_url)) {
                DownloadRecordingJob::dispatch($recording->id);
            }
            return [
                'status' => 'success',
                'code' => 200,
                'is_duplicate' => true,
                'recording' => $recording,
                'message' => 'Idempotent duplicate callback acknowledged'
            ];
        }

        // Build new Recording record
        $recording = new Recording();
        $recording->provider_call_id = self::freeProviderCallId($effectiveCallId, $dialerLog ?: $callLog);
        $recording->call_id = $effectiveCallId;
        $recording->duration = $duration;
        $recording->recording_url = $recordingUrl;
        $recording->call_status = 'connected';
        $recording->download_status = !empty($recordingUrl) ? 'pending' : 'awaiting';
        $recording->download_attempts = 0;
        $recording->raw_payload = $rawJson;

        if ($dialerLog) {
            $recording->dialer_log_id = $dialerLog->id;
            $recording->source_code = 2;
            $recording->source_type = 'Dialing';
            $recording->user_id = $dialerLog->created_by;
            $recording->agent_name = $dialerLog->user->name ?? null;
            $recording->lead_id = $dialerLog->lead_id;
            $recording->customer_name = $dialerLog->lead->name ?? null;
            $recording->campaign_id = $dialerLog->campaign_id;
            $recording->campaign_name = $dialerLog->campaign->name ?? null;
            $recording->phone_number = $dialerLog->phone_number ?: $phone;
            $recording->call_date = $dialerLog->created_at ?: Carbon::now();

            $dialerLog->recording_id = $recording->id;
        } elseif ($callLog) {
            $recording->call_log_id = $callLog->id;
            $recording->source_code = 0;
            $recording->source_type = 'Campaign Calling';
            $recording->user_id = $callLog->created_by;
            $recording->agent_name = $callLog->user->name ?? null;
            $recording->lead_id = $callLog->lead_id;
            $recording->customer_name = $callLog->lead->name ?? null;
            $recording->campaign_id = $callLog->lead->campaign_id ?? null;
            $recording->campaign_name = $callLog->lead->campaign->name ?? null;
            $recording->phone_number = $callLog->phonenumber ?: $phone;
            $recording->call_date = $callLog->created_at ?: Carbon::now();

            $callLog->recording_id = $recording->id;
        } else {
            $recording->source_code = 2;
            $recording->source_type = 'Dialing';
            $recording->phone_number = $phone ?: 'Unknown';
            $recording->call_date = Carbon::now();
        }

        $recording->save();

        if ($dialerLog) {
            $dialerLog->recording_id = $recording->id;
            $dialerLog->save();
        }
        if ($callLog) {
            $callLog->recording_id = $recording->id;
            $callLog->save();
        }

        Log::info("[TelephonyWebhook] Recording created successfully #{$recording->id} for call ID: {$effectiveCallId}, phone: {$recording->phone_number}, duration: {$duration}s");

        // 7. Dispatch audio file download in background job
        if (!empty($recordingUrl)) {
            DownloadRecordingJob::dispatch($recording->id);
        }

            return [
                'status' => 'success',
                'code' => 200,
                'is_duplicate' => false,
                'recording' => $recording,
                'message' => 'Recording record created successfully'
            ];
        } catch (\Throwable $e) {
            Log::error('[TelephonyWebhook] Exception in processWebhook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed processing webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Helper to update response on call log when call is not connected.
     */
    protected function updateCallLogResponseOnly(?string $refId, ?string $providerCallId, ?string $phone, array $payload, ?string $rawInput): void
    {
        $rawJson = is_string($rawInput) && !empty($rawInput) ? $rawInput : json_encode($payload);

        [$dialerLog, $callLog] = self::findCallRecord($refId, $providerCallId, $phone);
        $log = $dialerLog ?: $callLog;
        if (!$log) {
            return;
        }

        $log->click_response = $rawJson;
        if ($providerCallId && empty($log->provider_call_id)) {
            $log->provider_call_id = $providerCallId;
        }

        // Final outcome from the provider record (customer leg decides)
        $facts = self::providerCallFacts($payload);
        $outcome = $facts['outcome'] ?? Helper::callStateFromDialStatus($payload['status'] ?? $payload['call_status'] ?? $payload['dial_status'] ?? $payload['disposition'] ?? null);
        if ($outcome && $outcome !== 'completed') {
            $log->call_state = $outcome;
            if (empty($log->end_time)) {
                $log->end_time = $facts['ended_at'] ?: Carbon::now();
            }
            // the customer never talked: no talk time
            if ($log instanceof DialerLog) {
                $log->time_taken = 0;
                $log->duration = '00:00';
            } else {
                $log->duration = 0;
            }
        }
        $log->save();
    }

    /**
     * Read a provider call record (Click2Call "Call End" CDR or a generic gateway callback).
     * Click2Call: A party = agent, B party = customer. The CUSTOMER leg decides the outcome and the talk time.
     *
     * @return array outcome ('completed'|'busy'|'no-answer'|'failed'|null when unknown), talk (customer talk
     *               seconds), ended (bool), customer_connected_at / ended_at (Carbon|null), agent_answered (bool|null)
     */
    public static function providerCallFacts(array $p): array
    {
        $t = function ($v) {
            if (empty($v)) return null;
            try { return Carbon::createFromFormat('dmYHis', (string)$v); } catch (\Throwable $e) { return null; }
        };
        $a = $p['A_DIAL_STATUS'] ?? $p['a_dial_status'] ?? null;
        $b = $p['B_DIAL_STATUS'] ?? $p['b_dial_status'] ?? null;
        $bConnected = $t($p['B_PARTY_CONNECTED_TIME'] ?? null);
        $bEnd = $t($p['B_PARTY_END_TIME'] ?? null);
        $aEnd = $t($p['A_PARTY_END_TIME'] ?? null);
        $agentState = Helper::callStateFromDialStatus($a);

        $outcome = null;
        if ($b !== null && trim((string)$b) !== '') {
            $outcome = Helper::callStateFromDialStatus($b);
        } elseif ($agentState !== null && $agentState !== 'completed') {
            $outcome = 'failed'; // the agent's own phone did not answer: the customer was never dialled
        } elseif ($bConnected) {
            $outcome = 'completed';
        }

        $talk = 0;
        if ($bConnected && $bEnd) {
            $talk = $bConnected->diffInSeconds($bEnd, true);
        } elseif (isset($p['talk_duration']) && is_numeric($p['talk_duration'])) {
            $talk = (int)$p['talk_duration'];
        } elseif (isset($p['billsec']) && is_numeric($p['billsec'])) {
            $talk = (int)$p['billsec'];
        } elseif ($b === null && isset($p['duration']) && is_numeric($p['duration'])) {
            $talk = (int)$p['duration']; // generic gateway without leg details
        }
        if ($outcome !== null && $outcome !== 'completed') {
            $talk = 0;
        }

        $eventType = strtolower((string)($p['EVENT_TYPE'] ?? $p['event_type'] ?? ''));
        $ended = str_contains($eventType, 'end') || str_contains($eventType, 'hangup')
            || $bEnd !== null || $aEnd !== null
            || ($outcome !== null && $outcome !== 'completed');

        return [
            'outcome' => $outcome,
            'talk' => max(0, (int)$talk),
            'ended' => $ended,
            'customer_connected_at' => $bConnected,
            'ended_at' => $bEnd ?: $aEnd,
            'agent_answered' => $agentState === null ? null : $agentState === 'completed',
            'disconnected_by' => $p['DISCONNECTED_BY'] ?? null,
        ];
    }

    /**
     * Find the call record a provider event belongs to. The unique call ID (our reference_id, sent to the
     * provider as ref_id) is checked in BOTH tables before any weaker match, so a campaign call's event can
     * never land on a manual call (or the reverse). Returns [DialerLog|null, CallLog|null].
     */
    public static function findCallRecord(?string $refId, ?string $providerCallId, ?string $phone): array
    {
        if (!empty($refId)) {
            if ($d = DialerLog::where('reference_id', $refId)->first()) return [$d, null];
            if ($c = CallLog::where('reference_id', $refId)->first()) return [null, $c];
        }
        if (!empty($providerCallId)) {
            if ($d = DialerLog::where('provider_call_id', $providerCallId)->orWhere('webrtc_call_id', $providerCallId)->first()) return [$d, null];
            if ($c = CallLog::where('provider_call_id', $providerCallId)->orWhere('webrtc_call_id', $providerCallId)->first()) return [null, $c];
        }
        if (!empty($refId)) {
            if ($d = DialerLog::where('uuid', 'like', $refId . '%')->first()) return [$d, null];
        }
        if (!empty($phone)) {
            // Last resort: the most recent call to this number in the last 24h, whichever table it is in
            $d = DialerLog::where('phone_number', $phone)->where('created_at', '>=', Carbon::now()->subHours(24))->orderBy('id', 'desc')->first();
            $c = CallLog::where('phonenumber', $phone)->where('created_at', '>=', Carbon::now()->subHours(24))->orderBy('id', 'desc')->first();
            if ($d && $c) {
                return $d->created_at >= $c->created_at ? [$d, null] : [null, $c];
            }
            return [$d, $c];
        }
        return [null, null];
    }

    /**
     * Statuses kept in recordings.download_status:
     *  awaiting    - call connected, waiting for the provider to deliver the recording
     *  pending     - provider URL known, download queued
     *  downloading - download in progress
     *  completed   - real audio file stored on disk
     *  failed      - download failed after retries (playback falls back to the provider URL)
     *  unavailable - provider never delivered a recording
     */
    public const AWAITING_TIMEOUT_MINUTES = 60;

    /**
     * Store or update the recording row for a call that has ENDED, or when real recording data arrives.
     * Never creates a row for a call that was not connected, and never attaches a placeholder file:
     * the row stays "awaiting" (shown as Processing) until the provider recording arrives.
     *
     * @param string $sourceType 'Campaign Calling' or 'Dialing'
     * @param mixed $callRecord CallLog or DialerLog instance
     * @param array $data recording_url, file_path, duration, provider_call_id, raw_payload, call_status
     */
    public static function storeOrUpdateRecording(string $sourceType, $callRecord, array $data = []): ?Recording
    {
        try {
            if (!$callRecord) {
                return null;
            }

            $recordingUrl = self::validRecordingUrl($data['recording_url'] ?? null)
                ?? self::validRecordingUrl($callRecord->click_recording_url ?? null);
            $filePath = self::realAudioPath($data['file_path'] ?? null);
            $duration = Helper::durationToSeconds($data['duration'] ?? null);
            if ($duration <= 0) {
                $duration = self::callRecordSeconds($callRecord);
            }

            $recording = null;
            if (!empty($callRecord->recording_id)) {
                $recording = Recording::find($callRecord->recording_id);
            }
            $providerCallId = $data['provider_call_id']
                ?? $callRecord->provider_call_id
                ?? $callRecord->reference_id
                ?? $callRecord->webrtc_call_id
                ?? null;
            if (!$recording && !empty($providerCallId)) {
                $recording = self::ownRecordingByProviderId($providerCallId, $callRecord);
            }
            if ($recording && $recording->download_status === 'deleted') {
                return $recording; // deleted by an Admin: keep it deleted
            }

            // A recording row only exists for a call that actually connected (talk time, or real audio).
            $notConnected = in_array(strtolower((string)($callRecord->call_state ?? '')), ['no-answer', 'no_answer', 'busy', 'failed', 'cancelled', 'rejected'], true);
            if (!$recording && $notConnected && !$recordingUrl && !$filePath) {
                return null;
            }
            $answered =!empty($callRecord->answer_time) || in_array(strtolower((string)($data['call_status'] ?? '')), ['answered', 'connected'], true);
            if (!$recording && $duration <= 0 && !$recordingUrl && !$filePath && !$answered) {
                return null;
            }

            if (!$recording) {
                $recording = new Recording();
                $prefix = ($sourceType === 'Campaign Calling') ? 'camp_' : 'dial_';
                $recording->provider_call_id = self::freeProviderCallId($providerCallId ?: ($prefix . $callRecord->id . '_' . uniqid()), $callRecord);
                $recording->call_id = $recording->provider_call_id;
                $recording->download_attempts = 0;
                $recording->download_status = 'awaiting';
            }

            if ($duration > 0) {
                $recording->duration = $duration;
            }
            if ($recordingUrl) {
                $recording->recording_url = $recordingUrl;
            }
            $becameReady = false;
            if ($filePath) {
                $becameReady = $recording->download_status !== 'completed';
                $recording->file_path = $filePath;
                $recording->file_size = @filesize($filePath) ?: null;
                $recording->download_status = 'completed';
            } elseif ($recordingUrl && in_array($recording->download_status, ['awaiting', 'unavailable', null, ''], true)) {
                $recording->download_status = 'pending';
            }

            $recording->call_status = $data['call_status'] ?? ($recording->call_status ?: 'connected');

            if ($sourceType === 'Campaign Calling') {
                $recording->source_code = 0;
                $recording->source_type = 'Campaign Calling';
                $recording->call_log_id = $callRecord->id;
                $recording->campaign_id = $callRecord->campaign_id ?? ($callRecord->lead->campaign_id ?? ($data['campaign_id'] ?? null));
                $recording->phone_number = $callRecord->phonenumber ?: ($data['phone_number'] ?? 'Unknown');
            } else {
                $recording->source_code = 2;
                $recording->source_type = 'Dialing';
                $recording->dialer_log_id = $callRecord->id;
                $recording->campaign_id = $callRecord->campaign_id; // manual dial: always null
                $recording->phone_number = $callRecord->phone_number ?: ($data['phone_number'] ?? 'Unknown');
            }
            $recording->user_id = $callRecord->created_by;
            $recording->agent_name = $callRecord->user->name ?? ($data['agent_name'] ?? null);
            $recording->lead_id = $callRecord->lead_id;
            $recording->customer_name = $callRecord->lead->name ?? ($data['customer_name'] ?? null);
            $recording->campaign_name = $recording->campaign_id ? (\App\Models\Campaign::find($recording->campaign_id)->name ?? null) : null;
            $recording->call_date = $callRecord->created_at ?: Carbon::now();

            if (!empty($data['raw_payload'])) {
                $recording->raw_payload = is_string($data['raw_payload']) ? $data['raw_payload'] : json_encode($data['raw_payload']);
            }

            $recording->save();

            if ($callRecord->recording_id != $recording->id) {
                $callRecord->recording_id = $recording->id;
                $callRecord->save();
            }

            Log::info("[CallRecordingService] Stored/updated recording #{$recording->id} ({$recording->download_status}) for {$sourceType} call #{$callRecord->id}");

            if ($recording->download_status === 'pending') {
                DownloadRecordingJob::dispatch($recording->id);
            }
            if ($becameReady) {
                self::autoTranscribe($recording);   // audio arrived as a local file: same automatic transcript as a download
            }

            return $recording;
        } catch (\Exception $e) {
            Log::error("[CallRecordingService] Failed to store/update recording: " . $e->getMessage(), [
                'sourceType' => $sourceType,
                'callRecord_id' => $callRecord->id ?? null,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Delete ONE call's recording (Recordings page). The call itself (call_logs / dialer_logs row, lead,
     * reports) is kept; only the audio file and the recording reference go. The recording row stays as a
     * "deleted" marker so the provider poller / callbacks never download the same audio again.
     *
     * File safety: only files inside the recordings folders are touched. The file is first renamed aside,
     * then the database is updated in a transaction, and only after commit is the file removed. If the
     * rename or the database update fails, nothing is changed.
     *
     * @param int $sourceCode 0 = campaign call (call_logs), 2 = manual call (dialer_logs), 3 = standalone recording
     * @return array ['ok' => bool, 'code' => int, 'message' => string]
     */
    public static function deleteRecording(int $sourceCode, int $id, ?int $userId = null): array
    {
        $callLog = $dialerLog = null;
        if ($sourceCode == 2) {
            $dialerLog = DialerLog::find($id);
            if (!$dialerLog) return ['ok' => false, 'code' => 404, 'message' => 'Recording not found.'];
            $recording = $dialerLog->recording_id ? Recording::find($dialerLog->recording_id) : null;
        } elseif ($sourceCode == 3) {
            $recording = Recording::whereNull('call_log_id')->whereNull('dialer_log_id')->find($id);
            if (!$recording) return ['ok' => false, 'code' => 404, 'message' => 'Recording not found.'];
        } else {
            $callLog = CallLog::find($id);
            if (!$callLog) return ['ok' => false, 'code' => 404, 'message' => 'Recording not found.'];
            $recording = $callLog->recording_id ? Recording::find($callLog->recording_id) : null;
        }
        if ($recording && $recording->download_status === 'deleted') {
            return ['ok' => false, 'code' => 404, 'message' => 'This recording has already been deleted.'];
        }

        // Audio files that belong to this recording only
        $details = (new \App\Services\TranscriptionBillingService())->getRecordingDetails($sourceCode, $id);
        $candidates = array_filter([
            $recording->file_path ?? null,
            $dialerLog->file_path ?? null,
            $details['audio_path'] ?? null,
        ]);
        $files = [];
        foreach ($candidates as $path) {
            $real = self::realAudioPath($path);
            if ($real && self::isInsideRecordingsFolder($real)) {
                $files[realpath($real)] = true;
            }
        }
        $files = array_keys($files);

        // 1. Move files aside (reversible). Abort without any change if that fails.
        $moved = [];
        foreach ($files as $file) {
            $aside = $file . '.deleting';
            if (!@rename($file, $aside)) {
                foreach ($moved as $orig => $tmp) { @rename($tmp, $orig); }
                Log::error("[RecordingDelete] Could not move {$file}; nothing deleted.");
                return ['ok' => false, 'code' => 500, 'message' => 'The audio file could not be deleted (file in use or no permission). Nothing was changed.'];
            }
            $moved[$file] = $aside;
        }

        // 2. Database: mark the recording deleted and clear the call's recording references only
        try {
            DB::transaction(function () use (&$recording, $callLog, $dialerLog, $sourceCode, $id) {
                if (!$recording) {
                    // Older call with a recording file but no recordings row: create the "deleted" marker
                    $owner = $dialerLog ?: $callLog;
                    $recording = new Recording();
                    $recording->provider_call_id = self::freeProviderCallId($owner->reference_id ?: ('del_' . $sourceCode . '_' . $id), $owner);
                    $recording->call_id = $owner->reference_id;
                    $recording->source_code = $sourceCode;
                    $recording->source_type = $sourceCode == 2 ? 'Dialing' : 'Campaign Calling';
                    $recording->dialer_log_id = $dialerLog->id ?? null;
                    $recording->call_log_id = $callLog->id ?? null;
                    $recording->user_id = $owner->created_by;
                    $recording->phone_number = $dialerLog->phone_number ?? $callLog->phonenumber ?? 'Unknown';
                    $recording->call_date = $owner->created_at;
                }
                $recording->download_status = 'deleted';
                $recording->file_path = null;
                $recording->file_size = null;
                $recording->recording_url = null;
                $recording->save();

                if ($dialerLog) {
                    $dialerLog->file_path = null;
                    $dialerLog->click_recording_url = null;
                    $dialerLog->recording_id = $recording->id;
                    $dialerLog->save();
                }
                if ($callLog) {
                    $callLog->recording = null;
                    $callLog->click_recording_url = null;
                    $callLog->recording_id = $recording->id;
                    $callLog->save();
                }
            });
        } catch (\Throwable $e) {
            foreach ($moved as $orig => $tmp) { @rename($tmp, $orig); }
            Log::error('[RecordingDelete] Database update failed, audio restored: ' . $e->getMessage());
            return ['ok' => false, 'code' => 500, 'message' => 'The recording could not be deleted. Nothing was changed.'];
        }

        // 3. Committed: remove the moved files for good
        foreach ($moved as $orig => $tmp) {
            if (!@unlink($tmp)) {
                Log::warning("[RecordingDelete] Recording #{$recording->id} deleted but leftover file could not be removed: {$tmp}");
            }
        }

        Log::info("AUDIT LOG: Call recording deleted by User ID {$userId}: source {$sourceCode} #{$id}, recording #{$recording->id}, files: " . json_encode(array_keys($moved)));
        return ['ok' => true, 'code' => 200, 'message' => 'Recording deleted successfully.'];
    }

    /**
     * Automatic transcript for a recording whose audio file is now ready, when "AI Transcript" (auto) is on in
     * Company Settings. Runs in the background (TranscribeRecordingJob); the recording stays playable meanwhile.
     * A recording that already has a transcript, or one queued / running / failed, is left alone
     * (failed ones are retried by hand with "Retry"), so the same audio is never sent to Sarvam twice.
     */
    public static function autoTranscribe(Recording $recording): ?string
    {
        try {
            // Company's Transcription settings (License Info): automatic on, a valid key, and the recording in scope
            // (Connected calls only = the call has talk time; All recordings; None = manual only)
            if ($recording->download_status !== 'completed' || !self::realAudioPath($recording->file_path)
                || !\App\Services\SarvamKeyService::autoTranscribeOn()
                || !\App\Services\SarvamKeyService::inAutoScope((int)$recording->duration)) {
                return null;
            }
            [$sc, $logId] = $recording->call_log_id ? [0, $recording->call_log_id]
                : ($recording->dialer_log_id ? [2, $recording->dialer_log_id] : [3, $recording->id]);
            if (\App\Models\CallAiAnalysis::findFor($sc, (int)$logId)) {
                return null;
            }
            return (new \App\Services\TranscriptionBillingService())->queueRecording($recording, null, false);
        } catch (\Throwable $e) {
            Log::warning("[AutoTranscript] Not queued for recording #{$recording->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Only audio inside the recordings storage folders may ever be deleted.
     */
    public static function isInsideRecordingsFolder(string $path): bool
    {
        $real = realpath($path);
        if (!$real || stripos(basename($real), 'leadger_demo_call') !== false) {
            return false;
        }
        $roots = array_filter([storage_path('app/recordings'), env('recordings_upload')]);
        foreach ($roots as $root) {
            $rootReal = realpath($root);
            if ($rootReal && stripos($real, rtrim($rootReal, '\\/') . DIRECTORY_SEPARATOR) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * A recording found by provider call id, but only if it belongs to this call (or to no call yet).
     * Never returns another call's recording, even if an old bug gave both the same id.
     */
    public static function ownRecordingByProviderId(?string $providerCallId, $callRecord): ?Recording
    {
        if (empty($providerCallId)) {
            return null;
        }
        $rec = Recording::where('provider_call_id', $providerCallId)->first();
        if (!$rec || !$callRecord) {
            return $rec;
        }
        $isDialer = $callRecord instanceof DialerLog;
        $ownerId = $isDialer ? $rec->dialer_log_id : $rec->call_log_id;
        $otherOwner = $isDialer ? $rec->call_log_id : $rec->dialer_log_id;
        if (($ownerId && (int)$ownerId !== (int)$callRecord->id) || (!$ownerId && $otherOwner)) {
            return null;
        }
        return $rec;
    }

    /**
     * recordings.provider_call_id is unique: if another call's row already uses this id, suffix it.
     */
    public static function freeProviderCallId(string $providerCallId, $callRecord): string
    {
        if (!Recording::where('provider_call_id', $providerCallId)->exists()) {
            return $providerCallId;
        }
        $suffix = $callRecord instanceof DialerLog ? '_d' : '_c';
        return $providerCallId . $suffix . ($callRecord->id ?? uniqid());
    }

    /**
     * Talk time in seconds stored on a CallLog (duration, int) or DialerLog (time_taken int / duration "mm:ss").
     */
    public static function callRecordSeconds($callRecord): int
    {
        if ($callRecord instanceof DialerLog) {
            $sec = (int)($callRecord->time_taken ?? 0);
            return $sec > 0 ? $sec : Helper::durationToSeconds($callRecord->duration ?? null);
        }
        return Helper::durationToSeconds($callRecord->duration ?? null);
    }

    /**
     * Return the URL only if it is a real http(s) recording URL from the provider.
     */
    public static function validRecordingUrl($url): ?string
    {
        if (!is_string($url)) {
            return null;
        }
        $url = trim($url);
        if ($url === '' || stripos($url, 'leadger_demo_call') !== false || !preg_match('#^https?://#i', $url)) {
            return null;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    /**
     * Return an absolute path for a real, non-empty audio file, or null. Placeholder files are rejected.
     */
    public static function realAudioPath($path): ?string
    {
        if (!is_string($path) || trim($path) === '' || stripos($path, 'leadger_demo_call') !== false) {
            return null;
        }
        foreach ([$path, public_path($path), storage_path('app/' . ltrim($path, '/\\'))] as $candidate) {
            if (@is_file($candidate) && @filesize($candidate) > 0) {
                return $candidate;
            }
        }
        return null;
    }

    /**
     * Path to the ffmpeg binary (FFMPEG_PATH, or the bundled core/ffmpeg.exe).
     */
    public static function ffmpegPath(): ?string
    {
        // FFMPEG_PATH, the copy shipped in core/ (Windows), or one installed on the server (Linux: apt install ffmpeg)
        foreach (array_filter([env('FFMPEG_PATH'), base_path('ffmpeg.exe'), base_path('ffmpeg'), '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg']) as $c) {
            if (is_file($c)) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Run ffmpeg with an argument array (no shell, so Windows quoting cannot mangle paths or "%03d").
     * Returns [exitCode, combinedOutput].
     */
    public static function runFfmpeg(array $args): array
    {
        $ffmpeg = self::ffmpegPath();
        if (!$ffmpeg) {
            return [127, 'ffmpeg not found'];
        }
        $proc = proc_open(array_merge([$ffmpeg], $args), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) {
            return [1, 'could not start ffmpeg'];
        }
        $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($proc), $out];
    }

    /**
     * Read the real audio length in seconds from the file itself (ffmpeg, or the WAV header as fallback).
     */
    public static function probeAudioDuration(string $path): int
    {
        if (!is_file($path)) {
            return 0;
        }
        if (self::ffmpegPath()) {
            [, $out] = self::runFfmpeg(['-hide_banner', '-i', $path]);
            if ($out && preg_match('/Duration:\s*(\d+):(\d{2}):(\d{2}(?:\.\d+)?)/', $out, $m)) {
                return (int)round((int)$m[1] * 3600 + (int)$m[2] * 60 + (float)$m[3]);
            }
        }
        $fh = @fopen($path, 'rb');
        if ($fh) {
            $header = fread($fh, 4096);
            fclose($fh);
            if (substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WAVE') {
                $byteRate = unpack('V', substr($header, 28, 4))[1] ?? 0;
                $dataPos = strpos($header, 'data');
                if ($byteRate > 0 && $dataPos !== false) {
                    $dataSize = unpack('V', substr($header, $dataPos + 4, 4))[1] ?? 0;
                    return (int)round($dataSize / $byteRate);
                }
            }
        }
        return 0;
    }

    /**
     * Write a known talk time back to the call record, without overwriting an existing real value.
     */
    public static function syncCallRecordDuration(Recording $recording, int $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }
        if ($recording->dialer_log_id && ($log = DialerLog::find($recording->dialer_log_id)) && self::callRecordSeconds($log) <= 0) {
            $log->time_taken = $seconds;
            $log->duration = Helper::formatDuration($seconds);
            $log->save();
        }
        if ($recording->call_log_id && ($log = CallLog::find($recording->call_log_id)) && self::callRecordSeconds($log) <= 0) {
            $log->duration = $seconds;
            $log->save();
        }
    }
}
