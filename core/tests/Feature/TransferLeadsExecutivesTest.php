<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\CampaignsController;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/** Assign / Un-Assign / Transfer Leads: Admin accounts are never executives, also when posted directly. */
class TransferLeadsExecutivesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admins_are_not_executives_and_cannot_receive_leads()
    {
        $admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $tele = User::where('status', 1)->whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        Auth::login($admin);

        $names = CampaignsController::executiveUsers()->pluck('id');
        $this->assertFalse($names->contains($admin->id));
        $this->assertTrue($names->contains($tele->id));

        $lead = Lead::forceCreate(['name' => 'ZZ exec test', 'phonenumber' => '9000002001', 'campaign_id' => 3, 'created_by' => $admin->id, 'sales_person_id' => $tele->id]);
        $post = function (array $data) use ($admin) {
            $req = Request::create('/x', 'POST', $data);
            $req->setUserResolver(fn () => $admin);
            app()->instance('request', $req);
            return app(CampaignsController::class)->transferAction($req);
        };

        $this->assertSame(422, $post(['leadsArray' => [$lead->id], 'staff' => $admin->id])->getStatusCode());
        $this->assertNull($lead->fresh()->assigned_to, 'nothing assigned to an Admin');

        $this->assertSame(200, $post(['leadsArray' => [$lead->id], 'staff' => $tele->id])->getStatusCode());
        $this->assertSame($tele->id, (int)$lead->fresh()->assigned_to);
        $this->assertSame($tele->id, (int)$lead->fresh()->sales_person_id, 'no sales person sent: the current one is kept');
    }
}
