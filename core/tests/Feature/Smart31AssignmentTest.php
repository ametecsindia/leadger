<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\WebRTCController;
use App\Models\User;
use App\Support\Smart31Schedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smart31 numbers assigned to teams (Smart31 group edit page) are offered to those teams' agents for calling:
 * the page saves exactly the selected teams on every row of the group, and the rows the license server / Add Voucher
 * insert for a new day get the group's teams back. Test rows are marked (group_name) and rolled back.
 */
class Smart31AssignmentTest extends TestCase
{
    use DatabaseTransactions;

    private const GROUP = 'PHPUNIT Smart31AssignmentTest';

    private int $teamA;
    private int $teamB;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
        Carbon::setTestNow(Carbon::create(2099, 1, 5, 10, 0, 0, Smart31Schedule::TIMEZONE));
        Cache::put(Smart31Schedule::CACHE_KEY, ['c2c' => 50, 'webrtc' => 50], 60);
        $this->teamA = DB::table('teams')->insertGetId(['name' => 'PHPUNIT team A', 'created_at' => now(), 'updated_at' => now()]);
        $this->teamB = DB::table('teams')->insertGetId(['name' => 'PHPUNIT team B', 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();
        parent::tearDown();
    }

    private function row(string $vmn, string $date, ?string $teams): void
    {
        DB::table('smart31_vmn_numbers')->insert(['vmn' => $vmn, 'usage_date' => $date, 'c2c' => 1, 'webrtc' => 1, 'obd' => 0,
            'type' => 'smart31', 'group_name' => self::GROUP, 'assigned_groups' => $teams, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function agent(int $team): User
    {
        $u = new User();
        $u->id = 990000 + $team;
        $u->team_id = (string)$team;
        return $u;
    }

    private function offered(int $team): array
    {
        Cache::flush();
        Cache::put(Smart31Schedule::CACHE_KEY, ['c2c' => 50, 'webrtc' => 50], 60);
        return array_column(Smart31Schedule::lines($this->agent($team), 'c2c')['lines'], 'vmn');
    }

    private function save(array $teams): void
    {
        app(WebRTCController::class)->smart31GroupUpdate(Request::create('/x', 'POST', ['group_name' => self::GROUP, 'assigned_groups' => $teams]));
    }

    public function test_assigning_a_group_offers_its_numbers_to_the_team_and_unticking_removes_them()
    {
        $today = Smart31Schedule::now()->toDateString();
        $this->row('9900055101', Smart31Schedule::now()->subDay()->toDateString(), null);
        $this->row('9900055101', $today, null);
        $this->assertSame([], $this->offered($this->teamA), 'not assigned yet');

        $this->save([(string)$this->teamA, ' ' . $this->teamB . ' ', '', '0', '99999999']);   // messy input
        $this->assertSame(
            [$this->teamA . ',' . $this->teamB],
            DB::table('smart31_vmn_numbers')->where('group_name', self::GROUP)->distinct()->pluck('assigned_groups')->all(),
            'every row of the group, clean ids of existing teams only'
        );
        $this->assertSame(['9900055101'], $this->offered($this->teamA));
        $this->assertSame(['9900055101'], $this->offered($this->teamB));

        $this->save([(string)$this->teamA]);   // team B un-ticked
        $this->assertSame(['9900055101'], $this->offered($this->teamA));
        $this->assertSame([], $this->offered($this->teamB), 'un-ticking a team removes it (no more merging)');
    }

    public function test_a_new_days_numbers_keep_the_teams_of_their_group()
    {
        $this->row('9900055102', Smart31Schedule::now()->subDay()->toDateString(), (string)$this->teamA);
        $this->row('9900055102', Smart31Schedule::now()->toDateString(), null);   // the license server's row for today
        $this->assertSame([], $this->offered($this->teamA), 'before: today has no team');

        $this->assertSame(1, Smart31Schedule::inheritAssignments());
        $this->assertSame(['9900055102'], $this->offered($this->teamA));
    }

    public function test_add_voucher_reinsert_gets_the_teams_back_from_history()
    {
        DB::table('smart31_history')->insert(['vmn' => '9900055103', 'usage_date' => Smart31Schedule::now()->subDay()->toDateString(),
            'c2c' => 1, 'webrtc' => 1, 'obd' => 0, 'type' => 'smart31', 'group_name' => self::GROUP,
            'assigned_groups' => (string)$this->teamB, 'created_at' => now(), 'updated_at' => now()]);
        $this->row('9900055103', Smart31Schedule::now()->toDateString(), null);

        Smart31Schedule::inheritAssignments();
        $this->assertSame(['9900055103'], $this->offered($this->teamB));
    }

    public function test_the_edit_page_shows_the_current_assignment()
    {
        $this->row('9900055104', Smart31Schedule::now()->subDays(3)->toDateString(), (string)$this->teamB);   // old
        $this->row('9900055104', Smart31Schedule::now()->toDateString(), (string)$this->teamA);               // current
        $view = app(WebRTCController::class)->smart31GroupEdit(self::GROUP);
        $this->assertSame([(string)$this->teamA], $view->getData()['assignedIds']);
    }
}
