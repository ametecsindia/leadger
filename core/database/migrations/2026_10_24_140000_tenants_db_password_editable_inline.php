<?php

use App\Services\TenantsCopy;
use App\Support\Tenancy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * leadger_tenants.db_password TEXT -> VARCHAR(512), so database tools (SQLyog) edit it in the grid like the other
 * columns instead of a "Blob field" popup. Same values, same NULL rule; the table is altered in place (not recreated).
 * Done for every companies list this installation uses: its own table, the master list (TENANCY_DB_DATABASE) and the
 * copy (TENANCY_COPY_DATABASE). Skipped where a stored value would not fit (none do: plain or encrypted passwords).
 */
return new class extends Migration
{
    private const LENGTH = 512;

    public function up(): void
    {
        foreach ($this->tables() as [$conn, $db, $table]) {
            $col = $this->column($conn, $db, $table);
            if (!$col || $col->type !== 'text') {
                continue;
            }
            $longest = (int)DB::connection($conn)->table('tenants')->max(DB::raw('CHAR_LENGTH(db_password)'));
            if ($longest > self::LENGTH) {
                continue;
            }
            DB::connection($conn)->statement("ALTER TABLE `$db`.`$table` MODIFY `db_password` VARCHAR(" . self::LENGTH . ") NULL");
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as [$conn, $db, $table]) {
            $col = $this->column($conn, $db, $table);
            if ($col && $col->type === 'varchar') {
                DB::connection($conn)->statement("ALTER TABLE `$db`.`$table` MODIFY `db_password` TEXT NULL");
            }
        }
    }

    /** [connection, database, table] of each companies list, once each. */
    private function tables(): array
    {
        $conns = ['mysql'];
        if (Tenancy::registry() === 'tenancy') {
            $conns[] = 'tenancy';
            $conns[] = TenantsCopy::connection();
        }
        $out = [];
        foreach (array_unique($conns) as $conn) {
            if (!Schema::connection($conn)->hasTable('tenants')) {
                continue;
            }
            $db = (string)config("database.connections.$conn.database");
            $table = preg_replace('/[^A-Za-z0-9_]/', '', (string)config("database.connections.$conn.prefix")) . 'tenants';
            $out["$db.$table"] = [$conn, str_replace('`', '', $db), $table];
        }
        return array_values($out);
    }

    private function column(string $conn, string $db, string $table): ?object
    {
        return DB::connection($conn)->selectOne('SELECT DATA_TYPE AS type FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$db, $table, 'db_password']);
    }
};
