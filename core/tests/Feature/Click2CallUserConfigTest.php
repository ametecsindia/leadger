<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\CallChannelsController;
use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Models\Permissions;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Click2Call per user = users.c2c_channel + users.click_agent_number. Staff create/edit, the Click2Call
 * and Call Channels pages, the Calling Workspace and the call endpoints all use the same saved values.
 * (Staff users get smartping ids here so UsersController::update never calls the external SmartPing API.)
 */
class Click2CallUserConfigTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);
    }

    private function staff(array $attrs = []): User
    {
        return User::forceCreate(array_merge([
            'name' => 'C2C Test Agent', 'email' => 'c2c_test_' . uniqid() . '@example.test', 'password' => bcrypt('x'),
            'permissions_id' => Permissions::where('name', 'Telecaller')->value('id'), 'status' => 1,
            'mobile' => '9' . random_int(100000000, 999999999), 'team_id' => '1',
            'c2c_channel' => 0, 'webrtc_channel' => 0, 'click_agent_number' => null,
            'smartping_user_id' => 'test', 'smartping_password' => 'test',
        ], $attrs));
    }

    /** Save through the Edit Staff modal: same fields the form posts. */
    private function saveStaffModal(User $u, ?string $callingMethod, array $extra = []): void
    {
        $data = array_merge([
            'name' => $u->name, 'email' => $u->email, 'mobile' => $u->mobile, 'permissions_id' => $u->permissions_id,
            'team_id' => [1], 'calling_method_present' => '1',
            'shift_id' => \App\Models\Shift::value('id'), 'work_location_id' => \App\Models\WorkLocation::value('id'),
        ], $extra);
        if ($callingMethod) $data['calling_method'] = $callingMethod;
        $data = array_filter($data, fn ($v) => $v !== null); // e.g. the Profile form sends no calling section at all
        $req = Request::create('/users/' . $u->id . '/update', 'POST', $data);
        $req->setUserResolver(fn () => $this->admin);
        app()->instance('request', $req);
        app(UsersController::class)->update($req, $u->id);
    }

    private function channelConfigFor(User $u): array
    {
        Auth::login($u);
        $req = Request::create('/dialer/channel-config', 'GET');
        $req->setUserResolver(fn () => $u);
        app()->instance('request', $req);
        $res = json_decode(app(DialerController::class)->getChannelConfig($req)->getContent(), true);
        Auth::login($this->admin);
        return $res;
    }

    private function click2callAs(User $u): array
    {
        Auth::login($u);
        $req = Request::create('/dialer/click2call', 'POST', ['mobile' => '9000000001', 'vmn' => '7714581128', 'source' => 'manual']);
        $req->setUserResolver(fn () => $u);
        app()->instance('request', $req);
        $res = app(DialerController::class)->click2call($req);
        Auth::login($this->admin);
        return [$res->getStatusCode(), json_decode($res->getContent(), true)];
    }

    public function test_enabling_click2call_in_edit_staff_saves_it_and_the_workspace_shows_it()
    {
        $u = $this->staff();
        $this->assertFalse($this->channelConfigFor($u)['click2call_enabled'], 'disabled user: no global fallback any more');

        $this->saveStaffModal($u, 'click2call');
        $u->refresh();
        $this->assertSame(1, (int)$u->c2c_channel);
        $this->assertSame($u->mobile, $u->click_agent_number, 'agent number = the staff mobile');

        $cfg = $this->channelConfigFor($u);
        $this->assertTrue($cfg['click2call_enabled']);
        $this->assertSame($u->mobile, $cfg['click2call_agent_number']);
        $this->assertContains('click2call', $cfg['channels']);

        // Click2Call / Call Channels page lists the user
        $view = app(CallChannelsController::class)->index();
        $this->assertTrue($view->getData()['c2cUsers']->contains('id', $u->id));
    }

    public function test_disabling_click2call_blocks_the_workspace_and_the_call_endpoint()
    {
        $u = $this->staff(['c2c_channel' => 1, 'click_agent_number' => null]);
        $u->forceFill(['click_agent_number' => $u->mobile])->save();
        $this->assertTrue($this->channelConfigFor($u)['click2call_enabled']);

        $this->saveStaffModal($u, null); // Click2Call unticked
        $u->refresh();
        $this->assertSame(0, (int)$u->c2c_channel);
        $cfg = $this->channelConfigFor($u);
        $this->assertFalse($cfg['click2call_enabled']);
        $this->assertSame('Click2Call is not enabled for your account.', $cfg['click2call_message']);

        [$code, $body] = $this->click2callAs($u);
        $this->assertSame(403, $code, 'server refuses a direct Click2Call request');
        $this->assertStringContainsString('not enabled', $body['message']);
    }

    public function test_profile_form_without_calling_section_does_not_change_click2call()
    {
        $u = $this->staff(['c2c_channel' => 1]);
        $u->forceFill(['click_agent_number' => $u->mobile])->save();
        $this->saveStaffModal($u, null, ['calling_method_present' => null]); // Profile page posts no marker
        $this->assertSame(1, (int)$u->fresh()->c2c_channel);
    }

    public function test_choosing_webrtc_turns_click2call_off_but_keeps_the_saved_number()
    {
        $u = $this->staff(['c2c_channel' => 1]);
        $u->forceFill(['click_agent_number' => $u->mobile])->save();
        $this->saveStaffModal($u, 'webrtc');
        $this->assertSame(0, (int)$u->fresh()->c2c_channel);
        $this->assertSame($u->mobile, $u->fresh()->click_agent_number);
    }

    public function test_enabled_but_no_agent_number_is_reported_not_offered()
    {
        $u = $this->staff(['c2c_channel' => 1, 'mobile' => null, 'click_agent_number' => null]);
        $cfg = $this->channelConfigFor($u);
        $this->assertFalse($cfg['click2call_enabled']);
        $this->assertSame('Click2Call is enabled but no agent mobile number is saved for you.', $cfg['click2call_message']);
        $this->assertSame("Click2Call needs the agent's mobile number. Add a mobile number for this user.", $u->setClick2Call(true));
    }

    public function test_licence_limit_is_enforced_when_enabling()
    {
        $limit = \App\Helpers\Helper::licensedClick2CallUsers();
        if ($limit <= 0) {
            $this->markTestSkipped('No Click2Call licence limit configured.');
        }
        $inUse = User::where('c2c_channel', 1)->count();
        for ($i = $inUse; $i < $limit; $i++) {
            $f = $this->staff();
            $this->assertNull($f->setClick2Call(true));
            $f->save();
        }
        $over = $this->staff();
        $this->assertStringContainsString('limit reached', (string)$over->setClick2Call(true));
        $this->assertSame(0, (int)$over->fresh()->c2c_channel);
    }
}
