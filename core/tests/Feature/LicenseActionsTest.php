<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\UsersController;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * License Info tab: "Sync Did" and "Delete License". The real .env is never touched (a temporary copy is used) and
 * database changes are rolled back.
 */
class LicenseActionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $telecaller;
    private array $tmp = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->telecaller = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp as $f) @unlink($f);
        parent::tearDown();
    }

    private function as(User $u): void
    {
        Auth::login($u);
        $u->unsetRelation('permissionsGroup');
    }

    private function controllerCall(string $method, array $data = [])
    {
        $req = Request::create('/x', 'POST', $data);
        app()->instance('request', $req);
        return app(UsersController::class)->$method($req);
    }

    public function test_only_admins_can_sync_delete_or_read_the_license()
    {
        $this->as($this->telecaller);
        foreach (['syncDid', 'deleteLicense', 'getLicenseInfo'] as $m) {
            $res = $this->controllerCall($m, ['confirm' => 'DELETE']);
            $this->assertSame(403, $res->getStatusCode(), "$m must be refused for a telecaller");
        }
        $this->assertNotNull(DB::table('settings')->value('full_response'), 'the license was not deleted');
    }

    public function test_delete_license_needs_the_confirmation()
    {
        $this->as($this->admin);
        $res = $this->controllerCall('deleteLicense');
        $this->assertSame(422, $res->getStatusCode());
        $this->assertNotNull(DB::table('settings')->value('full_response'), 'nothing deleted without confirmation');
    }

    public function test_only_the_license_line_of_the_env_file_is_cleared()
    {
        $env = tempnam(sys_get_temp_dir(), 'env');
        $this->tmp[] = $env;
        file_put_contents($env, "APP_NAME=LeadGER\r\nlicense=\"abc123\"\r\nOTHER_license=keep-me\r\n# license=commented\r\nDB_DATABASE=lt_leadger\r\n");
        $this->assertTrue(LicenseService::clearEnvLicense($env));
        $this->assertSame("APP_NAME=LeadGER\r\nlicense=\"\"\r\nOTHER_license=keep-me\r\n# license=commented\r\nDB_DATABASE=lt_leadger\r\n", file_get_contents($env));
    }

    public function test_delete_license_clears_the_stored_license_and_the_installation_key()
    {
        $this->as($this->admin);
        $env = tempnam(sys_get_temp_dir(), 'env');
        $this->tmp[] = $env;
        file_put_contents($env, "APP_NAME=LeadGER\nlicense=abc123\n");

        $result = LicenseService::deleteLicense($env);   // rolled back after the test
        $this->assertTrue($result['ok']);
        $this->assertNull(DB::table('settings')->value('full_response'));
        $this->assertNull(LicenseService::licenseData());
        $this->assertTrue($result['env_cleared'], 'main installation: its license key is cleared');
        $this->assertSame("APP_NAME=LeadGER\nlicense=\"\"\n", file_get_contents($env));
    }

    public function test_sync_without_license_data_says_so()
    {
        $this->as($this->admin);
        DB::table('settings')->update(['full_response' => null]);   // rolled back
        $res = $this->controllerCall('syncDid');
        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('Activate the license first', $res->getData(true)['message']);
    }
}
