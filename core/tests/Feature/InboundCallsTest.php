<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\InboundCall;
use App\Models\User;
use App\Services\InboundCallService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Reports > Inbound Calls: webhook ingestion, lead / agent matching, visibility, filters and export. */
class InboundCallsTest extends TestCase
{
    use DatabaseTransactions;

    private const VMN = '8045670001';
    private const OTHER_COMPANY_VMN = '8045679999';

    private User $admin;
    private int $leadId;
    private int $agentId;
    private int $otherAgentId;
    private string $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);   // login-hours check is not under test
        // requests to "localhost" are a tenants-table company on its own connection (outside the test transaction)
        \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1');
        $this->today = now()->format('Y-m-d');

        DB::table('inbound_calls')->delete();   // rolled back after each test
        DB::table('obd_did')->insert(['virtual_number' => '+91' . self::VMN, 'click2call' => 1, 'obd' => 0, 'team_id' => '901',
            'created_at' => now(), 'updated_at' => now()]);

        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->leadId = DB::table('leads')->insertGetId(['name' => 'Inbound Test Lead', 'phonenumber' => '99588 87262',
            'last_actioned_date' => now(), 'created_at' => now(), 'updated_at' => now()]);
        // an older lead with the same number: the most recently active one wins
        DB::table('leads')->insert(['name' => 'Older Lead', 'phonenumber' => '9958887262', 'last_actioned_date' => now()->subYear(),
            'created_at' => now()->subYear(), 'updated_at' => now()->subYear()]);
        $this->agentId = $this->makeUser('Telecaller', 'Inbound Agent One', '+91 91234 56780', '901');
        $this->otherAgentId = $this->makeUser('Telecaller', 'Inbound Agent Two', '9123456781', '902');
    }

    private function makeUser(string $role, string $name, string $mobile, ?string $team = null): int
    {
        $id = DB::table('users')->insertGetId([
            'name' => $name, 'email' => uniqid('inbound', true) . '@example.test', 'password' => bcrypt('x'),
            'permissions_id' => DB::table('permissions')->where('name', $role)->value('id'), 'status' => 1,
            'mobile' => $mobile, 'team_id' => $team, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($team) {
            DB::table('team_user')->insert(['user_id' => $id, 'team_id' => (int)$team, 'created_at' => now(), 'updated_at' => now()]);
        }
        return $id;
    }

    private function event(array $fields): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('api.telephony.inboundCall'), $fields, ['Secret-Key' => config('services.telephony.secret')]);
    }

    private function inbound(string $id, string $time, ?string $agent = '9123456780', string $customer = '9958887262', string $vmn = self::VMN): void
    {
        $base = ['call_id' => $id, 'dni' => $vmn, 'cli' => $customer, 'start_time' => $this->today . ' ' . $time];
        $svc = new InboundCallService();
        $svc->record($base + ['event' => 'ringing']);
        $svc->record($agent ? $base + ['event' => 'answered', 'a_party_no' => $agent, 'duration' => 30] : $base + ['event' => 'no-answer', 'duration' => 0]);
    }

    private function report(User $user, array $params = []): array
    {
        $this->actingAs($user);
        return $this->postJson(route('inboundCallsData'), $params + ['from_date' => $this->today, 'to_date' => $this->today, 'length' => 100])
            ->assertOk()->json();
    }

    /* ---- webhook ---- */

    public function test_webhook_creates_one_record_per_call_and_updates_it_with_each_event()
    {
        $base = ['call_id' => 'T-100', 'dni' => '+91' . self::VMN, 'cli' => '+919958887262'];
        $this->event($base + ['event' => 'ringing', 'start_time' => $this->today . ' 11:42:00'])->assertOk()->assertJsonPath('results.0.result', 'created');
        $this->event(['call_id' => 'T-100', 'event' => 'answered', 'a_party_no' => '919123456780'])->assertOk()->assertJsonPath('results.0.result', 'updated');
        $this->event(['call_id' => 'T-100', 'event' => 'ended', 'end_time' => $this->today . ' 11:44:05', 'duration' => 125, 'secret' => 'x', 'api_key' => 'y'])
            ->assertOk();

        $this->assertSame(1, InboundCall::where('call_id', 'T-100')->count());
        $c = InboundCall::where('call_id', 'T-100')->first();
        $this->assertSame('answered', $c->status);
        $this->assertSame($this->today . ' 11:42:00', $c->start_time->format('Y-m-d H:i:s'));
        $this->assertSame($this->today . ' 11:44:05', $c->end_time->format('Y-m-d H:i:s'));
        $this->assertSame(125, $c->duration_seconds);
        $this->assertSame(self::VMN, $c->vmn);
        $this->assertSame($this->leadId, $c->lead_id);
        $this->assertSame($this->agentId, $c->agent_user_id);
        $this->assertCount(3, $c->raw_payload['events']);
        $this->assertStringNotContainsString('api_key', json_encode($c->raw_payload), 'secrets are never stored');
    }

    public function test_duplicate_events_do_not_create_duplicates()
    {
        $e = ['call_id' => 'T-200', 'dni' => self::VMN, 'cli' => '9958887262', 'event' => 'ringing', 'start_time' => $this->today . ' 10:00:00'];
        $this->event($e)->assertOk();
        $this->event($e)->assertOk();
        $this->event([$e, $e])->assertOk();   // a batch with the same event again

        $this->assertSame(1, InboundCall::where('call_id', 'T-200')->count());
        $this->assertCount(1, InboundCall::where('call_id', 'T-200')->first()->raw_payload['events']);
    }

    public function test_webhook_refuses_a_wrong_secret_and_ignores_other_companies_vmns()
    {
        $this->postJson(route('api.telephony.inboundCall'), ['call_id' => 'T-300', 'dni' => self::VMN], ['Secret-Key' => 'wrong'])->assertStatus(401);
        $this->postJson(route('api.telephony.inboundCall'), ['call_id' => 'T-300', 'dni' => self::VMN])->assertStatus(401);
        $this->event(['call_id' => 'T-301', 'dni' => self::OTHER_COMPANY_VMN, 'cli' => '9958887262', 'event' => 'ringing'])
            ->assertOk()->assertJsonPath('results.0.result', 'ignored');
        $this->event(['dni' => self::VMN])->assertStatus(422);

        $this->assertSame(0, InboundCall::whereIn('call_id', ['T-300', 'T-301'])->count());
    }

    /* ---- matching ---- */

    public function test_lead_and_agent_matching_works_with_every_mobile_format()
    {
        foreach (['9958887262', '+919958887262', '09958887262', '91 99588-87262'] as $i => $format) {
            $this->inbound('M-' . $i, '09:0' . $i . ':00', ['9123456780', '+919123456780', '09123456780', '91-9123-456780'][$i], $format);
            $c = InboundCall::where('call_id', 'M-' . $i)->first();
            $this->assertSame('9958887262', $c->customer_mobile_normalized, $format);
            $this->assertSame($this->leadId, $c->lead_id, "lead for $format");
            $this->assertSame($this->agentId, $c->agent_user_id, "agent for $format");
        }
    }

    public function test_nightly_rematch_links_calls_to_leads_added_later()
    {
        $this->inbound('R-1', '09:00:00', null, '8887776665');
        $this->assertNull(InboundCall::where('call_id', 'R-1')->value('lead_id'));
        $lead = DB::table('leads')->insertGetId(['name' => 'Later Lead', 'phonenumber' => '+91 88877 76665', 'created_at' => now(), 'updated_at' => now()]);

        $this->artisan('inbound-calls:rematch')->assertExitCode(0);
        $this->assertSame($lead, InboundCall::where('call_id', 'R-1')->value('lead_id'));
    }

    /* ---- visibility ---- */

    public function test_telecaller_sees_only_the_calls_they_answered()
    {
        $this->inbound('V-1', '09:00:00', '9123456780');   // answered by agent one
        $this->inbound('V-2', '09:05:00', '9123456781');   // answered by agent two
        $this->inbound('V-3', '09:10:00', null);           // missed

        $own = $this->report(User::find($this->agentId));
        $this->assertSame(['V-1'], array_column($own['data'], 'call_id'));
        $this->assertSame(1, $own['kpis']['total']);

        $all = $this->report($this->admin);
        $this->assertSame(['V-3', 'V-2', 'V-1'], array_column($all['data'], 'call_id'));
        $this->assertSame('-', $all['data'][0]['agent_name'], 'missed call: no agent name');
        $this->assertSame('Inbound Agent One', $all['data'][2]['agent_name']);
    }

    public function test_manager_sees_the_calls_of_their_team()
    {
        $this->inbound('T-1', '09:00:00', '9123456780', '9958887262', self::VMN);
        $this->inbound('T-2', '09:05:00', '9123456781', '9958887262', self::VMN);
        DB::table('obd_did')->insert(['virtual_number' => '8045670002', 'click2call' => 1, 'obd' => 0, 'team_id' => '902', 'created_at' => now(), 'updated_at' => now()]);
        $this->inbound('T-3', '09:10:00', '9123456781', '9958887262', '8045670002');

        // team 902's manager: agent two's calls (T-2, T-3), not T-1 (team 901's VMN, answered by team 901's agent)
        $manager = $this->makeUser('Manager', 'Inbound Manager', '9000000001', '902');
        $this->assertSame(['T-3', 'T-2'], array_column($this->report(User::find($manager))['data'], 'call_id'));
    }

    public function test_calls_of_another_company_database_are_not_visible()
    {
        // each company has its own database: a call is only ever stored in the database of the company owning the VMN
        $this->inbound('C-1', '09:00:00');
        (new InboundCallService())->record(['call_id' => 'C-2', 'dni' => self::OTHER_COMPANY_VMN, 'cli' => '9958887262', 'event' => 'ringing']);
        $this->assertSame(['C-1'], array_column($this->report($this->admin)['data'], 'call_id'));
    }

    public function test_page_is_refused_without_the_inbound_calls_permission()
    {
        $role = DB::table('permissions')->where('name', 'Telecaller')->value('id');
        DB::table('permissions')->where('id', $role)->update(['inbound_calls_status' => 0]);
        $this->actingAs(User::find($this->agentId));
        $this->postJson(route('inboundCallsData'), ['from_date' => $this->today, 'to_date' => $this->today])->assertStatus(403);
        $this->get(route('inboundCalls'))->assertRedirect(route('NoPermission'));
    }

    /* ---- filters, search, sort, pagination ---- */

    public function test_date_filters_search_sort_and_pagination()
    {
        $this->inbound('F-1', '08:00:00');
        $this->inbound('F-2', '09:00:00', null, '7001112223');
        $this->inbound('F-3', '10:00:00', '9123456781', '7001112224');
        InboundCall::create(['call_id' => 'F-OLD', 'vmn' => self::VMN, 'customer_mobile' => '9958887262', 'customer_mobile_normalized' => '9958887262',
            'start_time' => now()->subDays(3)->setTime(9, 0), 'status' => 'missed']);

        $r = $this->report($this->admin);
        $this->assertSame(['F-3', 'F-2', 'F-1'], array_column($r['data'], 'call_id'), 'today only, newest first');
        $this->assertSame(['total' => 3, 'unique_callers' => 3, 'matched' => 1], $r['kpis']);
        $this->assertSame(3, $r['recordsTotal']);

        $week = $this->report($this->admin, ['from_date' => now()->subDays(6)->format('Y-m-d')]);
        $this->assertSame(4, $week['kpis']['total']);
        $this->assertSame(3, $week['kpis']['unique_callers'], 'F-OLD shares the caller of F-1');

        $this->assertSame(['F-2'], array_column($this->report($this->admin, ['search' => '7001112223'])['data'], 'call_id'));
        $this->assertSame(['F-3'], array_column($this->report($this->admin, ['search' => 'Agent Two'])['data'], 'call_id'));
        $this->assertSame(['F-1'], array_column($this->report($this->admin, ['search' => (string)$this->leadId])['data'], 'call_id'));
        $this->assertSame(1, $this->report($this->admin, ['search' => 'Agent Two'])['kpis']['total'], 'KPIs follow the search');

        $this->assertSame(['F-1', 'F-2', 'F-3'], array_column($this->report($this->admin, ['sort' => 'start_time', 'dir' => 'asc'])['data'], 'call_id'));
        $this->assertSame(['F-3', 'F-2', 'F-1'], array_column($this->report($this->admin, ['sort' => 'call_id', 'dir' => 'desc'])['data'], 'call_id'));

        $page = $this->report($this->admin, ['length' => 10, 'start' => 0, 'from_date' => now()->subDays(6)->format('Y-m-d')]);
        $this->assertCount(4, $page['data']);
        $this->assertCount(0, $this->report($this->admin, ['length' => 10, 'start' => 10])['data']);

        $row = $r['data'][2];
        $this->assertSame($this->leadId, $row['lead_id']);
        $this->assertSame(now()->format('d M Y') . ', 08:00 AM', $row['start_time']);
        $this->assertSame('Inbound Agent One', $row['agent_name']);
    }

    public function test_parameters_are_validated()
    {
        $this->actingAs($this->admin);
        $url = route('inboundCallsData');
        $this->postJson($url, ['from_date' => '2026-10-17', 'to_date' => '2026-10-16'])->assertStatus(422)->assertJsonValidationErrors('to_date');
        $this->postJson($url, ['from_date' => '2026-01-01', 'to_date' => '2026-10-16'])->assertStatus(422)->assertJsonValidationErrors('to_date');
        $this->postJson($url, ['from_date' => '17-10-2026', 'to_date' => '2026-10-17'])->assertStatus(422)->assertJsonValidationErrors('from_date');
        $this->postJson($url, ['from_date' => '2026-10-17', 'to_date' => '2026-10-17', 'sort' => 'password'])->assertStatus(422)->assertJsonValidationErrors('sort');
        $this->postJson($url, ['from_date' => '2026-10-17', 'to_date' => '2026-10-17', 'length' => 5000])->assertStatus(422)->assertJsonValidationErrors('length');
        $this->postJson($url, ['from_date' => '2026-07-18', 'to_date' => '2026-10-17'])->assertOk();   // exactly 92 days
    }

    /* ---- export ---- */

    public function test_export_matches_the_table()
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->inbound(sprintf('E-%02d', $i), sprintf('%02d:%02d:00', 8 + intdiv($i, 6), $i), $i % 3 ? '9123456780' : null);
        }
        $params = ['from_date' => $this->today, 'to_date' => $this->today, 'sort' => 'start_time', 'dir' => 'desc'];
        $table = $this->report($this->admin, $params)['data'];

        $csv = $this->get(route('inboundCallsExport', ['format' => 'csv'] + $params));
        $csv->assertOk();
        $this->assertStringContainsString('inbound-calls-' . $this->today . '.csv', $csv->headers->get('content-disposition'));
        $body = $csv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $lines = array_map('str_getcsv', array_filter(explode("\n", trim(substr($body, 3)))));
        $this->assertSame(\App\Http\Controllers\Dashboard\InboundCallsController::COLUMNS, $lines[0]);
        $this->assertCount(13, $lines, 'all 12 rows, not only one page');
        foreach ($table as $i => $r) {
            $this->assertSame([(string)($r['lead_id'] ?? '-'), $r['call_id'], $r['customer_mobile'], $r['vmn'], $r['start_time'], $r['agent_number'], $r['agent_name']], $lines[$i + 1]);
        }

        $xlsx = $this->get(route('inboundCallsExport', ['format' => 'xlsx'] + $params));
        $xlsx->assertOk();
        $this->assertStringContainsString('inbound-calls-' . $this->today . '.xlsx', $xlsx->headers->get('content-disposition'));
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($xlsx->baseResponse->getFile()->getPathname()));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml') . $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        foreach (['Lead ID', 'Agent Name', 'E-12', 'E-01'] as $text) {
            $this->assertStringContainsString($text, $sheet);
        }
    }
}
