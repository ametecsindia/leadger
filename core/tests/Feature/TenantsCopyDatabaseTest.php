<?php

namespace Tests\Feature;

use App\Services\TenantsCopy;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** TENANCY_COPY_DATABASE: the companies list copy goes to that database, and this installation's own table is left alone. */
class TenantsCopyDatabaseTest extends TestCase
{
    private const MASTER = 'lt_copytest_master';
    private const COPY = 'lt_copytest_copy';

    protected function tearDown(): void
    {
        foreach ([self::MASTER, self::COPY] as $db) {
            DB::connection('mysql')->statement("DROP DATABASE IF EXISTS `$db`");
        }
        parent::tearDown();
    }

    public function test_the_copy_goes_to_the_copy_database_only()
    {
        $prefix = (string)config('database.connections.mysql.prefix');
        $create = fn ($db) => DB::connection('mysql')->statement("CREATE TABLE `$db`.`{$prefix}tenants` (id BIGINT UNSIGNED PRIMARY KEY,
            subdomain VARCHAR(191), db_host VARCHAR(191), db_name VARCHAR(191), db_user VARCHAR(191), db_password TEXT NULL,
            created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)");
        foreach ([self::MASTER, self::COPY] as $db) {
            DB::connection('mysql')->statement("CREATE DATABASE `$db`");
            $create($db);
        }
        DB::connection('mysql')->table(DB::raw('`' . self::MASTER . "`.`{$prefix}tenants`"))->insert([
            ['id' => 4, 'subdomain' => 'a.leadger.in', 'db_host' => 'h', 'db_name' => 'a_db', 'db_user' => 'u', 'db_password' => 'p'],
            ['id' => 5, 'subdomain' => 'b.leadger.in', 'db_host' => 'h', 'db_name' => 'b_db', 'db_user' => 'u', 'db_password' => 'p'],
        ]);
        DB::connection('mysql')->table(DB::raw('`' . self::COPY . "`.`{$prefix}tenants`"))
            ->insert(['id' => 2, 'subdomain' => 'localhost', 'db_host' => '127.0.0.1', 'db_name' => 'old', 'db_user' => 'root']);
        $ownBefore = DB::connection('mysql')->table('tenants')->orderBy('id')->get()->toArray();

        config(['tenancy.database' => self::MASTER, 'tenancy.copy_database' => self::COPY,
            'database.connections.tenancy.database' => self::MASTER]);
        DB::purge('tenancy');

        $this->assertSame(['copied' => 2, 'removed' => 1], TenantsCopy::sync());
        $this->assertSame(['a.leadger.in', 'b.leadger.in'],
            DB::connection('mysql')->table(DB::raw('`' . self::COPY . "`.`{$prefix}tenants`"))->orderBy('id')->pluck('subdomain')->all());
        $this->assertEquals($ownBefore, DB::connection('mysql')->table('tenants')->orderBy('id')->get()->toArray(), 'own table untouched');
    }
}
