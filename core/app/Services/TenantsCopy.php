<?php

namespace App\Services;

use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * When the companies list lives in another database (TENANCY_DB_DATABASE, e.g. tenants_leadger, which the
 * installation serving the companies reads), this installation's own database (Leadger_app2) keeps a real table
 * leadger_tenants with the same 8 columns and rows. The other database stays the master: this copy is refreshed from
 * it (every minute and after each change in Configuration > Companies); edits made in the copy itself are overwritten.
 */
class TenantsCopy
{
    public const COLUMNS = ['id', 'subdomain', 'db_host', 'db_name', 'db_user', 'db_password', 'created_at', 'updated_at'];

    /** Is there a copy to keep (list in another database, real table here)? */
    public static function enabled(): bool
    {
        return Tenancy::registry() === 'tenancy' && Schema::connection('mysql')->hasTable('tenants');
    }

    /** @return array{copied: int, removed: int}|null null when there is no copy to keep */
    public static function sync(): ?array
    {
        if (!self::enabled()) {
            return null;
        }
        try {
            $rows = DB::connection('tenancy')->table('tenants')->orderBy('id')->get(self::COLUMNS)->map(fn ($r) => (array)$r)->all();
            return DB::connection('mysql')->transaction(function () use ($rows) {
                $copy = DB::connection('mysql')->table('tenants');
                if ($rows) {
                    $copy->upsert($rows, ['id'], array_diff(self::COLUMNS, ['id']));
                }
                $removed = DB::connection('mysql')->table('tenants')->whereNotIn('id', array_column($rows, 'id') ?: [0])->delete();
                return ['copied' => count($rows), 'removed' => $removed];
            });
        } catch (\Throwable $e) {
            Log::error('[Tenancy] Companies list copy not refreshed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
