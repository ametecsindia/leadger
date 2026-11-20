<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Login sessions (user_logs) for Agent Performance and the Dashboard: login time, "Online" while the user is on
 * LeadGER, and the logout time.
 *
 *  - Every page asks the server every 5-30 seconds (call notifications poll): that marks the open session as seen
 *    (user_logs.updated_at, at most once a minute).
 *  - Logout writes the logout time (LoginController). When the browser is closed or the session expires nobody logs
 *    out: a session not seen for STALE_MINUTES is closed at the time it was last seen, so the user is not "Online"
 *    forever and the logout time is real.
 *  - A user still working after their session was closed that way gets a new session row (login time = now).
 */
class UserPresence
{
    public const STALE_MINUTES = 5;
    private const TOUCH_EVERY_SECONDS = 60;

    /** The signed-in user is on a LeadGER page now. Never breaks the request. */
    public static function touch(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        try {
            $now = Carbon::now();
            $open = DB::table('user_logs')->where('user_id', $userId)->whereNull('logout_date')->orderByDesc('id')->first(['id', 'updated_at']);
            if ($open) {
                if (!$open->updated_at || Carbon::parse($open->updated_at)->lt($now->copy()->subSeconds(self::TOUCH_EVERY_SECONDS))) {
                    DB::table('user_logs')->where('id', $open->id)->update(['updated_at' => $now]);
                }
                return;
            }
            // closed as inactive (or never written), but the user is working: a new session from now
            DB::table('user_logs')->insert([
                'user_id' => $userId, 'scenario' => 'login', 'info' => 'Session resumed',
                'login_date' => $now->toDateTimeString(), 'ip_address' => request()->ip(),
                'user_agent_details' => substr((string)request()->userAgent(), 0, 1000),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[UserPresence] touch: ' . $e->getMessage());
        }
    }

    /** Sessions not seen for STALE_MINUTES: closed at the time they were last seen. Returns how many. */
    public static function closeStale(): int
    {
        try {
            $limit = Carbon::now()->subMinutes(self::STALE_MINUTES);
            $closed = 0;
            DB::table('user_logs')->whereNull('logout_date')->whereNotNull('login_date')
                ->where(fn ($q) => $q->where('updated_at', '<', $limit)->orWhere(fn ($n) => $n->whereNull('updated_at')->where('login_date', '<', $limit)))
                ->orderBy('id')->limit(500)->get(['id', 'login_date', 'updated_at'])
                ->each(function ($row) use (&$closed) {
                    $start = Carbon::parse($row->login_date);
                    $end = self::lastSeen($row);
                    $closed += DB::table('user_logs')->where('id', $row->id)->whereNull('logout_date')->update([
                        'logout_date' => $end->toDateTimeString(),
                        'logged_in_duration' => self::duration($start, $end),
                        'scenario' => 'logout',
                        'info' => 'No activity (browser closed / session expired)',
                    ]);
                });
            return $closed;
        } catch (\Throwable $e) {
            Log::warning('[UserPresence] closeStale: ' . $e->getMessage());
            return 0;
        }
    }

    /** When the session was last seen: its last activity, never before its login. */
    public static function lastSeen($row): Carbon
    {
        $start = Carbon::parse($row->login_date);
        $seen = $row->updated_at ? Carbon::parse($row->updated_at) : $start;
        return $seen->lt($start) ? $start : $seen;
    }

    /** "HH:MM:SS" (hours can go past 24). */
    public static function duration(Carbon $start, Carbon $end): string
    {
        $s = max(0, $start->diffInSeconds($end, false));
        return sprintf('%02d:%02d:%02d', intdiv((int)$s, 3600), intdiv((int)$s % 3600, 60), (int)$s % 60);
    }
}
