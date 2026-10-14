<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TenantSetup;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use PDO;

class MigrateTenants extends Command
{
    protected $signature = 'tenants:migrate';
    protected $description = 'Run migrations for all tenant databases';

    public function handle()
    {
        $tenants = TenantSetup::all();

        foreach ($tenants as $tenant) {
            $this->info("🔄 Migrating: {$tenant->subdomain}");

            Config::set("database.connections.tenant", [
                'driver' => 'mysql',
                'host' => $tenant->db_host,
                'port' => env('DB_PORT', 3306),
                'database' => $tenant->db_name,
                'username' => $tenant->db_user,
                'password' => $tenant->db_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => env('DB_TABLE_PREFIX', ''),
                'strict' => false,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ]);

            DB::purge('tenant');
            DB::reconnect('tenant');
            DB::setDefaultConnection('tenant');

            try {
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--force' => true,
                ]);
                $this->info("✅ Migrated: {$tenant->db_name}");
            } catch (\Exception $e) {
                $this->error("❌ Migration failed for {$tenant->db_name}: " . $e->getMessage());
            }
        }
    }
}

