<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\InboundCall;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Inbound Calls: the customer mobile link opens the campaign lead calling page or Manual Dial with the number. */
class InboundCallLinkTest extends TestCase
{
    use DatabaseTransactions;

    private const NUMBER = '9154936399';

    private User $admin;
    private int $agent;
    private int $otherAgent;
    private int $campaignA;
    private int $campaignB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection

        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->agent = $this->user('Link Agent', '901');
        $this->otherAgent = $this->user('Other Link Agent', '902');
        $this->campaignA = $this->campaign('Link Campaign A', '901');
        $this->campaignB = $this->campaign('Link Campaign B', '901');
    }

    private function user(string $name, string $team): int
    {
        return DB::table('users')->insertGetId(['name' => $name, 'email' => uniqid('link', true) . '@example.test', 'password' => bcrypt('x'),
            'permissions_id' => DB::table('permissions')->where('name', 'Telecaller')->value('id'), 'status' => 1, 'team_id' => $team,
            'created_at' => now(), 'updated_at' => now()]);
    }

    private function campaign(string $name, string $team, string $expiry = '+30 days'): int
    {
        return DB::table('campaigns')->insertGetId(['name' => $name, 'source_id' => 0, 'segment_id' => 0, 'team_id' => $team, 'status' => '1',
            'created_by' => $this->admin->id, 'expiry_date' => date('Y-m-d H:i:s', strtotime($expiry)), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function lead(int $campaign, string $phone, ?int $assigned, ?string $lastAction = null): int
    {
        return DB::table('leads')->insertGetId(['name' => 'Link Lead', 'campaign_id' => $campaign, 'phonenumber' => $phone,
            'assigned_to' => $assigned, 'is_public' => '0', 'last_actioned_date' => $lastAction, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function inbound(string $customer, ?int $agent): InboundCall
    {
        return InboundCall::create(['call_id' => uniqid('LINK-'), 'customer_mobile' => $customer,
            'customer_mobile_normalized' => \App\Services\InboundCallService::normalizeMobile($customer), 'vmn' => '8045670001',
            'start_time' => now(), 'agent_user_id' => $agent, 'status' => $agent ? 'answered' : 'missed']);
    }

    private function open(int $user, InboundCall $call)
    {
        $this->actingAs(User::find($user));
        return $this->get(route('inboundCallOpen', ['inboundCall' => $call->id]));
    }

    private function manualUrl(string $n): string
    {
        return route('dialer', ['tab' => 'manual', 'number' => $n]);
    }

    public function test_a_lead_in_one_active_campaign_opens_its_calling_page()
    {
        $lead = $this->lead($this->campaignA, self::NUMBER, $this->agent);
        $call = $this->inbound(self::NUMBER, $this->agent);
        $this->open($this->agent, $call)->assertRedirect(route('leadCallingView', ['campaignId' => $this->campaignA, 'leadId' => $lead]) . '?inbound=' . $call->id);
    }

    public function test_every_number_format_resolves_the_same()
    {
        $lead = $this->lead($this->campaignA, '+91 91549 36399', $this->agent);
        $target = route('leadCallingView', ['campaignId' => $this->campaignA, 'leadId' => $lead]);
        foreach (['9154936399', '+919154936399', '09154936399'] as $format) {
            $call = $this->inbound($format, $this->agent);
            $this->open($this->agent, $call)->assertRedirect($target . '?inbound=' . $call->id);
        }
    }

    public function test_a_number_without_a_lead_opens_manual_dial_with_the_number()
    {
        $call = $this->inbound('+919000011122', $this->agent);
        $this->open($this->agent, $call)->assertRedirect($this->manualUrl('9000011122'))->assertSessionMissing('inboundNotice');
    }

    public function test_leads_of_expired_or_manual_dial_campaigns_open_manual_dial()
    {
        $this->lead($this->campaign('Old Link Campaign', '901', '-2 days'), self::NUMBER, $this->agent);
        $this->lead($this->campaign('Manual Dial', '901'), self::NUMBER, $this->agent);
        $this->open($this->agent, $this->inbound(self::NUMBER, $this->agent))->assertRedirect($this->manualUrl(self::NUMBER));
    }

    public function test_two_campaigns_prefer_the_users_own_lead_else_show_the_chooser()
    {
        $own = $this->lead($this->campaignA, self::NUMBER, $this->agent);
        $public = $this->lead($this->campaignB, self::NUMBER, null);
        DB::table('leads')->where('id', $public)->update(['is_public' => '1']);   // the agent may work it too
        $call = $this->inbound(self::NUMBER, $this->agent);
        $this->open($this->agent, $call)->assertRedirect(route('leadCallingView', ['campaignId' => $this->campaignA, 'leadId' => $own]) . '?inbound=' . $call->id);

        // admin: two leads, none theirs, none worked -> the chooser on the Inbound Calls page
        $this->open($this->admin->id, $call)->assertRedirect(route('inboundCalls', ['choose' => $call->id]));
        $json = $this->getJson(route('inboundCallOpen', ['inboundCall' => $call->id]))->assertOk()->json();
        $this->assertSame('choose', $json['type']);
        $this->assertSame(['Open in Link Campaign A', 'Open in Link Campaign B', 'Call in Manual Dial'], array_column($json['options'], 'label'));
        $this->assertSame($this->manualUrl(self::NUMBER), end($json['options'])['url']);

        // the most recently worked lead wins
        DB::table('leads')->where('id', $own)->update(['last_actioned_date' => now()]);
        $this->open($this->admin->id, $call)->assertRedirect(route('leadCallingView', ['campaignId' => $this->campaignA, 'leadId' => $own]) . '?inbound=' . $call->id);
    }

    public function test_another_agents_lead_is_not_revealed()
    {
        $hidden = $this->lead($this->campaignA, self::NUMBER, $this->otherAgent);
        $call = $this->inbound(self::NUMBER, $this->agent);
        $res = $this->open($this->agent, $call);
        $res->assertRedirect($this->manualUrl(self::NUMBER))->assertSessionHas('inboundNotice', 'Opened in Manual Dial');
        $this->assertStringNotContainsString((string)$hidden, $res->headers->get('Location'));

        // the tooltip does not name the campaign either
        $row = $this->postJson(route('inboundCallsData'), ['from_date' => now()->format('Y-m-d'), 'to_date' => now()->format('Y-m-d')])->json('data.0');
        $this->assertSame('Call in Manual Dial', $row['call_title']);
    }

    public function test_table_rows_carry_the_link_and_where_it_opens()
    {
        $this->lead($this->campaignA, self::NUMBER, $this->agent);
        $call = $this->inbound('+91' . self::NUMBER, $this->agent);
        $this->inbound('9000011122', $this->agent);
        $this->actingAs(User::find($this->agent));
        DB::enableQueryLog();
        $rows = collect($this->postJson(route('inboundCallsData'), ['from_date' => now()->format('Y-m-d'), 'to_date' => now()->format('Y-m-d')])->json('data'));
        $leadQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'phone_normalized'))->count();
        $this->assertSame(1, $leadQueries, 'one lead query for the whole page');

        $linked = $rows->firstWhere('call_id', $call->call_id);
        $this->assertSame('+91' . self::NUMBER, $linked['customer_mobile'], 'number text unchanged');
        $this->assertSame(route('inboundCallOpen', ['inboundCall' => $call->id]), $linked['call_url']);
        $this->assertSame('Call in campaign: Link Campaign A', $linked['call_title']);
        $this->assertSame('Call in Manual Dial', $rows->firstWhere('customer_mobile', '9000011122')['call_title']);
    }

    public function test_a_telecaller_cannot_open_another_agents_inbound_call()
    {
        $call = $this->inbound(self::NUMBER, $this->otherAgent);
        $this->open($this->agent, $call)->assertForbidden();
        $this->open($this->agent, $this->inbound(self::NUMBER, null))->assertForbidden();   // missed: nobody's
        $this->actingAs(User::find($this->agent))->get('/admin/reports/inbound-calls/999999999/call')->assertNotFound();
    }

    public function test_the_calling_page_falls_back_to_manual_dial_when_the_lead_cannot_be_opened()
    {
        $lead = $this->lead($this->campaignA, self::NUMBER, $this->agent);
        $call = $this->inbound(self::NUMBER, $this->agent);
        DB::table('leads')->where('id', $lead)->update(['assigned_to' => $this->otherAgent]);   // moved to someone else
        $this->actingAs(User::find($this->agent))
            ->get(route('leadCallingView', ['campaignId' => $this->campaignA, 'leadId' => $lead]) . '?inbound=' . $call->id)
            ->assertRedirect($this->manualUrl(self::NUMBER))->assertSessionHas('inboundNotice', 'Opened in Manual Dial');
    }

    public function test_manual_dial_fills_only_a_valid_number()
    {
        $this->actingAs($this->admin);
        $this->get(route('dialer', ['tab' => 'manual', 'number' => '+91 9154936399']))->assertOk()->assertSee('var prefill = "+91 9154936399"', false);
        foreach (['<script>', '9154936399abc', '12345678901234567', "9154'1"] as $bad) {
            $this->get(route('dialer', ['tab' => 'manual', 'number' => $bad]))->assertOk()->assertSee('var prefill = null', false);
        }
    }
}
