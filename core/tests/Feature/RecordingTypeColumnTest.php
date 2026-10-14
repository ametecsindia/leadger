<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\RecordingsController;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\DialerLog;
use App\Models\Lead;
use App\Models\Recording;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/** Call Recordings > Type: the calling method of the call (Click2Call / WebRTC), never "Campaign". */
class RecordingTypeColumnTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);
    }

    private function campaignCall(array $attrs): CallLog
    {
        $campaign = Campaign::firstOrFail();
        $lead = Lead::forceCreate(['name' => 'Type Test', 'phonenumber' => '9' . random_int(100000000, 999999999), 'campaign_id' => $campaign->id, 'created_by' => $this->admin->id]);
        $log = CallLog::forceCreate(array_merge(['phonenumber' => $lead->phonenumber, 'lead_id' => $lead->id, 'campaign_id' => $campaign->id,
            'created_by' => $this->admin->id, 'call_source' => 'campaign', 'call_state' => 'completed', 'duration' => 20,
            'reference_id' => 'TYPE_' . uniqid(), 'created_at' => Carbon::now()], $attrs));
        $rec = Recording::forceCreate(['provider_call_id' => $log->reference_id, 'call_id' => $log->reference_id, 'call_log_id' => $log->id,
            'source_code' => 0, 'source_type' => 'Campaign Calling', 'user_id' => $this->admin->id, 'phone_number' => $log->phonenumber,
            'duration' => 20, 'file_path' => storage_path('app/recordings/type_test.wav'), 'download_status' => 'completed', 'call_date' => Carbon::now()]);
        $log->forceFill(['recording_id' => $rec->id])->save();
        return $log;
    }

    private function typeCell(int $sourceCode, int $logId): string
    {
        $req = Request::create('/x', 'POST', ['draw' => 1, 'start' => 0, 'length' => 500, 'columns' => [['data' => 'created_at']], 'order' => [['column' => 0, 'dir' => 'desc']]]);
        $req->setUserResolver(fn () => $this->admin);
        app()->instance('request', $req);
        $row = collect(json_decode(app(RecordingsController::class)->recordingData($req)->getContent(), true)['data'])
            ->first(fn ($r) => (int)$r['source_code'] === $sourceCode && (int)$r['log_id'] === $logId);
        $this->assertNotNull($row);
        return $row['source_type'];
    }

    public function test_campaign_recordings_show_the_calling_method_not_campaign()
    {
        $c2c = $this->campaignCall(['call_type' => 'click2call']);
        $web = $this->campaignCall(['call_type' => 'webrtc']);
        $flexiWeb = $this->campaignCall(['call_type' => 'webrtc_flexidial']);
        $legacyWeb = $this->campaignCall(['call_type' => '', 'webrtc_call_id' => 'sip-call-1']);
        $legacyC2c = $this->campaignCall(['call_type' => '', 'click_response' => '{"status":"success"}']);
        $unknown = $this->campaignCall(['call_type' => '']);

        $this->assertStringContainsString("click2call'>Click2Call<", $this->typeCell(0, $c2c->id));
        $this->assertStringContainsString("webrtc'>WebRTC<", $this->typeCell(0, $web->id));
        $this->assertStringContainsString("webrtc'>WebRTC<", $this->typeCell(0, $flexiWeb->id));
        $this->assertStringContainsString("webrtc'>WebRTC<", $this->typeCell(0, $legacyWeb->id), 'legacy row: WebRTC call id');
        $this->assertStringContainsString("click2call'>Click2Call<", $this->typeCell(0, $legacyC2c->id), 'legacy row: Click2Call response');
        $this->assertStringContainsString('>—<', $this->typeCell(0, $unknown->id), 'no method recorded: not guessed');
        foreach ([$c2c, $web, $unknown] as $l) {
            $this->assertStringNotContainsString('Campaign', $this->typeCell(0, $l->id));
        }
    }

    public function test_manual_flexidial_call_is_click2call()
    {
        $log = DialerLog::forceCreate(['phone_number' => '9' . random_int(100000000, 999999999), 'created_by' => $this->admin->id, 'time_taken' => 20,
            'call_type' => 'flexidial', 'call_source' => 'manual', 'call_state' => 'completed', 'reference_id' => 'TYPE_' . uniqid(),
            'file_path' => storage_path('app/recordings/type_test.wav'), 'created_at' => Carbon::now()]);
        $this->assertStringContainsString("click2call'>Click2Call<", $this->typeCell(2, $log->id));
    }
}
