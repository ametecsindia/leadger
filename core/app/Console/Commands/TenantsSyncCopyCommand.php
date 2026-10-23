<?php

namespace App\Console\Commands;

use App\Services\TenantsCopy;
use Illuminate\Console\Command;

/**
 * Refresh this installation's copy of the companies list (Leadger_app2.leadger_tenants) from the master list
 * (TENANCY_DB_DATABASE, e.g. tenants_leadger). Scheduled every minute; does nothing without such a copy.
 */
class TenantsSyncCopyCommand extends Command
{
    protected $signature = 'tenants:sync-copy';
    protected $description = 'Refresh the copy of the companies list in this database from the master list';

    public function handle(): int
    {
        $r = TenantsCopy::sync();
        $this->line($r === null ? 'No copy to refresh (the companies list is in this database, or no copy table).'
            : "Companies list copy refreshed: {$r['copied']} row(s), {$r['removed']} removed.");
        return self::SUCCESS;
    }
}
