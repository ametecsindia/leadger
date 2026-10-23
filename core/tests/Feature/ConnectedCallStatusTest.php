<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\CampaignsController;
use App\Http\Controllers\Dashboard\DialerController;
use App\Jobs\DownloadRecordingJob;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\DialerLog;
use App\Models\Lead;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Disposition Settings "Only after a connected call": such a status is refused for a call that did not connect. */
class ConnectedCallStatusTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake([DownloadRecordingJob::class]);
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);
        Status::query()->update(['lead_pool' => 0, 'needs_connected_call' => 0]);   // rolled back
    }

    private function send($controller, string $method, array $data, ...$args)
    {
        $req = Request::create('/x', 'POST', $data);
        $req->setUserResolver(fn () => $this->admin);
        app()->instance('request', $req);
        return app($controller)->{$method}($req, ...$args);
    }

    public function test_manual_dial_status_needs_a_connected_call_only_when_ticked()
    {
        [$interested, $notConnected] = Status::take(2)->get()->all();
        $interested->forceFill(['needs_connected_call' => 1])->save();
        $phone = '9' . random_int(100000000, 999999999);
        $log = DialerLog::forceCreate(['phone_number' => $phone, 'created_by' => $this->admin->id, 'call_state' => 'no-answer', 'call_type' => 'click2call']);

        $refused = $this->send(DialerController::class, 'savelog', ['mobile' => $phone, 'status_id' => $interested->id, 'id' => $log->id]);
        $this->assertSame(422, $refused->getStatusCode());
        $this->assertSame("{$interested->name} can only be saved after a connected call.", $refused->getData(true)['message']);

        $this->assertSame(200, $this->send(DialerController::class, 'savelog', ['mobile' => $phone, 'status_id' => $notConnected->id, 'id' => $log->id])->getStatusCode(), 'unticked status is saved');

        $log->forceFill(['answer_time' => now(), 'call_state' => 'completed'])->save();
        $this->assertSame(200, $this->send(DialerController::class, 'savelog', ['mobile' => $phone, 'status_id' => $interested->id, 'id' => $log->id])->getStatusCode(), 'connected call is saved');
    }

    public function test_campaign_queue_status_needs_a_connected_call()
    {
        $interested = Status::firstOrFail();
        $interested->forceFill(['needs_connected_call' => 1])->save();
        $lead = Lead::forceCreate(['name' => 'Connected Test', 'phonenumber' => '9' . random_int(100000000, 999999999), 'campaign_id' => Campaign::value('id'), 'created_by' => $this->admin->id]);
        $call = CallLog::forceCreate(['phonenumber' => $lead->phonenumber, 'lead_id' => $lead->id, 'campaign_id' => $lead->campaign_id, 'created_by' => $this->admin->id, 'call_state' => 'no-answer', 'call_type' => 'click2call']);

        $refused = $this->send(CampaignsController::class, 'saveLeadCallingDisposition', ['main_disposition' => (string)$interested->id, 'call_log_id' => $call->id], $lead->campaign_id, $lead->id);
        $this->assertSame(422, $refused->getStatusCode());

        $call->forceFill(['duration' => 42])->save();
        $ok = $this->send(CampaignsController::class, 'saveLeadCallingDisposition', ['main_disposition' => (string)$interested->id, 'call_log_id' => $call->id], $lead->campaign_id, $lead->id);
        $this->assertSame(200, $ok->getStatusCode());
        $this->assertSame($interested->id, (int)$lead->fresh()->status);
    }
}
