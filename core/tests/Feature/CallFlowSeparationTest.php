<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Controllers\Dashboard\ReportsController;
use App\Jobs\DownloadRecordingJob;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\DialerLog;
use App\Models\Lead;
use App\Models\Recording;
use App\Models\Status;
use App\Models\User;
use App\Services\CallRecordingService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Manual calls -> dialer_logs, campaign calls -> call_logs; both are listed in the Call Report (Source Manual / Campaign),
 * recordings of both -> recordings (Call Recording), all linked by the unique call ID.
 *
 * Controllers are called directly (not over HTTP): the Tenants middleware reconnects the
 * database per request, which would escape the test transaction.
 */
class CallFlowSeparationTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $campaign;
    protected $lead;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake([DownloadRecordingJob::class]);

        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->first() ?? User::first();
        Auth::login($this->admin);

        $this->campaign = Campaign::first();
        $this->lead = Lead::forceCreate([
            'name' => 'Flow Test Lead',
            'phonenumber' => '9' . random_int(100000000, 999999999),
            'campaign_id' => $this->campaign->id,
            'status' => Status::first()->id ?? 1,
            'assigned_to' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);
    }

    private function manualCall(array $attrs = []): DialerLog
    {
        return DialerLog::forceCreate(array_merge([
            'phone_number' => $this->lead->phonenumber,
            'created_by' => $this->admin->id,
            'time_taken' => 0,
            'duration' => '00:00',
            'call_type' => 'click2call',
            'call_source' => 'manual',
            'call_state' => 'initiated',
            'start_time' => Carbon::now(),
            'reference_id' => 'TESTMAN_' . uniqid(),
        ], $attrs));
    }

    private function campaignCall(array $attrs = []): CallLog
    {
        return CallLog::forceCreate(array_merge([
            'phonenumber' => $this->lead->phonenumber,
            'lead_id' => $this->lead->id,
            'campaign_id' => $this->campaign->id,
            'created_by' => $this->admin->id,
            'duration' => 0,
            'call_type' => 'click2call',
            'call_source' => 'campaign',
            'call_state' => 'initiated',
            'start_time' => Carbon::now(),
            'reference_id' => 'TESTCAMP_' . uniqid(),
            'verified' => 1,
        ], $attrs));
    }

    private function connectedPayload(string $refId, int $talk, string $url): array
    {
        return [
            'ref_id' => $refId,
            'call_id' => 'PROV_' . $refId,
            'b_party_no' => $this->lead->phonenumber,
            'recording_url' => $url,
            'A_DIAL_STATUS' => 'ANSWER',
            'B_DIAL_STATUS' => 'ANSWER',
            'talk_duration' => $talk,
        ];
    }

    private function callJson(callable $fn): array
    {
        ob_start();
        $res = $fn();
        $echoed = ob_get_clean();
        $body = $echoed !== '' ? $echoed : (is_object($res) && method_exists($res, 'getContent') ? $res->getContent() : (string)$res);
        return json_decode($body, true) ?? [];
    }

    private function reportRequest(array $data = []): Request
    {
        $req = Request::create('/test', 'POST', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 100,
            'from_date' => Carbon::today()->format('Y-m-d'), 'to_date' => Carbon::today()->format('Y-m-d'),
            'columns' => [['data' => 'id']], 'order' => [['column' => 0, 'dir' => 'desc']],
        ], $data));
        $req->setUserResolver(fn () => $this->admin);
        app()->instance('request', $req);
        return $req;
    }

    public function test_manual_call_goes_to_dialer_logs_with_one_recording()
    {
        $log = $this->manualCall();
        $service = new CallRecordingService();
        $payload = $this->connectedPayload($log->reference_id, 65, 'https://voip.example.test/rec/manual_' . $log->id . '.wav');

        $service->processWebhook($payload);
        $service->processWebhook($payload); // repeated callback must not duplicate anything

        $log->refresh();
        $this->assertSame('completed', $log->call_state);
        $this->assertSame(65, (int)$log->time_taken);
        $this->assertSame('01:05', $log->duration);
        $this->assertSame(1, Recording::where('dialer_log_id', $log->id)->count());
        $rec = Recording::where('dialer_log_id', $log->id)->first();
        $this->assertSame(2, (int)$rec->source_code);
        $this->assertSame($rec->id, (int)$log->recording_id);
        $this->assertSame(0, CallLog::where('reference_id', $log->reference_id)->count(), 'manual call must not be stored as a campaign call');

        $report = $this->callJson(fn () => app(ReportsController::class)->callReportData($this->reportRequest(['length' => 500])));
        $row = collect($report['data'])->firstWhere('call_id', $log->reference_id);
        $this->assertNotNull($row, 'manual call is listed in the Call Report');
        $this->assertSame('Manual', $row['source']);
        $this->assertSame('Manual Dial', $row['campaign']);
        $this->assertSame('01:05', $row['duration']);
        $this->assertSame('Connected', $row['call_result']);
    }

    public function test_campaign_call_goes_to_call_reports_with_one_recording()
    {
        $log = $this->campaignCall();
        $before = DialerLog::count();
        $service = new CallRecordingService();
        $payload = $this->connectedPayload($log->reference_id, 125, 'https://voip.example.test/rec/camp_' . $log->id . '.wav');

        $service->processWebhook($payload);
        $service->processWebhook($payload);

        $log->refresh();
        $this->assertSame('completed', $log->call_state);
        $this->assertSame(125, (int)$log->duration);
        $this->assertSame(1, Recording::where('call_log_id', $log->id)->count());
        $this->assertSame(0, (int)Recording::where('call_log_id', $log->id)->value('source_code'));
        $this->assertSame($before, DialerLog::count(), 'campaign call must not create a manual call row');

        $report = $this->callJson(fn () => app(ReportsController::class)->callReportData($this->reportRequest(['length' => 500])));
        $row = collect($report['data'])->firstWhere('call_id', $log->reference_id);
        $this->assertNotNull($row, 'campaign call is listed in Call Reports');
        $this->assertSame($this->campaign->name, $row['campaign']);
        $this->assertSame((int)$this->lead->id, (int)$row['lead_id']);
        $this->assertSame('02:05', $row['duration']);
        $this->assertSame('Campaign', $row['source']);
        $this->assertSame('Connected', $row['call_result']);
        $this->assertSame('-', $row['status'], 'no Call Outcome saved yet');
        $this->assertSame('processing', $row['recording']['state'], 'file not downloaded yet -> Processing, not a player');

        $manualOnly = $this->callJson(fn () => app(ReportsController::class)->callReportData($this->reportRequest(['length' => 500, 'source' => 'manual'])));
        $this->assertNull(collect($manualOnly['data'])->firstWhere('call_id', $log->reference_id), 'campaign call is not a manual call');
    }

    public function test_unanswered_busy_and_failed_calls_get_outcome_and_no_recording()
    {
        $service = new CallRecordingService();
        foreach (['NOANSWER' => ['no-answer', 'No Answer'], 'BUSY' => ['busy', 'Busy'], 'FAILED' => ['failed', 'Failed']] as $provider => [$state, $label]) {
            $manual = $this->manualCall();
            $camp = $this->campaignCall();
            foreach ([$manual, $camp] as $log) {
                $service->processWebhook(['ref_id' => $log->reference_id, 'A_DIAL_STATUS' => 'ANSWER', 'B_DIAL_STATUS' => $provider, 'talk_duration' => 0]);
                $log->refresh();
                $this->assertSame($state, $log->call_state, "{$provider} sets call_state on " . class_basename($log));
                $this->assertNotNull($log->end_time);
                $this->assertNull($log->recording_id, 'no recording for a call that did not connect');
                $this->assertSame($label, Helper::callOutcome($log->call_state, 0));
            }
        }
    }

    /** Customer did not answer but the provider recorded the ringing: the outcome stays and the audio is kept. */
    public function test_unanswered_call_keeps_the_providers_ringing_recording()
    {
        $service = new CallRecordingService();
        foreach ([$this->manualCall(), $this->campaignCall()] as $log) {
            $url = 'https://voip.example.test/recordings/c2c/ringing-' . class_basename($log) . '-' . $log->id . '.wav';
            $res = $service->processWebhook(['ref_id' => $log->reference_id, 'A_DIAL_STATUS' => 'Connected', 'B_DIAL_STATUS' => 'No Answer',
                'EVENT_TYPE' => 'Call End', 'RecordVoice' => $url]);
            $log->refresh();
            $this->assertSame('success', $res['status']);
            $this->assertSame('no-answer', $log->call_state, 'still an unanswered call');
            $this->assertNotNull($log->recording_id, 'ringing recording kept on ' . class_basename($log));
            $this->assertSame($url, Recording::find($log->recording_id)->recording_url);
            $this->assertNull($log->answer_time, 'not marked as answered');
        }
        Queue::assertPushed(DownloadRecordingJob::class);

        // agent did not answer: the provider sends no audio, so there is still none
        $agentMissed = $this->manualCall();
        $service->processWebhook(['ref_id' => $agentMissed->reference_id, 'A_DIAL_STATUS' => 'No Answer', 'B_DIAL_STATUS' => '', 'EVENT_TYPE' => 'Call End']);
        $this->assertNull($agentMissed->fresh()->recording_id);
    }

    public function test_campaign_event_never_lands_on_a_manual_call_to_the_same_number()
    {
        $manual = $this->manualCall();          // newer manual call to the same phone
        $camp = $this->campaignCall(['created_at' => Carbon::now()->subMinutes(5)]);

        (new CallRecordingService())->processWebhook(['ref_id' => $camp->reference_id, 'b_party_no' => $this->lead->phonenumber, 'B_DIAL_STATUS' => 'BUSY', 'talk_duration' => 0]);

        $this->assertSame('busy', $camp->fresh()->call_state);
        $this->assertSame('initiated', $manual->fresh()->call_state, 'manual call untouched');
    }

    public function test_campaign_disposition_saved_from_dialer_page_updates_the_campaign_call()
    {
        $camp = $this->campaignCall(['call_state' => 'completed', 'duration' => 30]);
        // a manual call whose id equals the campaign call id (ids of the two tables overlap)
        $clash = DialerLog::find($camp->id) ?? $this->manualCall(['id' => $camp->id]);
        $clashState = $clash->call_state;

        $req = Request::create('/dialer/savelog', 'POST', [
            'id' => $camp->id, 'is_campaign' => '1', 'mobile' => $this->lead->phonenumber,
            'status_id' => Status::first()->id, 'comments' => 'flow test',
        ]);
        $req->setUserResolver(fn () => $this->admin);
        app()->instance('request', $req);
        app(DialerController::class)->savelog($req);

        $this->assertSame((int)Status::first()->id, (int)$camp->fresh()->next_status);
        $this->assertSame('flow test', $camp->fresh()->remarks);
        $this->assertSame($clashState, $clash->fresh()->call_state, 'manual dialer log with the same id untouched');
    }

    public function test_recording_rows_are_only_created_for_real_audio()
    {
        $notConnected = $this->manualCall(['call_state' => 'no-answer']);
        $this->assertNull(CallRecordingService::storeOrUpdateRecording('Dialing', $notConnected, [
            'file_path' => 'assets/uploads/recordings/leadger_demo_call.mp3',
        ]), 'placeholder file never creates a recording');

        $connected = $this->manualCall(['call_state' => 'completed', 'time_taken' => 40, 'duration' => '00:40']);
        $rec = CallRecordingService::storeOrUpdateRecording('Dialing', $connected, ['call_status' => 'completed']);
        $this->assertNotNull($rec);
        $this->assertSame('awaiting', $rec->download_status, 'shown as Processing until the provider file arrives');
        $this->assertNull($rec->file_path);
        $this->assertSame(1, Recording::where('dialer_log_id', $connected->id)->count());

        CallRecordingService::storeOrUpdateRecording('Dialing', $connected->fresh(), ['call_status' => 'completed']);
        $this->assertSame(1, Recording::where('dialer_log_id', $connected->id)->count(), 'repeated update is idempotent');
    }

    public function test_callback_never_reuses_another_calls_recording()
    {
        // Old data: a manual call's recording carries the campaign call's id
        $camp = $this->campaignCall();
        $manual = $this->manualCall(['call_state' => 'completed', 'time_taken' => 28, 'duration' => '00:28']);
        $manualRec = Recording::forceCreate([
            'provider_call_id' => $camp->reference_id, 'call_id' => $camp->reference_id, 'dialer_log_id' => $manual->id,
            'source_code' => 2, 'source_type' => 'Dialing', 'phone_number' => $manual->phone_number,
            'duration' => 28, 'download_status' => 'completed', 'call_status' => 'completed',
        ]);
        $manual->forceFill(['recording_id' => $manualRec->id])->save();

        (new CallRecordingService())->processWebhook($this->connectedPayload($camp->reference_id, 12, 'https://voip.example.test/rec/own_' . $camp->id . '.wav'));

        $this->assertSame(28, (int)$manualRec->fresh()->duration, "manual call's recording untouched");
        $campRec = Recording::where('call_log_id', $camp->id)->first();
        $this->assertNotNull($campRec, 'campaign call gets its own recording');
        $this->assertNotSame($manualRec->id, $campRec->id);
        $this->assertSame(12, (int)$campRec->duration);
    }

    public function test_database_rejects_duplicate_call_ids()
    {
        $log = $this->campaignCall();
        $this->expectException(QueryException::class);
        $this->campaignCall(['reference_id' => $log->reference_id]);
    }
}
