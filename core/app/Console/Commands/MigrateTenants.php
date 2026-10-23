<?php

namespace App\Console\Commands;

use App\Models\TenantSetup;
use App\Support\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Apply the database migrations to every company database (the central one is migrated by `php artisan migrate`).
 * Code is shared, so one deploy updates every company; the schema of each company database is brought up to date
 * here. Laravel's migrations table in each company database prevents running a migration twice; a failing company
 * is reported and recorded (tenants.last_migration_*), the others continue; re-running retries only what is left.
 *
 *   php artisan tenants:migrate --status              pending migrations per company
 *   php artisan tenants:migrate --pretend             the SQL that would run (review before a risky change)
 *   php artisan tenants:migrate --backup              mysqldump each company database first (storage/app/backups)
 *   php artisan tenants:migrate --tenant=a.leadger.in only these companies; --all includes suspended companies
 */
class MigrateTenants extends Command
{
    protected $signature = 'tenants:migrate {--tenant=* : only these hosts} {--all : also suspended / failed companies}
        {--status : show pending migrations only} {--pretend : show the SQL, change nothing} {--backup : back up each database first}';
    protected $description = 'Run migrations for all tenant databases';

    public function handle(): int
    {
        if (Tenancy::manageOnly()) {
            $this->error('TENANCY_MANAGE_ONLY: the installation that serves these companies migrates their databases (run it there).');
            return self::FAILURE;
        }
        if (!Tenancy::hasTenantsTable()) {
            $this->info('No tenants table: only the central database exists (php artisan migrate).');
            return self::SUCCESS;
        }

        $lock = Cache::store('tenancy')->lock('tenants-migrate', 3600);
        if (!$lock->get()) {
            $this->error('Another tenants:migrate is running.');
            return self::FAILURE;
        }

        $failed = 0;
        try {
            $tenants = TenantSetup::query()
                ->when(!$this->option('all'), fn ($q) => $q->active())
                ->when($this->option('tenant'), fn ($q, $hosts) => $q->whereIn('subdomain', $hosts))
                ->orderBy('id')->get();
            if ($tenants->isEmpty()) {
                $this->info('No companies to migrate.');
                return self::SUCCESS;
            }

            foreach ($tenants as $tenant) {
                $this->line("<comment>{$tenant->subdomain}</comment> ({$tenant->db_name})");
                if (!$this->migrateOne($tenant)) {
                    $failed++;
                }
            }
        } finally {
            Tenancy::apply(Tenancy::central());
            $lock->release();
        }

        $this->line($failed ? "<error>{$failed} company database(s) failed - see above and tenants.last_migration_error.</error>"
            : '<info>All company databases are up to date.</info>');
        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function migrateOne(TenantSetup $tenant): bool
    {
        try {
            Tenancy::useTenant($tenant);

            if ($this->option('status')) {
                Artisan::call('migrate:status', ['--database' => 'tenant', '--pending' => true]);
                $this->line(trim(Artisan::output()) ?: '  nothing pending');
                return true;
            }
            if ($this->option('backup') && !$this->option('pretend')) {
                $file = $this->backup($tenant);
                $this->line('  backup: ' . $file);
            }

            Artisan::call('migrate', ['--database' => 'tenant', '--force' => true, '--pretend' => (bool)$this->option('pretend')]);
            $out = trim(Artisan::output());
            $this->line($out !== '' ? preg_replace('/^/m', '  ', $out) : '  nothing to migrate');

            if (!$this->option('pretend')) {
                $tenant->forceFill(['last_migrated_at' => now(), 'last_migration_status' => 'ok', 'last_migration_error' => null,
                    'app_version' => self::appVersion()])->save();
            }
            return true;
        } catch (\Throwable $e) {
            $this->error('  failed: ' . $e->getMessage());
            Log::error('[Tenancy] Company migration failed', ['company' => $tenant->subdomain, 'error' => $e->getMessage()]);
            try {
                $tenant->forceFill(['last_migrated_at' => now(), 'last_migration_status' => 'failed',
                    'last_migration_error' => mb_substr($e->getMessage(), 0, 2000)])->save();
            } catch (\Throwable $ignored) {
            }
            return false;
        } finally {
            Tenancy::apply(Tenancy::central());
        }
    }

    /** mysqldump of the company database, gzipped, in storage/app/backups/tenants (the password via MYSQL_PWD). */
    private function backup(TenantSetup $tenant): string
    {
        $dir = storage_path('app/backups/tenants');
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        $file = $dir . DIRECTORY_SEPARATOR . $tenant->storageKey() . '-' . date('Ymd_His') . '.sql.gz';

        $process = proc_open(
            ['mysqldump', '--single-transaction', '--quick', '--routines', '-h', (string)$tenant->db_host, '-P', (string)env('DB_PORT', 3306),
                '-u', (string)$tenant->db_user, (string)$tenant->db_name],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['MYSQL_PWD' => (string)$tenant->db_password] + getenv()
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('mysqldump could not be started (install mysql-client) - use the command without --backup to skip');
        }
        $gz = gzopen($file, 'wb6');
        while (!feof($pipes[1])) {
            gzwrite($gz, (string)fread($pipes[1], 1 << 20));
        }
        gzclose($gz);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            @unlink($file);
            throw new \RuntimeException('backup failed, nothing migrated: ' . trim((string)$err));
        }
        return $file;
    }

    /** The deployed code version (git commit) recorded per company. */
    public static function appVersion(): string
    {
        $head = @file_get_contents(base_path('../.git/HEAD')) ?: @file_get_contents(base_path('.git/HEAD'));
        if ($head && str_starts_with($head, 'ref: ')) {
            $ref = trim(substr($head, 5));
            $hash = @file_get_contents(base_path('../.git/' . $ref)) ?: @file_get_contents(base_path('.git/' . $ref));
            return $hash ? substr(trim($hash), 0, 12) : 'unknown';
        }
        return $head ? substr(trim($head), 0, 12) : 'unknown';
    }
}
