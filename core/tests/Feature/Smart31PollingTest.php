<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Controllers\Dashboard\WebRTCController;
use App\Models\User;
use App\Support\Smart31Schedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Smart31 dropdown requests do not load the database: the calling page asks once a day (v=2) and works out the time
 * slot itself; the server keeps each answer 1 minute per agent / kind / day / slot; a page opened before this update
 * (no v=2) gets its numbers disabled with a reload hint, the plain vmns list unchanged. Test rows are marked
 * (group_name) on a future date with a team id no real agent has, and removed afterwards.
 */
class Smart31PollingTest extends TestCase
{
    use DatabaseTransactions;

    private const MARK = 'PHPUNIT Smart31PollingTest';
    private const TEAM = '990011';
    private const NUMBERS = ['9900021101', '9900021102', '9900021103'];

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
        $this->at(10, 0);
        $this->agent = new User();
        $this->agent->id = 990011;
        $this->agent->team_id = self::TEAM;
        Auth::setUser($this->agent);
        Cache::put(Smart31Schedule::CACHE_KEY, ['c2c' => 50, 'webrtc' => 50], 60);
        foreach (self::NUMBERS as $vmn) {
            DB::table('smart31_vmn_numbers')->insert(['vmn' => $vmn, 'usage_date' => Smart31Schedule::now()->toDateString(),
                'c2c' => 1, 'webrtc' => 1, 'obd' => 0, 'type' => 'smart31', 'group_name' => self::MARK,
                'assigned_groups' => self::TEAM, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    protected function tearDown(): void
    {
        DB::table('smart31_vmn_numbers')->where('group_name', self::MARK)->delete();
        Cache::flush();
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Fake "now": 5 Jan 2099 at H:M in Asia/Kolkata (the test rows are dated that day). */
    private function at(int $hour, int $minute): void
    {
        Carbon::setTestNow(Carbon::create(2099, 1, 5, $hour, $minute, 0, Smart31Schedule::TIMEZONE));
    }

    private function ask(string $type, bool $newPage = true): array
    {
        $params = ['type' => $type] + ($newPage ? ['v' => 2] : []);
        return app(DialerController::class)->getSmart31Vmns(Request::create('/x', 'GET', $params))->getData(true);
    }

    /** vmn => enabled, in the order shown. */
    private static function shown(array $res): array
    {
        return array_column($res['lines'], 'enabled', 'vmn');
    }

    public function test_the_new_page_gets_the_enabled_numbers_of_the_slot()
    {
        [$a, $b, $c] = self::NUMBERS;

        $this->at(10, 0);    // slot 1
        $res = $this->ask('click2call');
        $this->assertSame([$a => true, $b => false, $c => false], self::shown($res));
        $this->assertSame(1, $res['slot']);
        $this->assertNull($res['message']);

        $this->at(13, 0);    // slot 2: newest enabled first and default
        $res = $this->ask('webrtc');
        $this->assertSame([$b => true, $a => true, $c => false], self::shown($res));
        $this->assertTrue($res['lines'][0]['default']);

        $this->at(20, 1);    // after hours: all disabled, with the hours message
        $res = $this->ask('click2call');
        $this->assertSame([$a => false, $b => false, $c => false], self::shown($res));
        $this->assertSame(0, $res['slot']);
        $this->assertSame(Smart31Schedule::hoursMessage(), $res['message']);
        // every line carries its position: the page works out enabled / default from its own clock
        $this->assertSame([1, 2, 3], array_column($res['lines'], 'position'));
    }

    public function test_a_page_opened_before_the_update_is_asked_to_reload()
    {
        [$a, $b] = self::NUMBERS;
        $this->at(13, 0);
        $new = $this->ask('click2call');
        $old = $this->ask('click2call', false);

        $this->assertSame('Smart31 updated: press Ctrl+F5 to reload this page', $old['message']);
        $this->assertSame([], array_filter(array_column($old['lines'], 'enabled')));
        $this->assertSame([], array_filter(array_column($old['lines'], 'default')));
        // the plain list is the same as for the new page
        $this->assertSame($new['vmns'], $old['vmns']);
        $this->assertSame([$b, $a], array_slice(array_column($old['vmns'], 'number'), 0, 2));
    }

    public function test_a_licence_without_the_calling_way_returns_no_numbers()
    {
        Cache::put(Smart31Schedule::CACHE_KEY, ['c2c' => 0, 'webrtc' => 50], 60);
        $res = $this->ask('click2call');
        $this->assertSame([], $res['lines']);
        $this->assertSame('Smart31 Click2Call is not included in your licence', $res['message']);
        $this->assertCount(3, $this->ask('webrtc')['lines']);
    }

    public function test_the_answer_is_kept_one_minute_and_a_new_slot_is_seen_at_once()
    {
        $count = 0;
        DB::listen(function ($q) use (&$count) {
            if (str_contains($q->sql, 'smart31_vmn_numbers')) $count++;
        });
        $this->at(11, 59);
        $this->ask('click2call');
        $this->ask('click2call');
        $this->ask('click2call', false);
        $this->assertSame(1, $count, 'the same agent, kind and slot is read once');

        $this->at(12, 1);    // slot 2 starts: new key, the 2nd number is enabled at once
        $res = $this->ask('click2call');
        $this->assertSame(2, $count);
        $this->assertTrue($res['lines'][0]['enabled'] && $res['lines'][1]['enabled']);

        // the old endpoints use the same answer
        $this->assertSame(self::NUMBERS[1], app(WebRTCController::class)->getVmn(Request::create('/x'))->getData(true)['vmn']);
        $this->assertSame(2, $count);
    }

    public function test_the_calling_page_asks_once_a_day_and_not_every_30_seconds()
    {
        $blade = file_get_contents(resource_path('views/dashboard/calling-workspace.blade.php'));
        $this->assertStringNotContainsString('setInterval(lgSmart31Refresh, 30000)', $blade);
        $this->assertStringNotContainsString("\$(window).on('focus', lgSmart31Refresh)", $blade);
        $this->assertStringContainsString('v: 2', $blade);
        $this->assertStringContainsString('lg_smart31_day_', $blade);
    }

    public function test_queries_per_request_through_the_middleware()
    {
        URL::forceRootUrl('http://127.0.0.1');
        $user = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $sql = [];
        DB::listen(function ($q) use (&$sql) { $sql[] = $q->sql; });
        $runs = [];
        foreach ([1, 2] as $i) {
            $sql = [];
            $this->actingAs($user)->get(route('dialerSmart31Vmns', ['type' => 'click2call', 'v' => 2]))->assertOk();
            $runs[$i] = $sql;
        }
        $smart31 = fn (array $q) => count(array_filter($q, fn ($s) => str_contains($s, 'smart31_vmn_numbers')));
        fwrite(STDERR, "\n[Smart31] queries per request: first " . count($runs[1]) . ' (numbers: ' . $smart31($runs[1])
            . '), cached ' . count($runs[2]) . ' (numbers: ' . $smart31($runs[2]) . ")\n");
        $this->assertSame(1, $smart31($runs[1]));
        $this->assertSame(0, $smart31($runs[2]), 'the second request in the same minute does not read the numbers again');
    }
}
