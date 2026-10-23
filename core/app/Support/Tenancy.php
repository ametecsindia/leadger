<?php

namespace App\Support;

use App\Models\TenantSetup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

/**
 * Which company database is in use, and switching to it. Each company (license) has its own database, chosen per
 * request by the Tenants middleware (by host). Queued jobs carry context() (a tenant host or a database name, never
 * credentials) in their payload and the worker applies it (AppServiceProvider), so a job reads and writes its own
 * company's data and settings. Switching also moves the default cache to the company's own folder / prefix, and
 * storageKey() gives the company's own file folder.
 *
 * The central database (the "mysql" connection, DB_DATABASE) holds the companies list and is the data of the
 * central company itself.
 */
class Tenancy
{
    private static ?array $baseCache = null;

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

    /** The central company's context (the "mysql" connection's own database from .env). */
    public static function central(): array
    {
        return ['database' => (string)env('DB_DATABASE', config('database.connections.mysql.database'))];
    }

    public static function isCentral(): bool
    {
        return !isset(self::context()['tenant']);
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
        app()->instance('lg.tenant_key', $tenant->storageKey());
        self::isolateCache($tenant->storageKey());
    }

    /**
     * Point the default "mysql" connection at $dbname. Reconnect ONLY when it is a different database:
     * dropping a healthy connection to the same database discards any open transaction on it.
     */
    public static function useMainDatabase(string $dbname): void
    {
        app()->forgetInstance('lg.tenant_host');
        app()->forgetInstance('lg.tenant_key');
        self::isolateCache(null);
        if (config('database.connections.mysql.database') === $dbname) {
            DB::setDefaultConnection('mysql');   // back from a company: the central connection is still healthy
            return;
        }
        Config::set('database.connections.mysql.database', $dbname);
        DB::purge('mysql');
        DB::reconnect('mysql');
        DB::setDefaultConnection('mysql');
    }

    /* ---------------------------------------------------------------- companies */

    /** The company of a host (tenants table, central database), cached briefly; null = not a listed company. */
    public static function findTenant(string $host): ?TenantSetup
    {
        $host = strtolower(trim($host));
        if ($host === '' || !self::hasTenantsTable()) {
            return null;
        }
        $row = Cache::store('tenancy')->remember(self::lookupCacheKey($host), max(1, (int)config('tenancy.lookup_cache_seconds', 300)),
            fn () => TenantSetup::on('mysql')->where('subdomain', $host)->first()?->getAttributes() ?: false);
        return $row ? (new TenantSetup())->newFromBuilder($row, 'mysql') : null;
    }

    public static function lookupCacheKey(string $host): string
    {
        return 'tenant-host:' . sha1(strtolower(trim($host)));
    }

    /** Hosts of the central company (TENANCY_CENTRAL_HOSTS, default the APP_URL host). */
    public static function isCentralHost(string $host): bool
    {
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
        return in_array($host, array_map('strtolower', (array)config('tenancy.central_hosts', [])), true);
    }

    public static function hasTenantsTable(): bool
    {
        static $has = null;
        try {
            return $has ??= Schema::connection('mysql')->hasTable('tenants');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Every company as a context: the central company first, then each active company of the tenants table.
     * Used by scheduled tasks and commands that must run for all companies.
     */
    public static function companies(bool $activeOnly = true): array
    {
        $companies = [self::central()];
        if (self::hasTenantsTable()) {
            foreach (TenantSetup::on('mysql')->when($activeOnly, fn ($q) => $q->active())->orderBy('id')->pluck('subdomain') as $host) {
                $companies[] = ['tenant' => $host];
            }
        }
        return $companies;
    }

    /** Run $fn for every company (switching database, cache and folders), then return to the central company. */
    public static function forEachCompany(callable $fn, bool $activeOnly = true): array
    {
        $results = [];
        try {
            foreach (self::companies($activeOnly) as $company) {
                $label = $company['tenant'] ?? $company['database'];
                try {
                    self::apply($company);
                    $results[$label] = $fn($company, $label);
                } catch (\Throwable $e) {
                    $results[$label] = $e;
                }
            }
        } finally {
            self::apply(self::central());
        }
        return $results;
    }

    /* ---------------------------------------------------------------- per-company cache and files */

    /** The current company's folder name ('' = the central company, which keeps the existing folders). */
    public static function storageKey(): string
    {
        return app()->bound('lg.tenant_key') ? (string)app('lg.tenant_key') : '';
    }

    /** A folder of the current company: storage/app/recordings for the central company, .../tenants/<key> for others. */
    public static function path(string $base): string
    {
        $key = self::storageKey();
        return $key === '' ? $base : rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'tenants' . DIRECTORY_SEPARATOR . $key;
    }

    /**
     * Default cache store of the current company: its own folder (file driver) and prefix (other drivers), so a cached
     * setting, counter or lock of one company is never seen by another. Null = the central company (unchanged).
     */
    private static function isolateCache(?string $key): void
    {
        // the central company keeps the configured store; each other company gets its own named store (a copy of
        // it with its own folder / prefix) and becomes the default while it is active. Stores are never dropped.
        self::$baseCache ??= ['default' => (string)config('cache.default')];
        $base = self::$baseCache['default'];
        if ($key === null) {
            config(['cache.default' => $base]);
            return;
        }
        $name = 'company_' . $key;
        if (!config("cache.stores.{$name}")) {
            $store = (array)config("cache.stores.{$base}");
            if (($store['driver'] ?? '') === 'file') {
                $store['path'] = rtrim((string)$store['path'], '/\\') . DIRECTORY_SEPARATOR . 'tenants' . DIRECTORY_SEPARATOR . $key;
            }
            $store['prefix'] = (string)config('cache.prefix') . 'co_' . $key . '_';   // redis / memcached / database
            config(["cache.stores.{$name}" => $store]);
        }
        config(['cache.default' => $name]);
    }
}
