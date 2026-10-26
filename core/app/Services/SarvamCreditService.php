<?php

namespace App\Services;

use App\Models\CompanySarvamSetting;
use App\Models\TranscriptionJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Sarvam credits on Configuration > Profile > Licence Info (the Sarvam key card).
 *
 * Provider side: Sarvam has no documented API for the credit balance or spend (docs.sarvam.ai/api/platform/billing,
 * checked Oct 2026: the balance is "Credits Left" under Settings > Billing of the Sarvam dashboard only). So Total
 * Credits and Remaining Credits are "Not available from provider" - never invented. What Sarvam does tell an API
 * call: the key works, or 402 = the organisation is out of credits. Refresh Credits asks that with the existing
 * free key check (SarvamKeyService::test, nothing processed or billed).
 *
 * LeadGER side (Tracked Usage, not provider billing): every transcription request is a transcription_jobs row
 * (request id, Sarvam request id, company, user, recording, audio length, status, dates, estimated amount, tracked
 * amount = real audio minutes x the configured rate, currency). The same rows feed Reports > Transcript Report.
 * Cache keys use the company's own cache store (multi-company), so companies never share a value.
 */
class SarvamCreditService
{
    private const CACHE_KEY = 'sarvam_credit_check';
    private const LOCK_KEY = 'sarvam_credit_refresh';
    public const DASHBOARD_URL = 'https://dashboard.sarvam.ai';

    /** Everything the three cards and the note show. */
    public static function summary(): array
    {
        return [
            // Sarvam does not expose these through an API
            'total' => null,
            'remaining' => null,
            'provider_note' => 'Not available from provider',
            'dashboard_url' => self::DASHBOARD_URL,
            'provider' => self::lastCheck(),
            'tracked' => self::tracked(),
        ];
    }

    /** Tracked Usage of this company: all time and this month (completed transcripts), and the pending estimate. */
    public static function tracked(): array
    {
        $empty = ['all_time' => 0.0, 'this_month' => 0.0, 'transcripts' => 0, 'minutes' => 0, 'pending_estimate' => 0.0,
            'currency' => self::currency(), 'last_completed_at' => null];
        if (!Schema::hasTable('transcription_jobs')) {
            return $empty;
        }
        $done = TranscriptionJob::where('status', 'COMPLETED');
        $last = (clone $done)->max('completed_at');
        return [
            'all_time' => round((float)(clone $done)->sum('actual_cost'), 4),
            'this_month' => round((float)(clone $done)->where('completed_at', '>=', now()->startOfMonth())->sum('actual_cost'), 4),
            'transcripts' => (clone $done)->count(),
            'minutes' => (int)round(((int)(clone $done)->sum('duration_seconds')) / 60),
            'pending_estimate' => round((float)TranscriptionJob::whereIn('status', ['QUEUED', 'PROCESSING', SarvamKeyService::WAITING_KEY])->sum('estimated_cost'), 4),
            'currency' => TranscriptionJob::where('status', 'COMPLETED')->value('currency') ?: self::currency(),
            'last_completed_at' => $last ? \Carbon\Carbon::parse($last)->format('d M Y, h:i A') : null,
        ];
    }

    /** The last answer Sarvam gave to Refresh Credits (null = never refreshed). */
    public static function lastCheck(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * Refresh Credits: ask Sarvam (free key check) whether the key works and the account has credits.
     * @return array{ok: bool, busy?: bool, check: ?array, message: string}
     */
    public static function refresh(): array
    {
        $lock = Cache::lock(self::LOCK_KEY, 30);
        if (!$lock->get()) {
            return ['ok' => false, 'busy' => true, 'check' => self::lastCheck(), 'message' => 'A refresh is already running. Try again in a moment.'];
        }
        try {
            $settings = Schema::hasTable('company_sarvam_settings') ? CompanySarvamSetting::current() : null;
            $key = $settings ? $settings->apiKey() : null;
            if ($key === null) {
                return ['ok' => false, 'check' => self::lastCheck(), 'message' => 'Add a Sarvam API key first.'];
            }
            $result = SarvamKeyService::test($key);
            $state = (int)($result['http'] ?? 0) === 402 ? 'out_of_credits' : $result['state'];
            $answered = in_array($state, ['valid', 'out_of_credits', 'invalid'], true);   // Sarvam really answered
            $previous = self::lastCheck();
            $check = [
                'state' => $state,
                'message' => [
                    'valid' => 'Sarvam accepted the key: the account can be used. Its balance is shown only on the Sarvam dashboard.',
                    'out_of_credits' => 'Sarvam reports the account is out of credits (HTTP 402). Add credits on the Sarvam dashboard.',
                    'invalid' => 'Sarvam rejected the key.',
                    'rate_limited' => 'Sarvam is limiting requests right now. Try again later.',
                ][$state] ?? 'Could not reach Sarvam. Try again.',
                'checked_at' => now()->format('d M Y, h:i A'),
                // last time Sarvam actually answered; a failed attempt keeps the previous one
                'synced_at' => $answered ? now()->format('d M Y, h:i A') : ($previous['synced_at'] ?? null),
            ];
            Cache::put(self::CACHE_KEY, $check, now()->addDays(30));
            return ['ok' => $answered, 'check' => $check, 'message' => $check['message']];
        } finally {
            $lock->release();
        }
    }

    private static function currency(): string
    {
        try {
            return \App\Models\CompanyAiSetting::company()->currency ?: 'INR';
        } catch (\Throwable $e) {
            return 'INR';
        }
    }
}
