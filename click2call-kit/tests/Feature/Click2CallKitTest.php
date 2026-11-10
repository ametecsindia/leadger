<?php

namespace Tests\Feature;

use App\Jobs\C2c\DownloadRecording;
use App\Models\C2c\Call;
use App\Models\C2c\Vmn;
use App\Models\User;
use App\Services\Click2Call\VmnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Click2Call kit: Add Voucher, VMN rules, calling rules, starting calls, call status (poll + webhook),
 * recordings and the admin pages. The providers are faked: no real call is placed, no voucher is used.
 */
class Click2CallKitTest extends TestCase
{
    use RefreshDatabase;

    private const LICENSE = 'https://license.test';
    private const START = 'https://provider.test/c2c_call';
    private const CDR = 'https://gateway.test/click2call_data';

    private User $admin;
    private User $agent;
    /** Fake provider answers, changeable during a test: key => [body | callable(request), status] */
    private array $answers = [];

    private function answer(string $key, $body, int $status = 200): void
    {
        $this->answers[$key] = [$body, $status];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->answer('license_used', ['status' => 200]);
        $this->answer('license', ['status' => 400, 'message' => 'not set']);
        $this->answer('start', ['call_id' => 'PROV-1']);
        $this->answer('cdr', ['status' => 404]);
        $this->answer('rec', 'RIFFaudio');
        Http::fake(function (HttpRequest $r) {
            $url = $r->url();
            $key = match (true) {
                str_starts_with($url, self::LICENSE . '/api/did_voucher_used/') => 'license_used',
                str_starts_with($url, self::LICENSE) => 'license',
                $url === self::START => 'start',
                str_starts_with($url, self::CDR) => 'cdr',
                str_starts_with($url, 'https://rec.test/') => 'rec',
                default => null,
            };
            if ($key === null) {
                return Http::response('unexpected request', 599);
            }
            [$body, $status] = $this->answers[$key];
            return Http::response(is_callable($body) ? $body($r) : $body, $status);
        });
        // project-specific user columns the kit's trait reads by default (is_admin, team_id, mobile)
        Schema::table('users', function ($t) {
            if (!Schema::hasColumn('users', 'is_admin')) $t->boolean('is_admin')->default(false);
            if (!Schema::hasColumn('users', 'team_id')) $t->string('team_id')->nullable();
            if (!Schema::hasColumn('users', 'mobile')) $t->string('mobile')->nullable();
        });
        config([
            'click2call.license_url' => self::LICENSE, 'click2call.license_secret' => 'license-secret', 'click2call.crm_id' => 'CRM42',
            'click2call.start_url' => self::START, 'click2call.cdr_url' => self::CDR, 'click2call.gateway_secret' => 'gw-secret',
            'click2call.webhook_secret' => 'hook-secret', 'click2call.hours_from' => '00:00', 'click2call.hours_to' => '23:59',
            'click2call.daily_limit' => 100, 'click2call.timezone' => 'UTC',
        ]);
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->agent = User::factory()->create(['team_id' => '7', 'c2c_channel' => true, 'click_agent_number' => '9000000001']);
        Vmn::create(['virtual_number' => '8000000001', 'click2call' => true, 'team_ids' => [7], 'expires_at' => now()->addMonth()]);
    }

    private function voucherResponse(array $data = []): array
    {
        return ['status' => 200, 'message' => 'ok', 'data' => $data + [
            'click2call_did' => '8011111111,918022222222', 'obd_did' => '8022222222,8033333333', 'webrtc_did' => '8044444444',
            'tc_verified' => '8011111111', 'expiry' => now()->addYear()->format('Y-m-d'),
            'smart_groups' => [
                ['vmn' => '8055555555', 'usage_date' => now()->toDateString(), 'c2c' => 1, 'webrtc' => 0, 'obd' => 0, 'verified' => 1, 'group_id' => 'g1', 'group_name' => 'Group 1', 'type' => null],
                ['vmn' => '8066666666', 'usage_date' => now()->subDay()->toDateString(), 'c2c' => 1, 'webrtc' => 0, 'obd' => 0, 'verified' => 1, 'group_id' => 'g1', 'group_name' => 'Group 1', 'type' => null],
            ],
        ]];
    }

    /* ---------------- Add Voucher ---------------- */

    public function test_a_valid_voucher_replaces_the_numbers_and_keeps_the_old_ones_in_history()
    {
        $this->answer('license', $this->voucherResponse());

        $this->actingAs($this->admin)->post(route('c2c.vouchers.add'), ['voucher_code' => 'ABC123'])
            ->assertRedirect()->assertSessionHas('c2c_done', "DID's added Successfully");

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/api/did_voucher/ABC123/CRM42') && $r->header('Secret-Key')[0] === 'license-secret');
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/api/did_voucher_used/ABC123/'));

        $numbers = Vmn::orderBy('virtual_number')->get()->keyBy('virtual_number');
        $this->assertSame(['8011111111', '8022222222', '8033333333', '8044444444'], array_map('strval', $numbers->keys()->all()), 'old number replaced; 91 prefix removed; no duplicates');
        $this->assertTrue($numbers['8022222222']->click2call && $numbers['8022222222']->obd, 'in two lists -> both flags');
        $this->assertTrue($numbers['8044444444']->webrtc && !$numbers['8044444444']->click2call);
        $this->assertTrue($numbers['8011111111']->tc_verified);
        $this->assertSame([], $numbers['8011111111']->team_ids, 'teams are assigned by the admin afterwards');
        $this->assertSame(now()->addYear()->format('Y-m-d'), $numbers['8011111111']->expires_at->format('Y-m-d'));
        $this->assertSame(1, DB::table('c2c_vmn_history')->where('virtual_number', '8000000001')->count());
        $this->assertSame(['8055555555'], DB::table('c2c_smart31_numbers')->pluck('vmn')->all(), "only today's Smart31 numbers");
        $this->assertSame(4, DB::table('c2c_vouchers')->value('numbers'));
    }

    public function test_an_invalid_voucher_changes_nothing()
    {
        $this->answer('license', ['status' => 400, 'message' => 'Voucher already used']);
        $this->actingAs($this->admin)->post(route('c2c.vouchers.add'), ['voucher_code' => 'USED99'])
            ->assertSessionHas('c2c_error', 'Voucher already used');
        $this->assertSame(['8000000001'], array_map('strval', Vmn::pluck('virtual_number')->all()));

        $this->answer('license', 'down', 500);
        $this->post(route('c2c.vouchers.add'), ['voucher_code' => 'ABC123'])->assertSessionHas('c2c_error', 'Something went wrong');
    }

    public function test_only_admins_manage_vouchers_and_numbers()
    {
        $this->actingAs($this->agent)->get(route('c2c.vouchers'))->assertForbidden();
        $this->post(route('c2c.vouchers.add'), ['voucher_code' => 'ABC123'])->assertForbidden();
        $this->actingAs($this->admin)->get(route('c2c.vouchers'))->assertOk()->assertSee('+91 8000000001');
        $this->get(route('c2c.agents'))->assertOk()->assertSee($this->agent->name);
    }

    public function test_admin_assigns_teams_and_usage()
    {
        $vmn = Vmn::first();
        $this->actingAs($this->admin)->post(route('c2c.vmns.update', $vmn), ['team_ids' => [3, 9], 'usage' => 'both'])->assertSessionHas('c2c_done');
        $vmn->refresh();
        $this->assertSame([3, 9], $vmn->team_ids);
        $this->assertTrue($vmn->click2call && $vmn->obd);

        $this->post(route('c2c.vmns.bulk'), ['ids' => [$vmn->id], 'action' => 'update', 'usage' => 'obd'])->assertSessionHas('c2c_done');
        $this->assertFalse($vmn->fresh()->click2call);
        $this->post(route('c2c.vmns.bulk'), ['ids' => [$vmn->id], 'action' => 'delete']);
        $this->assertSame(0, Vmn::count());
    }

    /* ---------------- VMN rules ---------------- */

    public function test_users_see_their_teams_numbers_and_expired_numbers_are_refused()
    {
        Vmn::create(['virtual_number' => '8000000002', 'click2call' => true, 'team_ids' => [99]]);
        Vmn::create(['virtual_number' => '8000000003', 'click2call' => true, 'team_ids' => [7], 'expires_at' => now()->subDay()]);

        $this->assertSame(['8000000001', '8000000003'], VmnService::listFor($this->agent)->pluck('virtual_number')->all());
        $this->assertCount(3, VmnService::listFor($this->admin), 'admins see every number');
        $this->assertTrue(VmnService::check($this->agent, '+91 80000 00001')['ok']);
        $this->assertStringContainsString('expired on', VmnService::check($this->agent, '8000000003')['message']);
        $this->assertStringContainsString('not configured for Click2Call', VmnService::check($this->agent, '8000000002')['message']);
        $this->assertSame('Please select a Click2Call VMN.', VmnService::check($this->agent, '')['message']);
    }

    public function test_the_selected_pool_must_be_used()
    {
        DB::table('c2c_smart31_numbers')->insert(['vmn' => '8077777777', 'usage_date' => now()->toDateString(), 'c2c' => true, 'assigned_groups' => null, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertTrue(VmnService::check($this->agent, '8077777777', 'click2call', 'smart31')['ok']);
        $this->assertStringContainsString('Please select a Smart31 number', VmnService::check($this->agent, '8000000001', 'click2call', 'smart31')['message']);
        $this->assertStringContainsString('No Enabled 1600 number', VmnService::check($this->agent, '8000000001', 'click2call', '1600')['message']);
    }

    /* ---------------- Starting a call ---------------- */

    private function start(array $data = [])
    {
        return $this->actingAs($this->agent)->postJson(route('c2c.start'), $data + ['mobile' => '9876543210', 'vmn' => '8000000001']);
    }

    public function test_a_call_starts_with_the_same_request_as_leadger()
    {
        $res = $this->start(['mobile' => '919876543210'])->assertOk()->assertJson(['success' => true, 'message' => 'Call initiated successfully']);

        $call = Call::findOrFail($res->json('id'));
        $this->assertSame('initiated', $call->call_state, 'accepted is not connected');
        $this->assertSame('PROV-1', $call->provider_call_id);
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::START && $r['did'] === '8000000001' && $r['a_party_no'] === '9000000001'
            && $r['b_party_no'] === '9876543210' && $r['ref_id'] === $call->ref_id && $r['record'] === 1 && $r['recording'] === 1 && $r['record_call'] === 1);
        $this->assertSame(1, DB::table('c2c_daily_attempts')->where('phone', '9876543210')->value('attempt_count'));
    }

    public function test_a_provider_error_fails_the_call_and_gives_the_attempt_back()
    {
        $this->answer('start', ['error' => 'Invalid DID']);
        $this->start()->assertStatus(400)->assertJson(['success' => false, 'message' => 'Invalid DID']);
        $this->assertSame('failed', Call::first()->call_state);
        $this->assertSame(0, DB::table('c2c_daily_attempts')->where('phone', '9876543210')->value('attempt_count'));

        $this->answer('start', '<html>', 502);
        $this->start()->assertStatus(400)->assertJson(['message' => 'Invalid JSON response from initiate-call']);
    }

    public function test_calls_are_refused_by_the_rules()
    {
        $this->start(['mobile' => '98765'])->assertStatus(422);
        $this->start(['mobile' => '98765abcde'])->assertStatus(422)->assertJsonValidationErrors('mobile');
        $this->start(['vmn' => '8099999999'])->assertStatus(422)->assertJsonFragment(['success' => false]);

        config(['click2call.hours_from' => '00:00', 'click2call.hours_to' => '00:00']);
        if (now('UTC')->format('H:i') !== '00:00') {
            $this->start()->assertStatus(403)->assertJson(['reason' => 'outside_hours']);
        }
        config(['click2call.hours_to' => '23:59', 'click2call.daily_limit' => 1]);
        $this->start()->assertOk();
        $this->start()->assertStatus(422)->assertJson(['reason' => 'retry_limit', 'message' => 'Maximum call attempts reached for this number today (1).']);
        $this->assertGreaterThanOrEqual(2, DB::table('c2c_blocked_calls')->count());

        $this->agent->forceFill(['c2c_channel' => false])->save();
        $this->start()->assertForbidden();
    }

    public function test_a_second_start_while_one_is_starting_is_refused()
    {
        $lock = Cache::lock('c2c-agent-' . $this->agent->id, 6);
        $lock->get();
        $this->start()->assertStatus(429);
        $lock->release();
    }

    /* ---------------- Call status ---------------- */

    private function startedCall(): Call
    {
        $this->answer('start', ['call_id' => 'PROV-1']);
        return Call::findOrFail($this->start()->json('id'));
    }

    private function cdr(array $cdr, ?string $recording = null): array
    {
        return ['status' => 200, 'data' => ['response' => json_encode($cdr), 'recording_url' => $recording]];
    }

    public function test_the_status_poll_follows_the_call_record()
    {
        Queue::fake();
        $call = $this->startedCall();
        $url = route('c2c.status', $call);
        $now = now();
        $connected = $now->copy()->subSeconds(40)->format('dmYHis');

        $this->answer('cdr', ['status' => 404]);
        $this->getJson($url)->assertJson(['status' => 'initiated', 'message' => 'Calling...', 'final' => false]);

        $this->answer('cdr', $this->cdr(['A_DIAL_STATUS' => 'ANSWERED']));
        $this->getJson($url)->assertJson(['status' => 'ringing', 'message' => 'Ringing customer...']);

        $this->answer('cdr', $this->cdr(['A_DIAL_STATUS' => 'ANSWERED', 'B_PARTY_CONNECTED_TIME' => $connected]));
        $this->getJson($url)->assertJson(['status' => 'connected', 'final' => false]);

        $this->answer('cdr', $this->cdr(['A_DIAL_STATUS' => 'ANSWERED', 'B_DIAL_STATUS' => 'ANSWERED',
            'B_PARTY_CONNECTED_TIME' => $connected, 'B_PARTY_END_TIME' => $now->format('dmYHis')], 'https://rec.test/a.wav'));
        $this->getJson($url)->assertJson(['status' => 'completed', 'final' => true, 'talk_seconds' => 40]);
        Queue::assertPushed(DownloadRecording::class, fn ($j) => $j->callId === $call->id);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), self::CDR . '/' . $call->ref_id . '/8000000001') && $r->header('Secret-Key')[0] === 'gw-secret');
    }

    public function test_busy_no_answer_and_agent_not_answering()
    {
        $cases = [
            [['A_DIAL_STATUS' => 'ANSWERED', 'B_DIAL_STATUS' => 'BUSY'], 'busy', 'Customer line is busy'],
            [['A_DIAL_STATUS' => 'ANSWERED', 'B_DIAL_STATUS' => 'NOANSWER'], 'no-answer', 'Customer did not answer'],
            [['A_DIAL_STATUS' => 'NO ANSWER'], 'failed', 'Your phone did not answer, the customer was not dialled'],
            [['A_DIAL_STATUS' => 'ANSWERED', 'B_DIAL_STATUS' => 'CONGESTION'], 'failed', 'Call failed'],
        ];
        foreach ($cases as [$cdr, $state, $message]) {
            Cache::flush();
            $call = $this->startedCall();
            $this->answer('cdr', $this->cdr($cdr));
            $this->getJson(route('c2c.status', $call))->assertJson(['status' => $state, 'message' => $message, 'final' => true, 'talk_seconds' => 0]);
            $this->assertNull($call->fresh()->recording_status, 'no recording for an unanswered call');
        }
    }

    public function test_the_webhook_needs_the_secret_and_matches_by_ref_id()
    {
        Queue::fake();
        $call = $this->startedCall();
        $payload = ['ref_id' => $call->ref_id, 'A_DIAL_STATUS' => 'ANSWERED', 'B_DIAL_STATUS' => 'ANSWERED', 'talk_duration' => 75,
                    'recording_url' => 'https://rec.test/b.wav', 'secret' => 'never-stored'];

        $this->postJson(route('c2c.webhook'), $payload)->assertStatus(401);
        $this->postJson(route('c2c.webhook'), $payload, ['Secret-Key' => 'wrong'])->assertStatus(401);
        $this->postJson(route('c2c.webhook'), ['ref_id' => 'unknown'] + $payload, ['Secret-Key' => 'hook-secret'])->assertOk()->assertJson(['status' => 'ignored']);

        $this->postJson(route('c2c.webhook'), $payload, ['Secret-Key' => 'hook-secret'])->assertOk()->assertJson(['call_state' => 'completed']);
        $this->postJson(route('c2c.webhook'), $payload, ['Secret-Key' => 'hook-secret'])->assertOk();   // the same event again
        $call->refresh();
        $this->assertSame(75, $call->talk_seconds);
        $this->assertArrayNotHasKey('secret', $call->cdr);
        Queue::assertPushed(DownloadRecording::class, 1);
    }

    public function test_another_agent_cannot_read_or_change_a_call()
    {
        $call = $this->startedCall();
        $other = User::factory()->create(['team_id' => '7', 'c2c_channel' => true, 'click_agent_number' => '9000000002']);
        $this->actingAs($other)->getJson(route('c2c.status', $call))->assertForbidden();
        $this->postJson(route('c2c.outcome', $call), ['outcome' => 'Interested'])->assertForbidden();
        $this->actingAs($this->agent)->postJson(route('c2c.outcome', $call), ['outcome' => 'Interested', 'notes' => 'ok'])->assertOk();
        $this->assertSame('Interested', $call->fresh()->outcome);
    }

    /* ---------------- Recordings ---------------- */

    public function test_the_recording_is_downloaded_to_a_private_disk_and_streamed_only_to_its_agent()
    {
        Storage::fake('local');
        $call = $this->startedCall();
        $call->update(['call_state' => 'completed', 'recording_url' => 'https://rec.test/c.wav', 'recording_status' => 'pending']);

        (new DownloadRecording($call->id))->handle();
        $call->refresh();
        $this->assertSame('completed', $call->recording_status);
        Storage::disk('local')->assertExists($call->recording_path);

        $this->actingAs($this->agent)->get(route('c2c.recording', $call) . '?play=1')->assertOk();
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('c2c.recording', $call))->assertForbidden();
    }

    public function test_the_sweep_fetches_missing_results_and_gives_up_on_old_ones()
    {
        Queue::fake();
        $live = $this->startedCall();
        $waiting = Call::create(['user_id' => $this->agent->id, 'customer_number' => '9876500000', 'agent_number' => '9000000001', 'vmn' => '8000000001',
            'ref_id' => 'old-ref', 'call_state' => 'completed', 'recording_status' => 'awaiting', 'ended_at' => now()->subMinutes(90), 'created_at' => now()->subMinutes(95)]);
        $stuck = Call::create(['user_id' => $this->agent->id, 'customer_number' => '9876500001', 'agent_number' => '9000000001', 'vmn' => '8000000001',
            'ref_id' => 'stuck-ref', 'call_state' => 'initiated']);
        Call::whereKey($stuck->id)->update(['created_at' => now()->subMinutes(20)]);

        $busy = $this->cdr(['A_DIAL_STATUS' => 'ANSWERED', 'B_DIAL_STATUS' => 'BUSY']);
        $this->answer('cdr', fn (HttpRequest $r) => str_contains($r->url(), '/' . $live->ref_id . '/') ? $busy : ['status' => 404]);
        $this->artisan('c2c:sweep')->assertSuccessful();

        $this->assertSame('busy', $live->fresh()->call_state);
        $this->assertSame('unavailable', $waiting->fresh()->recording_status);
        $this->assertSame('failed', $stuck->fresh()->call_state);
    }

    public function test_the_dialer_page_renders_with_the_users_numbers()
    {
        $this->actingAs($this->agent)->get(route('c2c.dialer') . '?number=9876543210')->assertOk()
            ->assertSee('+918000000001')->assertSee('value="9876543210"', false)->assertSee('vendor/click2call/click2call.js');
    }
}
