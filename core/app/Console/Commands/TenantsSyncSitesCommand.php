<?php

namespace App\Console\Commands;

use App\Models\TenantSetup;
use App\Services\SiteProvisioner;
use App\Support\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Every site in the companies list gets a ready database: a site added by hand (SQLyog, Configuration > Companies)
 * opens within a minute. Per site (App\Services\SiteProvisioner): create the database if missing, create missing
 * tables, fill empty starting-data tables, copy the main site's Webmaster logins when it has no user. Existing rows are
 * never changed. Scheduled every minute; --dry-run only lists what would be done.
 */
class TenantsSyncSitesCommand extends Command
{
    protected $signature = 'tenants:sync-sites {--dry-run : only show what would be done}';
    protected $description = 'Prepare the database of every site in the companies list (create what is missing)';

    public function handle(SiteProvisioner $provisioner): int
    {
        if (!Tenancy::servesCompanies()) {
            $this->line('This installation does not serve sites from its companies list.');
            return self::SUCCESS;
        }
        Tenancy::apply(Tenancy::central());

        foreach (TenantSetup::query()->orderBy('id')->get() as $site) {
            $host = trim((string)$site->subdomain);
            if ($host === '' || Tenancy::isCentralHost($host)) {
                continue;   // an empty row, or the main site itself
            }
            if ($why = $provisioner->canPrepare($site)) {
                $this->warn("{$host}: skipped, {$why}.");
                continue;
            }
            try {
                $plan = $provisioner->plan($site->db_name);
                if (!$plan['create_database'] && !$plan['tables'] && !$plan['starting'] && !$plan['admins']) {
                    continue;   // ready
                }
                $summary = ($plan['create_database'] ? 'new database, ' : '') . count($plan['tables']) . ' missing tables, '
                    . count($plan['starting']) . ' starting-data tables, ' . $plan['admins'] . ' admin login(s)';
                if ($this->option('dry-run')) {
                    $this->line("{$host} ({$site->db_name}): would add {$summary}.");
                    continue;
                }
                $provisioner->prepare($site->db_name);
                $this->info("{$host} ({$site->db_name}): added {$summary}.");
                Log::info('[Tenancy] Site database prepared', ['host' => $host, 'database' => $site->db_name, 'added' => $summary]);
            } catch (\Throwable $e) {
                $this->error("{$host}: {$e->getMessage()}");
                Log::error('[Tenancy] Site database could not be prepared', ['host' => $host, 'error' => $e->getMessage()]);
            }
        }
        return self::SUCCESS;
    }
}
