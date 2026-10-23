<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Middleware\TelecallerMiddleware;
use App\Models\DialerLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Testing feedback (Oct 2026): Click2Call stops spinning once the call is closed on our side, Start Calling opens the
 * campaign's first lead the user may open (queue without other agents' leads), and a lead's Calls count only counts
 * calls whose feedback was saved.
 */
class CallingWorkspaceFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
    }

    private function callStatusOf(DialerLog $log): array
    {
        Auth::login($this->admin);
        $req = Request::create('/dialer/call-status', 'GET', ['id' => $log->id, 'is_campaign' => 0]);
        app()->instance('request', $req);
        return json_decode(app(DialerController::class)->callStatus($req)->getContent(), true);
    }

    private function manualCall(array $attrs): DialerLog
    {
        return DialerLog::forceCreate(array_merge(['phone_number' => '9' . random_int(100000000, 999999999), 'created_by' => $this->admin->id,
            'time_taken' => 0, 'duration' => '00:00', 'call_type' => 'click2call', 'call_source' => 'manual', 'call_state' => 'initiated',
            'start_time' => Carbon::now()->subMinutes(2), 'reference_id' => 'TESTFLOW_' . uniqid(), 'vmn_used' => null], $attrs));
    }

    public function test_a_call_closed_on_our_side_is_final_without_provider_data()
    {
        $open = $this->manualCall([]);
        $this->assertFalse($this->callStatusOf($open)['final'], 'still followed while open');

        $saved = $this->manualCall(['call_state' => 'completed', 'end_time' => Carbon::now(), 'time_taken' => 42, 'duration' => '00:42']);
        $res = $this->callStatusOf($saved);
        $this->assertSame(['ended', true, 42], [$res['status'], $res['final'], $res['duration']]);

        $noAnswer = $this->manualCall(['call_state' => 'no-answer', 'end_time' => Carbon::now()]);
        $this->assertSame('no_answer', $this->callStatusOf($noAnswer)['status']);
    }

    public function test_start_calling_opens_the_users_own_lead_and_hides_other_agents_leads()
    {
        $telecaller = DB::table('permissions')->where('name', 'Telecaller')->value('id');
        $mk = fn ($name) => DB::table('users')->insertGetId(['name' => $name, 'email' => uniqid('flow', true) . '@example.test', 'password' => bcrypt('x'),
            'permissions_id' => $telecaller, 'status' => 1, 'team_id' => '977', 'created_at' => now(), 'updated_at' => now()]);
        [$agent, $other] = [$mk('Flow Agent'), $mk('Flow Other')];
        $campaign = DB::table('campaigns')->insertGetId(['name' => 'Flow Campaign', 'source_id' => 0, 'segment_id' => 0, 'team_id' => '977', 'status' => '1',
            'created_by' => $this->admin->id, 'expiry_date' => now()->addDays(30), 'created_at' => now(), 'updated_at' => now()]);
        $lead = fn ($phone, $to) => DB::table('leads')->insertGetId(['name' => 'Flow Lead ' . $phone, 'campaign_id' => $campaign, 'phonenumber' => $phone,
            'assigned_to' => $to, 'is_public' => '0', 'created_at' => now(), 'updated_at' => now()]);
        $lead('9000000101', $other);            // first in the campaign, but another agent's
        $own = $lead('9000000102', $agent);

        $this->actingAs(User::find($agent))->get(route('leadCallingView', ['campaignId' => $campaign, 'leadId' => 0]))
            ->assertRedirect(route('leadCallingView', ['campaignId' => $campaign, 'leadId' => $own]));
        $this->get(route('leadCallingView', ['campaignId' => $campaign, 'leadId' => $own]))
            ->assertOk()->assertSee('9000000102')->assertDontSee('9000000101');
    }

    public function test_calls_count_counts_only_calls_with_feedback()
    {
        $campaign = DB::table('campaigns')->value('id');
        $lead = DB::table('leads')->insertGetId(['name' => 'Count Lead', 'campaign_id' => $campaign, 'phonenumber' => '9000000201',
            'created_at' => now(), 'updated_at' => now()]);
        $status = DB::table('statuses')->value('id');
        foreach ([$status, null, null] as $next) {
            DB::table('call_logs')->insert(['lead_id' => $lead, 'campaign_id' => $campaign, 'phonenumber' => '9000000201', 'call_source' => 'campaign',
                'next_status' => $next, 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
        }
        $res = $this->actingAs($this->admin)->getJson("/admin/campaigns/{$campaign}/lead/{$lead}/calling-data")->assertOk()->json();
        $this->assertSame(1, $res['lead']['calls_count']);
        $this->assertCount(3, $res['callLogs'], 'the history still lists every call');
    }
}
