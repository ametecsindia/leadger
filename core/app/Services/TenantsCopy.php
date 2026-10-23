<?php

namespace App\Services;

use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * When the companies list lives in another database (TENANCY_DB_DATABASE, e.g. tenants_leadger, which the
 * installation serving the companies reads), a copy database keeps a real table leadger_tenants with the same 8
 * columns and rows: TENANCY_COPY_DATABASE (e.g. leadger_app) on the same server and login, or this installation's
 * own database when that is empty. The other database stays the master: this copy is refreshed from it (every minute
 * and after each change in Configuration > Companies); edits made in the copy itself are overwritten.
 */
class TenantsCopy
{
    public const COLUMNS = ['id', 'subdomain', 'db_host', 'db_name', 'db_user', 'db_password', 'created_at', 'updated_at'];

    /** The connection of the copy database: "mysql" itself, or "tenants_copy" (mysql pointed at the copy database). */
    public static function connection(): string
    {
        $db = trim((string)config('tenancy.copy_database'));
        if ($db === '' || $db === (string)config('database.connections.mysql.database')) {
            return 'mysql';
        }
        if (config('database.connections.tenants_copy.database') !== $db) {
            config(['database.connections.tenants_copy' => array_merge(config('database.connections.mysql'), ['database' => $db])]);
            DB::purge('tenants_copy');
        }
        return 'tenants_copy';
    }

    /** Is there a copy to keep (list in another database, real table in the copy database)? */
    public static function enabled(): bool
    {
        return Tenancy::registry() === 'tenancy' && Schema::connection(self::connection())->hasTable('tenants');
    }

    /** @return array{copied: int, removed: int}|null null when there is no copy to keep */
    public static function sync(): ?array
    {
        try {
            if (!self::enabled()) {
                return null;
            }
            $rows = DB::connection('tenancy')->table('tenants')->orderBy('id')->get(self::COLUMNS)->map(fn ($r) => (array)$r)->all();
            $conn = DB::connection(self::connection());
            return $conn->transaction(function () use ($conn, $rows) {
                if ($rows) {
                    $conn->table('tenants')->upsert($rows, ['id'], array_diff(self::COLUMNS, ['id']));
                }
                $removed = $conn->table('tenants')->whereNotIn('id', array_column($rows, 'id') ?: [0])->delete();
                return ['copied' => count($rows), 'removed' => $removed];
            });
        } catch (\Throwable $e) {
            Log::error('[Tenancy] Companies list copy not refreshed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
