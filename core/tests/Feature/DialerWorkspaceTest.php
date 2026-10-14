<?php

namespace Tests\Feature;

use App\Models\DialerLog;
use App\Models\Lead;
use App\Models\Recording;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DialerWorkspaceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_channel_config_endpoint_returns_user_channels()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }

        $url = route('dialerChannelConfig', [], false);
        $response = $this->actingAs($user)->getJson($url);

        if ($response->status() !== 200) {
            dump("URL: " . $url . ", Status: " . $response->status() . ", Content: " . substr($response->getContent(), 0, 300));
        }

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'webrtc_enabled',
                'click2call_enabled'
            ]);
    }

    /** @test */
    public function test_today_performance_endpoint_returns_real_metrics()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }

        // Create a dialer log for today
        DialerLog::create([
            'phone_number' => '9876543210',
            'created_by' => $user->id,
            'time_taken' => 120,
            'call_state' => 'answered',
            'created_at' => Carbon::now('Asia/Kolkata')
        ]);

        $response = $this->actingAs($user)->getJson(route('dialerTodayPerformance'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'data' => [
                    'calls_made',
                    'connected',
                    'avg_talk_time',
                    'followups',
                    'target',
                    'percent_completed'
                ]
            ]);
    }

    /** @test */
    public function test_recent_activity_endpoint_returns_recent_calls()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }

        DialerLog::create([
            'phone_number' => '9988776655',
            'created_by' => $user->id,
            'time_taken' => 45,
            'call_state' => 'answered',
            'created_at' => Carbon::now()
        ]);

        $response = $this->actingAs($user)->getJson(route('dialerRecentActivity'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    /** @test */
    public function test_manual_outcome_saves_without_campaign()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }

        $status = DB::table('statuses')->first();
        $statusId = $status ? $status->id : 1;

        $response = $this->actingAs($user)->postJson(route('dialerSaveLog'), [
            'mobile' => '9123456789',
            'status_id' => $statusId,
            'campaign_id' => null, // explicitly null
            'comments' => 'Test outcome without campaign',
            'call_type' => 'click2call'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('dialer_logs', [
            'phone_number' => '9123456789',
            'campaign_id' => null,
            'created_by' => $user->id
        ]);
    }

    /** @test */
    public function test_quick_actions_endpoints_work()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }

        // 1. Quick note
        $noteRes = $this->actingAs($user)->postJson(route('dialerQuickNote'), [
            'phone' => '9123456789',
            'note' => 'Quick note test content'
        ]);
        $noteRes->assertStatus(200)->assertJson(['success' => true]);

        // 2. Quick followup
        $followupRes = $this->actingAs($user)->postJson(route('dialerQuickFollowup'), [
            'phone' => '9123456789',
            'followup_date' => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'remarks' => 'Follow up in 2 days'
        ]);
        // 9123456789 is not a lead: a follow-up must not report success when nothing was saved (FollowupFlowTest covers the saved case)
        $followupRes->assertStatus(422)->assertJson(['success' => false]);

        // 3. Quick tag
        $tagRes = $this->actingAs($user)->postJson(route('dialerQuickTag'), [
            'phone' => '9123456789',
            'tag' => 'VIP Client'
        ]);
        $tagRes->assertStatus(200)->assertJson(['success' => true]);

        // 4. Phone history
        $historyRes = $this->actingAs($user)->getJson(route('dialerPhoneHistory', ['phone' => '9123456789']));
        $historyRes->assertStatus(200)->assertJson(['status' => 'success']);
    }
}
