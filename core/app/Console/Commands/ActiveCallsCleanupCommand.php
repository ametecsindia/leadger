<?php

namespace App\Console\Commands;

use App\Http\Controllers\Dashboard\DialerController;
use App\Services\ActiveCallService;
use Illuminate\Console\Command;

/**
 * Close Dashboard active calls that have no sign of life (see ActiveCallService::cleanup), for the current company:
 *   php artisan tenants:each "active-calls:cleanup"     every minute (scheduled)
 */
class ActiveCallsCleanupCommand extends Command
{
    protected $signature = 'active-calls:cleanup {--no-provider : do not ask the provider about Click2Call calls}';
    protected $description = 'Close active calls without heartbeat / event, too long, or already finished';

    public function handle(): int
    {
        if (!ActiveCallService::ready()) {
            $this->line('active_calls table not created yet (php artisan migrate): nothing to do.');
            return self::SUCCESS;
        }
        $provider = $this->option('no-provider') ? null : fn (string $ref, string $vmn) => DialerController::gatewayCallRecord($ref, $vmn);
        $closed = ActiveCallService::cleanup($provider);
        $this->line("Closed {$closed} call(s); open now: " . ActiveCallService::liveCount() . '.');
        return self::SUCCESS;
    }
}
