<?php

namespace App\Services;

use App\Models\BlockedCallLog;
use App\Models\DailyPhoneCallAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallingRestrictionService
{
    /**
     * Cache key for settings.
     */
    const SETTINGS_CACHE_KEY = 'calling_restrictions_settings';

    /**
     * Get the application configured timezone.
     */
    public static function getTimezone(): string
    {
        $dbTz = @DB::table('webmaster_settings')->first()->timezone;
        return !empty($dbTz) ? $dbTz : config('app.timezone', 'Asia/Kolkata');
    }

    /**
     * Clear cached calling settings immediately.
     */
    public static function clearSettingsCache(): void
    {
        Cache::forget(self::SETTINGS_CACHE_KEY);
    }

    /**
     * Get current calling settings (calling hours from/to, daily retry limit).
     */
    public static function getSettings()
    {
        return DB::table('settings')->where('id', 1)->first();
    }

    /**
     * Normalize a phone number to digits only, canonicalizing country codes.
     * E.g., '9958887262', '+919958887262', '09958887262', '+91-9958-887-262'
     * all normalize to '9958887262'.
     *
     * @param string|null $phoneNumber
     * @return string
     */
    public static function normalizePhoneNumber(?string $phoneNumber): string
    {
        if (empty($phoneNumber)) {
            return '';
        }

        $digits = preg_replace('/\D+/', '', (string)$phoneNumber);

        // Standard Indian number normalization (10-digit mobile)
        // 1. Starts with 0 and 11 digits: e.g. 09958887262 -> 9958887262
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        // 2. Starts with 91 and 12 digits: e.g. 919958887262 -> 9958887262
        elseif (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }
        // 3. Starts with 091 and 13 digits: e.g. 0919958887262 -> 9958887262
        elseif (strlen($digits) === 13 && str_starts_with($digits, '091')) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    /**
     * Check if the given time is within configured calling hours.
     * Supports daytime windows (09:30 to 19:30) and overnight windows (22:00 to 06:00).
     *
     * @param Carbon|null $time
     * @param string|null $overrideFrom
     * @param string|null $overrideTo
     * @return array
     */
    public static function checkCallingHours(?Carbon $time = null, ?string $overrideFrom = null, ?string $overrideTo = null): array
    {
        $tz = self::getTimezone();
        $now = $time ? (clone $time)->setTimezone($tz) : Carbon::now($tz);
        $currentTime = $now->format('H:i');

        $settings = self::getSettings();

        $from = '09:30';
        $to = '19:30';

        if ($overrideFrom !== null) {
            $from = substr(trim($overrideFrom), 0, 5);
        } elseif (!empty($settings->calling_hours_from)) {
            $from = substr(trim($settings->calling_hours_from), 0, 5);
        } elseif (!empty($settings->caller_login)) {
            $from = substr(trim($settings->caller_login), 0, 5);
        }

        if ($overrideTo !== null) {
            $to = substr(trim($overrideTo), 0, 5);
        } elseif (!empty($settings->calling_hours_to)) {
            $to = substr(trim($settings->calling_hours_to), 0, 5);
        } elseif (!empty($settings->caller_logout)) {
            $to = substr(trim($settings->caller_logout), 0, 5);
        }

        $allowed = false;

        if ($from <= $to) {
            // Normal daytime window (e.g. 09:30 to 19:30)
            // Allow if from <= now <= to
            if ($currentTime >= $from && $currentTime <= $to) {
                $allowed = true;
            }
        } else {
            // Overnight window (e.g. 22:00 to 06:00)
            // Allow if now >= from OR now <= to
            if ($currentTime >= $from || $currentTime <= $to) {
                $allowed = true;
            }
        }

        $message = $allowed ? null : "Calls are allowed only between {$from} and {$to}.";

        return [
            'allowed' => $allowed,
            'from' => $from,
            'to' => $to,
            'current' => $currentTime,
            'timezone' => $tz,
            'message' => $message,
        ];
    }

    /**
     * Get calling hours status and UI notice for frontend display.
     */
    public static function getCallingHoursUiStatus(): array
    {
        $check = self::checkCallingHours();
        $isClosed = !$check['allowed'];
        $notice = $isClosed ? "Calling is closed. Opens at {$check['from']}" : null;

        return [
            'is_closed' => $isClosed,
            'opens_at' => $check['from'],
            'closes_at' => $check['to'],
            'notice' => $notice,
            'current_time' => $check['current'],
        ];
    }

    /**
     * Today's attempts to this number already in the call records (all agents, all campaigns), used to seed the
     * per-number counter. Only calls that actually started at the provider count: a provider / WebRTC call id or a
     * provider response is stored. Disposition-only rows and set-ups that failed before dialling are not attempts.
     */
    public static function countExistingAttemptsToday(string $normalizedPhone, string $callDate): int
    {
        $tz = self::getTimezone();
        $startOfDay = Carbon::createFromFormat('Y-m-d', $callDate, $tz)->startOfDay();
        $endOfDay = Carbon::createFromFormat('Y-m-d', $callDate, $tz)->endOfDay();

        $variants = array_unique([
            $normalizedPhone,
            '91' . $normalizedPhone,
            '+91' . $normalizedPhone,
            '0' . $normalizedPhone,
        ]);

        $started = function ($q) {
            $q->where(fn ($w) => $w->whereNotNull('provider_call_id')->where('provider_call_id', '!=', ''))
              ->orWhere(fn ($w) => $w->whereNotNull('webrtc_call_id')->where('webrtc_call_id', '!=', ''))
              ->orWhere(fn ($w) => $w->whereNotNull('click_response')->where('click_response', '!=', ''));
        };

        $callLogsCount = DB::table('call_logs')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('phonenumber', $variants)
            ->where($started)
            ->count();

        $dialerLogsCount = DB::table('dialer_logs')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('phone_number', $variants)
            ->where($started)
            ->count();

        return $callLogsCount + $dialerLogsCount;
    }

    /**
     * Log a blocked call attempt into leadger_blocked_call_logs.
     */
    public static function logBlockedCall(array $data): void
    {
        try {
            DB::table('blocked_call_logs')->insert([
                'phone_number' => $data['phone_number'] ?? '',
                'phone_normalized' => $data['phone_normalized'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'agent_name' => $data['agent_name'] ?? null,
                'reason' => $data['reason'] ?? 'blocked',
                'message' => $data['message'] ?? '',
                'channel' => $data['channel'] ?? null,
                'blocked_at' => $data['blocked_at'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::warning('[CALL_BLOCKED] Outbound call blocked', [
                'phone' => $data['phone_number'] ?? '',
                'normalized' => $data['phone_normalized'] ?? '',
                'agent' => $data['agent_name'] ?? ($data['user_id'] ?? 'unknown'),
                'reason' => $data['reason'] ?? '',
                'message' => $data['message'] ?? '',
                'channel' => $data['channel'] ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('[CALL_BLOCKED_LOG_ERROR] Could not log blocked call: ' . $e->getMessage());
        }
    }

    /**
     * Give back an attempt reserved by canPlaceOutboundCall(..., reserveAttempt: true) when the call did not start
     * (the provider rejected the request), so it does not count against this number's daily limit.
     */
    public static function releaseAttempt(string $phoneNumber, ?Carbon $atTime = null): void
    {
        $normalized = self::normalizePhoneNumber($phoneNumber);
        if ($normalized === '') return;
        $tz = self::getTimezone();
        $date = ($atTime ? (clone $atTime)->setTimezone($tz) : Carbon::now($tz))->format('Y-m-d');
        DB::table('daily_phone_call_attempts')->where('phone_normalized', $normalized)->where('call_date', $date)
            ->where('attempt_count', '>', 0)->decrement('attempt_count');
    }

    /**
     * Recompute today's per-number counters from the calls that actually started (repairs counts inflated by
     * attempts that never reached the customer). Returns [phone => [old, new]] for the rows that changed.
     */
    public static function recountToday(): array
    {
        $date = Carbon::now(self::getTimezone())->format('Y-m-d');
        $changed = [];
        foreach (DB::table('daily_phone_call_attempts')->where('call_date', $date)->get() as $row) {
            $actual = self::countExistingAttemptsToday($row->phone_normalized, $date);
            if ($actual !== (int)$row->attempt_count) {
                DB::table('daily_phone_call_attempts')->where('id', $row->id)->update(['attempt_count' => $actual, 'updated_at' => now()]);
                $changed[$row->phone_normalized] = [(int)$row->attempt_count, $actual];
            }
        }
        return $changed;
    }

    /**
     * MAIN SHARED ENFORCEMENT FUNCTION.
     * Evaluates both Calling Hours and Maximum Retry Limit in the backend.
     * Call this right before placing ANY outbound call.
     *
     * @param string $phoneNumber
     * @param User|int|null $agent
     * @param string|null $channel (e.g. 'click2call', 'webrtc', 'dialer', 'lead_action', 'auto_dialer')
     * @param bool $reserveAttempt Whether to increment the attempt counter if allowed (default true)
     * @param Carbon|null $atTime Optional mock time for testing
     * @return array ['allowed' => bool, 'reason' => string|null, 'message' => string|null, 'code' => int, 'normalized_phone' => string]
     */
    public static function canPlaceOutboundCall(
        string $phoneNumber,
        $agent = null,
        ?string $channel = null,
        bool $reserveAttempt = true,
        ?Carbon $atTime = null
    ): array {
        // Resolve agent
        $userId = null;
        $agentName = null;

        if ($agent instanceof User) {
            $userId = $agent->id;
            $agentName = $agent->name;
        } elseif (is_numeric($agent)) {
            $userObj = User::find($agent);
            if ($userObj) {
                $userId = $userObj->id;
                $agentName = $userObj->name;
            } else {
                $userId = (int)$agent;
            }
        } elseif (Auth::check()) {
            $authUser = Auth::user();
            $userId = $authUser->id;
            $agentName = $authUser->name;
        }

        $normalizedPhone = self::normalizePhoneNumber($phoneNumber);
        if (empty($normalizedPhone) || strlen($normalizedPhone) < 7) {
            return [
                'allowed' => false,
                'reason' => 'invalid_number',
                'message' => 'Please enter a valid phone number.',
                'code' => 422,
                'normalized_phone' => $normalizedPhone,
            ];
        }

        $tz = self::getTimezone();
        $now = $atTime ? (clone $atTime)->setTimezone($tz) : Carbon::now($tz);
        $callDate = $now->format('Y-m-d');

        // ----------------------------------------------------
        // 1. CALLING HOURS RULE
        // ----------------------------------------------------
        $hoursCheck = self::checkCallingHours($now);
        if (!$hoursCheck['allowed']) {
            $message = $hoursCheck['message'];

            self::logBlockedCall([
                'phone_number' => $phoneNumber,
                'phone_normalized' => $normalizedPhone,
                'user_id' => $userId,
                'agent_name' => $agentName,
                'reason' => 'outside_hours',
                'message' => $message,
                'channel' => $channel,
                'blocked_at' => $now,
            ]);

            return [
                'allowed' => false,
                'reason' => 'outside_hours',
                'message' => $message,
                'code' => 403,
                'normalized_phone' => $normalizedPhone,
                'details' => $hoursCheck,
            ];
        }

        // ----------------------------------------------------
        // 2. MAXIMUM RETRY RULE (CONCURRENCY-SAFE ATOMIC CHECK & INCREMENT)
        // ----------------------------------------------------
        $settings = self::getSettings();
        $maxRetries = 100;
        if ($settings && isset($settings->daily_call_limit) && (int)$settings->daily_call_limit > 0) {
            $maxRetries = (int)$settings->daily_call_limit;
        }

        // Execute inside a database transaction with row-level lock
        return DB::transaction(function () use (
            $normalizedPhone,
            $phoneNumber,
            $callDate,
            $maxRetries,
            $userId,
            $agentName,
            $channel,
            $now,
            $reserveAttempt
        ) {
            // One counter row per (number, day) - unique key. Create it if missing (seeded from today's calls that
            // started), then lock it: simultaneous calls to the same number wait here, so only one gets the last slot.
            if (!DB::table('daily_phone_call_attempts')->where('phone_normalized', $normalizedPhone)->where('call_date', $callDate)->exists()) {
                DB::table('daily_phone_call_attempts')->insertOrIgnore([
                    'phone_normalized' => $normalizedPhone,
                    'call_date' => $callDate,
                    'attempt_count' => self::countExistingAttemptsToday($normalizedPhone, $callDate),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $row = DB::table('daily_phone_call_attempts')
                ->where('phone_normalized', $normalizedPhone)
                ->where('call_date', $callDate)
                ->lockForUpdate()
                ->first();
            $currentCount = (int)$row->attempt_count;

            if ($currentCount >= $maxRetries) {
                $message = "Maximum call attempts reached for this number today ({$maxRetries}).";

                self::logBlockedCall([
                    'phone_number' => $phoneNumber,
                    'phone_normalized' => $normalizedPhone,
                    'user_id' => $userId,
                    'agent_name' => $agentName,
                    'reason' => 'retry_limit',
                    'message' => $message,
                    'channel' => $channel,
                    'blocked_at' => $now,
                ]);

                return [
                    'allowed' => false,
                    'reason' => 'retry_limit',
                    'message' => $message,
                    'code' => 403,
                    'current_count' => $currentCount,
                    'max_retries' => $maxRetries,
                    'normalized_phone' => $normalizedPhone,
                ];
            }

            if ($reserveAttempt) {
                DB::table('daily_phone_call_attempts')
                    ->where('phone_normalized', $normalizedPhone)
                    ->where('call_date', $callDate)
                    ->increment('attempt_count');
                $newCount = $currentCount + 1;
            } else {
                $newCount = $currentCount;
            }

            return [
                'allowed' => true,
                'reason' => null,
                'message' => null,
                'code' => 200,
                'current_count' => $newCount,
                'max_retries' => $maxRetries,
                'normalized_phone' => $normalizedPhone,
            ];
        }, 3);
    }

    /**
     * Backward compatibility wrapper for checkOutboundCallAllowed.
     */
    public static function checkOutboundCallAllowed($user = null, ?string $mobile = null): array
    {
        $phone = $mobile ?: '';
        $res = self::canPlaceOutboundCall($phone, $user, 'check', false);
        return [
            'allowed' => $res['allowed'],
            'message' => $res['message'],
            'reason' => $res['reason'],
        ];
    }

    /**
     * Backward compatibility wrapper for withCallLock.
     */
    public static function withCallLock($user, callable $callback)
    {
        $userId = is_object($user) ? $user->id : ($user ?: 0);
        $lockKey = "click2call_agent_lock_{$userId}";

        $lock = Cache::lock($lockKey, 6);

        if ($lock->get()) {
            try {
                return $callback();
            } finally {
                optional($lock)->release();
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Another call is currently being processed for your account. Please wait a moment.'
        ], 429);
    }
}
