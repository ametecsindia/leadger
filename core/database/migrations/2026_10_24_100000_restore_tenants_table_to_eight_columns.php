<?php

use App\Support\Tenancy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Companies list (tenants / leadger_tenants) back to exactly 8 columns:
 * id, subdomain, db_host, db_name, db_user, db_password, created_at, updated_at.
 *
 * Drops only the columns added by the first version of 2026_10_23_200000 (each only if it exists). The table is not
 * recreated, no row is removed, and the 8 columns keep their data. Before dropping, the values of the removed
 * columns are written to storage/app/backups/tenants/leadger_tenants-removed-columns-<time>.json; down() adds the
 * columns back and restores those values from the newest such file.
 */
return new class extends Migration
{
    private const REMOVED = ['name', 'slug', 'status', 'plan', 'max_users', 'expires_at', 'provisioning_status', 'provisioning_log',
        'app_version', 'last_migrated_at', 'last_migration_status', 'last_migration_error'];

    public function up(): void
    {
        if (DB::getDefaultConnection() !== 'mysql') {
            return;   // tenants:migrate on a company database: no companies list there
        }
        $connection = Tenancy::registry();
        $schema = Schema::connection($connection);
        if (!$schema->hasTable('tenants')) {
            return;
        }
        $present = array_values(array_filter(self::REMOVED, fn ($c) => $schema->hasColumn('tenants', $c)));
        if (!$present) {
            return;
        }

        // rollback copy of the values that are about to go (no passwords: only the removed columns)
        $rows = DB::connection($connection)->table('tenants')->get(array_merge(['id'], $present))->map(fn ($r) => (array)$r)->all();
        $dir = storage_path('app/backups/tenants');
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . 'leadger_tenants-removed-columns-' . date('Ymd_His') . '.json';
        if (file_put_contents($file, json_encode(['columns' => $present, 'rows' => $rows], JSON_PRETTY_PRINT)) === false) {
            throw new \RuntimeException('Could not write the rollback copy ' . $file . ' - nothing was changed.');
        }

        $schema->table('tenants', function (Blueprint $t) use ($present) {
            $t->dropColumn($present);   // their single-column indexes (slug unique, status) go with them
        });
    }

    public function down(): void
    {
        if (DB::getDefaultConnection() !== 'mysql') {
            return;
        }
        $connection = Tenancy::registry();
        $schema = Schema::connection($connection);
        if (!$schema->hasTable('tenants')) {
            return;
        }
        $schema->table('tenants', function (Blueprint $t) use ($schema) {
            $add = fn (string $c) => !$schema->hasColumn('tenants', $c);
            if ($add('name')) $t->string('name', 191)->nullable()->after('id');
            if ($add('slug')) $t->string('slug', 64)->nullable()->unique()->after('name');
            if ($add('status')) $t->string('status', 20)->default('active')->index();
            if ($add('plan')) $t->string('plan', 64)->nullable();
            if ($add('max_users')) $t->unsignedInteger('max_users')->nullable();
            if ($add('expires_at')) $t->date('expires_at')->nullable();
            if ($add('provisioning_status')) $t->string('provisioning_status', 30)->nullable();
            if ($add('provisioning_log')) $t->text('provisioning_log')->nullable();
            if ($add('app_version')) $t->string('app_version', 40)->nullable();
            if ($add('last_migrated_at')) $t->dateTime('last_migrated_at')->nullable();
            if ($add('last_migration_status')) $t->string('last_migration_status', 20)->nullable();
            if ($add('last_migration_error')) $t->text('last_migration_error')->nullable();
        });

        $files = glob(storage_path('app/backups/tenants/leadger_tenants-removed-columns-*.json')) ?: [];
        rsort($files);
        $saved = $files ? json_decode((string)file_get_contents($files[0]), true) : null;
        foreach ((array)($saved['rows'] ?? []) as $row) {
            $id = $row['id'] ?? null;
            unset($row['id']);
            if ($id !== null && $row) {
                DB::connection($connection)->table('tenants')->where('id', $id)->update($row);
            }
        }
    }
};
