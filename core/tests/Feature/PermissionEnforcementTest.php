<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A Telecaller (permissions: add/edit/delete/settings = 0) must not be able to create, change or delete
 * master data or configuration by calling the URLs directly. "Blocked" is proven by the database being
 * unchanged, not by the status code alone. Everything is rolled back.
 */
class PermissionEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    private User $tele;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $teleGroup = DB::table('permissions')->where('name', 'Telecaller')->first();
        $this->assertNotNull($teleGroup, 'Telecaller role missing');
        $this->assertSame(0, (int)$teleGroup->add_status, 'Telecaller role is expected to have add disabled');

        $this->tele = User::where('permissions_id', $teleGroup->id)->first()
            ?? User::forceCreate(['name' => 'Perm Test TC', 'email' => 'perm_tc_' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'permissions_id' => $teleGroup->id, 'status' => 1, 'team_id' => '1']);

        // inside the configured Telecaller login window (TelecallerMiddleware)
        $s = DB::table('settings')->first();
        $login = $s->caller_login ?? '10:30:00';
        Carbon::setTestNow(Carbon::today()->setTimeFromTimeString($login)->addMinutes(30));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** [module, table, store uri, update uri pattern, delete (method, uri pattern), fields for store/update] */
    public static function modules(): array
    {
        return [
            'sources'         => ['sources', 'admin/sources/store', 'admin/sources/{id}/update', null, ['name' => 'PermTest Source']],
            'substatuses'     => ['substatuses', 'admin/substatuses/store', 'admin/substatuses/{id}/update', null, ['name' => 'PermTest Sub', 'status_id' => 1]],
            'shifts'          => ['shifts', 'admin/shifts/store', 'admin/shifts/{id}/update', ['DELETE', 'admin/shifts/{id}/ajax-delete'], ['name' => 'PermTest Shift', 'start_time' => '09:00', 'end_time' => '18:00']],
            'sms gateways'    => ['sms_gateways', 'admin/sms/store', 'admin/sms/{id}/update', null, ['name' => 'PermTest SMS']],
            'email settings'  => ['email_settings', 'admin/email/store', 'admin/email/{id}/update', null, ['name' => 'PermTest Mail']],
            'client types'    => ['client_types', 'admin/clientTypes/store', 'admin/clientTypes/{id}/update', null, ['name' => 'PermTest Client']],
            'products'        => ['product_services', 'admin/productServices/store', 'admin/productServices/{id}/update', null, ['name' => 'PermTest Product']],
            'work locations'  => ['work_locations', 'admin/workLocations/store', 'admin/workLocations/{id}/update', null, ['name' => 'PermTest Loc', 'lat' => '17.4', 'long' => '78.4', 'radius' => 100]],
            'statuses'        => ['statuses', 'admin/statuses/store', 'admin/statuses/{id}/update', null, ['name' => 'PermTest Status']],
            'segments'        => ['segments', 'admin/segments/store', 'admin/segments/{id}/update', ['GET', 'admin/segments/{id}/delete'], ['name' => 'PermTest Segment']],
        ];
    }

    /** Roles that DO have add/edit permission must still get through (not the No-Permission page / 403). */
    #[\PHPUnit\Framework\Attributes\DataProvider('modules')]
    public function test_admin_is_not_blocked($table, $storeUri, $updateUri, $delete, $fields)
    {
        $admin = User::whereIn('permissions_id', DB::table('permissions')->where('name', 'Admin')->pluck('id'))->first();
        $this->assertNotNull($admin);
        $noPermission = route('NoPermission');

        $res = $this->actingAs($admin)->get('/' . str_replace('/store', '/create', $storeUri));
        if ($res->status() !== 404) {
            $this->assertNotEquals(403, $res->status(), "Admin blocked from {$storeUri}");
            $this->assertNotEquals($noPermission, $res->headers->get('Location'), "Admin sent to No-Permission from create page of {$table}");
        }
        $id = DB::table($table)->value('id');
        $res = $this->actingAs($admin)->post('/' . str_replace('{id}', $id, $updateUri), $fields);
        $this->assertNotEquals(403, $res->status(), "Admin got 403 on update of {$table}");
        $this->assertNotEquals($noPermission, $res->headers->get('Location'), "Admin sent to No-Permission on update of {$table}");
    }

    private function uriExists(string $method, string $uri): bool
    {
        $req = \Illuminate\Http\Request::create('/' . str_replace('{id}', '1', $uri), $method);
        try { app('router')->getRoutes()->match($req); return true; } catch (\Throwable $e) { return false; }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('modules')]
    public function test_telecaller_cannot_create_update_or_delete($table, $storeUri, $updateUri, $delete, $fields)
    {
        $nameCol = 'name';
        $before = DB::table($table)->count();

        // 1. create
        if ($this->uriExists('POST', $storeUri)) {
            $this->actingAs($this->tele)->post('/' . $storeUri, $fields);
            $this->assertSame($before, DB::table($table)->count(), "Telecaller could CREATE a row in {$table} via {$storeUri}");
        }

        // a row to try to change / delete (inserted directly, rolled back afterwards)
        $existing = (array)DB::table($table)->first();
        $this->assertNotEmpty($existing, "{$table} has no row to test with");
        $row = $existing; unset($row['id']);
        foreach ($row as $k => $v) if ($k === $nameCol) $row[$k] = 'PermTest Victim ' . uniqid();
        $id = DB::table($table)->insertGetId($row);
        $victimName = DB::table($table)->where('id', $id)->value($nameCol);

        // 2. update
        $uUri = str_replace('{id}', $id, $updateUri);
        if ($this->uriExists('POST', $uUri)) {
            $this->actingAs($this->tele)->post('/' . $uUri, array_merge($fields, [$nameCol => 'HACKED BY TELECALLER']));
            $this->assertSame($victimName, DB::table($table)->where('id', $id)->value($nameCol), "Telecaller could UPDATE {$table} #{$id} via {$uUri}");
        }

        // 3. delete (only modules that have a delete route)
        if (!$delete) return;
        [$dm, $dUri] = $delete;
        $dUri = str_replace('{id}', $id, $dUri);
        if ($this->uriExists($dm, $dUri)) {
            $this->actingAs($this->tele)->call($dm, '/' . $dUri);
            $this->assertTrue(DB::table($table)->where('id', $id)->exists(), "Telecaller could DELETE {$table} #{$id} via {$dm} {$dUri}");
        }
    }
}
