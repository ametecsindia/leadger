<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\TenantSetup;
use App\Models\User;
use App\Services\LicenseService;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * License Info > Delete License: the admin is signed out right after a successful delete, every other session of
 * the company is signed out on its next request, a failed delete keeps the admin signed in, and another company's
 * license and sessions are untouched.
 */
class LicenseDeleteLogoutTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private ?string $envBackup = null;
    private ?string $otherDb = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);   // calling hours
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        // signing out also signs out of SmartPing SSO (a real call): not from a test (rolled back)
        DB::table('users')->update(['smartping_user_id' => null]);
        $this->admin->refresh();
        $this->assertTrue(LicenseService::hasLicense(), 'the local company needs a license for this test');
        // on the main installation the delete also blanks license= in .env: put the file back afterwards
        $this->envBackup = file_get_contents(base_path('.env'));
    }

    protected function tearDown(): void
    {
        if ($this->envBackup !== null) {
            file_put_contents(base_path('.env'), $this->envBackup);
        }
        if ($this->otherDb) {
            Tenancy::useMainDatabase((string)env('DB_DATABASE'));
            $this->ddl()->statement('DROP DATABASE IF EXISTS `' . $this->otherDb . '`');
        }
        parent::tearDown();
    }

    private function deleteLicense(array $data = ['confirm' => 'DELETE'])
    {
        return $this->actingAs($this->admin)->postJson(route('usersDeleteLicense'), $data);
    }

    public function test_a_successful_delete_signs_the_admin_out_and_blocks_the_dashboard()
    {
        $this->deleteLicense()->assertOk()->assertJson(['status' => 'success', 'logged_out' => true, 'redirect' => url('/login')]);
        $this->assertGuest();
        $this->assertFalse(LicenseService::hasLicense());
        $this->assertStringContainsString('license=""', file_get_contents(base_path('.env')), 'existing delete behaviour kept');

        // refresh, Back or a typed dashboard URL: not signed in any more
        $this->get(route('adminHome'))->assertRedirect();
        $this->assertGuest();
        $this->getJson(route('leads'))->assertUnauthorized();
    }

    public function test_a_failed_delete_keeps_the_admin_signed_in()
    {
        $this->deleteLicense([])->assertStatus(422);   // not confirmed
        $this->assertAuthenticatedAs($this->admin);
        $this->assertTrue(LicenseService::hasLicense());

        $tele = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();
        $this->actingAs($tele)->postJson(route('usersDeleteLicense'), ['confirm' => 'DELETE'])->assertForbidden();
        $this->assertAuthenticatedAs($tele);
        $this->assertTrue(LicenseService::hasLicense());
        $this->get(route('adminHome'))->assertOk();
    }

    public function test_other_open_sessions_of_the_company_are_signed_out_on_their_next_request()
    {
        DB::table('settings')->update(['full_response' => null]);   // deleted from another browser
        $tele = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Telecaller'))->firstOrFail();

        $this->actingAs($tele)->getJson(route('callReport'))->assertUnauthorized()->assertJson(['logged_out' => true, 'redirect' => url('/login')]);
        $this->assertGuest();
        $this->actingAs($tele)->get(route('adminHome'))->assertRedirect(url('/login'));
        $this->assertGuest();
    }

    public function test_access_works_again_once_a_license_is_stored()
    {
        $license = DB::table('settings')->value('full_response');
        DB::table('settings')->update(['full_response' => null]);
        $this->actingAs($this->admin)->get(route('adminHome'))->assertRedirect(url('/login'));

        DB::table('settings')->update(['full_response' => $license]);   // activated again
        $this->actingAs($this->admin)->get(route('adminHome'))->assertOk();
    }

    public function test_another_companys_license_is_untouched()
    {
        // a second company with its own database and license
        $this->otherDb = 'lg_lic_test_' . substr(md5(uniqid('', true)), 0, 8);
        $tp = env('DB_TABLE_PREFIX', '');
        $this->ddl()->statement('CREATE DATABASE `' . $this->otherDb . '`');
        $this->ddl()->statement("CREATE TABLE `{$this->otherDb}`.`{$tp}settings` (id INT PRIMARY KEY, full_response LONGBLOB NULL)");
        $this->ddl()->statement("INSERT INTO `{$this->otherDb}`.`{$tp}settings` VALUES (1, 'other-company-license')");

        $this->deleteLicense()->assertOk()->assertJson(['logged_out' => true]);
        $this->assertFalse(LicenseService::hasLicense(), 'this company has no license');

        $other = new TenantSetup(['subdomain' => 'other.test', 'db_host' => env('DB_HOST'), 'db_name' => $this->otherDb,
            'db_user' => env('DB_USERNAME'), 'db_password' => env('DB_PASSWORD')]);
        Tenancy::useTenant($other);
        $this->assertTrue(LicenseService::hasLicense(), 'the other company keeps its license');
        $this->assertSame('other-company-license', DB::table('settings')->value('full_response'));
        Tenancy::useMainDatabase((string)env('DB_DATABASE'));
    }

    /** A connection of its own for CREATE / DROP DATABASE (they would end the test's transaction). */
    private function ddl()
    {
        if (!config('database.connections.lg_lic_ddl')) {
            config(['database.connections.lg_lic_ddl' => array_merge(config('database.connections.mysql'), ['prefix' => ''])]);
        }
        return DB::connection('lg_lic_ddl');
    }
}
