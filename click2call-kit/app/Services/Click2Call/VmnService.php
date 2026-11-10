<?php

namespace App\Services\Click2Call;

use App\Models\C2c\Vmn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which VMNs a user may call from, and the check before every call. Same rules and messages as LeadGER's
 * App\Services\VmnService:
 *  - Add Voucher numbers (c2c_vmns) flagged for the channel; admins see all, others only their teams' numbers
 *  - a number is usable until its expiry day ends
 *  - optional pools: "smart31" (Smart31 numbers) and "1600" (1600 / 101 numbers) - the selected pool must be used
 */
class VmnService
{
    public const CHANNEL_FLAGS = [
        'click2call' => ['vmn' => 'click2call', 'smart31' => 'c2c'],
        'webrtc'     => ['vmn' => 'webrtc',     'smart31' => 'webrtc'],
    ];

    public const POOLS = ['smart31' => 'Smart31', '1600' => 'Enabled 1600'];

    /** Digits only; 91XXXXXXXXXX -> XXXXXXXXXX (same as LeadGER). */
    public static function normalize($number): string
    {
        $n = preg_replace('/\D/', '', (string) $number);
        return (strlen($n) === 12 && str_starts_with($n, '91')) ? substr($n, 2) : $n;
    }

    public static function is1600($number, $type = null): bool
    {
        $n = self::normalize($number);
        return (string) $type === '1600' || str_starts_with($n, '1600') || str_starts_with($n, '101');
    }

    /**
     * Voucher numbers this user may use on this channel.
     * Each item: virtual_number, display (+91...), expiry_date (Y-m-d|null), expiry_label, available, status_label.
     */
    public static function listFor($user, string $channel = 'click2call'): Collection
    {
        $flag = self::CHANNEL_FLAGS[$channel]['vmn'];
        $teams = $user->c2cTeamIds();
        $admin = $user->isC2cAdmin();

        return Vmn::where($flag, true)->orderBy('virtual_number')->get()
            ->filter(fn (Vmn $v) => $admin || array_intersect(array_map('intval', $v->team_ids ?? []), $teams))
            ->map(fn (Vmn $v) => (object) [
                'virtual_number' => $v->virtual_number,
                'display' => '+91' . $v->virtual_number,
                'expiry_date' => $v->expires_at?->format('Y-m-d'),
                'expiry_label' => $v->expires_at?->format('d M Y'),
                'available' => $v->isAvailable(),
                'status_label' => $v->isAvailable() ? 'Available' : 'Expired',
            ])->values();
    }

    /** Numbers of one pool (smart31 | 1600) for this user and channel; same item shape as listFor(). */
    public static function poolFor($user, string $channel, string $pool): Collection
    {
        $teams = $user->c2cTeamIds();
        $admin = $user->isC2cAdmin();
        $items = DB::table('c2c_smart31_numbers')->where(self::CHANNEL_FLAGS[$channel]['smart31'], true)
            ->orderBy('vmn')->get(['vmn', 'type', 'assigned_groups'])
            ->filter(function ($r) use ($admin, $teams) {
                $groups = array_map('intval', (array) json_decode((string) $r->assigned_groups, true));
                return $admin || !$groups || array_intersect($groups, $teams);   // no groups = everyone
            })
            ->filter(fn ($r) => self::is1600($r->vmn, $r->type) === ($pool === '1600'))
            ->map(fn ($r) => (object) ['virtual_number' => self::normalize($r->vmn), 'display' => '+91' . self::normalize($r->vmn),
                'expiry_date' => null, 'expiry_label' => null, 'available' => true, 'status_label' => 'Available']);
        if ($pool === '1600') {
            $items = $items->concat(self::listFor($user, $channel)->filter(fn ($r) => self::is1600($r->virtual_number)));
        }
        return $items->unique(fn ($r) => self::normalize($r->virtual_number))->values();
    }

    /**
     * Server-side check before placing a call.
     * @return array{ok: bool, message: string}
     */
    public static function check($user, $vmn, string $channel = 'click2call', ?string $pool = null): array
    {
        $label = $channel === 'webrtc' ? 'WebRTC' : 'Click2Call';
        $num = self::normalize($vmn);

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
            return $row->available ? ['ok' => true, 'message' => '']
                : ['ok' => false, 'message' => "{$name} number +91{$num} expired on {$row->expiry_label}. Choose another number."];
        }

        if ($num === '') {
            return ['ok' => false, 'message' => "Please select a {$label} VMN."];
        }
        foreach (self::listFor($user, $channel) as $row) {
            if ($row->virtual_number === $num) {
                return $row->available ? ['ok' => true, 'message' => '']
                    : ['ok' => false, 'message' => "{$label} VMN +91{$num} expired on {$row->expiry_label}. Renew it in Add Voucher or choose another number."];
            }
        }
        return ['ok' => false, 'message' => "+91{$num} is not configured for {$label} calling for your account. Choose a number from the {$label} VMN list."];
    }
}
