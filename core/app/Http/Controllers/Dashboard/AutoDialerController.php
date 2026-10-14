<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AutoDialSession;
use App\Models\Lead;
use App\Services\CallingRestrictionService;
use App\Services\VmnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auto Dialer on the Calling Workspace (Campaign Queue tab).
 *
 * The browser places every call with the page's own campaign calling code (Click2Call / WebRTC buttons), so call
 * logs, recordings, calling hours, retry limits, the agent number check and VMN checks are the normal ones.
 * This controller only keeps the session: start checks, which lead is next, counters, pause / resume / stop.
 * One active (running or paused) session per agent.
 */
class AutoDialerController extends Controller
{
    private const TYPES = ['click2call', 'webrtc'];
    private const MAX_FAILURES_IN_ROW = 3;
    private const STALE_RUNNING_SECONDS = 45;   // no heartbeat while running: connection lost / tab closed
    private const STALE_PAUSED_MINUTES = 30;    // a paused session nobody came back to
    private const BUSY_CALL_MINUTES = 15;       // another agent's call on a lead within this time = lead in use
    private const OPEN_CALL_STATES = ['initiated', 'calling', 'ringing', 'answered', 'connected', 'in-progress', 'in_progress'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /** GET: the agent's active session (heartbeat). ?details=1 adds the remaining queue count. */
    public function status(Request $request)
    {
        $session = $this->active();
        if ($session) {
            $session->last_heartbeat_at = now();
            $session->save();
        }
        return response()->json(['success' => true, 'session' => $session ? $this->payload($session, $request->boolean('details')) : null]);
    }

    public function start(Request $request)
    {
        $user = Auth::user();
        $campaignId = (int)$request->input('campaign_id');
        $type = (string)$request->input('dialer_type');

        if ($campaignId <= 0) {
            return $this->fail('Select a campaign to start auto dialing');
        }
        if (!in_array($type, self::TYPES, true)) {
            return $this->fail('Choose a dialer type (Click2Call or WebRTC).');
        }

        $lock = Cache::lock('autodial_start_' . $user->id, 10);
        if (!$lock->get()) {
            return $this->fail('An auto dialer session is already starting. Please wait a moment.', 409);
        }
        try {
            if ($this->active()) {
                return $this->fail('An auto dialer session is already running for you.', 409);
            }
            $campaign = CampaignsController::getAllowedCampaignsForUser($user)->firstWhere('id', $campaignId);
            if (!$campaign) {
                return $this->fail('You are not assigned to this campaign.', 403);
            }
            $hours = CallingRestrictionService::checkCallingHours();
            if (!$hours['allowed']) {
                return $this->fail('Calling hours are over. ' . $hours['message']);
            }
            if ($error = $this->dialerTypeError($user, $type)) {
                return $this->fail($error);
            }
            if (!$this->queueLeadIds($campaignId)) {
                return $this->fail('No leads in the queue');
            }

            $session = AutoDialSession::create([
                'user_id' => $user->id,
                'campaign_id' => $campaignId,
                'dialer_type' => $type,
                'status' => 'running',
                'calls_made' => 0,
                'failures_in_row' => 0,
                'dialed_lead_ids' => [],
                'skipped' => [],
                'started_at' => now(),
                'last_heartbeat_at' => now(),
            ]);
            Log::info('[AutoDialer] session started', ['session' => $session->id, 'user' => $user->id, 'campaign' => $campaignId, 'type' => $type]);
            return response()->json(['success' => true, 'session' => $this->payload($session, true)]);
        } finally {
            $lock->release();
        }
    }

    /**
     * POST: the next lead to dial, in the queue's own order (same list and filters as "Next leads"), skipping leads
     * already dialed in this session, leads another agent is calling right now, and leads the calling rules block
     * (retry limit, invalid number, DND status). Ends the session when the queue is done or calling hours are over.
     */
    public function next()
    {
        $user = Auth::user();
        $session = $this->active();
        if (!$session) {
            return $this->fail('The auto dialer session has ended.', 409);
        }
        if ($session->status !== 'running') {
            return response()->json(['success' => true, 'status' => 'paused', 'session' => $this->payload($session)]);
        }
        if ($session->pending_outcome) {
            return $this->fail('Save the call outcome before the next call.', 409);
        }
        if (!CallingRestrictionService::checkCallingHours()['allowed']) {
            return $this->ended($session, 'calling_hours_over', 'Calling hours are over');
        }

        $dialed = array_map('intval', $session->dialed_lead_ids ?? []);
        $skipped = $session->skipped ?? [];
        $skippedIds = array_map(fn ($s) => (int)$s['lead_id'], $skipped);
        $busy = $this->leadsInUseByOthers($user->id);
        $dndIds = DB::table('statuses')->whereRaw('LOWER(TRIM(name)) = ?', ['dnd'])->pluck('id')->map(fn ($id) => (int)$id)->all();
        $skippedNow = [];

        $queue = $this->queueLeadIds($session->campaign_id);
        $pending = array_values(array_diff($queue, $dialed, $skippedIds));
        foreach ($pending as $i => $leadId) {
            if (in_array($leadId, $busy, true)) {
                continue;   // another agent is on it: leave it in the queue
            }
            $lead = Lead::find($leadId);
            $phone = $lead ? ($lead->mobile ?: $lead->phonenumber) : null;
            $reason = null;
            if (!$lead || !$phone) {
                $reason = 'invalid_number';
            } elseif ($dndIds && in_array((int)$lead->status, $dndIds, true)) {
                $reason = 'do_not_call';
            } else {
                $check = CallingRestrictionService::canPlaceOutboundCall($phone, $user, $session->dialer_type, false);
                if (!$check['allowed']) {
                    if (($check['reason'] ?? '') === 'outside_hours') {
                        return $this->ended($session, 'calling_hours_over', 'Calling hours are over');
                    }
                    $reason = $check['reason'] ?: 'blocked';
                }
            }
            if ($reason) {
                $skipped[] = ['lead_id' => $leadId, 'reason' => $reason, 'at' => now()->toDateTimeString()];
                $skippedNow[] = ['lead_id' => $leadId, 'reason' => $reason];
                Log::info('[AutoDialer] lead skipped', ['session' => $session->id, 'lead' => $leadId, 'reason' => $reason]);
                continue;
            }

            $dialed[] = $leadId;
            $session->forceFill([
                'dialed_lead_ids' => $dialed,
                'skipped' => $skipped,
                'current_lead_id' => $leadId,
                'last_heartbeat_at' => now(),
            ])->save();
            return response()->json([
                'success' => true,
                'status' => 'ok',
                'lead_id' => $leadId,
                'skipped' => $skippedNow,
                'session' => $this->payload($session) + ['remaining' => count($pending) - $i - 1],
            ]);
        }

        $session->skipped = $skipped;
        $session->save();
        if (array_intersect($pending, $busy)) {
            // Only leads other agents are calling are left: try again in a moment
            return response()->json(['success' => true, 'status' => 'wait', 'message' => 'Waiting for leads other agents are calling.',
                'skipped' => $skippedNow, 'session' => $this->payload($session)]);
        }
        return $this->ended($session, 'queue_completed', 'Queue completed', $skippedNow);
    }

    /**
     * POST event: call_started {call_log_id}, call_failed {reason}, outcome_saved {lead_id}.
     * Returns what the page should do next: continue | pause | end | none.
     */
    public function event(Request $request)
    {
        $session = $this->active();
        if (!$session) {
            return response()->json(['success' => true, 'action' => 'none', 'session' => null]);
        }
        $session->last_heartbeat_at = now();
        $action = 'continue';
        $message = null;

        switch ((string)$request->input('type')) {
            case 'call_started':
                $session->calls_made++;
                $session->failures_in_row = 0;
                $session->pending_outcome = true;
                // Flag the call log row the normal campaign call created (only this agent's call on the current lead)
                $callLogId = (int)$request->input('call_log_id');
                if ($callLogId > 0) {
                    DB::table('call_logs')->where('id', $callLogId)->where('created_by', Auth::id())
                        ->when($session->current_lead_id, fn ($q) => $q->where('lead_id', $session->current_lead_id))
                        ->update(['is_auto_dial' => 1, 'auto_dial_session_id' => $session->id]);
                }
                $action = 'none';
                break;

            case 'call_failed':
                $reason = (string)$request->input('reason');
                $session->pending_outcome = false;
                if ($reason === 'outside_hours') {
                    return $this->ended($session, 'calling_hours_over', 'Calling hours are over');
                }
                if (in_array($reason, ['retry_limit', 'invalid_number'], true)) {
                    // Blocked by the calling rules: the lead is skipped, not a gateway failure
                    $skipped = $session->skipped ?? [];
                    $skipped[] = ['lead_id' => (int)$session->current_lead_id, 'reason' => $reason, 'at' => now()->toDateTimeString()];
                    $session->skipped = $skipped;
                    Log::info('[AutoDialer] lead skipped', ['session' => $session->id, 'lead' => $session->current_lead_id, 'reason' => $reason]);
                    break;
                }
                $session->failures_in_row++;
                Log::warning('[AutoDialer] call failed', ['session' => $session->id, 'lead' => $session->current_lead_id,
                    'reason' => $reason, 'message' => mb_substr((string)$request->input('message'), 0, 200), 'in_row' => $session->failures_in_row]);
                if ($session->failures_in_row >= self::MAX_FAILURES_IN_ROW) {
                    $session->status = 'paused';
                    $action = 'pause';
                    $message = 'Auto dialer paused after repeated failures';
                }
                break;

            case 'outcome_saved':
                if ((int)$request->input('lead_id') === (int)$session->current_lead_id) {
                    $session->pending_outcome = false;
                }
                $action = $session->status === 'running' ? 'continue' : 'none';
                break;

            default:
                return $this->fail('Unknown event.');
        }

        $session->save();
        return response()->json(['success' => true, 'action' => $action, 'message' => $message, 'session' => $this->payload($session)]);
    }

    public function pause(Request $request)
    {
        $session = $this->active();
        if (!$session) {
            return response()->json(['success' => true, 'session' => null]);
        }
        $session->status = 'paused';
        $session->last_heartbeat_at = now();
        $session->save();
        return response()->json(['success' => true, 'session' => $this->payload($session, $request->boolean('details'))]);
    }

    public function resume(Request $request)
    {
        $user = Auth::user();
        $session = $this->active();
        if (!$session) {
            return $this->fail('The auto dialer session has ended.', 409);
        }
        $hours = CallingRestrictionService::checkCallingHours();
        if (!$hours['allowed']) {
            return $this->ended($session, 'calling_hours_over', 'Calling hours are over');
        }
        if ($error = $this->dialerTypeError($user, $session->dialer_type)) {
            return $this->fail($error);
        }
        $session->status = 'running';
        $session->failures_in_row = 0;
        $session->last_heartbeat_at = now();
        $session->save();
        return response()->json(['success' => true, 'session' => $this->payload($session, true)]);
    }

    public function stop(Request $request)
    {
        $session = $this->active();
        if ($session) {
            $session->end($request->input('reason') === 'connection_lost' ? 'connection_lost' : 'stopped');
        }
        return response()->json(['success' => true, 'session' => $session ? $this->payload($session) : null]);
    }

    /** Ends the agent's active session (used on logout). */
    public static function endFor(int $userId, string $reason): void
    {
        AutoDialSession::where('user_id', $userId)->active()->get()->each(fn ($s) => $s->end($reason));
    }

    /* ------------------------------------------------------------------ */

    /** The agent's running / paused session; stale ones are ended first. */
    private function active(): ?AutoDialSession
    {
        $session = AutoDialSession::where('user_id', Auth::id())->active()->latest('id')->first();
        if (!$session) {
            return null;
        }
        $beat = $session->last_heartbeat_at ?? $session->updated_at;
        if ($session->status === 'running' && $beat && $beat->lt(now()->subSeconds(self::STALE_RUNNING_SECONDS))) {
            $session->end('connection_lost');
            return null;
        }
        if ($session->status === 'paused' && $beat && $beat->lt(now()->subMinutes(self::STALE_PAUSED_MINUTES))) {
            $session->end('abandoned');
            return null;
        }
        return $session;
    }

    /** Why this agent cannot auto dial with this channel (null = ok). */
    private function dialerTypeError($user, string $type): ?string
    {
        if ($type === 'click2call') {
            if ((int)$user->c2c_channel !== 1) return 'Click2Call is not enabled for your account.';
            if ($user->click2CallAgentNumber() === null) return 'Your agent mobile number is not configured. Add it in your profile to use Click2Call.';
            if (!collect(VmnService::listFor($user, 'click2call'))->where('available', true)->count()) return 'No Click2Call VMN is assigned to you.';
            return null;
        }
        if ((int)$user->webrtc_channel !== 1) return 'WebRTC is not enabled for your account.';
        if (!collect(VmnService::listFor($user, 'webrtc'))->where('available', true)->count()) return 'No WebRTC VMN is assigned to you.';
        return null;
    }

    /** Lead ids of the campaign queue, exactly as the "Next leads" list builds it (order, role filters). */
    private function queueLeadIds(int $campaignId): array
    {
        $res = app(CampaignsController::class)->getCampaignQueueLeads($campaignId);
        $data = $res->getData(true);
        if (empty($data['success'])) {
            return [];
        }
        return array_map(fn ($l) => (int)$l['id'], $data['leads'] ?? []);
    }

    /** Leads another agent is calling right now (an open call, or the current lead of their auto dialer). */
    private function leadsInUseByOthers(int $userId): array
    {
        $onCall = DB::table('call_logs')->where('created_by', '!=', $userId)
            ->where('created_at', '>=', now()->subMinutes(self::BUSY_CALL_MINUTES))
            ->whereIn('call_state', self::OPEN_CALL_STATES)
            ->whereNotNull('lead_id')->pluck('lead_id');
        $autoDial = AutoDialSession::where('user_id', '!=', $userId)->active()->whereNotNull('current_lead_id')->pluck('current_lead_id');
        return $onCall->merge($autoDial)->map(fn ($id) => (int)$id)->unique()->values()->all();
    }

    private function payload(AutoDialSession $s, bool $details = false): array
    {
        $out = [
            'id' => $s->id,
            'campaign_id' => (int)$s->campaign_id,
            'campaign_name' => DB::table('campaigns')->where('id', $s->campaign_id)->value('name'),
            'dialer_type' => $s->dialer_type,
            'status' => $s->status,
            'calls_made' => (int)$s->calls_made,
            'failures_in_row' => (int)$s->failures_in_row,
            'current_lead_id' => $s->current_lead_id ? (int)$s->current_lead_id : null,
            'pending_outcome' => (bool)$s->pending_outcome,
            'end_reason' => $s->end_reason,
        ];
        if ($details && $s->isActive()) {
            $done = array_merge(array_map('intval', $s->dialed_lead_ids ?? []), array_map(fn ($x) => (int)$x['lead_id'], $s->skipped ?? []));
            $out['remaining'] = count(array_diff($this->queueLeadIds($s->campaign_id), $done));
        }
        return $out;
    }

    private function ended(AutoDialSession $session, string $reason, string $message, array $skipped = [])
    {
        $session->end($reason);
        Log::info('[AutoDialer] session ended', ['session' => $session->id, 'reason' => $reason, 'calls' => $session->calls_made]);
        return response()->json(['success' => true, 'status' => 'ended', 'reason' => $reason, 'message' => $message,
            'skipped' => $skipped, 'session' => $this->payload($session)]);
    }

    private function fail(string $message, int $code = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $code);
    }
}
