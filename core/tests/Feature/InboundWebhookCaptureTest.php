<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\InboundCall;
use App\Models\InboundWebhookEvent;
use App\Models\User;
use App\Services\InboundCallPuller;
use App\Services\InboundCallService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** Inbound Calls on live: raw capture of every webhook request, tolerant payloads, unmapped VMNs, backfill, IST days. */
class InboundWebhookCaptureTest extends TestCase
{
    use DatabaseTransactions;

    private const VMN = '8045670001';
    private const UNMAPPED_VMN = '8045679999';

    private string $today;
    private int $leadId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        // requests to "localhost" are a tenants-table company on its own connection (outside the test transaction)
        \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1');
        config(['services.telephony.inbound_single_company' => false, 'services.telephony.inbound_ips' => '']);
        $this->today = now()->format('Y-m-d');

        DB::table('inbound_calls')->delete();
        DB::table('inbound_webhook_events')->delete();   // rolled back after each test
        DB::table('obd_did')->insert(['virtual_number' => '+91' . self::VMN, 'click2call' => 1, 'obd' => 0,
            'created_at' => now(), 'updated_at' => now()]);
        $this->leadId = DB::table('leads')->insertGetId(['name' => 'Capture Test Lead', 'phonenumber' => '9958887262',
            'last_actioned_date' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function secret(): string
    {
        return (string)config('services.telephony.secret');
    }

    private function send(array $body): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('api.telephony.inboundCall'), $body, ['Secret-Key' => $this->secret()]);
    }

    public function test_a_valid_event_is_stored_raw_and_creates_one_call()
    {
        $this->send(['call_id' => 'C-1', 'dni' => self::VMN, 'cli' => '9958887262', 'event' => 'ringing', 'start_time' => $this->today . ' 10:00:00'])
            ->assertOk()->assertJsonPath('results.0.result', 'created');

        $call = InboundCall::where('call_id', 'C-1')->firstOrFail();
        $this->assertSame($this->leadId, $call->lead_id);
        $event = InboundWebhookEvent::sole();
        $this->assertSame('processed', $event->processing_status);
        $this->assertTrue($event->signature_valid);
        $this->assertSame('POST', $event->http_method);
        $this->assertSame($call->id, $event->inbound_call_id);
        $this->assertSame('C-1', $event->call_id);
        $this->assertStringNotContainsString($this->secret(), json_encode($event->headers) . $event->raw_body, 'the secret is never stored');
    }

    public function test_the_same_call_id_twice_updates_the_same_row_also_by_get_and_form_post()
    {
        $this->get(route('api.telephony.inboundCall', ['secret' => $this->secret(), 'call_id' => 'C-2', 'dni' => self::VMN,
            'cli' => '9958887262', 'event' => 'ringing', 'start_time' => $this->today . ' 11:00:00']))->assertOk();
        $this->post(route('api.telephony.inboundCall'), ['call_id' => 'C-2', 'event' => 'answered', 'agent_number' => '9123456780',
            'duration' => 40], ['Secret-Key' => $this->secret()])->assertOk()->assertJsonPath('results.0.result', 'updated');

        $this->assertSame(1, InboundCall::where('call_id', 'C-2')->count());
        $this->assertSame('answered', InboundCall::where('call_id', 'C-2')->value('status'));
        $this->assertSame(2, InboundWebhookEvent::where('call_id', 'C-2')->where('processing_status', 'processed')->count());
        $this->assertStringNotContainsString($this->secret(), InboundWebhookEvent::where('http_method', 'GET')->value('raw_body'));
    }

    public function test_payload_variants_and_number_formats()
    {
        // the calls (17:12) happened before "now": a call time is never in the future
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($this->today . ' 18:00:00', config('app.timezone')));
        $day = now()->format('d/m/Y');
        $variants = [
            'V-1' => ['CallSid' => 'V-1', 'CallFrom' => '+91 99588 87262', 'CallTo' => '0' . self::VMN, 'StartTime' => $day . ' 17:12:00'],
            'V-2' => ['CALL_ID' => 'V-2', 'CLI' => '919958887262', 'DNI' => '91' . self::VMN, 'A_START_TIME' => now()->format('dmY') . '171200'],
            'V-3' => ['data' => [['uuid' => 'V-3', 'caller_id' => '09958887262', 'did' => '+91-' . self::VMN, 'timestamp' => now()->setTime(17, 12)->timestamp]]],
            'V-4' => ['payload' => json_encode(['unique_id' => 'V-4', 'from' => '9958887262', 'to' => self::VMN,
                'call_start' => now()->setTime(17, 12)->utc()->format('Y-m-d\TH:i:s\Z')])],
        ];
        foreach ($variants as $id => $body) {
            $this->send($body)->assertOk()->assertJsonPath('results.0.result', 'created');
            $c = InboundCall::where('call_id', $id)->firstOrFail();
            $this->assertSame(self::VMN, $c->vmn, $id);
            $this->assertSame('9958887262', $c->customer_mobile_normalized, $id);
            $this->assertSame($this->leadId, $c->lead_id, $id);
            $this->assertSame($this->today . ' 17:12', $c->start_time->format('Y-m-d H:i'), $id . ' is read as India time');
        }
    }

    public function test_an_unmapped_vmn_is_kept_as_ignored_and_logged_then_backfill_saves_it_once_mapped()
    {
        Log::spy();
        $this->send(['call_id' => 'U-1', 'dni' => self::UNMAPPED_VMN, 'cli' => '9958887262', 'start_time' => $this->today . ' 12:00:00'])
            ->assertOk()->assertJsonPath('results.0.result', 'ignored');

        $this->assertSame(0, InboundCall::where('call_id', 'U-1')->count(), 'another company\'s VMN is not saved');
        $event = InboundWebhookEvent::sole();
        $this->assertSame('ignored', $event->processing_status);
        $this->assertStringContainsString('Unmapped VMN: ' . self::UNMAPPED_VMN, $event->failure_reason);
        Log::shouldHaveReceived('warning')->withArgs(fn ($m) => str_contains($m, 'Inbound call for an unmapped VMN: ' . self::UNMAPPED_VMN));

        // the number is added in Add Voucher: the backfill re-processes the stored event
        DB::table('obd_did')->insert(['virtual_number' => self::UNMAPPED_VMN, 'click2call' => 1, 'obd' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $this->artisan('inbound:backfill', ['--from' => $this->today, '--skip-provider' => true,
            '--company' => config('database.connections.mysql.database')])->assertExitCode(0);
        $this->assertSame(1, InboundCall::where('call_id', 'U-1')->count());
        $this->assertSame('processed', $event->fresh()->processing_status);
    }

    public function test_with_one_company_an_unmapped_vmn_is_saved()
    {
        config(['services.telephony.inbound_single_company' => true]);
        $this->send(['call_id' => 'U-2', 'dni' => self::UNMAPPED_VMN, 'cli' => '9958887262'])->assertOk()->assertJsonPath('results.0.result', 'created');
        $this->assertSame(self::UNMAPPED_VMN, InboundCall::where('call_id', 'U-2')->value('vmn'));
    }

    public function test_a_wrong_or_missing_secret_is_refused_and_still_recorded()
    {
        $this->postJson(route('api.telephony.inboundCall'), ['call_id' => 'S-1', 'dni' => self::VMN], ['Secret-Key' => 'wrong'])->assertStatus(401);
        $this->postJson(route('api.telephony.inboundCall'), ['call_id' => 'S-1', 'dni' => self::VMN])->assertStatus(401);

        $this->assertSame(0, InboundCall::where('call_id', 'S-1')->count());
        $events = InboundWebhookEvent::orderBy('id')->get();
        $this->assertCount(2, $events);
        $this->assertFalse($events[0]->signature_valid);
        $this->assertSame('failed', $events[0]->processing_status);
        $this->assertSame(['Wrong secret', 'No secret sent'], $events->pluck('failure_reason')->all());
        $this->assertStringNotContainsString('wrong', json_encode($events[0]->headers), 'the sent secret is not stored either');

        // a refused request is never replayed by the backfill
        $this->artisan('inbound:backfill', ['--from' => $this->today, '--skip-provider' => true,
            '--company' => config('database.connections.mysql.database')])->assertExitCode(0);
        $this->assertSame(0, InboundCall::where('call_id', 'S-1')->count());
    }

    public function test_pulled_calls_are_saved_even_when_the_vmn_format_differs_and_the_run_is_recorded()
    {
        Http::fake([config('services.telephony.incoming_url') . '*' => Http::response(['status' => 200, 'data' => [
            ['call_id' => 'P-1', 'dni' => self::UNMAPPED_VMN, 'cli' => '9958887262', 'a_start_time' => $this->today . ' 09:30:00', 'a_dial_status' => 'ANSWER', 'duration' => '25'],
            ['dni' => self::VMN, 'cli' => '9958887262'],   // no call_id: kept for review
        ]])]);
        $r = (new InboundCallPuller())->pull(now()->subHours(3), now());

        $this->assertSame(1, $r['created'], json_encode($r));
        $this->assertSame(1, $r['invalid']);
        $this->assertSame($this->leadId, InboundCall::where('call_id', 'P-1')->value('lead_id'));
        $this->assertSame(1, InboundWebhookEvent::where('source', 'pull')->where('processing_status', 'ignored')->count());
        $this->assertNotNull(InboundCallPuller::lastRun());
        Http::assertSent(fn ($req) => $req->hasHeader('Secret-Key') && isset($req['from'], $req['to'], $req['vmn'], $req['crm']));
    }

    public function test_today_in_india_time_includes_00_00_and_23_59_only()
    {
        $svc = new InboundCallService();
        $tomorrow = now()->addDay()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        foreach (['B-1' => $this->today . ' 00:00:00', 'B-2' => $this->today . ' 23:59:59', 'B-3' => $tomorrow . ' 00:00:00', 'B-4' => $yesterday . ' 23:59:59'] as $id => $t) {
            $svc->record(['call_id' => $id, 'dni' => self::VMN, 'cli' => '9958887262', 'start_time' => $t]);
        }
        $admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $rows = $this->actingAs($admin)->postJson(route('inboundCallsData'), ['from_date' => $this->today, 'to_date' => $this->today, 'length' => 100])
            ->assertOk()->json('data');
        $this->assertEqualsCanonicalizing(['B-1', 'B-2'], array_column($rows, 'call_id'));
    }

    public function test_admins_see_the_pipeline_line_and_others_do_not()
    {
        $admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->actingAs($admin)->get(route('inboundCalls'))->assertOk()
            ->assertSee('No inbound events received yet')->assertSee(route('api.telephony.inboundCall'));

        $this->send(['call_id' => 'A-1', 'dni' => self::VMN, 'cli' => '9958887262']);
        $this->actingAs($admin)->get(route('inboundCalls'))->assertOk()->assertSee('Last inbound event received:');

        $telecaller = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller')->where('inbound_calls_status', 1))->first();
        if ($telecaller) {
            $this->actingAs($telecaller)->get(route('inboundCalls'))->assertOk()->assertDontSee('icPipeline');
        }
    }
}
