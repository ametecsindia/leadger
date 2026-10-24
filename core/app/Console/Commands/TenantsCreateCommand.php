<?php

namespace App\Console\Commands;

use App\Models\TenantSetup;
use App\Services\SiteProvisioner;
use App\Support\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Set up a new site (company) on this installation, with its own empty database:
 *   php artisan tenants:create dev1.leadger.in dev1_leadger --admin-email=you@example.com
 *
 * 1. creates the database (refused when a database of that name already has tables);
 * 2. copies the table structure of the central database exactly (CREATE TABLE ... LIKE), no data;
 * 3. copies only the starting data a site needs to open (settings, roles, menus, statuses, countries ...);
 *    never leads, calls, recordings, VMNs or vouchers (App\Services\SiteProvisioner);
 * 4. creates one admin login (Webmaster role) - the password is printed once - and copies the main site's Webmaster
 *    logins (same email and password);
 * 5. adds the site to the companies list (tenants table), so its address opens its own database.
 * The central database is only read. A failure removes the half-made new database again. --dry-run only shows the plan.
 */
class TenantsCreateCommand extends Command
{
    protected $signature = 'tenants:create {host : the site address, e.g. dev1.leadger.in} {database : the new database name}
        {--admin-email= : login email of the site\'s admin} {--admin-name=Admin} {--admin-password= : default: a random one, printed once}
        {--dry-run : only show what would be done}';
    protected $description = 'Create a new site with its own empty database (structure + starting data from the central database)';

    public function handle(): int
    {
        $host = strtolower(trim((string)$this->argument('host')));
        $db = (string)$this->argument('database');
        $email = strtolower(trim((string)$this->option('admin-email')));

        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(:\d+)?$/', $host)) {
            return $this->fail2("\"{$host}\" is not a site address (e.g. dev1.leadger.in).");
        }
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $db)) {
            return $this->fail2('The database name may use letters, digits and _ only.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail2('Give the admin login email: --admin-email=you@example.com');
        }
        if (Tenancy::manageOnly() || !Tenancy::hasTenantsTable()) {
            return $this->fail2('This installation does not serve sites from its companies list (TENANCY_MANAGE_ONLY or no tenants table).');
        }
        $central = (string)config('database.connections.mysql.database');
        if (strcasecmp($db, $central) === 0) {
            return $this->fail2('The new site needs its own database, not the central one.');
        }
        if (Tenancy::isCentralHost($host)) {
            return $this->fail2("{$host} is the main site's address (APP_URL / TENANCY_CENTRAL_HOSTS).");
        }
        $existing = TenantSetup::query()->where('subdomain', $host)->first();
        if ($existing && strcasecmp((string)$existing->db_name, $db) !== 0) {
            return $this->fail2("{$host} is already in the companies list with database {$existing->db_name}.");
        }

        $provisioner = new SiteProvisioner();
        $tablesThere = $provisioner->databaseExists($db) ? count($provisioner->tables($db)) : 0;
        if ($tablesThere > 0) {
            return $this->fail2("Database {$db} already has {$tablesThere} tables. Use a new name (an existing database is never changed).");
        }
        $plan = $provisioner->plan($db);

        $this->line("Site:      {$host}");
        $this->line("Database:  {$db}" . ($plan['create_database'] ? ' (new)' : ' (exists, empty)'));
        $this->line('Structure: ' . count($plan['tables']) . " tables copied from {$central} (no data)");
        $this->line('Starting data: ' . count($plan['starting']) . ' tables (settings, roles, menus, statuses ...)');
        $this->line("Admin:     {$email} (Webmaster)" . ($plan['admins'] ? ", plus the main site's {$plan['admins']} Webmaster login(s)" : ''));
        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was changed.');
            return self::SUCCESS;
        }

        $q = fn (string $name) => '`' . str_replace('`', '', $name) . '`';
        $prefix = (string)config('database.connections.mysql.prefix');
        try {
            $provisioner->prepare($db);   // a database created here is removed again if this fails

            $users = DB::connection('mysql')->table(DB::raw($q($db) . '.' . $q($prefix . 'users')));
            $password = (string)$this->option('admin-password') ?: Str::password(14, true, true, false);
            if (!(clone $users)->where('email', $email)->exists()) {
                $role = DB::connection('mysql')->table(DB::raw($q($db) . '.' . $q($prefix . 'permissions')))->where('name', 'Webmaster')->value('id')
                    ?? DB::connection('mysql')->table(DB::raw($q($db) . '.' . $q($prefix . 'permissions')))->orderBy('id')->value('id');
                (clone $users)->insert(['name' => (string)$this->option('admin-name'), 'email' => $email, 'password' => Hash::make($password),
                    'permissions_id' => $role, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
            } else {
                $password = null;   // the main site's login with this email was copied: its own password works
            }

            $mysql = config('database.connections.mysql');
            if (!$existing) {
                TenantSetup::create(['subdomain' => $host, 'db_host' => $mysql['host'], 'db_name' => $db,
                    'db_user' => $mysql['username'], 'db_password' => $mysql['password']]);
            }
            Cache::store('tenancy')->forget(Tenancy::lookupCacheKey($host));
        } catch (\Throwable $e) {
            return $this->fail2('Not created: ' . $e->getMessage());
        }

        $this->info("Site {$host} is ready: open https://{$host}");
        $this->line("Admin login: {$email}");
        if ($password !== null && !$this->option('admin-password')) {
            $this->warn("Admin password (shown once, change it after logging in): {$password}");
        }
        $this->line('nginx must send this address to this installation (same root as the main site), with an SSL certificate.');
        return self::SUCCESS;
    }

    private function fail2(string $message): int
    {
        $this->error($message);
        return self::FAILURE;
    }
}
