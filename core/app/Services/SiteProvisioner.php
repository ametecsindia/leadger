<?php

namespace App\Services;

use App\Models\TenantSetup;
use Illuminate\Support\Facades\DB;

/**
 * Makes a site's (company's) own database ready to open, from the central database (only read):
 *  - creates the database when it does not exist;
 *  - creates the tables it is missing, with the central structure (CREATE TABLE ... LIKE, no data);
 *  - fills the starting data (settings, roles, menus, statuses ...) into those starting tables that are still empty;
 *  - with no user at all yet: copies the central Webmaster logins (same email and password), so the site can be opened.
 * Never deletes, overwrites or changes rows that exist. Used by tenants:create and, every minute, tenants:sync-sites.
 */
class SiteProvisioner
{
    /** Starting data copied from the central database (everything else starts empty). */
    public const STARTING_DATA = ['migrations', 'settings', 'webmaster_settings', 'webmaster_sections', 'webmaster_section_fields',
        'menus', 'sidebar_menus', 'permissions', 'countries', 'languages', 'statuses', 'substatuses', 'client_types'];

    /** Never in a site's database (the companies list lives in the central database only). */
    private const SKIP = ['tenants'];

    private $conn;
    private string $central;
    private string $prefix;

    public function __construct()
    {
        $this->conn = DB::connection('mysql');
        $this->central = (string)config('database.connections.mysql.database');
        $this->prefix = (string)config('database.connections.mysql.prefix');
    }

    /** May this site's database be prepared from here (same MySQL server as the central database, not the central one)? */
    public function canPrepare(TenantSetup $site): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', (string)$site->db_name)) {
            return 'the database name may use letters, digits and _ only';
        }
        if (strcasecmp((string)$site->db_name, $this->central) === 0) {
            return 'it points to the main database';
        }
        $here = strtolower((string)config('database.connections.mysql.host'));
        if (strtolower((string)$site->db_host) !== $here && !in_array(strtolower((string)$site->db_host), ['127.0.0.1', 'localhost'], true)) {
            return "its database is on another server ({$site->db_host})";
        }
        return null;
    }

    /** What prepare() would do: ['create_database' => bool, 'tables' => [...], 'starting' => [...], 'admins' => n]. */
    public function plan(string $db): array
    {
        $exists = $this->databaseExists($db);
        $have = $exists ? $this->tables($db) : [];
        $missing = array_values(array_diff($this->centralTables(), $have));
        $starting = array_values(array_filter($this->startingTables(), fn ($t) => in_array($t, $missing, true)
            || ($exists && in_array($t, $have, true) && $this->isEmpty($db, $t))));
        $usersTable = $this->prefix . 'users';
        $needAdmins = !in_array($usersTable, $have, true) || $this->isEmpty($db, $usersTable);
        return ['create_database' => !$exists, 'tables' => $missing, 'starting' => $starting,
            'admins' => $needAdmins ? count($this->centralAdmins()) : 0];
    }

    /** Make the database ready; returns the plan that was carried out. A database created here is removed again on failure. */
    public function prepare(string $db): array
    {
        $plan = $this->plan($db);
        $q = fn (string $n) => '`' . str_replace('`', '', $n) . '`';
        try {
            if ($plan['create_database']) {
                $this->conn->statement('CREATE DATABASE ' . $q($db) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }
            foreach ($plan['tables'] as $t) {
                $this->conn->statement('CREATE TABLE IF NOT EXISTS ' . $q($db) . '.' . $q($t) . ' LIKE ' . $q($this->central) . '.' . $q($t));
            }
            $copied = [];
            foreach ($plan['starting'] as $t) {
                if ($this->isEmpty($db, $t)) {   // checked again: never adds to a table that has rows
                    $this->conn->statement('INSERT INTO ' . $q($db) . '.' . $q($t) . ' SELECT * FROM ' . $q($this->central) . '.' . $q($t));
                    $copied[] = $t;
                }
            }
            // The roles' permission keys belong to the role ids: copied only together with the roles themselves
            $roles = $this->prefix . 'permissions';
            $keys = $this->prefix . 'role_permissions';
            if (in_array($roles, $copied, true) && in_array($keys, $this->tables($db), true) && in_array($keys, $this->tables($this->central), true)
                && $this->isEmpty($db, $keys)) {
                $this->conn->statement('INSERT INTO ' . $q($db) . '.' . $q($keys) . ' SELECT * FROM ' . $q($this->central) . '.' . $q($keys));
            }
            if ($plan['admins'] && $this->isEmpty($db, $this->prefix . 'users')) {
                foreach ($this->centralAdmins() as $u) {
                    $this->conn->table(DB::raw($q($db) . '.' . $q($this->prefix . 'users')))->insert($u);
                }
            }
        } catch (\Throwable $e) {
            if ($plan['create_database']) {
                try { $this->conn->statement('DROP DATABASE IF EXISTS ' . $q($db)); } catch (\Throwable $ignored) {}
            }
            throw $e;
        }
        return $plan;
    }

    public function databaseExists(string $db): bool
    {
        return (bool)$this->conn->selectOne('SELECT 1 AS x FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$db]);
    }

    /** @return string[] the database's tables (physical names, with the prefix) */
    public function tables(string $db): array
    {
        return array_column($this->conn->select("SELECT TABLE_NAME AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$db]), 'n');
    }

    /** The central tables a site gets (prefixed, without the companies list and backup copies). */
    private function centralTables(): array
    {
        return array_values(array_filter($this->tables($this->central), fn ($t) => ($this->prefix === '' || str_starts_with($t, $this->prefix))
            && !in_array(substr($t, strlen($this->prefix)), self::SKIP, true) && !preg_match('/(_bak_\d+|_backup)$/', $t)));
    }

    private function startingTables(): array
    {
        return array_values(array_filter($this->centralTables(), fn ($t) => in_array(substr($t, strlen($this->prefix)), self::STARTING_DATA, true)));
    }

    private function isEmpty(string $db, string $table): bool
    {
        return !$this->conn->selectOne('SELECT 1 AS x FROM `' . str_replace('`', '', $db) . '`.`' . str_replace('`', '', $table) . '` LIMIT 1');
    }

    /** The central Webmaster logins (active), as rows to insert: same email, name, role and password hash. */
    private function centralAdmins(): array
    {
        $role = DB::table('permissions')->where('name', 'Webmaster')->value('id');
        if (!$role) {
            return [];
        }
        return DB::table('users')->where('permissions_id', $role)->where('status', 1)->orderBy('id')
            ->get(['name', 'email', 'password', 'permissions_id'])
            ->map(fn ($u) => ['name' => $u->name, 'email' => $u->email, 'password' => $u->password, 'permissions_id' => $u->permissions_id,
                'status' => 1, 'created_at' => now(), 'updated_at' => now()])
            ->all();
    }
}
