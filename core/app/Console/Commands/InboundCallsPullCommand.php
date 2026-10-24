<?php

namespace App\Console\Commands;

use App\Models\InboundWebhookEvent;
use App\Models\TenantSetup;
use App\Services\InboundCallPuller;
use App\Support\Tenancy;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Pull the inbound call logs of the last few hours from the provider and upsert them into inbound_calls by call_id,
 * for the main database and every company in the tenants table (App\Services\InboundCallPuller). Scheduled every
 * minute; the webhook delivers the same calls live. Also removes inbound_webhook_events older than 30 days.
 */
class InboundCallsPullCommand extends Command
{
    protected $signature = 'inbound-calls:pull {--hours=3 : how far back to ask for calls} {--max-pages=50}';
    protected $description = 'Pull inbound call logs from the telephony provider into inbound_calls';

    public function handle(): int
    {
        if (!\App\Helpers\TcfgHelper::incoming_vmn) {
            $this->info('Incoming VMN feature is disabled.');
            return self::SUCCESS;
        }

        $to = Carbon::now();
        $from = $to->copy()->subHours(max(1, (int)$this->option('hours')));
        foreach (self::companies() as $company) {
            $label = $company['tenant'] ?? $company['database'];
            try {
                Tenancy::apply($company);
                if (!Schema::hasTable('inbound_calls')) {
                    continue;   // company database not migrated yet
                }
                $this->info($label . ': ' . json_encode((new InboundCallPuller())->pull($from, $to, (int)$this->option('max-pages'))));
                if (Schema::hasTable('inbound_webhook_events')) {
                    InboundWebhookEvent::where('received_at', '<', now()->subDays(InboundWebhookEvent::KEEP_DAYS))->limit(5000)->delete();
                }
            } catch (\Throwable $e) {
                Log::error('[InboundCalls] Pull failed', ['company' => $label, 'error' => $e->getMessage()]);
                $this->error($label . ': ' . $e->getMessage());
            }
        }
        Tenancy::apply(self::companies()[0]);
        return self::SUCCESS;
    }

    /** The central company and every active company of the tenants table, as Tenancy contexts. */
    public static function companies(): array
    {
        return Tenancy::companies();
    }
}
