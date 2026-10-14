<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Controllers\Dashboard\ReportsController;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\DialerLog;
use App\Models\Lead;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Manual Dial calls (dialer_logs, call_source = 'manual') never carry a campaign - not the campaign selected
 * on screen, not the campaign of a lead whose number was dialled - and the Call Report shows them as "Manual Dial".
 * Campaign calls (call_logs) keep their real campaign.
 */
class ManualDialCampaignTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Campaign $campaign;
    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);
        $this->campaign = Campaign::firstOrFail();
        $this->lead = Lead::forceCreate([
            'name' => 'Manual Dial Test Lead', 'phonenumber' => '9' . random_int(100000000, 999999999),
            'campaign_id' => $this->campaign->id, 'status' => Status::first()->id ?? 1, 'created_by' => $this->admin->id,
        ]);
    }

    private function req(string $uri, array $data): Request
    {
        $r = Request::create($uri, 'POST', $data);
        $r->setUserResolver(fn () => $this->admin);
        app()->instance('request', $r);
        return $r;
    }

    /** Today's Call Report rows (source: all | campaign | manual). */
    private function reportRows(string $source = 'all'): \Illuminate\Support\Collection
    {
        $r = $this->req('/x', ['draw' => 1, 'start' => 0, 'length' => 500, 'columns' => [['data' => 'time']], 'order' => [['column' => 0, 'dir' => 'desc']],
            'from_date' => Carbon::today()->format('Y-m-d'), 'to_date' => Carbon::today()->format('Y-m-d'), 'source' => $source]);
        return collect(app(ReportsController::class)->callReportData($r)->getData(true)['data'] ?? []);
    }

    public function test_a_manual_call_never_stores_a_campaign()
    {
        $log = DialerLog::forceCreate([
            'phone_number' => $this->lead->phonenumber, 'created_by' => $this->admin->id, 'time_taken' => 0,
            'call_source' => 'manual', 'call_type' => 'click2call', 'campaign_id' => $this->campaign->id,
            'lead_id' => $this->lead->id, 'reference_id' => 'TESTMD_' . uniqid(),
        ]);
        $this->assertNull($log->fresh()->campaign_id, 'campaign stripped on save');
        $this->assertSame($this->lead->id, (int)$log->fresh()->lead_id, 'lead link kept for history');

        $log->campaign_id = $this->campaign->id;  // any later update path
        $log->save();
        $this->assertNull($log->fresh()->campaign_id);
    }

    public function test_save_outcome_and_call_state_update_do_not_attach_the_selected_or_lead_campaign()
    {
        // Saving the outcome for a dialled number that belongs to a campaign lead, with "test" selected on screen
        app(DialerController::class)->savelog($this->req('/dialer/savelog', [
            'mobile' => $this->lead->phonenumber, 'status_id' => Status::first()->id,
            'campaign_id' => $this->campaign->id, 'comments' => 'manual dial test',
        ]));
        $log = DialerLog::where('phone_number', $this->lead->phonenumber)->latest('id')->firstOrFail();
        $this->assertSame('manual', $log->call_source);
        $this->assertNull($log->campaign_id);
        $this->assertSame($this->lead->id, (int)$log->lead_id);
        $this->assertSame('manual dial test', $log->comments);

        // Live call-state update carrying the on-screen campaign
        app(DialerController::class)->updateCallState($this->req('/dialer/update-call-state', [
            'id' => $log->id, 'state' => 'completed', 'duration' => 12, 'campaign_id' => $this->campaign->id,
        ]));
        $this->assertNull($log->fresh()->campaign_id);
        $this->assertSame(12, (int)$log->fresh()->time_taken);
    }

    public function test_call_report_shows_manual_calls_as_manual_dial_and_campaign_calls_keep_their_campaign()
    {
        $manual = DialerLog::forceCreate([
            'phone_number' => $this->lead->phonenumber, 'created_by' => $this->admin->id, 'time_taken' => 5, 'duration' => '00:05',
            'call_source' => 'manual', 'call_type' => 'webrtc', 'campaign_id' => $this->campaign->id,
            'reference_id' => 'TESTMD_' . uniqid(), 'feedback' => 'fb', 'comments' => 'cm',
        ]);
        $row = $this->reportRows('manual')->first(fn ($r) => $r['source'] === 'Manual' && $r['id'] === $manual->id);
        $this->assertNotNull($row);
        $this->assertSame('Manual Dial', $row['campaign']);
        $this->assertSame($this->admin->name, $row['telecaller']);
        $this->assertSame($this->lead->phonenumber, $row['mobile']);
        $this->assertSame('WebRTC', $row['call_type']);
        $this->assertSame('fb', $row['feedback']);
        $this->assertSame('cm', $row['comments']);
        $this->assertSame('00:05', $row['duration']);

        // Every manual row shows Manual Dial
        $this->assertSame([], $this->reportRows('manual')->pluck('campaign')->unique()->reject(fn ($c) => $c === 'Manual Dial')->values()->all());

        // A genuine campaign call keeps its real campaign (Call Reports / call_logs)
        $camp = CallLog::forceCreate([
            'phonenumber' => $this->lead->phonenumber, 'lead_id' => $this->lead->id, 'campaign_id' => $this->campaign->id,
            'created_by' => $this->admin->id, 'call_source' => 'campaign', 'call_state' => 'completed', 'duration' => 20,
            'call_type' => 'click2call', 'reference_id' => 'TESTMDC_' . uniqid(), 'verified' => 1,
        ]);
        $this->assertSame($this->campaign->id, (int)$camp->fresh()->campaign_id);
        $campRow = $this->reportRows()->first(fn ($r) => $r['source'] === 'Campaign' && $r['id'] === $camp->id);
        $this->assertSame($this->campaign->name, $campRow['campaign'] ?? null);
        $this->assertNull($this->reportRows('manual')->firstWhere('call_id', $camp->reference_id), 'campaign call is not a manual call');
    }
}
