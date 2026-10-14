<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\Click2CallController;
use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Models\Permissions;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Staff phone number: users.mobile (contact number, Staff page) and users.click_agent_number (Click2Call
 * calling number). Editing the number in Edit Staff saves users.mobile, and the calling number follows it
 * when it was the same number. (smartping ids are set so update() never calls the external SmartPing API.)
 */
class StaffMobileSyncTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        Auth::login($this->admin);
    }

    private function staff(string $mobile, array $attrs = []): User
    {
        return User::forceCreate(array_merge([
            'name' => 'Mobile Sync Agent', 'email' => 'mobile_sync_' . uniqid() . '@example.test', 'password' => bcrypt('x'),
            'permissions_id' => Permissions::where('name', 'Telecaller')->value('id'), 'status' => 1, 'team_id' => '1',
            'mobile' => $mobile, 'c2c_channel' => 1, 'click_agent_number' => $mobile, 'webrtc_channel' => 0,
            'smartping_user_id' => 'test', 'smartping_password' => 'test',
        ], $attrs));
    }

    /** Edit Staff modal (with its Calling Method section) or the Profile form (without it). */
    private function edit(User $u, string $mobile, bool $staffModal = true): void
    {
        $data = ['name' => $u->name, 'email' => $u->email, 'mobile' => $mobile, 'permissions_id' => $u->permissions_id, 'team_id' => [1],
            'shift_id' => \App\Models\Shift::value('id'), 'work_location_id' => \App\Models\WorkLocation::value('id')];
        if ($staffModal) {
            $data += ['calling_method_present' => '1', 'calling_method' => (int)$u->c2c_channel === 1 ? 'click2call' : 'webrtc'];
        }
        $req = Request::create('/users/' . $u->id . '/update', 'POST', $data);
        $req->setUserResolver(fn () => $this->admin);
        app()->instance('request', $req);
        app(UsersController::class)->update($req, $u->id);
    }

    private function click2CallPageNumber(User $u): ?string
    {
        $req = Request::create('/x', 'POST', ['draw' => 1, 'start' => 0, 'length' => 200, 'columns' => [['data' => 'name']], 'order' => [['column' => 0, 'dir' => 'asc']]]);
        app()->instance('request', $req);
        ob_start();
        $res = app(Click2CallController::class)->click2CallData($req);
        $out = ob_get_clean();
        $json = json_decode($out !== '' ? $out : $res->getContent(), true);
        foreach ($json['data'] ?? [] as $row) {
            if (str_contains(strip_tags($row['name'] ?? ''), $u->name) && str_contains($row['action'] ?? json_encode($row), (string)$u->id)) {
                return trim(strip_tags($row['click_agent_number']));
            }
        }
        return null;
    }

    private function staffPageShows(User $u, string $mobile): bool
    {
        $html = app(UsersController::class)->index()->render();
        return str_contains($html, $mobile) && str_contains($html, "mobile: '" . $mobile . "'");
    }

    public function test_edit_staff_number_updates_staff_page_click2call_page_and_workspace()
    {
        $u = $this->staff('9811111111');
        $this->edit($u, '9822222222');
        $u->refresh();
        $this->assertSame('9822222222', $u->mobile, 'contact number saved (was never saved before the fix)');
        $this->assertSame('9822222222', $u->click_agent_number, 'calling number followed');
        $this->assertTrue($this->staffPageShows($u, '9822222222'), 'Staff page row + Edit form show the new number');
        $this->assertSame('9822222222', $this->click2CallPageNumber($u), 'Click2Call page shows the new number');
        $this->assertSame('9822222222', $u->click2CallAgentNumber(), 'Calling Workspace uses the new number');

        // a second edit works as well
        $this->edit($u->fresh(), '9833333333');
        $this->assertSame('9833333333', $u->fresh()->mobile);
        $this->assertSame('9833333333', $u->fresh()->click_agent_number);
    }

    public function test_formatted_input_is_normalized_and_invalid_input_is_rejected()
    {
        $u = $this->staff('9811111112');
        $this->edit($u, '+91 98444-55566');
        $this->assertSame('+919844455566', $u->fresh()->mobile);
        $this->assertSame('919844455566', $u->fresh()->click_agent_number);

        try {
            $this->edit($u->fresh(), '12ab');
            $this->fail('invalid number accepted');
        } catch (ValidationException $e) {
            $this->assertSame('Enter a valid mobile number: 10 to 15 digits, optionally starting with +.', $e->validator->errors()->first('mobile'));
        }
        $this->assertSame('+919844455566', $u->fresh()->mobile, 'nothing saved on a failed validation');
    }

    public function test_a_different_calling_number_set_on_the_click2call_page_is_kept()
    {
        $u = $this->staff('9811111113', ['click_agent_number' => '9700000001']);
        $this->edit($u, '9855555555');
        $this->assertSame('9855555555', $u->fresh()->mobile);
        $this->assertSame('9700000001', $u->fresh()->click_agent_number, 'deliberately different calling number preserved');
    }

    public function test_profile_form_also_saves_the_number_and_keeps_click2call_on()
    {
        $u = $this->staff('9811111114');
        $this->edit($u, '9866666666', false);
        $this->assertSame('9866666666', $u->fresh()->mobile);
        $this->assertSame('9866666666', $u->fresh()->click_agent_number);
        $this->assertSame(1, (int)$u->fresh()->c2c_channel);
    }
}
