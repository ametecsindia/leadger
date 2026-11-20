<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\NotificationsController;
use App\Models\User;
use App\Services\UserPresence;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Login sessions for Agent Performance: online while the pages report activity; logout time = logout, or the last
 * activity when the browser was closed / the session expired; a user still working gets a new session.
 */
class UserPresenceTest extends TestCase
{
    use DatabaseTransactions;

    private User $tc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tc = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        DB::table('user_logs')->where('user_id', $this->tc->id)->delete();   // rolled back after each test
    }

    private function open(string $loginAgo, string $seenAgo): int
    {
        return DB::table('user_logs')->insertGetId(['user_id' => $this->tc->id, 'scenario' => 'login', 'info' => 'User Login',
            'login_date' => now()->sub($loginAgo)->toDateTimeString(), 'created_at' => now()->sub($loginAgo), 'updated_at' => now()->sub($seenAgo)]);
    }

    private function log(int $id)
    {
        return DB::table('user_logs')->where('id', $id)->first();
    }

    public function test_an_active_session_stays_online_and_is_marked_seen_by_the_page_poll()
    {
        $id = $this->open('2 hours', '3 minutes');
        Auth::login($this->tc);
        app(NotificationsController::class)->poll(Request::create('/x', 'GET'));

        UserPresence::closeStale();   // (other users' old sessions in the test database may be closed too)
        $row = $this->log($id);
        $this->assertNull($row->logout_date, 'still online');
        $this->assertTrue(now()->diffInSeconds(\Carbon\Carbon::parse($row->updated_at), true) < 5, 'seen now');
    }

    public function test_a_closed_browser_is_logged_out_at_its_last_activity()
    {
        $id = $this->open('3 hours', '40 minutes');
        UserPresence::closeStale();
        $row = $this->log($id);
        $this->assertSame($row->updated_at, $row->logout_date, 'logout = last activity');
        $this->assertMatchesRegularExpression('/^02:2\d:\d\d$/', $row->logged_in_duration, 'about 2 h 20 min');
    }

    public function test_the_next_login_closes_the_old_session_at_its_last_activity_not_at_10_pm()
    {
        $id = $this->open('1 day', '23 hours');
        $seen = $this->log($id)->updated_at;
        app(\App\Http\Controllers\Auth\LoginController::class)->userLogs($this->tc, Request::create('/x', 'POST'));
        $old = $this->log($id);
        $this->assertSame($seen, $old->logout_date, 'logout = when it was last seen (yesterday), not now or 10 PM');
        $this->assertSame(1, DB::table('user_logs')->where('user_id', $this->tc->id)->whereNull('logout_date')->count(), 'the new login is open');
    }

    public function test_a_user_still_working_after_an_inactive_close_gets_a_new_session()
    {
        $id = $this->open('1 hour', '30 minutes');
        UserPresence::closeStale();
        Auth::login($this->tc);
        app(NotificationsController::class)->poll(Request::create('/x', 'GET'));
        $open = DB::table('user_logs')->where('user_id', $this->tc->id)->whereNull('logout_date')->get();
        $this->assertCount(1, $open);
        $this->assertNotSame($id, (int)$open[0]->id);
    }
}
