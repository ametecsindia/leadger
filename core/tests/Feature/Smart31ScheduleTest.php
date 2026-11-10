<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Controllers\Dashboard\WebRTCController;
use App\Models\User;
use App\Services\VmnService;
use App\Support\Smart31Schedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Smart31: up to 3 numbers per agent for today, enabled by time slot (Asia/Kolkata), and the calling ways the licence
 * includes. Test rows are marked (group_name) on a future date, with a team id no real agent has, and deleted afterwards.
 */
class Smart31ScheduleTest extends TestCase
{
    use DatabaseTransactions;

    private const MARK = 'PHPUNIT Smart31ScheduleTest';
    private const TEAM = '990001';
    private const OTHER_TEAM = '990002';
    // numbers in the order they are added (id ascending)
    private const A = '9900011101';   // c2c
    private const B = '9900011102';   // c2c + webrtc
    private const C = '9900011103';   // c2c
    private const D = '9900011104';   // c2c + webrtc: 4th c2c number (not used), 2nd webrtc number
    private const E = '9900011105';   // c2c, another team: not assigned to the agent

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->at(10, 0);
        $this->agent = self::userInTeams(self::TEAM);
        Auth::setUser($this->agent);
        $this->licence(['c2c' => 50, 'webrtc' => 50]);
        $date = Smart31Schedule::now()->toDateString();
        foreach ([self::A => [1, 0, self::TEAM], self::B => [1, 1, self::TEAM], self::C => [1, 0, self::TEAM],
                  self::D => [1, 1, self::TEAM], self::E => [1, 0, self::OTHER_TEAM]] as $vmn => [$c2c, $webrtc, $team]) {
            DB::table('smart31_vmn_numbers')->insert(['vmn' => $vmn, 'usage_date' => $date, 'c2c' => $c2c, 'webrtc' => $webrtc, 'obd' => 0,
                'type' => 'smart31', 'group_name' => self::MARK, 'assigned_groups' => $team, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    protected function tearDown(): void
    {
        DB::table('smart31_vmn_numbers')->where('group_name', self::MARK)->delete();
        Smart31Schedule::forget();
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** An agent that is not saved: only its teams (team_id) are read. */
    private static function userInTeams(string $teams): User
    {
        $user = new User();
        $user->team_id = $teams;
        return $user;
    }

    /** Fake "now": 5 Jan 2099 at H:M in Asia/Kolkata. */
    private function at(int $hour, int $minute): void
    {
        Carbon::setTestNow(Carbon::create(2099, 1, 5, $hour, $minute, 0, Smart31Schedule::TIMEZONE));
    }

    /** Licence channel counts as the cache holds them (null = key missing from the licence). */
    private function licence(array $counts): void
    {
        Cache::put(Smart31Schedule::CACHE_KEY, $counts, 60);
    }

    private function listed(string $type): array
    {
        $res = app(DialerController::class)->getSmart31Vmns(Request::create('/x', 'GET', ['type' => $type]))->getData(true);
        return array_column($res['lines'], 'vmn');
    }

    private function lines(string $kind): array
    {
        return Smart31Schedule::lines($this->agent, $kind);
    }

    public function test_slot_edges()
    {
        foreach ([[8, 59, 0], [9, 0, 1], [12, 0, 1], [12, 1, 2], [15, 0, 2], [15, 1, 3], [20, 0, 3], [20, 1, 0]] as [$h, $m, $slot]) {
            $this->at($h, $m);
            $this->assertSame($slot, Smart31Schedule::slot(), sprintf('%02d:%02d', $h, $m));
        }
        $this->at(12, 0);
        $this->assertSame(1, Smart31Schedule::slot(Carbon::create(2099, 1, 5, 12, 0, 59, Smart31Schedule::TIMEZONE)), '12:00:59 is still slot 1');
        $this->assertSame(2, Smart31Schedule::slot(Carbon::create(2099, 1, 5, 6, 31, 0, 'UTC')), '06:31 UTC = 12:01 IST');
    }

    public function test_enabled_and_disabled_numbers_and_the_default()
    {
        $this->at(12, 30);   // slot 2: numbers 1 and 2
        $res = $this->lines('c2c');
        $this->assertSame(2, $res['slot']);
        $this->assertNull($res['message']);
        $this->assertSame([self::B, self::A, self::C], array_column($res['lines'], 'vmn'), 'enabled first (newest first), then disabled; only the first 3');
        $this->assertSame([true, false, false], array_column($res['lines'], 'default'), 'the newest enabled number is the default');
        $this->assertSame([true, true, false], array_column($res['lines'], 'enabled'));
        $this->assertSame('3:01 PM', $res['lines'][2]['available_from']);
        $this->assertNotContains(self::E, array_column($res['lines'], 'vmn'), 'another team\'s number is not listed');

        $this->at(9, 0);
        $this->assertSame([self::A, self::B, self::C], array_column($this->lines('c2c')['lines'], 'vmn'));
        $this->assertSame(['9:00 AM', '12:01 PM', '3:01 PM'], array_column($this->lines('c2c')['lines'], 'available_from'));

        $this->at(20, 0);
        $res = $this->lines('c2c');
        $this->assertSame([self::C, self::B, self::A], array_column($res['lines'], 'vmn'));
        $this->assertSame(self::C, $res['lines'][0]['vmn']);
        $this->assertTrue($res['lines'][0]['default']);

        $this->at(20, 1);
        $res = $this->lines('c2c');
        $this->assertSame([false, false, false], array_column($res['lines'], 'enabled'), 'all disabled after 8:00 PM');
        $this->assertSame([false, false, false], array_column($res['lines'], 'default'));
        $this->assertSame('Smart31 calling is allowed only from 9:00 AM to 8:00 PM', $res['message']);

        $this->at(15, 30);
        $old = app(WebRTCController::class)->getVmn(Request::create('/x', 'GET'))->getData(true);
        $this->assertSame(self::C, $old['vmn'], 'get-smart31-vmn returns the default number');
        $this->assertCount(3, $old['lines']);
    }

    public function test_an_agent_without_a_team_is_not_filtered_by_team()
    {
        $res = Smart31Schedule::lines(self::userInTeams(''), 'c2c');
        $this->assertContains(self::A, array_column($res['lines'], 'vmn'));
    }

    public function test_backend_refusals()
    {
        $this->at(10, 0);
        $this->assertNull(Smart31Schedule::callError($this->agent, 'c2c', '+91' . self::A), 'number 1 in slot 1');
        $this->assertSame('Smart31 number +91' . self::B . ' can be called from 12:01 PM only', Smart31Schedule::callError($this->agent, 'c2c', self::B));
        $this->assertSame('This Smart31 number is not assigned to you for today', Smart31Schedule::callError($this->agent, 'c2c', self::E));
        $this->assertSame('This Smart31 number is not assigned to you for today', Smart31Schedule::callError($this->agent, 'c2c', self::D), 'the 4th number is not one of the 3');
        $this->assertSame('Please select VMN.', Smart31Schedule::callError($this->agent, 'c2c', ''));

        $this->at(8, 59);
        $this->assertSame('Smart31 number +91' . self::A . ' can be called from 9:00 AM only', Smart31Schedule::callError($this->agent, 'c2c', self::A));
        $this->at(20, 1);
        $this->assertSame('Smart31 number +91' . self::C . ' can be called from 3:01 PM to 8:00 PM only', Smart31Schedule::callError($this->agent, 'c2c', self::C));

        // the call checks the endpoints use: with Smart31 selected, and a Smart31 number without it (WebRTC)
        $this->at(12, 1);
        $this->assertTrue(VmnService::check($this->agent, self::B, 'click2call', 'smart31')['ok']);
        $this->assertSame('Smart31 number +91' . self::C . ' can be called from 3:01 PM only', VmnService::check($this->agent, self::C, 'click2call', 'smart31')['message']);
        $this->assertSame('Please select VMN.', VmnService::check($this->agent, '', 'click2call', 'smart31')['message']);
        $this->assertTrue(VmnService::check($this->agent, self::D, 'webrtc')['ok'], 'WebRTC number 2 in slot 2');
        $this->assertSame('This Smart31 number is not assigned to you for today', VmnService::check($this->agent, self::A, 'webrtc')['message'], 'a Click2Call-only number in WebRTC');

        $this->licence(['c2c' => 50, 'webrtc' => 0]);
        $this->assertSame('Smart31 WebRTC is not included in your licence', VmnService::check($this->agent, self::B, 'webrtc')['message']);
    }

    public function test_a_number_enabled_for_both_ways_is_in_both_lists()
    {
        $this->at(15, 30);
        $this->assertContains(self::B, $this->listed('click2call'));
        $this->assertSame([self::D, self::B], $this->listed('webrtc'), 'webrtc=1 numbers, also those with c2c=1');
        $this->assertNull(Smart31Schedule::callError($this->agent, 'c2c', self::B));
        $this->assertNull(Smart31Schedule::callError($this->agent, 'webrtc', self::B));
    }

    public function test_click2call_only_licence()
    {
        $this->licence(['c2c' => 50, 'webrtc' => 0]);
        $this->assertSame(['c2c' => true, 'webrtc' => false], Smart31Schedule::licensedKinds());
        $this->assertNotEmpty($this->listed('click2call'));
        $this->assertSame([], $this->listed('webrtc'), 'no WebRTC list even for webrtc=1 numbers');
        $this->assertNull(app(WebRTCController::class)->getwVmn(Request::create('/x', 'GET'))->getData(true)['wvmn']);
        $this->assertSame('Smart31 WebRTC is not included in your licence', Smart31Schedule::callError($this->agent, 'webrtc', self::B));
    }

    public function test_webrtc_only_licence()
    {
        $this->licence(['c2c' => 0, 'webrtc' => 50]);
        $this->assertSame(['c2c' => false, 'webrtc' => true], Smart31Schedule::licensedKinds());
        $this->assertSame([], $this->listed('click2call'));
        $this->assertNotEmpty($this->listed('webrtc'));
        $this->assertSame('Smart31 Click2Call is not included in your licence', Smart31Schedule::callError($this->agent, 'c2c', self::A));
        $this->assertSame('Smart31 Click2Call is not included in your licence', VmnService::check($this->agent, self::A, 'click2call', 'smart31')['message']);
    }

    public function test_both_in_licence()
    {
        $this->licence(['c2c' => 50, 'webrtc' => 50]);
        $this->assertSame(['c2c' => true, 'webrtc' => true], Smart31Schedule::licensedKinds());
        $this->assertNotEmpty($this->listed('click2call'));
        $this->assertNotEmpty($this->listed('webrtc'));
    }

    public function test_neither_in_licence()
    {
        $this->licence(['c2c' => 0, 'webrtc' => '0']);
        $this->assertSame(['c2c' => false, 'webrtc' => false], Smart31Schedule::licensedKinds());
        $this->assertSame([], $this->listed('click2call'));
        $this->assertSame([], $this->listed('webrtc'));
        $this->assertFalse(VmnService::check($this->agent, self::A, 'click2call', 'smart31')['ok']);
        $this->assertFalse(VmnService::check($this->agent, self::B, 'webrtc')['ok']);
    }

    public function test_a_key_missing_from_the_licence_is_allowed()
    {
        $this->licence(['c2c' => 50, 'webrtc' => null]);   // older licence without webrtc_channels
        $this->assertSame(['c2c' => true, 'webrtc' => true], Smart31Schedule::licensedKinds());
        $this->assertNotEmpty($this->listed('webrtc'));
        $this->licence([]);   // neither key
        $this->assertSame(['c2c' => true, 'webrtc' => true], Smart31Schedule::licensedKinds());
    }

    public function test_click2call_endpoint_refuses_before_dialing()
    {
        Http::fake();   // nothing may reach the call provider
        $this->licence(['c2c' => 0, 'webrtc' => 50]);
        $agent = User::all()->first(fn ($u) => $u->canClick2Call() && ($u->permissionsGroup->name ?? '') === 'Telecaller');
        if (!$agent) {
            $this->markTestSkipped('No Telecaller with Click2Call enabled in the local database.');
        }
        Auth::login($agent);
        $req = Request::create('/x', 'POST', ['mobile' => '9000000777', 'vmn' => self::B, 'smart31_enabled' => 1, 'source' => 'manual']);
        $req->setUserResolver(fn () => $agent);
        app()->instance('request', $req);
        $res = app(DialerController::class)->click2call($req);
        $body = $res->getData(true);
        $this->assertFalse($body['success']);
        if ($res->getStatusCode() !== 422 || $body['message'] === 'CRM ID is not available') {
            $this->markTestIncomplete('Stopped before the number check (' . $res->getStatusCode() . ': ' . $body['message'] . ')');
        }
        $this->assertSame('Smart31 Click2Call is not included in your licence', $body['message']);
        Http::assertNothingSent();
    }
}
