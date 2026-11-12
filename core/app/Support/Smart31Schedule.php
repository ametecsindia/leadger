<?php

namespace App\Support;

use App\Services\LicenseService;
use App\Services\VmnService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Smart31 numbers of an agent for today, enabled by time slot (Asia/Kolkata), and the Smart31 calling ways
 * (Click2Call / WebRTC) the company's licence includes.
 *
 *  - Up to 3 numbers per agent and calling way: usage_date today, assigned to one of the agent's teams
 *    (assigned_groups; an agent without a team and an Admin are not filtered), in the order they were added (id).
 *    Number N is enabled from slot N on; before the first slot and after the last one all are disabled.
 *  - Licence data (settings.full_response "data"): c2c_channels / webrtc_channels above 0 allow that way;
 *    a key missing from the licence allows it too, so older licences keep working.
 */
class Smart31Schedule
{
    public const TIMEZONE = 'Asia/Kolkata';

    /** Slot => [first minute, last minute] (H:i, both included). */
    public const SLOTS = [
        1 => ['09:00', '12:00'],
        2 => ['12:01', '15:00'],
        3 => ['15:01', '20:00'],
    ];

    /** Cached licence channel counts only (no licence secrets); the cache is per company. */
    public const CACHE_KEY = 'lg_smart31_licence_channels';
    private const CACHE_SECONDS = 60;

    private const LABELS = ['c2c' => 'Click2Call', 'webrtc' => 'WebRTC'];

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    /** Current slot (1-3), or 0 outside calling hours. Times are compared as minutes of the day. */
    public static function slot(?Carbon $at = null): int
    {
        $minute = self::minuteOfDay($at ?? self::now());
        foreach (self::SLOTS as $slot => [$from, $to]) {
            if ($minute >= self::minutes($from) && $minute <= self::minutes($to)) {
                return $slot;
            }
        }
        return 0;
    }

    public static function hoursMessage(): string
    {
        return 'Smart31 calling is allowed only from ' . self::label(self::SLOTS[1][0]) . ' to ' . self::label(self::lastMinute());
    }

    /** VmnService channel -> kind: click2call -> c2c, webrtc -> webrtc. */
    public static function kindFor(string $channel): string
    {
        return $channel === 'webrtc' ? 'webrtc' : 'c2c';
    }

    /** The agent's Smart31 numbers for today and this kind (10 digits, in the order added, at most one per slot). */
    public static function numbers($user, string $kind)
    {
        $q = DB::table('smart31_vmn_numbers')->whereDate('usage_date', self::now()->toDateString())->where($kind, 1);
        // Admin: every Smart31 number of today, assigned to a group or not; the group filter is for the other roles (Telecallers)
        $teams = VmnService::isAdmin($user) ? [] : VmnService::teamIds($user);
        if ($teams) {
            $q->where(function ($w) use ($teams) {
                foreach ($teams as $t) {
                    $w->orWhereRaw('FIND_IN_SET(?, assigned_groups)', [$t]);
                }
            });
        }
        return $q->orderBy('id')->get(['vmn', 'type'])
            ->reject(fn ($r) => VmnService::is1600($r->vmn, $r->type))
            ->map(fn ($r) => VmnService::normalize($r->vmn))
            ->unique()->take(count(self::SLOTS))->values();
    }

    /**
     * Dropdown lines: enabled numbers first (the newest enabled one is the default), then the disabled ones
     * with the time they are enabled from. Empty when the licence does not include this way.
     *
     * @return array ['slot' => int, 'message' => ?string, 'lines' => [['vmn', 'position', 'enabled', 'available_from', 'default']]]
     */
    public static function lines($user, string $kind): array
    {
        $slot = self::slot();
        if ($error = self::licenceError($kind)) {
            return ['slot' => $slot, 'message' => $error, 'lines' => []];
        }
        $lines = self::numbers($user, $kind)->map(fn ($vmn, $i) => [
            'vmn' => $vmn,
            'position' => $i + 1,
            'enabled' => $slot >= $i + 1,
            'available_from' => self::label(self::SLOTS[$i + 1][0]),
            'default' => false,
        ]);
        $enabled = $lines->where('enabled', true)->reverse()->values()->all();
        if ($enabled) {
            $enabled[0]['default'] = true;
        }
        return [
            'slot' => $slot,
            'message' => $slot === 0 ? self::hoursMessage() : null,
            'lines' => array_merge($enabled, $lines->where('enabled', false)->values()->all()),
        ];
    }

    /** Why this agent may not call now with this Smart31 number in this way, or null when allowed. */
    public static function callError($user, string $kind, $vmn): ?string
    {
        if ($error = self::licenceError($kind)) {
            return $error;
        }
        $num = VmnService::normalize($vmn);
        if ($num === '') {
            return 'Please select VMN.';
        }
        $index = self::numbers($user, $kind)->search($num);
        if ($index === false) {
            return 'This Smart31 number is not assigned to you for today';
        }
        $position = $index + 1;
        if (self::slot() >= $position) {
            return null;
        }
        $from = self::label(self::SLOTS[$position][0]);
        $afterHours = self::minuteOfDay(self::now()) > self::minutes(self::lastMinute());
        return "Smart31 number +91{$num} can be called from {$from}" . ($afterHours ? ' to ' . self::label(self::lastMinute()) : '') . ' only';
    }

    /** A Smart31 number: in smart31_vmn_numbers (not a 1600 one) and not a normal Add Voucher / OBD DID. */
    public static function isSmart31($vmn): bool
    {
        $num = VmnService::normalize($vmn);
        if ($num === '') {
            return false;
        }
        $forms = [$num, '91' . $num, '+91' . $num];
        return DB::table('smart31_vmn_numbers')->whereIn('vmn', $forms)->get(['vmn', 'type'])
                ->contains(fn ($r) => !VmnService::is1600($r->vmn, $r->type))
            && !DB::table('obd_did')->whereIn('virtual_number', $forms)->exists();
    }

    /** @return array ['c2c' => bool, 'webrtc' => bool] */
    public static function licensedKinds(): array
    {
        $counts = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, function () {
            $data = LicenseService::licenseData() ?? [];
            return ['c2c' => $data['c2c_channels'] ?? null, 'webrtc' => $data['webrtc_channels'] ?? null];
        });
        $allows = fn ($n) => $n === null || $n === '' || (is_numeric($n) && $n > 0);
        return ['c2c' => $allows($counts['c2c'] ?? null), 'webrtc' => $allows($counts['webrtc'] ?? null)];
    }

    /** Message when the licence does not include this Smart31 calling way (c2c / webrtc), else null. */
    public static function licenceError(string $kind): ?string
    {
        return (self::licensedKinds()[$kind] ?? false) ? null : 'Smart31 ' . self::LABELS[$kind] . ' is not included in your licence';
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** End of calling hours: the last minute of the last slot (H:i). */
    private static function lastMinute(): string
    {
        return self::SLOTS[array_key_last(self::SLOTS)][1];
    }

    private static function minutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }

    private static function minuteOfDay(Carbon $at): int
    {
        $at = $at->copy()->setTimezone(self::TIMEZONE);
        return $at->hour * 60 + $at->minute;
    }

    /** "12:01" -> "12:01 PM" */
    private static function label(string $hhmm): string
    {
        return Carbon::createFromFormat('H:i', $hhmm, self::TIMEZONE)->format('g:i A');
    }
}
