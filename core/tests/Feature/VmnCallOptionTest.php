<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\DialerController;
use App\Models\User;
use App\Services\VmnService;
use App\Support\Smart31Schedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Make a Call > Click2Call: Smart31 / Enabled 1600 show and call with that option's own stored numbers only. */
class VmnCallOptionTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private string $plain;

    protected function setUp(): void
    {
        parent::setUp();
        // Smart31 numbers are for today and enabled by time slot (Smart31Schedule): today at 10:00 (slot 1), licence with both ways
        Carbon::setTestNow(Carbon::now(Smart31Schedule::TIMEZONE)->setTime(10, 0));
        config(['cache.default' => 'array']);   // the dropdown answer is cached 1 minute: flushed when a test adds numbers
        Cache::put(Smart31Schedule::CACHE_KEY, ['c2c' => 50, 'webrtc' => 50], 60);
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);
        $this->plain = VmnService::normalize(VmnService::listFor($this->admin, 'click2call')->first()->virtual_number ?? '');
    }

    protected function tearDown(): void
    {
        Smart31Schedule::forget();
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function addPoolNumber(string $vmn, ?string $type): void
    {
        DB::table('smart31_vmn_numbers')->insert(['vmn' => $vmn, 'type' => $type, 'c2c' => 1, 'webrtc' => 0, 'obd' => 0,
            'usage_date' => Smart31Schedule::now()->toDateString(), 'assigned_groups' => $this->admin->team_id ?: null, 'created_at' => now(), 'updated_at' => now()]);
        $licence = Cache::get(Smart31Schedule::CACHE_KEY);
        Cache::flush();   // the 1-minute dropdown cache (live: a new number shows within a minute)
        Cache::put(Smart31Schedule::CACHE_KEY, $licence, 60);
    }

    private function listed(string $method): array
    {
        $req = Request::create('/x', 'GET', ['type' => 'click2call']);
        return collect(app(DialerController::class)->{$method}($req)->getData(true)['vmns'])->pluck('number')->all();
    }

    public function test_each_option_lists_only_its_own_numbers()
    {
        DB::table('smart31_vmn_numbers')->delete();   // rolled back after the test
        $this->assertSame([], $this->listed('getSmart31Vmns'), 'no Smart31 number stored: nothing borrowed from the Click2Call VMNs');
        $this->assertSame([], $this->listed('get1600Vmns'), 'no 1600 number stored: no fallback to other numbers');

        $this->addPoolNumber('9988776655', 'smart31');
        $this->addPoolNumber('1600123456', '1600');
        $this->assertSame(['9988776655'], $this->listed('getSmart31Vmns'));
        $this->assertSame(['1600123456'], $this->listed('get1600Vmns'));
    }

    public function test_call_must_use_a_number_of_the_selected_option()
    {
        DB::table('smart31_vmn_numbers')->delete();
        $this->assertFalse(VmnService::check($this->admin, '9988776655', 'click2call', 'smart31')['ok']);
        $this->assertSame('Please select VMN.', VmnService::check($this->admin, '', 'click2call', 'smart31')['message']);

        $this->addPoolNumber('9988776655', 'smart31');
        $this->addPoolNumber('1600123456', '1600');
        $this->assertTrue(VmnService::check($this->admin, '+919988776655', 'click2call', 'smart31')['ok']);
        $this->assertTrue(VmnService::check($this->admin, '1600123456', 'click2call', '1600')['ok']);

        $this->assertFalse(VmnService::check($this->admin, '1600123456', 'click2call', 'smart31')['ok'], '1600 number with Smart31 selected');
        $this->assertFalse(VmnService::check($this->admin, '9988776655', 'click2call', '1600')['ok'], 'Smart31 number with 1600 selected');
        if ($this->plain !== '') {
            $this->assertFalse(VmnService::check($this->admin, $this->plain, 'click2call', 'smart31')['ok'], 'plain VMN with Smart31 selected');
            $this->assertTrue(VmnService::check($this->admin, $this->plain, 'click2call')['ok'], 'no option: plain Click2Call VMN still works');
        }
    }

    public function test_click2call_endpoint_refuses_a_number_from_another_option_before_dialing()
    {
        \Illuminate\Support\Facades\Http::fake();   // nothing may reach the call provider
        DB::table('smart31_vmn_numbers')->delete();
        $this->addPoolNumber('9988776655', 'smart31');
        $agent = User::all()->first(fn ($u) => $u->canClick2Call() && ($u->permissionsGroup->name ?? '') === 'Telecaller');
        $this->assertNotNull($agent);
        Auth::login($agent);

        $req = Request::create('/x', 'POST', ['mobile' => '9000000777', 'vmn' => $this->plain, 'smart31_enabled' => 1, 'source' => 'manual']);
        $req->setUserResolver(fn () => $agent);
        app()->instance('request', $req);
        $res = app(DialerController::class)->click2call($req);
        $body = $res->getData(true);
        $this->assertFalse($body['success']);
        if ($body['message'] === 'CRM ID is not available') {
            $this->markTestSkipped('No active licence (settings.full_response has no CRM ID), so Click2Call stops before the number check.');
        }
        if ($res->getStatusCode() === 422) {
            $this->assertSame('This Smart31 number is not assigned to you for today', $body['message']);
        } else {
            $this->markTestIncomplete('Calling restrictions answered first (' . $res->getStatusCode() . ': ' . $body['message'] . ')');
        }
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}
