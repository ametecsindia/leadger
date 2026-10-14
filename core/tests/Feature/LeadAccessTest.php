<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lead view / edit / update use the same record-level visibility rule as the lead list,
 * and saving a lead keeps its original creator. Rolled back afterwards.
 */
class LeadAccessTest extends TestCase
{
    use DatabaseTransactions;

    private User $tele;
    private User $admin;
    private int $otherLeadId;   // another team's campaign, not assigned, not public
    private int $ownLeadId;     // assigned to the telecaller

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $teleRole = DB::table('permissions')->where('name', 'Telecaller')->value('id');
        $this->tele = User::where('permissions_id', $teleRole)->firstOrFail();
        $this->admin = User::whereIn('permissions_id', DB::table('permissions')->where('name', 'Admin')->pluck('id'))->firstOrFail();

        $s = DB::table('settings')->first();
        Carbon::setTestNow(Carbon::today()->setTimeFromTimeString($s->caller_login ?? '10:30:00')->addMinutes(30));

        $teamTpl = (array)DB::table('teams')->first(); unset($teamTpl['id']);
        $otherTeam = DB::table('teams')->insertGetId(array_merge($teamTpl, ['name' => 'LA Other Team ' . uniqid()]));
        $campTpl = (array)DB::table('campaigns')->first(); unset($campTpl['id']);
        $otherCamp = DB::table('campaigns')->insertGetId(array_merge($campTpl, ['name' => 'LA Other Camp', 'team_id' => $otherTeam]));
        $ownCamp = DB::table('campaigns')->where('team_id', explode(',', (string)$this->tele->team_id)[0])->value('id') ?? $otherCamp;

        $lead = ['phonenumber' => '9000011111', 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now(), 'is_public' => 0];
        $this->otherLeadId = DB::table('leads')->insertGetId($lead + ['campaign_id' => $otherCamp, 'name' => 'LA Other Lead', 'assigned_to' => $this->admin->id]);
        $this->ownLeadId = DB::table('leads')->insertGetId($lead + ['campaign_id' => $ownCamp, 'name' => 'LA Own Lead', 'assigned_to' => $this->tele->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_telecaller_cannot_open_or_change_a_lead_they_cannot_see()
    {
        $this->actingAs($this->tele)->get("/admin/leads/{$this->otherLeadId}/edit")->assertStatus(403);
        $this->actingAs($this->tele)->get("/admin/leads/{$this->otherLeadId}/view")->assertStatus(403);

        $this->actingAs($this->tele)->post("/admin/leads/{$this->otherLeadId}/update", ['name' => 'CHANGED', 'phonenumber' => '9000011111', 'campaign_id' => 1])
            ->assertStatus(403);
        $this->assertSame('LA Other Lead', DB::table('leads')->where('id', $this->otherLeadId)->value('name'));
    }

    public function test_telecaller_can_save_own_lead_and_creator_is_kept()
    {
        $this->actingAs($this->tele)->post("/admin/leads/{$this->ownLeadId}/update", ['name' => 'LA Own Lead Edited', 'phonenumber' => '9000011111', 'campaign_id' => 1]);
        $row = DB::table('leads')->where('id', $this->ownLeadId)->first();
        $this->assertSame('LA Own Lead Edited', $row->name);
        $this->assertSame($this->admin->id, (int)$row->created_by, 'Saving a lead overwrote its creator');
        $this->assertSame($this->tele->id, (int)$row->last_actioned_by);
    }

    public function test_admin_can_edit_any_lead()
    {
        $this->actingAs($this->admin)->get("/admin/leads/{$this->otherLeadId}/edit")->assertStatus(200);
        $this->actingAs($this->admin)->post("/admin/leads/{$this->otherLeadId}/update", ['name' => 'LA Admin Edited', 'phonenumber' => '9000011111', 'campaign_id' => 1]);
        $this->assertSame('LA Admin Edited', DB::table('leads')->where('id', $this->otherLeadId)->value('name'));
    }
}
