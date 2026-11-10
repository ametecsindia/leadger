<?php

namespace App\Services\Click2Call;

use App\Jobs\C2c\DownloadRecording;
use App\Models\C2c\Call;
use Carbon\Carbon;

/**
 * Turns a provider call record (webhook payload or click2call_data CDR) into the call's state.
 * One function for the webhook, the live status poll and the sweep, so all three agree (idempotent).
 *
 * LeadGER: Helper::callStateFromDialStatus, CallRecordingService::providerCallFacts and ::processWebhook.
 *  - "connected" = the CUSTOMER leg answered; an HTTP 200 on the start request is never "connected"
 *  - busy / no answer / failed: state saved, no recording
 *  - completed with talk time (or a recording URL): recording downloaded
 */
class CallResult
{
    /** Provider dial status text -> busy | no-answer | failed | completed | null (unknown). */
    public static function state(?string $dialStatus): ?string
    {
        $s = preg_replace('/[^a-z]/', '', strtolower((string) $dialStatus));
        if ($s === '') return null;
        if (str_contains($s, 'busy')) return 'busy';
        foreach (['noanswer', 'unanswer', 'notanswered', 'missed'] as $k) if (str_contains($s, $k)) return 'no-answer';
        foreach (['fail', 'cancel', 'reject', 'congestion', 'chanunavail'] as $k) if (str_contains($s, $k)) return 'failed';
        foreach (['answer', 'connect', 'completed', 'success'] as $k) if (str_contains($s, $k)) return 'completed';
        return null;
    }

    /**
     * outcome: busy|no-answer|failed|completed|null, talk: seconds, ended: bool, connected_at, ended_at,
     * agent_answered: bool, agent_failed: bool (the agent's phone did not answer: the customer was never dialled)
     */
    public static function facts(array $p): array
    {
        $get = fn (string $k) => $p[$k] ?? $p[strtolower($k)] ?? null;
        $a = self::state($get('A_DIAL_STATUS'));
        $b = self::state($get('B_DIAL_STATUS'));
        $time = function (string $k) use ($get) {
            $v = $get($k);
            if (!$v) return null;
            try { return Carbon::createFromFormat('dmYHis', (string) $v, config('click2call.timezone')); } catch (\Throwable $e) { return null; }
        };
        $connected = $time('B_PARTY_CONNECTED_TIME');
        $bEnd = $time('B_PARTY_END_TIME');
        $aEnd = $time('A_PARTY_END_TIME');

        $agentFailed = $b === null && $a !== null && $a !== 'completed';
        $outcome = $b ?? ($agentFailed ? 'failed' : ($connected ? 'completed' : null));

        if ($connected && $bEnd && $bEnd->gte($connected)) {
            $rawTalk = $bEnd->diffInSeconds($connected, true);
        } else {
            $rawTalk = (int) ($p['talk_duration'] ?? $p['billsec'] ?? $p['BILLSEC'] ?? ($b === null ? ($p['duration'] ?? $p['DURATION'] ?? 0) : 0));
        }
        $talk = $outcome === 'completed' ? $rawTalk : 0;

        $event = strtolower((string) $get('EVENT_TYPE'));
        $ended = str_contains($event, 'end') || str_contains($event, 'hangup') || $bEnd || $aEnd
            || ($outcome !== null && $outcome !== 'completed');

        return [
            'outcome' => $outcome, 'talk' => max(0, (int) $talk), 'raw_talk' => max(0, (int) $rawTalk), 'ended' => (bool) $ended,
            'connected_at' => $connected, 'ended_at' => $bEnd ?? $aEnd,
            'agent_answered' => $a === 'completed', 'agent_failed' => $agentFailed,
            'disconnected_by' => $get('DISCONNECTED_BY'),
        ];
    }

    /** The recording URL of a payload (several provider spellings, as LeadGER accepts). */
    public static function recordingUrl(array $p): ?string
    {
        foreach (['recording_url', 'RecordVoice', 'record_voice', 'record_url', 'audio_url', 'file_url'] as $k) {
            $v = $p[$k] ?? null;
            if (is_string($v) && preg_match('#^https?://#i', $v)) return $v;
        }
        return null;
    }

    /**
     * Apply a provider record to the call. The same record twice changes nothing.
     * $final: the record is the provider's end-of-call result (the webhook). As in LeadGER's processWebhook, a final
     * record with talk time or a recording but no dial status is a completed call.
     */
    public function apply(Call $call, array $payload, ?string $recordingUrl = null, bool $final = false): Call
    {
        $f = self::facts($payload);
        $recordingUrl = $recordingUrl ?? self::recordingUrl($payload);
        if ($final && $f['outcome'] === null && ($f['raw_talk'] > 0 || $recordingUrl)) {
            $f['outcome'] = 'completed';
            $f['talk'] = $f['raw_talk'];
        }
        if ($final && $f['outcome'] !== null) {
            $f['ended'] = true;
        }
        $call->cdr = self::withoutSecrets($payload);
        if ($f['connected_at'] && !$call->answered_at) {
            $call->answered_at = $f['connected_at'];
        }

        if ($f['ended']) {
            if (!$call->isFinal() || $call->call_state === 'completed') {
                $call->call_state = $f['outcome'] ?? 'failed';
                $call->ended_at = $call->ended_at ?? ($f['ended_at'] ?? now());
                $call->talk_seconds = $f['talk'];
                if ($f['agent_failed']) {
                    $call->error = 'Your phone did not answer, the customer was not dialled';
                }
            }
            // a recording only for a connected call with talk time (or one the provider recorded anyway)
            if ($call->call_state === 'completed' && ($f['talk'] > 0 || $recordingUrl) && !$call->recording_path) {
                if ($recordingUrl) {
                    if ($call->recording_url !== $recordingUrl || in_array($call->recording_status, [null, 'awaiting', 'failed'], true)) {
                        $call->recording_url = $recordingUrl;
                        $call->recording_status = 'pending';
                        $call->save();
                        DownloadRecording::dispatch($call->id);
                        return $call;
                    }
                } elseif ($call->recording_status === null) {
                    $call->recording_status = 'awaiting';   // the sweep asks the gateway again later
                }
            }
        } elseif (!$call->isFinal()) {
            $call->call_state = $f['connected_at'] ? 'connected' : ($f['agent_answered'] ? 'ringing' : $call->call_state);
        }
        $call->save();
        return $call;
    }

    /** Never store a secret that a provider might echo back. */
    public static function withoutSecrets(array $p): array
    {
        $out = [];
        foreach ($p as $k => $v) {
            if (is_string($k) && preg_match('/secret|token|password|api[_-]?key|signature|authorization/i', $k)) continue;
            $out[$k] = is_array($v) ? self::withoutSecrets($v) : $v;
        }
        return $out;
    }
}
