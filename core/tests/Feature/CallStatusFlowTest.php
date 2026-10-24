<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\DialerController;
use App\Jobs\DownloadRecordingJob;
use App\Models\CallLog;
use App\Models\DialerLog;
use App\Models\Recording;
use App\Models\User;
use App\Services\CallRecordingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Calling Workspace status for Click2Call comes ONLY from the provider's call record
 * (Click2Call "Call End" CDR: A party = agent, B party = customer). Payload shape copied from real calls.
 * No network: vmn_used is empty so the live gateway lookup is skipped and the stored record is used.
 */
class CallStatusFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake([DownloadRecordingJob::class]);
        Auth::login(User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->first());
    }

    private function t(int $offsetSecs): string
    {
        return Carbon::now()->subMinutes(5)->addSeconds($offsetSecs)->format('dmYHis');
    }

    /** Provider record. $b = customer leg status; times relative to call start. */
    private function cdr(string $ref, ?string $a, ?string $b, array $times = [], array $extra = []): array
    {
        return array_merge([
            'SERVICE_TYPE' => 'CLICK_TO_CALL', 'EVENT_TYPE' => 'Call End', 'REF_ID' => $ref,
            'A_DIAL_STATUS' => $a, 'B_DIAL_STATUS' => $b,
            'A_PARTY_CONNECTED_TIME' => isset($times['a_conn']) ? $this->t($times['a_conn']) : '',
            'A_PARTY_END_TIME' => isset($times['end']) ? $this->t($times['end']) : '',
            'B_PARTY_CONNECTED_TIME' => isset($times['b_conn']) ? $this->t($times['b_conn']) : '',
            'B_PARTY_END_TIME' => isset($times['b_end']) ? $this->t($times['b_end']) : '',
            'DISCONNECTED_BY' => 'Customer',
        ], $extra);
    }

    private function manualCall(?array $clickResponse = null, array $attrs = []): DialerLog
    {
        return DialerLog::forceCreate(array_merge([
            'phone_number' => '9' . random_int(100000000, 999999999), 'created_by' => Auth::id(),
            'time_taken' => 0, 'duration' => '00:00', 'call_type' => 'click2call', 'call_source' => 'manual',
            'call_state' => 'initiated', 'start_time' => Carbon::now()->subMinutes(5),
            'reference_id' => 'TESTSTAT_' . uniqid(), 'vmn_used' => null,
            'click_response' => $clickResponse ? json_encode($clickResponse) : null,
            'created_at' => Carbon::now()->subMinutes(5),
        ], $attrs));
    }

    private function callStatusOf($log, bool $campaign = false): array
    {
        $req = Request::create('/dialer/call-status', 'GET', ['id' => $log->id, 'is_campaign' => $campaign ? 1 : 0]);
        app()->instance('request', $req);
        return json_decode(app(DialerController::class)->callStatus($req)->getContent(), true);
    }

    public function test_no_provider_data_yet_is_calling_and_nothing_is_invented()
    {
        // 5 minutes after dialling, still no provider record: the old code returned/saved "no-answer"
        $log = $this->manualCall();
        $res = $this->callStatusOf($log);
        $this->assertSame('calling', $res['status']);
        $this->assertFalse($res['final']);
        $this->assertSame('initiated', $log->fresh()->call_state, 'no state written from elapsed time');
        $this->assertNull($log->fresh()->end_time);
    }

    public function test_agent_answered_customer_ringing_shows_ringing()
    {
        $p = $this->cdr('x', 'Connected', '', ['a_conn' => 8]);
        unset($p['EVENT_TYPE']);
        $res = $this->callStatusOf($this->manualCall($p));
        $this->assertSame('ringing', $res['status']);
    }

    public function test_customer_connected_and_call_live_shows_connected_with_real_elapsed_time()
    {
        $p = $this->cdr('x', 'Connected', 'Connected', ['a_conn' => 8, 'b_conn' => 15]);
        unset($p['EVENT_TYPE']); // no end yet
        $res = $this->callStatusOf($this->manualCall($p));
        $this->assertSame('connected', $res['status']);
        $this->assertFalse($res['final']);
        $this->assertGreaterThanOrEqual(284, $res['connected_for']); // connected 5 min - 15 s ago
    }

    public function test_connected_call_that_ended_shows_call_ended_and_saves_customer_talk_time()
    {
        $log = $this->manualCall();
        $p = $this->cdr($log->reference_id, 'Connected', 'Connected', ['a_conn' => 8, 'b_conn' => 15, 'b_end' => 38, 'end' => 38],
            ['recording_url' => 'https://voip.example.test/rec/' . $log->id . '.wav']);
        $log->forceFill(['click_response' => json_encode($p)])->save();

        $res = $this->callStatusOf($log);
        $this->assertSame('ended', $res['status']);
        $this->assertTrue($res['final']);
        $this->assertSame(23, $res['duration'], 'customer talk time (B connected -> end), not agent leg 30s');
        $this->assertSame('00:23', $res['duration_formatted']);

        $log->refresh();
        $this->assertSame('completed', $log->call_state);
        $this->assertSame(23, (int)$log->time_taken);
        $this->assertSame('00:23', $log->duration);
        $this->assertNotNull($log->end_time);
        $this->assertSame(1, Recording::where('dialer_log_id', $log->id)->count());

        // duplicate / delayed polls and a late provider callback change nothing
        $this->callStatusOf($log);
        $this->callStatusOf($log);
        (new CallRecordingService())->processWebhook($p);
        $this->assertSame(1, Recording::where('dialer_log_id', $log->id)->count());
        $this->assertSame(23, (int)$log->fresh()->time_taken);
        $this->assertSame('completed', $log->fresh()->call_state);
    }

    public function test_customer_no_answer_busy_and_agent_not_answering()
    {
        $cases = [
            ['Connected', 'No Answer', 'no_answer', 'no-answer'],
            ['Connected', 'Busy', 'busy', 'busy'],
            ['Connected', 'Failed', 'failed', 'failed'],
            ['No Answer', '', 'failed', 'failed'],   // agent's own phone did not answer
        ];
        foreach ($cases as [$a, $b, $status, $state]) {
            $log = $this->manualCall();
            $p = $this->cdr($log->reference_id, $a, $b, ['a_conn' => $a === 'Connected' ? 8 : null, 'end' => 40],
                ['recording_url' => 'https://voip.example.test/rec/ring_' . $log->id . '.wav']); // agent-leg audio exists
            $log->forceFill(['click_response' => json_encode($p)])->save();

            $res = $this->callStatusOf($log);
            $this->assertSame($status, $res['status'], "A={$a} B={$b}");
            $this->assertTrue($res['final']);
            $this->assertSame(0, $res['duration']);
            $log->refresh();
            $this->assertSame($state, $log->call_state, "A={$a} B={$b}");
            $this->assertSame(0, (int)$log->time_taken, 'customer never talked: no talk time');
            // the provider's recording of the ringing is kept (testing feedback, Oct 2026): playable / downloadable,
            // while the call stays unanswered with no talk time
            $this->assertNotNull($log->recording_id, "ringing recording kept, A={$a} B={$b}");
            $this->assertSame('https://voip.example.test/rec/ring_' . $log->id . '.wav', \App\Models\Recording::find($log->recording_id)->recording_url);
        }
    }

    public function test_campaign_status_never_reads_a_manual_call_with_the_same_id()
    {
        $camp = CallLog::forceCreate([
            'phonenumber' => '9123456780', 'created_by' => Auth::id(), 'duration' => 0, 'call_type' => 'click2call',
            'call_source' => 'campaign', 'call_state' => 'initiated', 'reference_id' => 'TESTSTATC_' . uniqid(), 'verified' => 1,
        ]);
        $sameIdManual = DialerLog::find($camp->id) ?? $this->manualCall(null, ['id' => $camp->id]);
        $sameIdManual->forceFill(['click_response' => json_encode($this->cdr('m', 'Connected', 'Busy', ['end' => 20]))])->save();

        $this->assertSame('calling', $this->callStatusOf($camp, true)['status'], 'campaign call has no provider record yet');
        $this->assertSame('busy', $this->callStatusOf($sameIdManual, false)['status']);
    }

    public function test_provider_facts_reader()
    {
        $f = CallRecordingService::providerCallFacts($this->cdr('r', 'Connected', 'Connected', ['a_conn' => 0, 'b_conn' => 10, 'b_end' => 72, 'end' => 72], ['talk_duration' => 99]));
        $this->assertSame('completed', $f['outcome']);
        $this->assertSame(62, $f['talk'], 'B-leg times win over the provider total');
        $this->assertTrue($f['ended']);

        $f = CallRecordingService::providerCallFacts(['status' => 'connected', 'duration' => 40]);
        $this->assertNull($f['outcome'], 'generic gateway without legs: outcome left to the caller');
        $this->assertSame(40, $f['talk']);
    }
}
