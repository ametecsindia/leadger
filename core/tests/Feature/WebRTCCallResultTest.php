<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\WebRTCController;
use App\Http\Middleware\TelecallerMiddleware;
use App\Models\CallLog;
use App\Models\DialerLog;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** WebRTC calls store their TRUE result: only an answered call is "completed", has talk time and a recording row. */
class WebRTCCallResultTest extends TestCase
{
    use DatabaseTransactions;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        // requests to "localhost" are a tenants-table company on its own connection (outside the test transaction)
        \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1');
        $this->agent = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
    }

    private function manualCall(): DialerLog
    {
        return DialerLog::forceCreate(['phone_number' => '9154936351', 'created_by' => $this->agent->id, 'call_type' => 'webrtc',
            'call_source' => 'manual', 'call_state' => 'initiated', 'time_taken' => 0, 'duration' => '00:00',
            'reference_id' => 'wrtc-test-' . uniqid(), 'start_time' => now(), 'vmn_used' => '916744123664']);
    }

    private function state(int $id, string $state, array $extra = [], bool $campaign = false)
    {
        return $this->actingAs($this->agent)->postJson(route('dialerUpdateCallState'),
            ['id' => $id, 'state' => $state, 'is_campaign' => $campaign ? 1 : 0] + $extra)->assertOk();
    }

    public function test_a_webrtc_call_that_was_never_answered_is_failed_without_duration_or_recording()
    {
        $log = $this->manualCall();
        $this->state($log->id, 'ringing');
        $this->state($log->id, 'completed', ['duration' => 37]);   // e.g. the gateway's 200 OK only

        $log->refresh();
        $this->assertSame('failed', $log->call_state);
        $this->assertSame(0, (int)$log->time_taken);
        $this->assertSame('not answered', $log->sip_reason);
        $this->assertNull($log->answer_time);
        $this->assertSame(0, Recording::where('dialer_log_id', $log->id)->count(), 'no recording for a call that never connected');
    }

    public function test_an_answered_webrtc_call_is_completed_with_talk_time_and_a_recording_row()
    {
        $log = $this->manualCall();
        $this->state($log->id, 'answered');
        $this->assertNotNull($log->fresh()->answer_time);
        $this->state($log->id, 'completed', ['duration' => 42]);

        $log->refresh();
        $this->assertSame('completed', $log->call_state);
        $this->assertSame(42, (int)$log->time_taken);
        $this->assertSame(1, Recording::where('dialer_log_id', $log->id)->count());
    }

    public function test_the_sip_result_is_stored_for_busy_no_answer_and_failed_calls()
    {
        foreach ([['busy', 486, 'customer busy'], ['no-answer', 487, 'no answer'], ['failed', 404, 'number not found'], ['cancelled', 487, 'cancelled']] as [$state, $code, $reason]) {
            $log = $this->manualCall();
            $this->state($log->id, $state, ['sip_code' => $code, 'sip_reason' => $reason]);
            $log->refresh();
            $this->assertSame($state, $log->call_state);
            $this->assertSame($code, (int)$log->sip_code);
            $this->assertSame($reason, $log->sip_reason);
            $this->assertNotNull($log->end_time);
            $this->assertSame(0, (int)$log->time_taken);
            $this->assertSame(0, Recording::where('dialer_log_id', $log->id)->count());
        }
    }

    public function test_a_campaign_webrtc_call_updates_the_call_log_and_not_a_manual_dial_row()
    {
        $call = CallLog::forceCreate(['phonenumber' => '9154936351', 'call_type' => 'webrtc', 'call_source' => 'campaign',
            'call_state' => 'initiated', 'duration' => 0, 'created_by' => $this->agent->id, 'reference_id' => 'wrtc-camp-' . uniqid()]);
        $this->state($call->id, 'completed', ['duration' => 20, 'sip_reason' => 'no audio'], true);

        $call->refresh();
        $this->assertSame('failed', $call->call_state);
        $this->assertSame(0, (int)$call->duration);
        $this->assertSame('no audio', $call->sip_reason);
        $this->assertSame(0, Recording::where('call_log_id', $call->id)->count());

        $this->state($call->id, 'answered', [], true);
        $this->state($call->id, 'completed', ['duration' => 20], true);
        $this->assertSame('completed', $call->fresh()->call_state);
        $this->assertNotNull($call->fresh()->answer_time);
    }

    public function test_click2call_completion_is_unchanged()
    {
        $log = DialerLog::forceCreate(['phone_number' => '9154936351', 'created_by' => $this->agent->id, 'call_type' => 'click2call',
            'call_source' => 'manual', 'call_state' => 'initiated', 'time_taken' => 0, 'duration' => '00:00', 'reference_id' => 'c2c-test-' . uniqid()]);
        $this->state($log->id, 'completed', ['duration' => 15]);
        $this->assertSame('completed', $log->fresh()->call_state);
    }

    public function test_the_number_is_validated_and_sent_in_the_configured_trunk_format()
    {
        $this->actingAs($this->agent)->postJson(route('dialerwebrtc'), ['mobile' => '12345678901', 'vmn' => '916744123664'])
            ->assertStatus(422)->assertJsonPath('message', 'Enter a valid 10-digit mobile number.');

        $this->assertSame('9154936351', WebRTCController::nationalNumber('+91 91549 36351'));
        $this->assertSame('9154936351', WebRTCController::nationalNumber('09154936351'));
        $this->assertNull(WebRTCController::nationalNumber('915493635'));
        $expect = ['national' => '9154936351', '91' => '919154936351', '+91' => '+919154936351', '0' => '09154936351', 'prefix' => '7779154936351'];
        foreach ($expect as $format => $dialed) {
            config(['services.sip.dial_format' => $format]);
            $this->assertSame($dialed, WebRTCController::dialNumber('9154936351', ['response' => ['gateway_prefix' => '777']]), $format);
        }
    }

    public function test_the_debug_trace_is_off_by_default_and_never_logs_secrets()
    {
        config(['services.sip.debug' => false]);
        $this->actingAs($this->agent)->postJson(route('dialerWebrtcDebug'), ['events' => [['ev' => 'x']]])->assertNotFound();

        config(['services.sip.debug' => true]);
        Log::spy();
        $this->actingAs($this->agent)->postJson(route('dialerWebrtcDebug'), ['call_id' => 'c1', 'events' => [
            ['ev' => 'dial', 'data' => ['uri' => 'sip:9154936351@voip.example', 'password' => 'p4ss', 'authorization' => 'Digest x', 'token' => 't']],
        ]])->assertOk();
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            $json = json_encode($context);
            return $message === '[WebRTC-debug]' && str_contains($json, 'sip:9154936351@voip.example')
                && !str_contains($json, 'p4ss') && !str_contains($json, 'Digest') && !str_contains($json, '"token"');
        });
    }
}
