<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Click2Call / WebRTC virtual numbers (VMNs) as saved by Configuration -> Add Voucher (obd_did),
 * plus Smart31 / 1600 numbers (smart31_vmn_numbers). One place for "which numbers may this user
 * use on this channel" and "is this number usable right now".
 */
class VmnService
{
    public const CHANNEL_FLAGS = [
        'click2call' => ['did' => 'click2call', 'smart31' => 'c2c'],
        'webrtc'     => ['did' => 'webrtc',     'smart31' => 'webrtc'],
    ];

    /** Calling options in the dialer (Make a Call / Campaign Queue): label per pool. */
    public const POOLS = ['smart31' => 'Smart31', '1600' => 'Enabled 1600'];

    /** 1600 numbers: smart31_vmn_numbers.type = 1600, or a 1600 / 101 number. */
    public static function is1600($number, $type = null): bool
    {
        $n = self::normalize($number);
        return (string)$type === '1600' || str_starts_with($n, '1600') || str_starts_with($n, '101');
    }

    /**
     * Numbers of one calling option for this user and channel (same item shape as listFor()).
     *  - smart31: Smart31 numbers (smart31_vmn_numbers) flagged for the channel, except 1600 ones
     *  - 1600:    1600 numbers from smart31_vmn_numbers and from Add Voucher (obd_did)
     * Nothing else is mixed in: an option with no stored number returns an empty list.
     */
    public static function poolFor($user, string $channel, string $pool)
    {
        $q = DB::table('smart31_vmn_numbers')->where(self::CHANNEL_FLAGS[$channel]['smart31'], 1);
        if (!self::isAdmin($user)) {
            $teams = self::teamIds($user);
            $q->where(function ($w) use ($teams) {
                $w->whereNull('assigned_groups')->orWhere('assigned_groups', '');
                foreach ($teams as $t) {
                    $w->orWhereRaw('FIND_IN_SET(?, assigned_groups)', [$t]);
                }
            });
        }
        $items = $q->orderBy('vmn')->get(['vmn', 'type'])
            ->filter(fn ($r) => self::is1600($r->vmn, $r->type) === ($pool === '1600'))
            ->map(fn ($r) => (object)['virtual_number' => self::normalize($r->vmn), 'display' => '+91' . self::normalize($r->vmn),
                'expiry_date' => null, 'expiry_label' => null, 'available' => true, 'status_label' => 'Available']);
        if ($pool === '1600') {
            $items = $items->concat(self::listFor($user, $channel)->filter(fn ($r) => self::is1600($r->virtual_number)));
        }
        return $items->unique(fn ($r) => self::normalize($r->virtual_number))->values();
    }

    public static function normalize($number): string
    {
        $n = preg_replace('/[^0-9]/', '', (string)$number);
        if (strlen($n) == 12 && str_starts_with($n, '91')) {
            $n = substr($n, 2);
        }
        return $n;
    }

    public static function isAdmin($user): bool
    {
        return ($user->permissionsGroup->name ?? '') === 'Admin';
    }

    public static function teamIds($user): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string)($user->team_id ?? '')))));
    }

    /**
     * Add Voucher numbers enabled for a channel that this user may use (Admin: all; others: their teams).
     * Each item: virtual_number, display (+91...), expiry_date (Y-m-d|null), available (bool), status_label.
     */
    public static function listFor($user, string $channel)
    {
        $flag = self::CHANNEL_FLAGS[$channel]['did'];
        $q = DB::table('obd_did')
            ->select('virtual_number', DB::raw("FROM_BASE64(AES_DECRYPT(expiry, 'MyNicotex@1')) as expiry_date"))
            ->where($flag, 1);

        if (!self::isAdmin($user)) {
            $teams = self::teamIds($user);
            if (empty($teams)) {
                return collect();
            }
            $q->where(function ($w) use ($teams) {
                foreach ($teams as $t) {
                    $w->orWhereRaw('FIND_IN_SET(?, team_id)', [$t]);
                }
            });
        }

        $today = Carbon::today();
        return $q->orderBy('virtual_number')->get()
            ->unique(fn ($r) => self::normalize($r->virtual_number))
            ->map(function ($r) use ($today) {
                $expiry = null;
                if (!empty($r->expiry_date)) {
                    try { $expiry = Carbon::parse($r->expiry_date); } catch (\Throwable $e) { $expiry = null; }
                }
                $available = !$expiry || $expiry->endOfDay()->gte($today);
                $num = self::normalize($r->virtual_number);
                return (object)[
                    'virtual_number' => $r->virtual_number,
                    'display' => '+91' . $num,
                    'expiry_date' => $expiry ? $expiry->format('Y-m-d') : null,
                    'expiry_label' => $expiry ? $expiry->format('d M Y') : null,
                    'available' => $available,
                    'status_label' => $available ? 'Available' : 'Expired',
                ];
            })->values();
    }

    /**
     * Server-side check before placing a call: the number must be configured for THIS channel,
     * allowed for the user, and not expired. Smart31 / 1600 numbers (smart31_vmn_numbers) are accepted
     * when flagged for the channel, exactly as the dialer's Smart31 / 1600 lists offer them. With $pool
 * (smart31 / 1600: the calling option the agent selected) the number must belong to that option.
     *
     * @return array ['ok' => bool, 'message' => string]
     */
    public static function check($user, $vmn, string $channel, ?string $pool = null): array
    {
        $label = $channel === 'webrtc' ? 'WebRTC' : 'Click2Call';
        $num = self::normalize($vmn);

        // A call placed with Smart31 / Enabled 1600 selected must use a number of that option
        if ($pool !== null && isset(self::POOLS[$pool])) {
            $name = self::POOLS[$pool];
            $list = self::poolFor($user, $channel, $pool);
            if ($list->isEmpty()) {
                return ['ok' => false, 'message' => "No {$name} number is configured for {$label} calling. Add one, or switch {$name} off to use your {$label} VMN."];
            }
            $row = $num === '' ? null : $list->first(fn ($r) => self::normalize($r->virtual_number) === $num);
            if (!$row) {
                return ['ok' => false, 'message' => "Please select a {$name} number for {$label} calling."];
            }
            return $row->available
                ? ['ok' => true, 'message' => '']
                : ['ok' => false, 'message' => "{$name} number +91{$num} expired on {$row->expiry_label}. Choose another number."];
        }

        if ($num === '') {
            return ['ok' => false, 'message' => "Please select a {$label} VMN."];
        }

        foreach (self::listFor($user, $channel) as $row) {
            if (self::normalize($row->virtual_number) === $num) {
                return $row->available
                    ? ['ok' => true, 'message' => '']
                    : ['ok' => false, 'message' => "{$label} VMN +91{$num} expired on {$row->expiry_label}. Renew it in Add Voucher or choose another number."];
            }
        }

        $smartFlag = self::CHANNEL_FLAGS[$channel]['smart31'];
        $smart = DB::table('smart31_vmn_numbers')->where($smartFlag, 1)
            ->where(function ($w) use ($num) {
                $w->where('vmn', $num)->orWhere('vmn', '91' . $num)->orWhere('vmn', '+91' . $num);
            })->exists();
        if ($smart) {
            return ['ok' => true, 'message' => ''];
        }

        return ['ok' => false, 'message' => "+91{$num} is not configured for {$label} calling for your account. Choose a number from the {$label} VMN list."];
    }
}
