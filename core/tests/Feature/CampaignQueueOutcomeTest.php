<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\CampaignsController;
use App\Jobs\DownloadRecordingJob;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Calling Workspace > Campaign Queue: the outcome saved for the selected lead keeps the selected Status. */
class CampaignQueueOutcomeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_selected_status_substatus_feedback_and_followup_are_saved_on_the_selected_lead()
    {
        Queue::fake([DownloadRecordingJob::class]);
        $admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($admin);
        $campaign = Campaign::firstOrFail();
        $status = Status::has('substatuses')->firstOrFail();
        $sub = $status->substatuses->first();
        $lead = Lead::forceCreate(['name' => 'Queue Outcome Lead', 'phonenumber' => '9' . random_int(100000000, 999999999),
            'campaign_id' => $campaign->id, 'created_by' => $admin->id]);
        $other = Lead::forceCreate(['name' => 'Other Queue Lead', 'phonenumber' => '9' . random_int(100000000, 999999999),
            'campaign_id' => $campaign->id, 'created_by' => $admin->id]);

        // exactly what the page now sends (main_disposition = the selected Status id)
        $req = Request::create('/x', 'POST', ['main_disposition' => (string)$status->id, 'sub_disposition' => (string)$sub->id,
            'remarks' => 'Asked to call back', 'follow_up_on' => '2026-10-20 11:00:00']);
        $req->setUserResolver(fn () => $admin);
        app()->instance('request', $req);
        $res = app(CampaignsController::class)->saveLeadCallingDisposition($req, $campaign->id, $lead->id);
        $this->assertTrue($res->getData(true)['success']);

        $lead->refresh();
        $this->assertSame($status->id, (int)$lead->status, 'the selected status is stored (the chip text "ConnectedConnected" matched no status)');
        $this->assertSame($sub->id, (int)$lead->substatus);
        $this->assertSame('Asked to call back', $lead->remarks);
        $this->assertSame('2026-10-20 11:00:00', (string)$lead->follow_up_on);
        $log = CallLog::where('lead_id', $lead->id)->latest('id')->first();
        $this->assertSame($status->id, (int)$log->next_status);
        $this->assertNull($other->fresh()->status, 'other queue leads untouched');
    }
}
