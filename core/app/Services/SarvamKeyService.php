<?php

namespace App\Services;

use App\Jobs\TranscribeRecordingJob;
use App\Models\CallAiAnalysis;
use App\Models\CompanySarvamSetting;
use App\Models\TranscriptionJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The current company's Sarvam AI key: which key transcription uses, testing a key, status changes, the audit
 * trail and the usage numbers. Everything reads / writes the current company's own database only.
 * The key itself is never logged, returned or put in a queue payload.
 */
class SarvamKeyService
{
    /** Transcription jobs paused because the company's key is missing or invalid. */
    public const WAITING_KEY = 'WAITING_KEY';

    /** The key this company's transcription uses (null = do not call Sarvam). */
    public static function currentKey(): ?string
    {
        $settings = self::settingsOrNull();
        if ($settings && $settings->usable()) {
            return $settings->apiKey();
        }
        // Optional global fallback, only when explicitly allowed (default off)
        if ((!$settings || !$settings->hasKey()) && config('services.sarvam.allow_global_key')) {
            return config('services.sarvam.api_key') ?: null;
        }
        return null;
    }

    public static function language(): string
    {
        $lang = optional(self::settingsOrNull())->transcription_language;
        return isset(CompanySarvamSetting::LANGUAGES[$lang]) ? $lang : 'unknown';
    }

    /** Automatic transcription: switched on, a scope other than "None", and a usable key. */
    public static function autoTranscribeOn(): bool
    {
        $s = self::settingsOrNull();
        return $s && $s->auto_transcribe && $s->transcription_scope !== 'none' && self::currentKey() !== null;
    }

    /** Whether a new recording is in the automatic scope (Connected calls only = it has talk time). */
    public static function inAutoScope(int $talkSeconds): bool
    {
        $s = self::settingsOrNull();
        if (!$s || $s->transcription_scope === 'none') return false;
        return $s->transcription_scope === 'all' || $talkSeconds > 0;
    }

    public static function settingsOrNull(): ?CompanySarvamSetting
    {
        try {
            return Schema::hasTable('company_sarvam_settings') ? CompanySarvamSetting::current() : null;
        } catch (\Throwable $e) {
            Log::warning('[SarvamKey] settings not readable: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check a key with the cheapest authenticated Sarvam request: the status of a job id that does not exist.
     * Sarvam answers 400/404 for a valid key and 401/403 for an invalid one; nothing is processed or billed.
     *
     * @return array{state: string, message: string, http: int}  state: valid | invalid | rate_limited | unreachable
     */
    public static function test(string $key): array
    {
        $base = rtrim(config('services.sarvam.base_url') ?: 'https://api.sarvam.ai', '/');
        $probeId = date('Ymd') . '_00000000-0000-0000-0000-000000000000';
        try {
            $request = Http::timeout(10)->connectTimeout(10)->acceptJson()->withHeaders(['api-subscription-key' => $key]);
            if (is_file(public_path('ssl/cacert.pem'))) {
                $request = $request->withOptions(['verify' => public_path('ssl/cacert.pem')]);
            }
            $code = $request->get("{$base}/speech-to-text/job/v1/{$probeId}/status")->status();
        } catch (\Throwable $e) {
            return ['state' => 'unreachable', 'message' => 'Could not reach Sarvam. Try again', 'http' => 0];
        }
        if (in_array($code, [401, 403], true)) {
            return ['state' => 'invalid', 'message' => 'Key is invalid', 'http' => $code];
        }
        if ($code === 429) {
            return ['state' => 'rate_limited', 'message' => 'Quota or rate limit reached. Try again later', 'http' => $code];
        }
        if ($code >= 500 || $code === 0) {
            return ['state' => 'unreachable', 'message' => 'Could not reach Sarvam. Try again', 'http' => $code];
        }
        return ['state' => 'valid', 'message' => 'Key verified', 'http' => $code];   // 2xx / 400 / 404 / 422: authenticated
    }

    /**
     * A job got 401/403 from Sarvam: the key is marked invalid (auto transcription stops), and queued transcripts
     * wait for a new key instead of failing.
     */
    public static function markInvalid(string $reason = 'Sarvam rejected the key'): void
    {
        $settings = self::settingsOrNull();
        if (!$settings || !$settings->hasKey()) return;
        if ($settings->sarvam_key_status !== CompanySarvamSetting::STATUS_INVALID) {
            $settings->forceFill(['sarvam_key_status' => CompanySarvamSetting::STATUS_INVALID])->save();
            self::audit('invalidated', $settings->sarvam_key_last4, mb_substr($reason, 0, 200), null);
            Log::warning('[SarvamKey] key ****' . $settings->sarvam_key_last4 . ' marked invalid; transcription paused');
        }
        self::pauseQueued();
    }

    /** Queued (not started) transcripts wait for a key. */
    public static function pauseQueued(): int
    {
        if (!Schema::hasTable('transcription_jobs')) return 0;
        $jobs = TranscriptionJob::where('status', 'QUEUED')->get();
        foreach ($jobs as $job) {
            self::markWaiting($job);
        }
        return $jobs->count();
    }

    /** One transcript waits for a valid key (not a failure). */
    public static function markWaiting(TranscriptionJob $job): void
    {
        $job->status = self::WAITING_KEY;
        $job->error_message = 'Waiting for a valid Sarvam API key';
        $job->save();
        [$sc, $logId] = self::analysisKey($job);
        if ($analysis = CallAiAnalysis::findFor($sc, $logId)) {
            $analysis->status = 'waiting_key';
            $analysis->error_message = 'Waiting for a valid Sarvam API key';
            $analysis->save();
        }
    }

    /** A valid key was saved: transcripts that were waiting for it are queued again. */
    public static function resumeWaiting(): int
    {
        if (!Schema::hasTable('transcription_jobs')) return 0;
        $jobs = TranscriptionJob::where('status', self::WAITING_KEY)->get();
        foreach ($jobs as $job) {
            $job->status = 'QUEUED';
            $job->error_message = null;
            $job->save();
            [$sc, $logId] = self::analysisKey($job);
            if ($analysis = CallAiAnalysis::findFor($sc, $logId)) {
                $analysis->status = 'pending';
                $analysis->error_message = null;
                $analysis->save();
            }
            TranscribeRecordingJob::dispatch((int)$job->id);
        }
        return $jobs->count();
    }

    public static function analysisKey(TranscriptionJob $job): array
    {
        $sc = (int)$job->source_code;
        $logId = $sc == 0 ? (int)$job->call_id : (int)preg_replace('/\D/', '', (string)$job->recording_id);
        if ($sc != 0 && $logId <= 0) $logId = (int)$job->call_id;
        return [$sc, $logId];
    }

    /** This month's transcription usage of the company (calls transcribed, audio minutes, failed). */
    public static function usage(): array
    {
        if (!Schema::hasTable('transcription_jobs')) {
            return ['transcribed' => 0, 'minutes' => 0, 'failed' => 0];
        }
        $start = now()->startOfMonth();
        $done = TranscriptionJob::where('status', 'COMPLETED')->where('completed_at', '>=', $start);
        return [
            'transcribed' => (clone $done)->count(),
            'minutes' => (int)round(((int)(clone $done)->sum('duration_seconds')) / 60),
            'failed' => TranscriptionJob::where('status', 'FAILED')->where('updated_at', '>=', $start)->count(),
        ];
    }

    /** Audit trail entry: who did what to the key, when. Never the key (only its last 4 characters). */
    public static function audit(string $action, ?string $last4, ?string $detail = null, $user = false): void
    {
        try {
            if (!Schema::hasTable('sarvam_key_audit_logs')) return;
            $user = $user === false ? Auth::user() : $user;
            DB::table('sarvam_key_audit_logs')->insert([
                'user_id' => $user->id ?? null,
                'user_name' => $user->name ?? 'System',
                'action' => $action,
                'key_last4' => $last4,
                'detail' => $detail !== null ? mb_substr($detail, 0, 255) : null,
                'ip' => request() ? request()->ip() : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[SarvamKey] audit not written: ' . $e->getMessage());
        }
    }

    /** The message shown where a transcript cannot run because no key is set up. */
    public static function notSetUpMessage($user = null): string
    {
        $user = $user ?: Auth::user();
        return optional(optional($user)->permissionsGroup)->name === 'Admin'
            ? 'Add your Sarvam API key in Configuration → Profile → License Info to run transcripts'
            : 'Transcription is not set up. Please ask your admin.';
    }
}
