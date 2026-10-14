<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\CampaignsController;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/** Create Campaign has no Calling Type field: new campaigns allow both channels (each agent uses their own). */
class CampaignCreateCallingTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_campaign_created_without_calling_type_allows_both_channels()
    {
        $admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($admin);
        $req = Request::create('/admin/campaigns/store', 'POST', ['name' => 'ZZ calling type test', 'source_id' => 1, 'segment_id' => 1,
            'team_id' => 1, 'date' => now()->addMonth()->format('Y-m-d'), 'telecallers' => [$admin->id]]);
        $req->setUserResolver(fn () => $admin);
        app()->instance('request', $req);
        app(CampaignsController::class)->store($req);

        $c = Campaign::where('name', 'ZZ calling type test')->latest('id')->first();
        $this->assertNotNull($c);
        $this->assertSame('both', $c->calling_type);
        $this->assertSame('both', CampaignsController::normalizeCampaignCallingType($c));
    }
}
