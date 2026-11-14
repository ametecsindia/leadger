<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\NotificationsController;
use App\Models\Lead;
use App\Models\Status;
use App\Models\User;
use App\Services\ActiveCallService;
use App\Services\InboundCallNotifier;
use App\Services\InboundCallService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Inbound call notifications: ringing -> the matched agent only; answered -> the same notification + an active call;
 * ended -> popup closes; missed -> lead owner, team leader and admins; no duplicates; inactive users and old calls get
 * nothing; a failure in the notification code never stops saving the inbound call. Rows are rolled back.
 */
class InboundNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    private const AGENT_NO = '9900077001';
    private const CUSTOMER = '9900077002';
    private const VMN = '9900077000';

    private User $agent;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        ActiveCallService::resetReady();
        if (!InboundCallNotifier::ready() || !ActiveCallService::ready()) {
            $this->markTestSkipped('run php artisan migrate first');
        }
        config(['services.telephony.inbound_single_company' => true]);   // the test VMN is not in Add Voucher
        DB::table('active_calls')->delete();
        $this->agent = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->where('status', 1)->firstOrFail();
        DB::table('users')->where('id', $this->agent->id)->update(['click_agent_number' => self::AGENT_NO, 'status' => 1]);
    }

    private function event(string $callId, array $fields): array
    {
        return (new InboundCallService())->record(['call_id' => $callId, 'cli' => self::CUSTOMER, 'dni' => self::VMN,
            'start_time' => now()->format('Y-m-d H:i:s')] + $fields);
    }

    private function rows(string $callId)
    {
        return DB::table('notifications')->where('call_id', $callId)->get();
    }

    public function test_ringing_answered_and_ended_update_one_notification_of_the_agent_only()
    {
        $id = 'TESTIN_' . uniqid();
        $this->event($id, ['event' => 'ringing', 'agent_number' => '+91 ' . self::AGENT_NO]);
        $rows = $this->rows($id);
        $this->assertCount(1, $rows, 'only the agent the call is routed to');
        $this->assertSame($this->agent->id, (int)$rows[0]->user_id);
        $this->assertSame('inbound_ringing', $rows[0]->type);
        $this->assertSame(0, (int)$rows[0]->open_status);
        $this->assertStringContainsString('Incoming call from +91 ' . self::CUSTOMER, $rows[0]->body);
        $this->assertStringContainsString('on VMN ' . self::VMN, $rows[0]->body);
        $this->assertSame(0, ActiveCallService::liveCount(), 'ringing is not on call');

        $this->event($id, ['event' => 'answered']);
        $rows = $this->rows($id);
        $this->assertCount(1, $rows, 'the same notification is updated');
        $this->assertSame('inbound_answered', $rows[0]->type);
        $this->assertStringContainsString('On a call with', $rows[0]->body);
        $this->assertSame(1, ActiveCallService::liveCount(), 'answered inbound call is on call');
        $this->assertSame('inbound', ActiveCallService::liveList($this->admin)[0]['direction']);

        $this->event($id, ['event' => 'call ended', 'duration' => 35, 'end_time' => now()->format('Y-m-d H:i:s')]);
        $this->assertSame('ended', json_decode($this->rows($id)[0]->data, true)['state'], 'the popup closes');
        $this->assertSame(0, ActiveCallService::liveCount());
    }

    public function test_a_missed_call_notifies_the_lead_owner_the_team_leader_and_the_admins_once()
    {
        $team = '990077';
        DB::table('users')->where('id', $this->agent->id)->update(['team_id' => $team]);
        $leaderRole = DB::table('permissions')->where('name', 'Team Leader')->value('id');
        $leaderId = $leaderRole ? DB::table('users')->insertGetId(['name' => 'PHPUNIT Team Leader', 'email' => 'phpunit.tl.' . uniqid() . '@example.test',
            'password' => bcrypt('x'), 'permissions_id' => $leaderRole, 'team_id' => $team, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]) : null;
        $lead = Lead::forceCreate(['name' => 'PHPUNIT Inbound Lead', 'phonenumber' => self::CUSTOMER, 'assigned_to' => $this->agent->id,
            'status' => Status::first()->id ?? 1, 'created_by' => $this->admin->id]);

        $id = 'TESTIN_' . uniqid();
        $this->event($id, ['event' => 'no answer']);   // no agent involved
        $this->event($id, ['event' => 'no answer']);   // duplicate event

        $users = $this->rows($id)->pluck('user_id')->map(fn ($v) => (int)$v)->sort()->values()->all();
        $admins = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->where('status', 1)->pluck('id')->all();
        $expected = collect(array_merge([$this->agent->id], $admins, $leaderId ? [$leaderId] : []))->map(fn ($v) => (int)$v)->unique()->sort()->values()->all();
        $this->assertSame($expected, $users, 'lead owner + team leader + admins, one row each');
        $row = $this->rows($id)->firstWhere('user_id', $this->agent->id);
        $this->assertSame('inbound_missed', $row->type);
        $this->assertSame(0, (int)$row->open_status, 'missed stays in the bell until read');
        $this->assertStringContainsString('Missed call from +91 ' . self::CUSTOMER, $row->body);
        $this->assertStringContainsString('PHPUNIT Inbound Lead', $row->body);
        $this->assertSame($lead->id, (int)DB::table('inbound_calls')->where('call_id', $id)->value('lead_id'), 'the caller is that lead');
    }

    public function test_the_recipient_rule_is_a_setting_and_inactive_users_get_nothing()
    {
        config(['services.telephony.inbound_notify_missed' => 'agent']);
        DB::table('users')->where('id', $this->agent->id)->update(['status' => 0]);
        $id = 'TESTIN_' . uniqid();
        $this->event($id, ['event' => 'missed', 'agent_number' => self::AGENT_NO]);
        $this->assertCount(0, $this->rows($id), 'only the agent (inactive): nobody');
    }

    public function test_an_old_call_pulled_later_notifies_nobody()
    {
        $id = 'TESTIN_' . uniqid();
        (new InboundCallService())->record(['call_id' => $id, 'cli' => self::CUSTOMER, 'dni' => self::VMN, 'agent_number' => self::AGENT_NO,
            'start_time' => now()->subHours(3)->format('Y-m-d H:i:s'), 'event' => 'no answer'], 'pull');
        $this->assertCount(0, $this->rows($id));
        $this->assertTrue(DB::table('inbound_calls')->where('call_id', $id)->exists(), 'the call itself is saved');
    }

    public function test_a_failure_in_the_notification_code_does_not_stop_saving_the_call()
    {
        DB::listen(function ($q) {
            if (str_contains($q->sql, 'notifications') && str_starts_with(strtolower(ltrim($q->sql)), 'select')) {
                throw new \RuntimeException('notification store down (test)');
            }
        });
        $id = 'TESTIN_' . uniqid();
        $r = $this->event($id, ['event' => 'ringing', 'agent_number' => self::AGENT_NO]);
        $this->assertSame('created', $r['result']);
        $this->assertTrue(DB::table('inbound_calls')->where('call_id', $id)->exists());
    }

    public function test_a_rabbitmq_inbound_call_is_saved_even_when_its_vmn_is_not_in_add_voucher()
    {
        config(['services.telephony.inbound_single_company' => false]);   // several companies on this installation
        $ours = 'TESTIN_' . uniqid();
        $this->assertSame('created', (new InboundCallService())->record(['call_id' => $ours, 'cli' => self::CUSTOMER, 'dni' => self::VMN,
            'event_type' => 'Call End', 'duration' => 0, 'start_time' => now()->format('Y-m-d H:i:s')], 'rabbitmq')['result'],
            'RabbitMQ queue = this company (its CRM id)');
        $this->assertSame('missed', DB::table('inbound_calls')->where('call_id', $ours)->value('status'));

        $unknown = 'TESTIN_' . uniqid();
        $this->assertSame('ignored', (new InboundCallService())->record(['call_id' => $unknown, 'cli' => self::CUSTOMER, 'dni' => self::VMN,
            'event' => 'ringing'], 'webhook')['result'], 'a webhook for an unknown VMN still waits for the mapping');
    }

    public function test_older_rabbitmq_inbound_calls_are_imported_into_the_report_once()
    {
        $callId = 'TESTIN_' . uniqid();
        DB::table('incoming_vmn_logs')->insert(['call_id' => $callId, 'dni' => self::VMN, 'a_party_no' => self::AGENT_NO,
            'a_start_time' => now()->subHours(2), 'a_dial_status' => '', 'cli' => self::CUSTOMER, 'recording_url' => '',
            'duration' => 47, 'response' => '{}', 'file_path' => '', 'created_at' => now(), 'updated_at' => now()]);

        $this->artisan('inbound:import-vmn-logs', ['--days' => 1])->assertSuccessful();
        $this->artisan('inbound:import-vmn-logs', ['--days' => 1])->assertSuccessful();

        $rows = DB::table('inbound_calls')->where('call_id', $callId)->get();
        $this->assertCount(1, $rows, 'no duplicate');
        $this->assertSame('answered', $rows[0]->status);
        $this->assertSame(47, (int)$rows[0]->duration_seconds);
        $this->assertSame($this->agent->id, (int)$rows[0]->agent_user_id);
        $this->assertCount(0, $this->rows($callId), 'an old call notifies nobody');
    }

    public function test_the_poll_returns_the_agents_notifications_and_mark_all_read_clears_the_count()
    {
        $id = 'TESTIN_' . uniqid();
        $this->event($id, ['event' => 'ringing', 'agent_number' => self::AGENT_NO]);
        Auth::login($this->agent);
        $res = app(NotificationsController::class)->poll(Request::create('/x', 'GET'))->getData(true);
        $this->assertGreaterThanOrEqual(1, $res['unread']);
        $item = collect($res['items'])->firstWhere('data.inbound_id', (int)DB::table('inbound_calls')->where('call_id', $id)->value('id'));
        $this->assertSame('ringing', $item['data']['state']);
        $this->assertSame(self::CUSTOMER, $item['data']['number']);
        $this->assertNotEmpty($item['data']['url'], 'Open lead / Manual Dial link');

        Auth::login($this->admin);
        $this->assertFalse(collect(app(NotificationsController::class)->poll(Request::create('/x', 'GET'))->getData(true)['items'])
            ->contains(fn ($n) => ($n['data']['inbound_id'] ?? 0) === $item['data']['inbound_id']), 'another user does not see it');

        Auth::login($this->agent);
        app(NotificationsController::class)->readCalls(Request::create('/x', 'POST'));
        $this->assertSame(0, app(NotificationsController::class)->poll(Request::create('/x', 'GET'))->getData(true)['unread']);
    }
}
