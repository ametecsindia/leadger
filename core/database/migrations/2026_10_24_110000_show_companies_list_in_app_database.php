<?php

use App\Support\Tenancy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * When the companies list lives in another database (TENANCY_DB_DATABASE, e.g. tenants_leadger on app.leadger.in),
 * show it in this installation's own database too (Leadger_app2) as the view leadger_tenants: the same 8 columns and
 * the same rows, read and written through to the real table - one list, never a second copy that drifts apart.
 * Nothing happens when the list is in this database already, or when a real leadger_tenants table exists here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDefaultConnection() !== 'mysql' || Tenancy::registry() !== 'tenancy') {
            return;
        }
        [$here, $there, $name] = $this->names();
        $existing = DB::connection('mysql')->selectOne(
            'SELECT TABLE_TYPE AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$here, $name]);
        if ($existing && $existing->t !== 'VIEW') {
            return;   // a real table of that name: left alone
        }
        DB::connection('mysql')->statement(sprintf(
            'CREATE OR REPLACE VIEW `%s`.`%s` AS SELECT `id`, `subdomain`, `db_host`, `db_name`, `db_user`, `db_password`, `created_at`, `updated_at` FROM `%s`.`%s`',
            $here, $name, $there, $name));
    }

    public function down(): void
    {
        if (DB::getDefaultConnection() !== 'mysql' || Tenancy::registry() !== 'tenancy') {
            return;
        }
        [$here, , $name] = $this->names();
        $existing = DB::connection('mysql')->selectOne(
            'SELECT TABLE_TYPE AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$here, $name]);
        if ($existing && $existing->t === 'VIEW') {
            DB::connection('mysql')->statement(sprintf('DROP VIEW `%s`.`%s`', $here, $name));
        }
    }

    /** [this database, the companies list's database, table name with prefix] - names only, no user input. */
    private function names(): array
    {
        $clean = fn ($v) => preg_replace('/[^A-Za-z0-9_]/', '', (string)$v);
        return [
            $clean(config('database.connections.mysql.database')),
            $clean(config('database.connections.tenancy.database')),
            $clean(config('database.connections.tenancy.prefix')) . 'tenants',
        ];
    }
};
