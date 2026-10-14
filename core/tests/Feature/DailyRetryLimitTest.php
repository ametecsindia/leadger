<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\DialerController;
use App\Models\User;
use App\Services\CallingRestrictionService as R;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Maximum retry of a particular mobile number in a day" (settings.daily_call_limit):
 * counted per normalized number per day (Asia/Kolkata), across all agents and campaigns.
 */
class DailyRetryLimitTest extends TestCase
{
    use DatabaseTransactions;

    private Carbon $noon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->noon = Carbon::now(R::getTimezone())->setTime(12, 0);
        $this->limit(5);
    }

    protected function tearDown(): void
    {
        R::clearSettingsCache();   // the rolled-back test values must not stay cached
        parent::tearDown();
    }

    private function limit(int $n): void
    {
        DB::table('settings')->where('id', 1)->update(['daily_call_limit' => $n, 'calling_hours_from' => '00:00', 'calling_hours_to' => '23:59']);
        R::clearSettingsCache();   // what Save Calling Settings does
    }

    private function attempt(string $phone, $agent = null, ?Carbon $at = null): array
    {
        return R::canPlaceOutboundCall($phone, $agent, 'click2call', true, $at ?? $this->noon);
    }

    private function attemptsUsed(string $phone, ?Carbon $at = null): int
    {
        return (int)DB::table('daily_phone_call_attempts')->where('phone_normalized', R::normalizePhoneNumber($phone))
            ->where('call_date', ($at ?? $this->noon)->format('Y-m-d'))->value('attempt_count');
    }

    private function num(): string
    {
        return '70' . random_int(10000000, 99999999);
    }

    public function test_limit_is_per_number_and_other_numbers_stay_callable()
    {
        [$a, $b] = [$this->num(), $this->num()];
        for ($i = 1; $i <= 5; $i++) {
            $this->assertTrue($this->attempt($a)['allowed'], "attempt {$i} to A");
        }
        $sixth = $this->attempt($a);
        $this->assertFalse($sixth['allowed']);
        $this->assertSame('Maximum call attempts reached for this number today (5).', $sixth['message']);
        $this->assertTrue($this->attempt($b)['allowed'], 'B is never blocked by A');
        $this->assertSame(5, $this->attemptsUsed($a), 'the blocked 6th attempt is not counted');
        $this->assertSame(1, $this->attemptsUsed($b));
    }

    public function test_same_number_in_different_formats_and_from_different_agents_counts_once()
    {
        $a = $this->num();
        $agents = User::where('status', 1)->limit(2)->pluck('id')->all();
        $this->attempt($a, $agents[0]);
        $this->attempt('+91' . $a, $agents[1] ?? $agents[0]);
        $this->attempt('0' . $a);
        $this->attempt('91-' . substr($a, 0, 5) . ' ' . substr($a, 5));
        $this->assertSame(4, $this->attemptsUsed($a));
    }

    public function test_check_only_calls_and_failed_starts_do_not_use_the_limit()
    {
        $a = $this->num();
        R::canPlaceOutboundCall($a, null, 'click2call', false, $this->noon);   // a controller's check
        $this->assertSame(0, $this->attemptsUsed($a));

        $this->attempt($a);
        R::releaseAttempt($a, $this->noon);   // the provider did not start the call
        $this->assertSame(0, $this->attemptsUsed($a));
    }

    public function test_agent_or_setup_errors_before_dialling_do_not_count()
    {
        $user = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($user);
        $a = $this->num();
        $req = Request::create('/x', 'POST', ['mobile' => $a, 'vmn' => '0000000000', 'source' => 'manual']);
        $req->setUserResolver(fn () => $user);
        app()->instance('request', $req);
        $res = app(DialerController::class)->click2call($req);   // fails before any provider request (channel / CRM / VMN / agent)
        $this->assertFalse($res->getData(true)['success']);
        $this->assertSame(0, $this->attemptsUsed($a, Carbon::now(R::getTimezone())), 'nothing reached the customer');
    }

    public function test_count_resets_the_next_day_and_a_new_limit_applies_at_once()
    {
        $a = $this->num();
        for ($i = 0; $i < 5; $i++) $this->attempt($a);
        $this->assertFalse($this->attempt($a)['allowed']);
        $this->assertTrue($this->attempt($a, null, $this->noon->copy()->addDay())['allowed'], 'next day starts at 0');

        $this->limit(7);
        $this->assertTrue($this->attempt($a)['allowed'], '6th allowed once the limit is 7');
        $this->assertTrue($this->attempt($a)['allowed']);
        $this->assertFalse($this->attempt($a)['allowed'], '8th blocked');
    }

    /** Two separate processes race for the last slot of one number: exactly one may get it. */
    public function test_two_simultaneous_attempts_at_the_last_slot()
    {
        DB::rollBack();   // the racing processes must see committed rows
        $a = '79' . random_int(10000000, 99999999);
        $date = Carbon::now(R::getTimezone())->format('Y-m-d');
        $max = (int)(R::getSettings()->daily_call_limit ?: 100);
        try {
            DB::table('daily_phone_call_attempts')->insert(['phone_normalized' => $a, 'call_date' => $date, 'attempt_count' => $max - 1, 'created_at' => now(), 'updated_at' => now()]);
            $code = 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();'
                . ' usleep(300000 - (int)(microtime(true) * 1e6) % 300000);'
                . ' $r = App\Services\CallingRestrictionService::canPlaceOutboundCall("' . $a . '", null, "test", true, Carbon\Carbon::now(App\Services\CallingRestrictionService::getTimezone())->setTime(12, 0));'
                . ' echo $r["allowed"] ? "allowed" : "blocked";';
            $procs = [];
            foreach ([1, 2] as $i) {
                $procs[$i] = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i], base_path());
            }
            $out = [];
            foreach ($procs as $i => $p) {
                $out[] = trim(stream_get_contents($pipes[$i][1]));
                proc_close($p);
            }
            sort($out);
            $this->assertSame(['allowed', 'blocked'], $out, 'one call gets the last slot, the other is blocked');
            $this->assertSame($max, (int)DB::table('daily_phone_call_attempts')->where('phone_normalized', $a)->where('call_date', $date)->value('attempt_count'));
        } finally {
            DB::table('daily_phone_call_attempts')->where('phone_normalized', $a)->delete();
            DB::table('blocked_call_logs')->where('phone_normalized', $a)->delete();
            DB::beginTransaction();   // hand DatabaseTransactions a transaction to roll back
        }
    }
}
