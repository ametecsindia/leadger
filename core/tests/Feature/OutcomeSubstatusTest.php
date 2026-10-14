<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\CampaignsController;
use App\Http\Controllers\Dashboard\DialerController;
use App\Jobs\DownloadRecordingJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Call Outcome (Manual Dial + Campaign Queue): a substatus is accepted only for its own status. */
class OutcomeSubstatusTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake([DownloadRecordingJob::class]);
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);
        Status::query()->update(['lead_pool' => 0]);   // no follow-up date needed for these saves (rolled back)
    }

    private function send($controller, string $method, array $data, ...$args)
    {
        $req = Request::create('/x', 'POST', $data);
        $req->setUserResolver(fn () => $this->admin);
        app()->instance('request', $req);
        return app($controller)->{$method}($req, ...$args);
    }

    public function test_a_substatus_of_another_status_is_rejected_and_its_own_is_saved()
    {
        [$a, $b] = Status::has('substatuses')->with('substatuses')->take(2)->get()->all();
        $lead = Lead::forceCreate(['name' => 'Outcome Sub Test', 'phonenumber' => '9' . random_int(100000000, 999999999), 'campaign_id' => Campaign::value('id'), 'created_by' => $this->admin->id]);

        $wrong = $this->send(DialerController::class, 'savelog', ['mobile' => $lead->phonenumber, 'status_id' => $a->id, 'substatus_id' => $b->substatuses->first()->id]);
        $this->assertSame(422, $wrong->getStatusCode());
        $this->assertSame('The selected substatus does not belong to the selected status.', $wrong->getData(true)['message']);

        $ok = $this->send(DialerController::class, 'savelog', ['mobile' => $lead->phonenumber, 'status_id' => $a->id, 'substatus_id' => $a->substatuses->first()->id]);
        $this->assertSame(200, $ok->getStatusCode());
        $this->assertSame([$a->id, $a->substatuses->first()->id], [(int)$lead->fresh()->status, (int)$lead->fresh()->substatus]);

        $noSubStatus = Status::doesntHave('substatuses')->firstOrFail();
        $this->assertSame(200, $this->send(DialerController::class, 'savelog', ['mobile' => $lead->phonenumber, 'status_id' => $noSubStatus->id])->getStatusCode(), 'no substatus needed');

        $camp = $this->send(CampaignsController::class, 'saveLeadCallingDisposition', ['main_disposition' => (string)$a->id, 'sub_disposition' => (string)$b->substatuses->first()->id], $lead->campaign_id, $lead->id);
        $this->assertSame(422, $camp->getStatusCode(), 'Campaign Queue rejects it too');
    }
}
