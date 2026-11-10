<?php

namespace App\Services\Click2Call;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rules checked before every call. Same as LeadGER's CallingRestrictionService:
 *  1. calling hours (company timezone)                               -> 403 "Calls are allowed only between ..."
 *  2. at most N calls per customer number per day (atomic counter)   -> 422 "Maximum call attempts reached ..."
 *  Blocked attempts are written to c2c_blocked_calls.
 *  3. one call start per agent at a time (cache lock)                -> 429 "Another call is currently being processed ..."
 */
class CallingRules
{
    /** @return array{allowed: bool, reason?: string, message?: string, code?: int} */
    public function check(string $phone, $user, bool $reserve = true): array
    {
        $tz = (string) config('click2call.timezone');
        $now = Carbon::now($tz);
        $normalized = VmnService::normalize($phone);

        if (strlen($normalized) < 7) {
            return ['allowed' => false, 'reason' => 'invalid_number', 'message' => 'Please enter a valid phone number.', 'code' => 422];
        }

        $from = substr((string) config('click2call.hours_from'), 0, 5);
        $to = substr((string) config('click2call.hours_to'), 0, 5);
        $time = $now->format('H:i');
        if ($time < $from || $time > $to) {
            return $this->blocked($normalized, $user, 'outside_hours', "Calls are allowed only between {$from} and {$to}.", 403, $now);
        }

        $limit = max(1, (int) config('click2call.daily_limit'));
        return DB::transaction(function () use ($normalized, $user, $limit, $now, $reserve) {
            $date = $now->toDateString();
            DB::table('c2c_daily_attempts')->insertOrIgnore([
                'phone' => $normalized, 'call_date' => $date, 'attempt_count' => 0, 'created_at' => $now, 'updated_at' => $now,
            ]);
            // row lock: two simultaneous calls to the same number cannot both take the last attempt
            $row = DB::table('c2c_daily_attempts')->where('phone', $normalized)->where('call_date', $date)->lockForUpdate()->first();
            if ((int) $row->attempt_count >= $limit) {
                return $this->blocked($normalized, $user, 'retry_limit', "Maximum call attempts reached for this number today ({$limit}).", 422, $now);
            }
            if ($reserve) {
                DB::table('c2c_daily_attempts')->where('id', $row->id)->update(['attempt_count' => $row->attempt_count + 1, 'updated_at' => $now]);
            }
            return ['allowed' => true];
        });
    }

    /** Give back an attempt when the provider could not start the call (LeadGER does the same). */
    public function release(string $phone): void
    {
        DB::table('c2c_daily_attempts')->where('phone', VmnService::normalize($phone))
            ->where('call_date', Carbon::now((string) config('click2call.timezone'))->toDateString())
            ->where('attempt_count', '>', 0)->decrement('attempt_count');
    }

    /** Run $callback while holding this agent's lock; null when another call start holds it. */
    public function withAgentLock($user, callable $callback)
    {
        $lock = Cache::lock('c2c-agent-' . $user->getKey(), (int) config('click2call.agent_lock_seconds', 6));
        if (!$lock->get()) {
            return null;
        }
        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    private function blocked(string $phone, $user, string $reason, string $message, int $code, Carbon $at): array
    {
        DB::table('c2c_blocked_calls')->insert([
            'phone' => $phone, 'user_id' => $user?->getKey(), 'reason' => $reason, 'message' => $message,
            'blocked_at' => $at, 'created_at' => $at, 'updated_at' => $at,
        ]);
        return ['allowed' => false, 'reason' => $reason, 'message' => $message, 'code' => $code];
    }
}
