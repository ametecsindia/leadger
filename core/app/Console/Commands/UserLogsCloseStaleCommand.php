<?php

namespace App\Console\Commands;

use App\Services\UserPresence;
use Illuminate\Console\Command;

/**
 * Login sessions nobody logged out of (browser closed, session expired) and not seen for 5 minutes: closed at the
 * time they were last seen, so Agent Performance and the Dashboard do not show those users "Online".
 *   php artisan tenants:each "user-logs:close-stale"   (scheduled every minute)
 */
class UserLogsCloseStaleCommand extends Command
{
    protected $signature = 'user-logs:close-stale';
    protected $description = 'Close login sessions that were not seen for 5 minutes, at their last activity';

    public function handle(): int
    {
        $n = UserPresence::closeStale();
        if ($n) {
            $this->line("Closed {$n} inactive session(s).");
        }
        return self::SUCCESS;
    }
}
