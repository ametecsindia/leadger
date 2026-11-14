<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\DialerLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Calls in progress right now (table active_calls), the one source for the Dashboard "On Call Now" card, its
 * "Live active calls" panel and the "On call" status on the activity floor.
 *
 * A call is active only once BOTH sides answered, until it ends:
 *  - WebRTC: the browser's true answer (200 OK + ICE + customer audio) -> answered; a heartbeat every 15 s; hang-up /
 *    page close -> ended (DialerController::updateCallState, activeCallHeartbeat)
 *  - Click2Call: the provider's live record says the customer leg is connected (DialerController::callStatus, and
 *    active-calls:cleanup asks the provider for calls nobody is following)
 *  - Inbound: the inbound event says answered by an agent (InboundCallService)
 *  - any call log saved as finished (end time / final state) ends its active call (model events, AppServiceProvider)
 * active-calls:cleanup closes calls without any sign of life, so no "ghost" call stays on the dashboard.
 */
class ActiveCallService
{
    public const FINAL_STATES = ['completed', 'busy', 'no-answer', 'no_answer', 'failed', 'cancelled', 'ended'];
    public const WEBRTC_STALE_SECONDS = 90;
    public const SEE_ALL_ROLES = ['Webmaster', 'Admin', 'Manager'];

    /** @var array<string, bool> database name => table exists */
    private static array $ready = [];

    /** The table exists in this company's database (before the migration everything here does nothing). */
    public static function ready(): bool
    {
        try {
            $db = (string)DB::connection()->getDatabaseName();
            return self::$ready[$db] ??= Schema::hasTable('active_calls');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** For tests and after migrating in the same process. */
    public static function resetReady(): void
    {
        self::$ready = [];
    }

    public static function keyFor($log): ?string
    {
        if ($log instanceof CallLog) return 'call:' . $log->id;
        if ($log instanceof DialerLog) return 'dialer:' . $log->id;
        return null;
    }

    /**
     * A campaign / manual call answered by both sides. A call already ended (row closed, or its log finished) is never
     * opened again.
     */
    public static function answeredFromLog($log, string $channel, ?Carbon $answeredAt = null): void
    {
        $key = self::keyFor($log);
        if (!$key || !self::ready() || self::logFinished($log)) {
            return;
        }
        $isCall = $log instanceof CallLog;
        $leadId = $log->lead_id ?: null;
        self::answered($key, [
            'log_table' => $isCall ? 'call_logs' : 'dialer_logs',
            'call_log_id' => $log->id,
            'direction' => 'outbound',
            'channel' => $channel === 'webrtc' || $channel === 'sip' ? 'webrtc' : 'click2call',
            'provider_call_id' => $log->provider_call_id ?: $log->reference_id,
            'agent_user_id' => $log->created_by ?: null,
            'customer_number' => $isCall ? $log->phonenumber : $log->phone_number,
            'lead_id' => $leadId,
            'campaign_id' => $isCall ? ($log->campaign_id ?: null) : ($leadId ? DB::table('leads')->where('id', $leadId)->value('campaign_id') : null),
            'vmn' => $log->vmn_used,
        ], $answeredAt);
    }

    /** Open (or keep open) the active call $key. */
    public static function answered(string $key, array $fields, ?Carbon $answeredAt = null): void
    {
        if (!self::ready()) {
            return;
        }
        $now = Carbon::now();
        $row = DB::table('active_calls')->where('call_key', $key)->first();
        if ($row && $row->ended_at) {
            return;   // finished: never reopened by a late event
        }
        if ($row) {
            DB::table('active_calls')->where('id', $row->id)->update(array_filter($fields, fn ($v) => $v !== null && $v !== '')
                + ['last_seen_at' => $now, 'updated_at' => $now]);
            return;
        }
        try {
            DB::table('active_calls')->insert($fields + [
                'call_key' => $key,
                'company_id' => 1,
                'answered_at' => $answeredAt ?? $now,
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // the same call answered twice at the same moment: the other request opened it
        }
    }

    /** WebRTC heartbeat: the call is still on. */
    public static function heartbeat(string $key, ?int $userId = null): bool
    {
        if (!self::ready()) {
            return false;
        }
        $open = DB::table('active_calls')->where('call_key', $key)->whereNull('ended_at')
            ->when($userId, fn ($q) => $q->where('agent_user_id', $userId));
        if (!(clone $open)->exists()) {
            return false;
        }
        // (MySQL reports 0 changed rows when the time is the same second: the result is "open", not the row count)
        $open->update(['last_seen_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        return true;
    }

    /** Close the active call $key (no-op when it is not open). */
    public static function ended(string $key, string $reason = 'ended', ?int $duration = null): void
    {
        if (!self::ready()) {
            return;
        }
        $row = DB::table('active_calls')->where('call_key', $key)->whereNull('ended_at')->first();
        if (!$row) {
            return;
        }
        $now = Carbon::now();
        DB::table('active_calls')->where('id', $row->id)->update([
            'ended_at' => $now,
            'end_reason' => mb_substr($reason, 0, 40),
            'duration_seconds' => $duration ?? max(0, (int)Carbon::parse($row->answered_at)->diffInSeconds($now, true)),
            'updated_at' => $now,
        ]);
    }

    /** A call log saved as finished (any path: agent outcome, provider webhook, browser hang-up). */
    public static function endFromLog($log): void
    {
        $key = self::keyFor($log);
        if ($key && self::logFinished($log)) {
            $talk = $log instanceof DialerLog ? (int)$log->time_taken : (int)$log->duration;
            self::ended($key, (string)($log->call_state ?: 'ended'), $talk > 0 ? $talk : null);
        }
    }

    public static function logFinished($log): bool
    {
        return !empty($log->end_time) || in_array((string)$log->call_state, self::FINAL_STATES, true);
    }

    /** All open calls of an agent (logout). */
    public static function endForUser(int $userId, string $reason = 'agent logged out'): int
    {
        if (!self::ready()) {
            return 0;
        }
        $keys = DB::table('active_calls')->where('agent_user_id', $userId)->whereNull('ended_at')->pluck('call_key');
        foreach ($keys as $key) {
            self::ended($key, $reason);
        }
        return $keys->count();
    }

    /** Open calls that are really alive (a WebRTC call needs a heartbeat in the last 90 s), within the user's scope. */
    public static function liveQuery($user = null)
    {
        $q = DB::table('active_calls')->whereNull('ended_at')
            ->where(fn ($w) => $w->where('channel', '!=', 'webrtc')
                ->orWhere('last_seen_at', '>=', Carbon::now()->subSeconds(self::WEBRTC_STALE_SECONDS)));
        $agents = $user ? self::visibleAgentIds($user) : null;
        if ($agents !== null) {
            $q->whereIn('agent_user_id', $agents ?: [0]);
        }
        return $q;
    }

    public static function liveCount($user = null): int
    {
        return self::ready() ? self::liveQuery($user)->count() : 0;
    }

    /**
     * The open calls for the dashboard panel: agent, customer (masked like the campaign masks it), lead / campaign,
     * channel, direction and answered time (ms, for the live timer).
     */
    public static function liveList($user): array
    {
        if (!self::ready()) {
            return [];
        }
        $rows = self::liveQuery($user)->orderBy('answered_at')->limit(200)->get();
        $users = DB::table('users')->whereIn('id', $rows->pluck('agent_user_id')->filter()->unique())->pluck('name', 'id');
        $leads = DB::table('leads')->whereIn('id', $rows->pluck('lead_id')->filter()->unique())->get(['id', 'name', 'campaign_id'])->keyBy('id');
        $campaignIds = $rows->pluck('campaign_id')->merge($leads->pluck('campaign_id'))->filter()->unique();
        $campaigns = DB::table('campaigns')->whereIn('id', $campaignIds)->get(['id', 'name', 'number_masking'])->keyBy('id');
        $seeAll = in_array(optional($user->permissionsGroup)->name, self::SEE_ALL_ROLES, true);

        return $rows->map(function ($r) use ($users, $leads, $campaigns, $seeAll) {
            $lead = $r->lead_id ? $leads->get($r->lead_id) : null;
            $campaign = $campaigns->get($r->campaign_id ?: ($lead->campaign_id ?? 0));
            $mask = !$seeAll && $campaign && $campaign->number_masking;
            $number = (string)$r->customer_number;
            return [
                'id' => $r->id,
                'agent_user_id' => $r->agent_user_id,
                'agent' => $users[$r->agent_user_id] ?? ($r->agent_number ?: 'Unknown agent'),
                'customer' => $mask && $number !== '' ? substr($number, 0, 1) . 'XXXXXXX' . substr($number, -2) : $number,
                'lead' => $lead->name ?? null,
                'campaign' => $campaign->name ?? null,
                'channel' => $r->channel,
                'direction' => $r->direction,
                'answered_ms' => Carbon::parse($r->answered_at)->getTimestamp() * 1000,
            ];
        })->values()->all();
    }

    /**
     * Agents whose calls this user may see: null = everyone (Webmaster / Admin / Manager); a Team Leader: the members
     * of their teams and themselves; anyone else: only themselves.
     */
    public static function visibleAgentIds($user): ?array
    {
        $role = (string)optional($user->permissionsGroup)->name;
        if (in_array($role, self::SEE_ALL_ROLES, true)) {
            return null;
        }
        if ($role === 'Team Leader') {
            $teams = VmnService::teamIds($user);
            $ids = $teams ? DB::table('users')->where(function ($w) use ($teams) {
                foreach ($teams as $t) {
                    $w->orWhereRaw('FIND_IN_SET(?, team_id)', [$t]);
                }
            })->pluck('id')->map(fn ($v) => (int)$v)->all() : [];
            return array_values(array_unique(array_merge($ids, [(int)$user->id])));
        }
        return [(int)$user->id];
    }

    /**
     * Close calls without a sign of life: a WebRTC call with no heartbeat for 90 s, any call open longer than the
     * maximum (ACTIVE_CALL_MAX_MINUTES, default 180), a call whose log is already finished, an inbound call that
     * ended, and a Click2Call call the provider reports as ended (asked when nobody's page is following it).
     *
     * @return int calls closed
     */
    public static function cleanup(?callable $providerRecord = null): int
    {
        if (!self::ready()) {
            return 0;
        }
        $closed = 0;
        $now = Carbon::now();
        $close = function ($row, string $reason) use (&$closed) {
            self::ended($row->call_key, $reason);
            Log::warning("[ActiveCalls] Closed {$row->call_key} ({$row->channel}, agent {$row->agent_user_id}): {$reason}");
            $closed++;
        };
        $max = max(5, (int)config('services.telephony.active_call_max_minutes', 180));

        foreach (DB::table('active_calls')->whereNull('ended_at')->orderBy('id')->limit(500)->get() as $row) {
            $log = match ($row->log_table) {
                'call_logs' => CallLog::find($row->call_log_id),
                'dialer_logs' => DialerLog::find($row->call_log_id),
                default => null,
            };
            if ($row->channel === 'webrtc' && (!$row->last_seen_at || Carbon::parse($row->last_seen_at)->lt($now->copy()->subSeconds(self::WEBRTC_STALE_SECONDS)))) {
                $close($row, 'no heartbeat');
            } elseif (Carbon::parse($row->answered_at)->lt($now->copy()->subMinutes($max))) {
                $close($row, 'longer than ' . $max . ' minutes');
            } elseif ($log && self::logFinished($log)) {
                $close($row, 'call log finished');
            } elseif (in_array($row->log_table, ['call_logs', 'dialer_logs'], true) && !$log) {
                $close($row, 'call log deleted');
            } elseif ($row->log_table === 'inbound_calls' && DB::table('inbound_calls')->where('id', $row->call_log_id)->whereNotNull('end_time')->exists()) {
                $close($row, 'inbound call ended');
            } elseif ($row->channel === 'click2call' && $providerRecord && $log && Carbon::parse($row->last_seen_at)->lt($now->copy()->subSeconds(30))
                && !empty($log->reference_id) && !empty($log->vmn_used)) {
                // nobody's page asked for 30 s: ask the provider whether the call is still on
                $payload = $providerRecord($log->reference_id, $log->vmn_used);
                $facts = $payload ? CallRecordingService::providerCallFacts($payload) : null;
                if ($facts && $facts['ended']) {
                    try {
                        app(CallRecordingService::class)->processWebhook($payload);   // saves the real outcome too
                    } catch (\Throwable $e) {
                        Log::warning('[ActiveCalls] Provider outcome not saved: ' . $e->getMessage());
                    }
                    $close($row, 'provider reports ended');
                } elseif ($facts) {
                    self::heartbeat($row->call_key);
                }
            }
        }
        return $closed;
    }
}
