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
 * Call Recordings feedback (Oct 2026): unanswered calls (customer or agent, Click2Call and WebRTC) are listed without
 * audio, and the Lead ID is shown for campaign calls only (manual dial calls show "—").
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

    public function test_unanswered_calls_are_listed_without_audio_and_lead_id_only_for_campaign_calls()
    {
        $lead = DB::table('leads')->insertGetId(['name' => 'Rec Lead', 'phonenumber' => '9000000501', 'campaign_id' => DB::table('campaigns')->value('id'),
            'created_at' => now(), 'updated_at' => now()]);
        $manual = DialerLog::forceCreate(['phone_number' => '9000000501', 'lead_id' => $lead, 'created_by' => $this->admin->id, 'call_source' => 'manual',
            'call_type' => 'click2call', 'call_state' => 'no-answer', 'time_taken' => 0, 'duration' => '00:00', 'end_time' => now()]);
        $webrtc = CallLog::forceCreate(['phonenumber' => '9000000501', 'lead_id' => $lead, 'campaign_id' => DB::table('campaigns')->value('id'),
            'created_by' => $this->admin->id, 'call_source' => 'campaign', 'call_type' => 'webrtc', 'call_state' => 'failed', 'end_time' => now()]);
        $open = DialerLog::forceCreate(['phone_number' => '9000000502', 'created_by' => $this->admin->id, 'call_source' => 'manual',
            'call_type' => 'click2call', 'call_state' => 'initiated', 'time_taken' => 0, 'duration' => '00:00']);

        $rows = $this->rows();
        $m = $rows->first(fn ($r) => (int)$r['source_code'] === 2 && (int)$r['log_id'] === $manual->id);
        $w = $rows->first(fn ($r) => (int)$r['source_code'] === 0 && (int)$r['log_id'] === $webrtc->id);

        $this->assertNotNull($m, 'unanswered Click2Call listed');
        $this->assertStringContainsString('No answer', $m['recording']);
        $this->assertStringContainsString('—', $m['id'], 'manual dial: no Lead ID');
        $this->assertStringContainsString('disabled', $m['play'], 'nothing to play');
        $this->assertStringNotContainsString('deleteRecording', $m['play']);

        $this->assertNotNull($w, 'unanswered WebRTC campaign call listed');
        $this->assertStringContainsString('Not answered', $w['recording']);
        $this->assertStringContainsString((string)$lead, $w['id'], 'campaign call: Lead ID shown');

        $this->assertNull($rows->first(fn ($r) => (int)$r['source_code'] === 2 && (int)$r['log_id'] === $open->id), 'a call still in progress is not listed');
    }

    public function test_an_unanswered_call_with_a_ringing_recording_can_be_played()
    {
        $log = DialerLog::forceCreate(['phone_number' => '9000000601', 'created_by' => $this->admin->id, 'call_source' => 'manual',
            'call_type' => 'click2call', 'call_state' => 'no-answer', 'time_taken' => 0, 'duration' => '00:00', 'end_time' => now(),
            'click_recording_url' => 'https://voip.example.test/recordings/c2c/ringing-601.wav']);
        $row = $this->rows()->first(fn ($r) => (int)$r['source_code'] === 2 && (int)$r['log_id'] === $log->id);

        $this->assertStringContainsString('No answer', $row['recording']);
        $this->assertStringContainsString('Ringing recording', $row['recording']);
        $this->assertStringNotContainsString('disabled', $row['play'], 'play and download work');
    }
}
