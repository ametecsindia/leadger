<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DialerController;
use App\Models\CallLog;
use App\Models\DialerLog;
use App\Models\User;
use App\Services\ActiveCallService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dashboard "On Call Now": a call counts only once both sides answered, until it ends; ghost calls are closed;
 * role scope; the dashboard endpoint is never stale. Controllers are called directly (the Tenants middleware would
 * leave the test transaction); every row is rolled back.
 */
class ActiveCallsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        ActiveCallService::resetReady();
        if (!ActiveCallService::ready()) {
            $this->markTestSkipped('active_calls table missing: php artisan migrate');
        }
        DB::table('active_calls')->delete();   // only this test's calls are counted (rolled back afterwards)
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->agent = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
    }

    private function manualCall(User $by, string $type = 'webrtc'): DialerLog
    {
        Auth::login($by);
        return DialerLog::forceCreate(['phone_number' => '9876543210', 'created_by' => $by->id, 'call_type' => $type,
            'call_source' => 'manual', 'call_state' => 'initiated', 'reference_id' => 'TESTAC_' . uniqid(), 'uuid' => 'TESTAC_' . uniqid(),
            'time_taken' => 0, 'duration' => '00:00', 'start_time' => now()]);
    }

    private function state(DialerLog $log, string $state, int $duration = 0): void
    {
        Auth::login(User::find($log->created_by));
        $res = app(DialerController::class)->updateCallState(Request::create('/x', 'POST', ['id' => $log->id, 'state' => $state, 'duration' => $duration]));
        $this->assertTrue($res->getData(true)['success'] ?? false, json_encode($res->getData(true)));
    }

    private function onCall($viewer = null): int
    {
        return ActiveCallService::liveCount($viewer ?? $this->admin);
    }

    public function test_ringing_does_not_count_answered_counts_hangup_removes()
    {
        $a = $this->manualCall($this->agent);
        $this->state($a, 'initiated');
        $this->state($a, 'ringing');
        $this->assertSame(0, $this->onCall(), 'ringing is not a call in progress');

        $this->state($a, 'answered');   // WebRTC: 200 OK + ICE + customer audio
        $this->assertSame(1, $this->onCall());

        $b = $this->manualCall($this->admin);
        $this->state($b, 'answered');
        $this->assertSame(2, $this->onCall(), 'two calls in progress');

        $this->state($a, 'ended', 42);
        $this->assertSame(1, $this->onCall(), 'hang-up ends it');
        $row = DB::table('active_calls')->where('call_key', 'dialer:' . $a->id)->first();
        $this->assertNotNull($row->ended_at);
        $this->assertSame('completed', $a->fresh()->call_state, 'the call log is final too');

        $this->state($a, 'answered');   // a late event never reopens a finished call
        $this->assertSame(1, $this->onCall());
    }

    public function test_failed_or_unanswered_calls_never_count()
    {
        foreach (['busy', 'no-answer', 'failed', 'cancelled'] as $st) {
            $log = $this->manualCall($this->agent);
            $this->state($log, 'ringing');
            $this->state($log, $st);
        }
        // WebRTC "ended" without a true answer is a failed call, not a call in progress
        $log = $this->manualCall($this->agent);
        $this->state($log, 'ended');
        $this->assertSame(0, $this->onCall());
        $this->assertSame(0, DB::table('active_calls')->count());
    }

    public function test_click2call_counts_when_the_provider_reports_both_legs_up_and_any_finished_log_ends_it()
    {
        $log = CallLog::forceCreate(['phonenumber' => '9876500011', 'created_by' => $this->agent->id, 'call_type' => 'click2call',
            'call_source' => 'campaign', 'call_state' => 'initiated', 'reference_id' => 'TESTAC_' . uniqid(), 'duration' => 0]);
        ActiveCallService::answeredFromLog($log, 'click2call', Carbon::now()->subSeconds(20));
        $this->assertSame(1, $this->onCall());
        $call = ActiveCallService::liveList($this->admin)[0];
        $this->assertSame('click2call', $call['channel']);
        $this->assertSame('outbound', $call['direction']);
        $this->assertEqualsWithDelta((Carbon::now()->getTimestamp() - 20) * 1000, $call['answered_ms'], 2000, 'timer from the answer');

        // the agent saves the outcome (any code path that finishes the log)
        $log->call_state = 'completed';
        $log->end_time = now();
        $log->save();
        $this->assertSame(0, $this->onCall());
    }

    public function test_a_webrtc_call_without_heartbeat_is_not_counted_and_is_closed_by_the_cleanup()
    {
        $log = $this->manualCall($this->agent);
        $this->state($log, 'answered');
        Auth::login($this->agent);
        $this->assertTrue(app(DialerController::class)->activeCallHeartbeat(Request::create('/x', 'POST', ['id' => $log->id]))->getData(true)['active']);

        DB::table('active_calls')->where('call_key', 'dialer:' . $log->id)->update(['last_seen_at' => now()->subSeconds(95)]);
        $this->assertSame(0, $this->onCall(), 'no heartbeat for 90 s: not live');

        $this->assertSame(1, ActiveCallService::cleanup());
        $this->assertSame('no heartbeat', DB::table('active_calls')->where('call_key', 'dialer:' . $log->id)->value('end_reason'));
    }

    public function test_cleanup_closes_too_long_and_finished_calls_and_logout_closes_the_agents_calls()
    {
        $long = CallLog::forceCreate(['phonenumber' => '9876500012', 'created_by' => $this->agent->id, 'call_type' => 'click2call',
            'call_source' => 'campaign', 'call_state' => 'initiated', 'reference_id' => 'TESTAC_' . uniqid(), 'duration' => 0]);
        ActiveCallService::answeredFromLog($long, 'click2call', Carbon::now()->subHours(4));
        $done = CallLog::forceCreate(['phonenumber' => '9876500013', 'created_by' => $this->agent->id, 'call_type' => 'click2call',
            'call_source' => 'campaign', 'call_state' => 'initiated', 'reference_id' => 'TESTAC_' . uniqid(), 'duration' => 0]);
        ActiveCallService::answeredFromLog($done, 'click2call');
        DB::table('call_logs')->where('id', $done->id)->update(['end_time' => now()]);   // finished without model events (raw SQL)
        $this->assertSame(2, ActiveCallService::cleanup());

        $c = CallLog::forceCreate(['phonenumber' => '9876500014', 'created_by' => $this->agent->id, 'call_type' => 'click2call',
            'call_source' => 'campaign', 'call_state' => 'initiated', 'reference_id' => 'TESTAC_' . uniqid(), 'duration' => 0]);
        ActiveCallService::answeredFromLog($c, 'click2call');
        $this->assertSame(1, ActiveCallService::endForUser($this->agent->id));
        $this->assertSame(0, $this->onCall());
    }

    public function test_role_scope()
    {
        $mine = $this->manualCall($this->agent);
        $this->state($mine, 'answered');
        $other = $this->manualCall($this->admin);
        $this->state($other, 'answered');

        $this->assertSame(2, $this->onCall($this->admin), 'admin: the whole company');
        $this->assertSame(1, $this->onCall($this->agent), 'telecaller: only their own call');
        $this->assertSame([$this->agent->id], array_column(ActiveCallService::liveList($this->agent), 'agent_user_id'));

        $leader = new User();
        $leader->id = 999991;
        $leader->team_id = (string)($this->agent->team_id ?: '');
        $leader->setRelation('permissionsGroup', (new \App\Models\Permissions())->forceFill(['name' => 'Team Leader']));
        $visible = ActiveCallService::visibleAgentIds($leader);
        $this->assertContains(999991, $visible);
        $this->assertSame($this->agent->team_id ? true : false, in_array($this->agent->id, $visible, true), 'team leader: their team');
        if (!array_intersect(\App\Services\VmnService::teamIds($this->admin), \App\Services\VmnService::teamIds($this->agent))) {
            $this->assertNotContains($this->admin->id, $visible, 'team leader: not other teams');
        }
    }

    public function test_the_dashboard_endpoint_returns_the_live_number_without_caching()
    {
        Auth::login($this->admin);
        $live = fn () => app(DashboardController::class)->liveCalls()->getData(true);
        $this->assertSame(0, $live()['on_call']);

        $log = $this->manualCall($this->agent);
        $this->state($log, 'answered');
        Auth::login($this->admin);
        $d = $live();
        $this->assertSame(1, $d['on_call'], 'seen at once, not cached');
        $this->assertSame('WebRTC', ['webrtc' => 'WebRTC'][$d['calls'][0]['channel']]);
        $this->assertArrayHasKey($this->agent->id, $d['agents'], 'the agent shows "On call" on the activity floor');

        $this->state($log, 'ended', 30);
        Auth::login($this->admin);
        $this->assertSame(0, $live()['on_call']);
    }
}

