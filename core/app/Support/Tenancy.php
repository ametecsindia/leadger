<?php

namespace App\Support;

use App\Models\TenantSetup;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Which company database is in use, and switching to it. Each company (license) has its own database, chosen per
 * request by the Tenants middleware. Queued jobs carry context() (a tenant host or a database name, never
 * credentials) and call apply() in the worker, so a job reads and writes its own company's data and settings.
 */
class Tenancy
{
    /** The current company: ['tenant' => host] for a tenants-table company, else ['database' => name]. */
    public static function context(): array
    {
        if (DB::getDefaultConnection() === 'tenant' && app()->bound('lg.tenant_host')) {
            return ['tenant' => (string)app('lg.tenant_host')];
        }
        return ['database' => (string)config('database.connections.' . DB::getDefaultConnection() . '.database')];
    }

    /** A short, stable label of the current company (for cache keys / locks; no secrets). */
    public static function key(): string
    {
        $c = self::context();
        return isset($c['tenant']) ? 't:' . $c['tenant'] : 'd:' . ($c['database'] ?? '');
    }

    /** Switch to the company of a context() captured earlier (e.g. when the job was queued). */
    public static function apply(?array $context): void
    {
        if (!$context) {
            return;
        }
        if (!empty($context['tenant'])) {
            if (self::context() === ['tenant' => $context['tenant']]) {
                return;
            }
            $tenant = TenantSetup::on('mysql')->where('subdomain', $context['tenant'])->first();
            if (!$tenant) {
                throw new \RuntimeException('The company of this job no longer exists.');
            }
            self::useTenant($tenant);
            return;
        }
        if (!empty($context['database'])) {
            self::useMainDatabase($context['database']);
        }
    }

    /** Point the "tenant" connection at this company's database and make it the default. */
    public static function useTenant(TenantSetup $tenant): void
    {
        config()->set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => $tenant->db_host,
            'port' => env('DB_PORT', 3306),
            'database' => $tenant->db_name,
            'username' => $tenant->db_user,
            'password' => $tenant->db_password,
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => env('DB_TABLE_PREFIX', ''),
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]) : [],
        ]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
        app()->instance('lg.tenant_host', $tenant->subdomain);
    }

    /**
     * Point the default "mysql" connection at $dbname. Reconnect ONLY when it is a different database:
     * dropping a healthy connection to the same database discards any open transaction on it.
     */
    public static function useMainDatabase(string $dbname): void
    {
        $alreadyThere = DB::getDefaultConnection() === 'mysql'
            && config('database.connections.mysql.database') === $dbname;
        if ($alreadyThere) {
            return;
        }
        Config::set('database.connections.mysql.database', $dbname);
        DB::purge('mysql');
        DB::reconnect('mysql');
        DB::setDefaultConnection('mysql');
    }
}
