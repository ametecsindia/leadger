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
        $webmasters = DB::table('users')->where('status', 1)->where('permissions_id', DB::table('permissions')->where('name', 'Webmaster')->value('id'))->count();
        $this->assertSame(1 + $webmasters, (int)$count('users'), "the new admin and the main site's Webmaster logins only");
        $this->assertSame(0, (int)$count('leads'));
        $this->assertSame(0, (int)$count('obd_did'), 'no VMNs of the main site');
        $this->assertSame((int)DB::table('permissions')->count(), (int)$count('permissions'), 'roles copied');
        $this->assertSame((int)DB::table('migrations')->count(), (int)$count('migrations'), 'later migrations run normally');
        $this->assertSame(self::DB, TenantSetup::query()->where('subdomain', self::HOST)->value('db_name'));

        $this->artisan('tenants:create', ['host' => 'other.leadger.test', 'database' => self::DB, '--admin-email' => 'x@example.test'])
            ->assertFailed();   // a database that already has tables is never touched
    }

    /** A site added to the list by hand (SQLyog) gets its database within the next sync-sites run. */
    public function test_sync_sites_prepares_a_site_added_by_hand_and_never_changes_existing_rows()
    {
        $mysql = config('database.connections.mysql');
        TenantSetup::create(['subdomain' => self::HOST, 'db_host' => $mysql['host'], 'db_name' => self::DB,
            'db_user' => $mysql['username'], 'db_password' => $mysql['password']]);
        $p = config('database.connections.mysql.prefix');
        $q = fn (string $t) => '`' . self::DB . '`.`' . $p . $t . '`';
        $count = fn (string $t) => (int)DB::connection('mysql')->selectOne('SELECT COUNT(*) AS n FROM ' . $q($t))->n;

        $this->artisan('tenants:sync-sites')->assertSuccessful();
        $this->assertSame((int)DB::table('settings')->count(), $count('settings'), 'starting data filled');
        $this->assertSame(0, $count('leads'));
        $webmasters = DB::table('users')->where('status', 1)->where('permissions_id', DB::table('permissions')->where('name', 'Webmaster')->value('id'))->count();
        $this->assertSame($webmasters, $count('users'), "the main site's Webmaster logins");

        // later runs: a missing table is created again, an emptied starting table is refilled, rows that exist stay as they are
        DB::connection('mysql')->statement('DROP TABLE ' . $q('leads'));
        DB::connection('mysql')->statement('DELETE FROM ' . $q('statuses'));
        DB::connection('mysql')->table(DB::raw($q('users')))->update(['name' => 'Changed on the site']);
        $this->artisan('tenants:sync-sites')->assertSuccessful();
        $this->assertSame(0, $count('leads'), 'table created again');
        $this->assertSame((int)DB::table('statuses')->count(), $count('statuses'));
        $this->assertSame($webmasters, DB::connection('mysql')->table(DB::raw($q('users')))->where('name', 'Changed on the site')->count(), 'users untouched');
    }

    public function test_sync_sites_skips_a_site_pointing_at_the_main_database()
    {
        $mysql = config('database.connections.mysql');
        TenantSetup::create(['subdomain' => self::HOST, 'db_host' => $mysql['host'], 'db_name' => $mysql['database'],
            'db_user' => $mysql['username'], 'db_password' => $mysql['password']]);
        $before = DB::table('settings')->count();
        $this->artisan('tenants:sync-sites')->expectsOutputToContain('skipped, it points to the main database')->assertSuccessful();
        $this->assertSame($before, DB::table('settings')->count());
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
