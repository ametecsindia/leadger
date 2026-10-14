<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\CampaignsController;
use App\Http\Controllers\Dashboard\DialerController;
use App\Jobs\DownloadRecordingJob;
use App\Models\Campaign;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Manual Dialing outcome / quick follow-up -> lead follow-up -> Follow-ups page (Leads list, filter=followup).
 * Which statuses need a follow-up comes from Disposition Settings ("Requires follow-up date" = statuses.lead_pool).
 */
class FollowupFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Lead $lead;
    private Status $needsFollowup;
    private Status $noFollowup;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake([DownloadRecordingJob::class]);
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);

        $this->needsFollowup = Status::orderBy('id')->firstOrFail();
        $this->needsFollowup->forceFill(['lead_pool' => 1])->save();
        $this->noFollowup = Status::where('id', '!=', $this->needsFollowup->id)->firstOrFail();
        $this->noFollowup->forceFill(['lead_pool' => 0])->save();

        $this->lead = Lead::forceCreate(['name' => 'Followup Test Lead', 'phonenumber' => '9' . random_int(100000000, 999999999),
            'campaign_id' => Campaign::value('id'), 'status' => $this->noFollowup->id, 'created_by' => $this->admin->id]);
    }

    private function hit(string $method, array $data)
    {
        $req = Request::create('/x', 'POST', $data);
        $req->setUserResolver(fn () => Auth::user());
        app()->instance('request', $req);
        $res = app(DialerController::class)->{$method}($req);
        return [$res->getStatusCode(), $res->getData(true)];
    }

    private function save(array $extra = []): array
    {
        return $this->hit('savelog', array_merge(['mobile' => $this->lead->phonenumber, 'status_id' => $this->needsFollowup->id,
            'comments' => 'Call back about pricing', 'call_type' => 'click2call'], $extra));
    }

    /** Rows of the Follow-ups page for one tab. */
    private function followupsPage(string $tab = 'Pending'): array
    {
        $req = Request::create('/x', 'POST', ['draw' => 1, 'start' => 0, 'length' => 500, 'filter' => 'followup', 'followup_tab' => $tab,
            'columns' => [['data' => 'name']], 'order' => [['column' => 0, 'dir' => 'asc']]]);
        $req->setUserResolver(fn () => Auth::user());
        app()->instance('request', $req);
        return json_decode(app(CampaignsController::class)->leadsList($req)->getContent(), true);
    }

    private function rowFor(array $page): ?array
    {
        return collect($page['data'])->first(fn ($r) => str_contains($r['id'], ">{$this->lead->id}<"));
    }

    public function test_status_that_requires_followup_needs_a_valid_future_date()
    {
        [$code, $res] = $this->save();
        $this->assertSame(422, $code);
        $this->assertSame("{$this->needsFollowup->name} requires a follow-up date and time.", $res['message']);

        [$code, $res] = $this->save(['follow_up_on' => Carbon::now()->subDay()->format('Y-m-d\TH:i')]);
        $this->assertSame(422, $code);
        $this->assertSame('The follow-up date and time must be in the future.', $res['message']);

        [$code] = $this->save(['follow_up_on' => 'not-a-date']);
        $this->assertSame(422, $code);
        $this->assertNull($this->lead->fresh()->follow_up_on, 'nothing saved on a rejected submit');
    }

    public function test_save_outcome_creates_followup_shown_on_followups_page_without_duplicates()
    {
        $at = Carbon::now()->addDays(2)->setTime(11, 30);
        [$code, $res] = $this->save(['follow_up_on' => $at->format('Y-m-d\TH:i')]);
        $this->assertSame(200, $code);
        $this->assertSame($at->format('Y-m-d H:i:s'), $res['follow_up_on']);

        $lead = $this->lead->fresh();
        $this->assertSame($at->format('Y-m-d H:i:s'), (string)$lead->follow_up_on);
        $this->assertSame($this->needsFollowup->id, (int)$lead->status);
        $this->assertSame($this->admin->id, (int)$lead->assigned_to, 'unassigned lead gets the agent who booked it');
        $this->assertSame('Call back about pricing', $lead->remarks);

        $page = $this->followupsPage('Pending');
        $row = $this->rowFor($page);
        $this->assertNotNull($row, 'listed under Follow-ups > Pending');
        $this->assertSame($this->needsFollowup->name, $row['status_name']);
        $this->assertSame($this->admin->name, $row['assign']);
        $this->assertSame(Campaign::find($this->lead->campaign_id)->name, $row['campaign_name']);
        $this->assertSame('Call back about pricing', $row['remarks']);
        $this->assertGreaterThanOrEqual(1, $page['followup_counts']['Pending']);

        // same submit again: still one follow-up and one calendar event
        $this->save(['follow_up_on' => $at->format('Y-m-d\TH:i')]);
        $this->assertSame(1, Event::where('title', "Follow-up: {$lead->name} (Lead {$lead->id})")->count());
        $this->assertSame(1, collect($this->followupsPage('Pending')['data'])->filter(fn ($r) => str_contains($r['id'], ">{$lead->id}<"))->count());
    }

    public function test_status_without_followup_rule_keeps_working_and_number_without_lead_is_reported()
    {
        [$code, $res] = $this->save(['status_id' => $this->noFollowup->id]);
        $this->assertSame(200, $code);
        $this->assertNull($this->lead->fresh()->follow_up_on);
        $this->assertNull($this->rowFor($this->followupsPage('Pending')));

        [$code, $res] = $this->hit('savelog', ['mobile' => '9000000' . random_int(100, 999), 'status_id' => $this->needsFollowup->id,
            'follow_up_on' => Carbon::now()->addDay()->format('Y-m-d\TH:i')]);
        $this->assertSame(200, $code, 'the call itself is still saved');
        $this->assertStringContainsString('not a lead', $res['warning']);
    }

    public function test_quick_followup_validates_and_reschedules_one_followup()
    {
        $at = Carbon::now()->addDays(3)->setTime(15, 0);
        [$code, $res] = $this->hit('quickFollowup', ['mobile' => $this->lead->phonenumber, 'followup_datetime' => $at->format('Y-m-d\TH:i'), 'remarks' => 'Demo call']);
        $this->assertSame(200, $code);
        $this->assertSame($at->format('Y-m-d H:i:s'), (string)$this->lead->fresh()->follow_up_on);
        $this->assertSame('Demo call', $this->lead->fresh()->remarks);

        $later = $at->copy()->addDay();
        $this->hit('quickFollowup', ['lead_id' => $this->lead->id, 'followup_datetime' => $later->format('Y-m-d\TH:i'), 'remarks' => 'Demo call']);
        $this->assertSame($later->format('Y-m-d H:i:s'), (string)$this->lead->fresh()->follow_up_on, 'moved, not duplicated');
        $this->assertSame(1, Event::where('title', "Follow-up: {$this->lead->name} (Lead {$this->lead->id})")->count());

        $this->assertSame(422, $this->hit('quickFollowup', ['lead_id' => $this->lead->id])[0], 'date required');
        $this->assertSame(422, $this->hit('quickFollowup', ['lead_id' => $this->lead->id, 'followup_datetime' => Carbon::now()->subHour()->format('Y-m-d\TH:i')])[0]);
        [$code, $res] = $this->hit('quickFollowup', ['mobile' => '9000000123', 'followup_datetime' => $later->format('Y-m-d\TH:i')]);
        $this->assertSame(422, $code, 'no fake success when there is no lead');

        $other = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $this->lead->forceFill(['assigned_to' => $this->admin->id, 'is_public' => 0])->save();
        Auth::login($other);
        $this->assertSame(403, $this->hit('quickFollowup', ['lead_id' => $this->lead->id, 'followup_datetime' => $later->format('Y-m-d\TH:i')])[0]);
    }

    public function test_followup_moves_to_overdue_and_done()
    {
        $this->lead->forceFill(['follow_up_on' => Carbon::now()->subHour(), 'last_actioned_date' => Carbon::now()->subHours(3)])->save();
        $this->assertNotNull($this->rowFor($this->followupsPage('Overdue')));
        $this->assertNull($this->rowFor($this->followupsPage('Pending')));

        $this->save(['status_id' => $this->noFollowup->id]);   // the agent calls the lead after the follow-up time
        $this->assertNotNull($this->rowFor($this->followupsPage('Done')));
        $this->assertNull($this->rowFor($this->followupsPage('Overdue')));
    }
}
