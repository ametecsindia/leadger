<?php

namespace App\Services;

use App\Models\InboundCall;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound call notifications (header bell + "Incoming call" popup), one row per user and call in the notifications
 * table (user_id + call_id unique), updated as the call goes on:
 *  - ringing  -> the agent the call is routed to: "Incoming call from <number> (<lead>, <campaign>) on VMN <vmn>"
 *  - answered -> the same row: "On a call with <number>"; ended -> the popup closes (data.state = ended)
 *  - missed   -> a persistent, unread "Missed call from <number> at <time>" for the recipients of
 *                INBOUND_NOTIFY_MISSED (default agent,lead_owner,team_leader,admins)
 * Only users of this company's database, active ones; numbers masked as the lead's campaign masks them for the
 * user's role. Calls that started more than INBOUND_NOTIFY_MAX_AGE minutes ago (default 30: old pulled call logs,
 * backfills) notify nobody. Never throws: a failure is logged and the inbound call is saved anyway.
 */
class InboundCallNotifier
{
    public const TYPES = ['ringing' => 'inbound_ringing', 'answered' => 'inbound_answered', 'missed' => 'inbound_missed'];

    public static function ready(): bool
    {
        try {
            return Schema::hasColumn('notifications', 'call_id');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return int notifications created or updated */
    public static function notify(InboundCall $call): int
    {
        try {
            if (!self::ready() || !$call->call_id) {
                return 0;
            }
            $maxAge = (int)config('services.telephony.inbound_notify_max_age', 30);
            $started = $call->start_time ?? $call->created_at;
            if ($started && Carbon::parse($started)->lt(Carbon::now()->subMinutes(max(1, $maxAge)))) {
                return 0;   // an old call (pulled later / backfill): nothing to alert about
            }

            $ended = !empty($call->end_time);
            $state = match (true) {
                $call->status === 'missed' => 'missed',
                $call->status === 'answered' => $ended ? 'ended' : 'answered',
                $call->status === 'ringing' && !$ended => 'ringing',
                default => null,
            };
            if (!$state) {
                return 0;
            }
            $recipients = $state === 'missed' ? self::missedRecipients($call) : array_filter([(int)$call->agent_user_id]);
            $count = 0;
            foreach (self::activeUsers($recipients) as $user) {
                $count += self::write($user, $call, $state);
            }
            return $count;
        } catch (\Throwable $e) {
            Log::error('[InboundNotify] Notification not created for call ' . $call->call_id . ': ' . $e->getMessage());
            return 0;
        }
    }

    /** Recipients of a missed call, by INBOUND_NOTIFY_MISSED (agent, lead_owner, team_leader, admins). */
    public static function missedRecipients(InboundCall $call): array
    {
        $rule = array_map('trim', explode(',', strtolower((string)config('services.telephony.inbound_notify_missed', 'agent,lead_owner,team_leader,admins'))));
        $ids = [];
        if (in_array('agent', $rule, true) && $call->agent_user_id) {
            $ids[] = (int)$call->agent_user_id;
        }
        $owner = $call->lead_id ? (int)DB::table('leads')->where('id', $call->lead_id)->value('assigned_to') : 0;
        if (in_array('lead_owner', $rule, true) && $owner) {
            $ids[] = $owner;
        }
        if (in_array('team_leader', $rule, true)) {
            foreach (array_filter([$owner, (int)$call->agent_user_id]) as $member) {
                $ids = array_merge($ids, self::teamLeadersOf($member));
            }
        }
        if (in_array('admins', $rule, true)) {
            $ids = array_merge($ids, self::usersWithRole('Admin'));
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private static function teamLeadersOf(int $userId): array
    {
        $teams = VmnService::teamIds(DB::table('users')->where('id', $userId)->first(['team_id']));
        if (!$teams) {
            return [];
        }
        return DB::table('users')->join('permissions', 'permissions.id', '=', 'users.permissions_id')
            ->where('permissions.name', 'Team Leader')
            ->where(function ($w) use ($teams) {
                foreach ($teams as $t) {
                    $w->orWhereRaw('FIND_IN_SET(?, ' . DB::getTablePrefix() . 'users.team_id)', [$t]);
                }
            })->pluck('users.id')->map(fn ($v) => (int)$v)->all();
    }

    private static function usersWithRole(string $role): array
    {
        return DB::table('users')->join('permissions', 'permissions.id', '=', 'users.permissions_id')
            ->where('permissions.name', $role)->pluck('users.id')->map(fn ($v) => (int)$v)->all();
    }

    private static function activeUsers(array $ids)
    {
        return $ids ? \App\Models\User::with('permissionsGroup')->whereIn('id', $ids)->where('status', 1)->get() : collect();
    }

    /** Create or update this user's row for the call (idempotent: user_id + call_id). */
    private static function write($user, InboundCall $call, string $state): int
    {
        $lead = $call->lead_id ? DB::table('leads')->where('id', $call->lead_id)->first(['id', 'name', 'campaign_id']) : null;
        $campaign = $lead && $lead->campaign_id ? DB::table('campaigns')->where('id', $lead->campaign_id)->first(['name', 'number_masking']) : null;
        $seeAll = in_array(optional($user->permissionsGroup)->name, ActiveCallService::SEE_ALL_ROLES, true);
        $raw = (string)($call->customer_mobile_normalized ?: $call->customer_mobile);
        $number = (!$seeAll && $campaign && $campaign->number_masking && $raw !== '') ? substr($raw, 0, 1) . 'XXXXXXX' . substr($raw, -2) : $raw;
        $display = $number !== '' ? '+91 ' . $number : 'an unknown number';
        $context = implode(', ', array_filter([$lead->name ?? null, $campaign->name ?? null]));
        $at = Carbon::parse($call->start_time ?? Carbon::now());

        [$type, $title, $body] = match ($state) {
            'ringing' => [self::TYPES['ringing'], 'Incoming call', "Incoming call from {$display}" . ($context ? " ({$context})" : '') . ($call->vmn ? " on VMN {$call->vmn}" : '')],
            'answered' => [self::TYPES['answered'], 'On a call', "On a call with {$display}" . ($context ? " ({$context})" : '')],
            'ended' => [self::TYPES['answered'], 'Call ended', "Call with {$display} ended"],
            default => [self::TYPES['missed'], 'Missed call', "Missed call from {$display} at " . $at->format('h:i A') . ($context ? " ({$context})" : '')],
        };
        $destination = (new InboundCallDestination($user, (int)$call->id))->forNumber((string)$call->customer_mobile_normalized);
        $data = [
            'state' => $state,
            'number' => $number,
            'lead' => $lead->name ?? null,
            'campaign' => $campaign->name ?? null,
            'vmn' => $call->vmn,
            'url' => $destination['url'] ?? InboundCallDestination::manualUrl((string)$call->customer_mobile_normalized),
            'at' => $at->toIso8601String(),
            'inbound_id' => (int)$call->id,
        ];

        $now = Carbon::now();
        $values = ['type' => $type, 'title' => $title, 'body' => mb_substr($body, 0, 500), 'data' => json_encode($data), 'updated_at' => $now]
            // ringing and missed need attention (unread); answered / ended do not
            + (in_array($state, ['ringing', 'missed'], true) ? ['open_status' => 0] : ['open_status' => 1]);
        $existing = DB::table('notifications')->where('user_id', $user->id)->where('call_id', $call->call_id)->first(['id', 'type']);
        if ($existing) {
            if ($existing->type === self::TYPES['missed'] && $state !== 'missed') {
                return 0;   // a missed call stays missed
            }
            DB::table('notifications')->where('id', $existing->id)->update($values);
            return 1;
        }
        try {
            DB::table('notifications')->insert($values + ['user_id' => $user->id, 'call_id' => $call->call_id, 'created_at' => $now]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            DB::table('notifications')->where('user_id', $user->id)->where('call_id', $call->call_id)->update($values);
        }
        return 1;
    }
}
