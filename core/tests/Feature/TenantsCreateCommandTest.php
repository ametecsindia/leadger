<?php

namespace Tests\Feature;

use App\Models\TenantSetup;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** tenants:create - a new site gets its own database: structure + starting data only, one admin, listed in tenants. */
class TenantsCreateCommandTest extends TestCase
{
    private const DB = 'lt_tenants_create_test';
    private const HOST = 'create-test.leadger.test';

    protected function tearDown(): void
    {
        TenantSetup::query()->where('subdomain', self::HOST)->delete();
        DB::connection('mysql')->statement('DROP DATABASE IF EXISTS `' . self::DB . '`');
        parent::tearDown();
    }

    public function test_a_new_site_gets_its_own_empty_database_with_starting_data_and_one_admin()
    {
        $this->artisan('tenants:create', ['host' => self::HOST, 'database' => self::DB, '--admin-email' => 'site-admin@example.test',
            '--admin-password' => 'Site-Pass-2026'])->assertSuccessful();

        $count = fn (string $table) => DB::connection('mysql')->selectOne('SELECT COUNT(*) AS n FROM `' . self::DB . '`.`'
            . config('database.connections.mysql.prefix') . $table . '`')->n;
        $this->assertSame(1, (int)$count('users'), 'only the new admin');
        $this->assertSame(0, (int)$count('leads'));
        $this->assertSame(0, (int)$count('obd_did'), 'no VMNs of the main site');
        $this->assertSame((int)DB::table('permissions')->count(), (int)$count('permissions'), 'roles copied');
        $this->assertSame((int)DB::table('migrations')->count(), (int)$count('migrations'), 'later migrations run normally');
        $this->assertSame(self::DB, TenantSetup::query()->where('subdomain', self::HOST)->value('db_name'));

        $this->artisan('tenants:create', ['host' => 'other.leadger.test', 'database' => self::DB, '--admin-email' => 'x@example.test'])
            ->assertFailed();   // a database that already has tables is never touched
    }

    public function test_refuses_the_main_database_and_bad_input()
    {
        $main = (string)config('database.connections.mysql.database');
        $this->artisan('tenants:create', ['host' => self::HOST, 'database' => $main, '--admin-email' => 'a@example.test'])->assertFailed();
        $this->artisan('tenants:create', ['host' => 'not a host', 'database' => self::DB, '--admin-email' => 'a@example.test'])->assertFailed();
        $this->artisan('tenants:create', ['host' => self::HOST, 'database' => 'bad-name;', '--admin-email' => 'a@example.test'])->assertFailed();
        $this->artisan('tenants:create', ['host' => self::HOST, 'database' => self::DB])->assertFailed();   // no admin email
        $this->assertFalse((bool)DB::connection('mysql')->selectOne('SELECT 1 AS x FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [self::DB]));
    }
}
