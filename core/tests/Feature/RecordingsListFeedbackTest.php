<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\RecordingsController;
use App\Models\CallLog;
use App\Models\DialerLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Call Recordings (Telecaller feedback, Nov 2026): only connected calls with a recording are listed; calls nobody
 * answered / disconnected (busy, no answer, failed, cancelled) and calls still in progress are not, even with a
 * ringing recording. A Telecaller sees only the recordings of their own calls.
 */
class RecordingsListFeedbackTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);
    }

    private function rows(): \Illuminate\Support\Collection
    {
        $req = Request::create('/x', 'POST', ['draw' => 1, 'start' => 0, 'length' => 500, 'from_date' => date('Y-m-d'), 'to_date' => date('Y-m-d'),
            'columns' => [['data' => 'created_at']], 'order' => [['column' => 0, 'dir' => 'desc']]]);
        $req->setUserResolver(fn () => Auth::user());
        app()->instance('request', $req);
        return collect(json_decode(app(RecordingsController::class)->recordingData($req)->getContent(), true)['data']);
    }

    private function row($rows, int $source, int $id)
    {
        return $rows->first(fn ($r) => (int)$r['source_code'] === $source && (int)$r['log_id'] === $id);
    }

    public function test_only_connected_calls_with_a_recording_are_listed()
    {
        $campaign = DB::table('campaigns')->value('id');
        $lead = DB::table('leads')->insertGetId(['name' => 'Rec Lead', 'phonenumber' => '9000000501', 'campaign_id' => $campaign,
            'created_at' => now(), 'updated_at' => now()]);
        $connected = CallLog::forceCreate(['phonenumber' => '9000000501', 'lead_id' => $lead, 'campaign_id' => $campaign,
            'created_by' => $this->admin->id, 'call_source' => 'campaign', 'call_type' => 'click2call', 'call_state' => 'completed', 'duration' => 42,
            'end_time' => now(), 'click_recording_url' => 'https://voip.example.test/recordings/c2c/connected-501.wav']);
        $noAnswer = DialerLog::forceCreate(['phone_number' => '9000000501', 'lead_id' => $lead, 'created_by' => $this->admin->id, 'call_source' => 'manual',
            'call_type' => 'click2call', 'call_state' => 'no-answer', 'time_taken' => 0, 'duration' => '00:00', 'end_time' => now()]);
        $ringing = DialerLog::forceCreate(['phone_number' => '9000000601', 'created_by' => $this->admin->id, 'call_source' => 'manual',
            'call_type' => 'click2call', 'call_state' => 'busy', 'time_taken' => 0, 'duration' => '00:00', 'end_time' => now(),
            'click_recording_url' => 'https://voip.example.test/recordings/c2c/ringing-601.wav']);
        $failed = CallLog::forceCreate(['phonenumber' => '9000000501', 'lead_id' => $lead, 'campaign_id' => $campaign,
            'created_by' => $this->admin->id, 'call_source' => 'campaign', 'call_type' => 'webrtc', 'call_state' => 'failed', 'end_time' => now()]);
        $open = DialerLog::forceCreate(['phone_number' => '9000000502', 'created_by' => $this->admin->id, 'call_source' => 'manual',
            'call_type' => 'click2call', 'call_state' => 'initiated', 'time_taken' => 0, 'duration' => '00:00']);

        $rows = $this->rows();
        $c = $this->row($rows, 0, $connected->id);
        $this->assertNotNull($c, 'connected call with a recording listed');
        $this->assertStringContainsString((string)$lead, $c['id'], 'campaign call: Lead ID shown');
        $this->assertNull($this->row($rows, 2, $noAnswer->id), 'no answer: not listed');
        $this->assertNull($this->row($rows, 2, $ringing->id), 'busy, even with a ringing recording: not listed');
        $this->assertNull($this->row($rows, 0, $failed->id), 'failed: not listed');
        $this->assertNull($this->row($rows, 2, $open->id), 'a call still in progress: not listed');
    }

    public function test_a_telecaller_sees_only_their_own_calls_recordings()
    {
        $tc = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $campaign = DB::table('campaigns')->value('id');
        $lead = DB::table('leads')->insertGetId(['name' => 'Rec TC Lead', 'phonenumber' => '9000000701', 'campaign_id' => $campaign,
            'assigned_to' => $tc->id, 'is_public' => '1', 'created_at' => now(), 'updated_at' => now()]);
        $mk = fn ($by) => CallLog::forceCreate(['phonenumber' => '9000000701', 'lead_id' => $lead, 'campaign_id' => $campaign, 'created_by' => $by,
            'call_source' => 'campaign', 'call_type' => 'click2call', 'call_state' => 'completed', 'duration' => 30, 'end_time' => now(),
            'click_recording_url' => 'https://voip.example.test/recordings/c2c/tc-' . $by . '.wav']);
        $own = $mk($tc->id);
        $other = $mk($this->admin->id);   // another user's call on the Telecaller's own (public) lead

        DB::table('campaigns')->where('id', $campaign)->update(['team_id' => (string)(explode(',', (string)$tc->team_id)[0] ?: '0')]);   // the Telecaller's team
        Auth::login($tc);
        $rows = $this->rows();
        $this->assertNotNull($this->row($rows, 0, $own->id), 'own call listed');
        $this->assertNull($this->row($rows, 0, $other->id), "another agent's call is not listed");
    }
}
