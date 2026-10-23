<?php

namespace Tests\Feature;

use App\Models\TenantSetup;
use App\Services\CallRecordingService;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * One code base, a database per company: a real second company database (lt_leadger_tenant_test, created over a
 * separate connection so the test transaction is not committed by DDL) checks that data, cache, queued jobs,
 * sessions, folders and hosts never cross between companies.
 */
class TenancyIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private const TEST_DB = 'lt_leadger_tenant_test';
    private const HOST = 'company-t.leadger.test';

    private static bool $schemaReady = false;
    private TenantSetup $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1');
        $this->prepareCompanyDatabase();

        $c = config('database.connections.mysql');
        $this->tenant = TenantSetup::create(['name' => 'Company T', 'slug' => 'company-t', 'subdomain' => self::HOST,
            'db_host' => $c['host'], 'db_name' => self::TEST_DB, 'db_user' => $c['username'], 'db_password' => $c['password'], 'status' => 'active']);
        Cache::store('tenancy')->flush();
    }

    protected function tearDown(): void
    {
        Tenancy::apply(Tenancy::central());
        Cache::store('tenancy')->flush();
        parent::tearDown();
    }

    /** Company database with the same leads / recordings tables as the central one (schema only). */
    private function prepareCompanyDatabase(): void
    {
        $c = config('database.connections.mysql');
        $pdo = new \PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password']);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . self::TEST_DB . '`');
        if (!self::$schemaReady) {
            // every table of the central database, structure only (a new, empty company)
            $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = " . $pdo->quote($c['database'])
                . " AND table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `" . self::TEST_DB . "`.`{$table}`");
                $pdo->exec("CREATE TABLE `" . self::TEST_DB . "`.`{$table}` LIKE `{$c['database']}`.`{$table}`");
            }
            self::$schemaReady = true;
        }
        $pdo->exec("DELETE FROM `" . self::TEST_DB . "`.`" . ($c['prefix'] ?? '') . "leads`");
    }

    public function test_data_written_for_one_company_is_never_seen_by_another()
    {
        $centralLeads = DB::table('leads')->count();

        Tenancy::useTenant($this->tenant);
        $this->assertSame(self::TEST_DB, DB::connection()->getDatabaseName());
        DB::table('leads')->insert(['name' => 'Only in Company T', 'phonenumber' => '9000000001', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(1, DB::table('leads')->count());

        Tenancy::apply(Tenancy::central());
        $this->assertSame(config('database.connections.mysql.database'), DB::connection()->getDatabaseName());
        $this->assertSame($centralLeads, DB::table('leads')->count());
        $this->assertSame(0, DB::table('leads')->where('name', 'Only in Company T')->count());
    }

    public function test_the_cache_is_separate_per_company()
    {
        Cache::put('calling_restrictions_settings', 'central-hours', 60);
        Tenancy::useTenant($this->tenant);
        $this->assertNull(Cache::get('calling_restrictions_settings'), 'company T does not see the central setting');
        Cache::put('calling_restrictions_settings', 'company-t-hours', 60);

        Tenancy::apply(Tenancy::central());
        $this->assertSame('central-hours', Cache::get('calling_restrictions_settings'));
        Tenancy::useTenant($this->tenant);
        $this->assertSame('company-t-hours', Cache::get('calling_restrictions_settings'));
        Cache::forget('calling_restrictions_settings');
        Tenancy::apply(Tenancy::central());
        Cache::forget('calling_restrictions_settings');
    }

    public function test_a_queued_job_runs_for_the_company_that_queued_it_and_the_worker_returns_after()
    {
        $payload = ['lg_company' => ['tenant' => self::HOST]];

        $job = \Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->shouldReceive('payload')->andReturn($payload);
        Event::dispatch(new JobProcessing('database', $job));
        $this->assertSame(self::TEST_DB, DB::connection()->getDatabaseName(), 'the job runs in company T');
        Event::dispatch(new JobProcessed('database', $job));
        $this->assertSame(config('database.connections.mysql.database'), DB::connection()->getDatabaseName(), 'the worker is back');
    }

    public function test_jobs_queued_by_a_company_go_to_the_central_queue_with_the_company_in_the_payload()
    {
        Tenancy::useTenant($this->tenant);
        $id = \Illuminate\Support\Facades\Queue::connection('database')->push(new \App\Jobs\DownloadRecordingJob(987654), '', 'iso-test');
        Tenancy::apply(Tenancy::central());

        $row = DB::connection('mysql')->table('jobs')->where('id', $id)->first();
        $this->assertNotNull($row, 'the job is in the central jobs table, where the workers look');
        $this->assertSame(['tenant' => self::HOST], json_decode($row->payload, true)['lg_company']);
    }

    public function test_hosts_resolve_to_their_company_and_a_suspended_or_unknown_company_is_refused()
    {
        $this->app['env'] = 'production';
        config(['tenancy.central_hosts' => ['central.leadger.test']]);

        $this->get('http://' . self::HOST . '/admin/login')->assertOk();
        $this->assertSame(self::TEST_DB, DB::connection()->getDatabaseName());
        Tenancy::apply(Tenancy::central());

        $this->get('http://central.leadger.test/admin/login')->assertOk();
        $this->assertSame(config('database.connections.mysql.database'), DB::connection()->getDatabaseName());

        $this->get('http://nobody.leadger.test/admin/login')->assertNotFound();

        $this->tenant->update(['status' => TenantSetup::STATUS_SUSPENDED]);
        $this->get('http://' . self::HOST . '/admin/login')->assertForbidden();
    }

    public function test_a_session_of_one_company_is_not_accepted_by_another()
    {
        $this->app['env'] = 'production';
        config(['tenancy.central_hosts' => ['central.leadger.test']]);
        $admin = \App\Models\User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();

        $login = 'login_web_' . sha1(\Illuminate\Auth\SessionGuard::class);

        // a session logged in at company T, presented at the central host (copied cookie): refused
        $this->withSession(['lg_company' => 't:' . self::HOST, $login => $admin->id])
            ->get('http://central.leadger.test/admin/reports/inbound-calls')
            ->assertRedirect();   // the session was discarded: back to the login page
        $this->assertGuest();

        // the same login in a session of the central company itself: accepted
        $this->withSession(['lg_company' => Tenancy::key(), $login => $admin->id])
            ->get('http://central.leadger.test/admin/reports/inbound-calls')
            ->assertOk();
    }

    public function test_each_company_has_its_own_recordings_folder_and_cannot_delete_another_companys_files()
    {
        $central = storage_path('app/recordings');
        $this->assertSame($central, Tenancy::path($central));

        Tenancy::useTenant($this->tenant);
        $own = Tenancy::path($central);
        $this->assertSame($central . DIRECTORY_SEPARATOR . 'tenants' . DIRECTORY_SEPARATOR . 'company-t', $own);
        @mkdir($own, 0755, true);
        file_put_contents($ownFile = $own . DIRECTORY_SEPARATOR . 'iso-test.wav', 'x');
        file_put_contents($centralFile = $central . DIRECTORY_SEPARATOR . 'iso-test-central.wav', 'x');
        $this->assertTrue(CallRecordingService::isInsideRecordingsFolder($ownFile));
        $this->assertFalse(CallRecordingService::isInsideRecordingsFolder($centralFile), 'company T cannot touch central files');

        Tenancy::apply(Tenancy::central());
        $this->assertTrue(CallRecordingService::isInsideRecordingsFolder($centralFile));
        $this->assertFalse(CallRecordingService::isInsideRecordingsFolder($ownFile), 'the central company cannot touch company T files');
        @unlink($ownFile);
        @unlink($centralFile);
    }

    public function test_tenants_each_runs_a_task_for_every_active_company_and_returns_to_central()
    {
        TenantSetup::create(['name' => 'Suspended Co', 'subdomain' => 'off.leadger.test', 'db_host' => '127.0.0.1', 'db_name' => 'none',
            'db_user' => 'none', 'db_password' => 'x', 'status' => TenantSetup::STATUS_SUSPENDED]);
        $seen = [];
        Tenancy::forEachCompany(function ($company, $label) use (&$seen) {
            $seen[$label] = DB::connection()->getDatabaseName();
        });
        $this->assertSame(self::TEST_DB, $seen[self::HOST] ?? null);
        $this->assertArrayNotHasKey('off.leadger.test', $seen, 'suspended companies are skipped');
        $this->assertSame(config('database.connections.mysql.database'), DB::connection()->getDatabaseName());
    }

    public function test_company_database_passwords_are_stored_encrypted()
    {
        config(['tenancy.encrypt_passwords' => true]);
        $this->tenant->update(['db_password' => config('database.connections.mysql.password')]);
        $raw = DB::table('tenants')->where('id', $this->tenant->id)->value('db_password');
        $this->assertNotSame(config('database.connections.mysql.password'), $raw);
        $this->assertSame(config('database.connections.mysql.password'), $this->tenant->fresh()->db_password);
        DB::table('tenants')->where('id', $this->tenant->id)->update(['db_password' => 'plain-old']);
        $this->assertSame('plain-old', $this->tenant->fresh()->db_password, 'rows saved before encryption still work');
        $this->assertArrayNotHasKey('db_password', $this->tenant->fresh()->toArray(), 'never serialised');
    }
}
