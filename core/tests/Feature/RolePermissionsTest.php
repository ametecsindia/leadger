<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\Permissions;
use App\Models\User;
use App\Support\RoleAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Roles & Permissions: the registry (config/permissions.php) matches the sidebar, roles are saved and validated on
 * the server, every listed page / AJAX call / export is refused without its key, and the existing roles keep the
 * access they had.
 */
class RolePermissionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);   // calling hours
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
    }

    private function userWithRole(int $roleId): User
    {
        $id = DB::table('users')->insertGetId(['name' => 'Perm Test User', 'email' => uniqid('perm', true) . '@example.test', 'password' => bcrypt('x'),
            'permissions_id' => $roleId, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        return User::findOrFail($id);
    }

    private function createRole(array $keys, string $name = 'Sales Manager Test')
    {
        return $this->actingAs($this->admin)->postJson(route('rolesStore'), ['name' => $name, 'description' => 'For the test', 'permissions' => $keys]);
    }

    public function test_every_sidebar_item_has_a_permission_key_and_every_listed_action_exists()
    {
        $menu = file_get_contents(resource_path('views/dashboard/layouts/menu.blade.php'));
        $nav = substr($menu, strpos($menu, '<ul class="lg-nav-list">'));
        preg_match_all("/route\('([A-Za-z0-9_]+)'/", $nav, $m);
        $sidebar = array_values(array_unique($m[1]));

        $listed = [];
        foreach (RoleAccess::groups() as $group) {
            foreach ($group['items'] as $item) {
                $listed = array_merge($listed, $item['sidebar'] ?? []);
            }
        }
        $this->assertNotEmpty($sidebar);
        $this->assertSame([], array_values(array_diff($sidebar, $listed)), 'sidebar links without a permission item');
        $this->assertSame([], array_values(array_diff($listed, $sidebar)), 'permission items that are not in the sidebar');

        foreach (array_keys(RoleAccess::routeMap()) as $action) {
            [$ctl, $method] = explode('@', $action);
            $class = "App\\Http\\Controllers\\Dashboard\\{$ctl}Controller";
            $this->assertTrue(class_exists($class) && method_exists($class, $method), "{$action} does not exist");
        }
        foreach (RoleAccess::routeMap() as $action => $keys) {
            foreach ($keys as $key) {
                $this->assertTrue(RoleAccess::exists($key), "{$action} needs unknown key {$key}");
            }
        }
    }

    public function test_creating_a_role_saves_its_keys_slug_and_audit_entry()
    {
        $keys = ['dashboard.home.view', 'leads.lead_list.view', 'leads.lead_list.create', 'leads.lead_list.edit', 'reports.call_report.view', 'reports.call_report.export'];
        $r = $this->createRole($keys)->assertCreated()->json('role');

        $this->assertSame('sales_manager_test', $r['slug']);
        $this->assertSame(6, $r['permissions_count']);
        $saved = DB::table('role_permissions')->where('role_id', $r['id'])->pluck('permission_key')->sort()->values()->all();
        $this->assertSame(collect($keys)->sort()->values()->all(), $saved);
        $this->assertSame('created', DB::table('role_audit_logs')->where('role_id', $r['id'])->value('action'));

        // the same name again (any case) is refused
        $this->createRole(['dashboard.home.view'], 'SALES manager test')->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_an_action_without_view_unknown_and_admin_only_keys_are_rejected()
    {
        $this->createRole(['reports.call_report.export'])->assertStatus(422)->assertJsonValidationErrors('permissions');
        $this->createRole(['reports.call_report.view', 'reports.everything.view'])->assertStatus(422)->assertJsonValidationErrors('permissions');
        $this->createRole(['configuration.roles_permissions.view'])->assertStatus(422)->assertJsonValidationErrors('permissions');
        $this->createRole(['recordings.call_recordings.view', 'recordings.call_recordings.delete'])->assertStatus(422);
        $this->createRole(['dashboard.home.view'], 'A')->assertStatus(422)->assertJsonValidationErrors('name');
        $this->createRole(['dashboard.home.view'], 'Admin')->assertStatus(422)->assertJsonValidationErrors('name');
        $this->assertSame(0, Permissions::where('name', 'Sales Manager Test')->count());
    }

    public function test_only_full_access_roles_can_manage_roles()
    {
        $manager = $this->userWithRole((int)Permissions::where('name', 'Manager')->value('id'));
        $this->actingAs($manager)->postJson(route('rolesStore'), ['name' => 'Sneaky', 'permissions' => []])->assertForbidden();
        $this->actingAs($manager)->getJson(route('rolesIndex'))->assertForbidden();
        $this->actingAs($manager)->get(route('usersEdit', $manager->id))->assertOk()
            ->assertDontSee('id="tab-roles-permissions"', false)->assertDontSee('id="tab-company-settings"', false)
            ->assertDontSee('id="tab-license-info"', false);
        $this->actingAs($this->admin)->get(route('usersEdit', $this->admin->id))->assertOk()->assertSee('id="tab-roles-permissions"', false);
    }

    public function test_a_role_without_inbound_calls_gets_403_on_the_page_and_its_export_and_no_sidebar_link()
    {
        $role = $this->createRole(['dashboard.home.view', 'reports.call_report.view'])->assertCreated()->json('role.id');
        $user = $this->userWithRole($role);

        $this->actingAs($user)->get(route('inboundCalls'))->assertForbidden();
        $this->actingAs($user)->getJson(route('inboundCallsExport', ['format' => 'csv']))->assertForbidden()
            ->assertJsonPath('permission', 'reports.inbound_calls.export');
        $this->actingAs($user)->getJson(route('leads'))->assertForbidden();
        $this->actingAs($user)->getJson(route('recordings'))->assertForbidden();
        $this->actingAs($user)->getJson(route('callReportExport', ['format' => 'csv']))->assertForbidden();

        $page = $this->actingAs($user)->get(route('callReport'))->assertOk();
        $page->assertSee('href="' . route('callReport') . '"', false);
        $page->assertDontSee('href="' . route('inboundCalls') . '"', false);
        $page->assertDontSee('href="' . route('leads') . '"', false);
        $page->assertSee('[data-lg-perm="reports.call_report.export"]', false);   // the export button is hidden
    }

    public function test_changing_a_role_applies_to_its_users_on_the_next_request()
    {
        $role = $this->createRole(['dashboard.home.view', 'reports.call_report.view', 'reports.call_report.export'])->json('role.id');
        $user = $this->userWithRole($role);
        $this->actingAs($user)->get(route('callReport'))->assertOk();

        $this->actingAs($this->admin)->putJson(route('rolesUpdate', $role), ['name' => 'Sales Manager Test', 'permissions' => ['dashboard.home.view']])
            ->assertOk()->assertJsonPath('role.permissions_count', 1);
        $this->actingAs($user)->get(route('callReport'))->assertForbidden();
        $this->assertSame('updated', DB::table('role_audit_logs')->where('role_id', $role)->orderByDesc('id')->value('action'));
        $before = json_decode(DB::table('role_audit_logs')->where('role_id', $role)->orderByDesc('id')->value('before'), true);
        $this->assertContains('reports.call_report.view', $before['keys']);
    }

    public function test_roles_with_users_and_system_roles_cannot_be_deleted_or_changed()
    {
        $role = $this->createRole(['dashboard.home.view'])->json('role.id');
        $this->userWithRole($role);
        $this->actingAs($this->admin)->deleteJson(route('rolesDestroy', $role))->assertStatus(422)->assertJsonFragment(['status' => 'error']);
        $this->assertTrue(Permissions::whereKey($role)->exists());

        $adminRole = $this->admin->permissions_id;
        $this->actingAs($this->admin)->deleteJson(route('rolesDestroy', $adminRole))->assertStatus(422);
        $this->actingAs($this->admin)->putJson(route('rolesUpdate', $adminRole), ['name' => 'Admin', 'permissions' => []])->assertStatus(422);

        DB::table('users')->where('permissions_id', $role)->delete();
        $this->actingAs($this->admin)->deleteJson(route('rolesDestroy', $role))->assertOk();
        $this->assertSame(0, DB::table('role_permissions')->where('role_id', $role)->count(), 'its keys are removed');
        $this->assertSame('deleted', DB::table('role_audit_logs')->where('role_id', $role)->orderByDesc('id')->value('action'));
    }

    public function test_duplicate_copies_the_keys_and_is_audited()
    {
        $tele = (int)Permissions::where('name', 'Telecaller')->value('id');
        $r = $this->actingAs($this->admin)->postJson(route('rolesStore'), ['name' => 'Telecaller Copy Test', 'duplicate_of' => $tele,
            'permissions' => RoleAccess::roleKeys(Permissions::find($tele))])->assertCreated()->json('role');
        $this->assertSame(RoleAccess::roleKeys(Permissions::find($tele)), RoleAccess::roleKeys(Permissions::find($r['id'])));
        $this->assertSame('duplicated', DB::table('role_audit_logs')->where('role_id', $r['id'])->value('action'));
    }

    public function test_nobody_removes_their_own_admin_access_or_gives_admin_without_being_admin()
    {
        $tele = (int)Permissions::where('name', 'Telecaller')->value('id');
        $this->actingAs($this->admin)->postJson(route('usersUpdateRole', $this->admin->id), ['permissions_id' => $tele])->assertStatus(422);
        $this->assertSame((int)$this->admin->permissions_id, (int)$this->admin->fresh()->permissions_id);

        $manager = $this->userWithRole((int)Permissions::where('name', 'Manager')->value('id'));
        $victim = $this->userWithRole($tele);
        $this->actingAs($manager)->postJson(route('usersUpdateRole', $victim->id), ['permissions_id' => $this->admin->permissions_id])->assertStatus(422);
        $this->assertSame($tele, (int)$victim->fresh()->permissions_id);
    }

    public function test_existing_roles_keep_their_old_access_after_the_migration()
    {
        foreach (Permissions::orderBy('id')->get() as $role) {
            if (RoleAccess::isFullAccessRole($role)) {
                $this->assertCount(count(RoleAccess::keys()), RoleAccess::roleKeys($role), "{$role->name} has everything");
                continue;
            }
            if (!$role->permissions_managed) {
                continue;
            }
            $this->assertEqualsCanonicalizing(RoleAccess::legacyKeys($role), RoleAccess::roleKeys($role), "{$role->name} keeps the access of before");
        }

        $tele = Permissions::where('name', 'Telecaller')->first();
        $keys = RoleAccess::roleKeys($tele);
        $this->assertContains('calling.dialing.view', $keys);
        $this->assertContains('recordings.call_recordings.play', $keys);
        $this->assertNotContains('leads.lead_list.export', $keys, 'lead export was Webmaster / Admin / Manager');
        $this->assertNotContains('recordings.call_recordings.transcribe', $keys);
        $this->assertNotContains('people.staff.view', $keys);
        $this->assertContains('recordings.call_recordings.transcribe', RoleAccess::roleKeys(Permissions::where('name', 'Manager')->first()));

        // only the Admin deletes recordings: never part of a role's keys, and the Admin rule still applies
        $this->assertTrue(RoleAccess::isAdminOnly('recordings.call_recordings.delete'));
        $user = $this->userWithRole($tele->id);
        $this->actingAs($user)->deleteJson(route('recordingDelete', [1, 2]))->assertForbidden();
    }

    public function test_a_role_that_was_never_saved_keeps_its_old_access()
    {
        $id = DB::table('permissions')->insertGetId(['name' => 'Legacy Test Role', 'view_status' => 1, 'add_status' => 1, 'edit_status' => 1,
            'delete_status' => 0, 'active_status' => 1, 'settings_status' => 0, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $role = Permissions::find($id);
        $this->assertFalse((bool)$role->permissions_managed);
        $this->assertEqualsCanonicalizing(RoleAccess::legacyKeys($role), RoleAccess::roleKeys($role));
        $this->actingAs($this->userWithRole($id))->get(route('callReport'))->assertOk();
    }
}
