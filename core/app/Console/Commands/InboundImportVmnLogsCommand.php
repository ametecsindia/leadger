<?php

namespace App\Console\Commands;

use App\Services\InboundCallService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound calls that came through RabbitMQ (callrecording_pull.py -> incoming_vmn_logs) before they were also saved
 * for the Inbound Calls report: puts them into inbound_calls, for the current company. Matched by call_id, so running
 * it again changes nothing; incoming_vmn_logs is only read. Calls older than INBOUND_NOTIFY_MAX_AGE notify nobody.
 *   php artisan tenants:each "inbound:import-vmn-logs --days=30"
 */
class InboundImportVmnLogsCommand extends Command
{
    protected $signature = 'inbound:import-vmn-logs {--days=30 : how many days back}';
    protected $description = 'Copy RabbitMQ inbound calls (incoming_vmn_logs) into the Inbound Calls report';

    public function handle(): int
    {
        if (!Schema::hasTable('incoming_vmn_logs') || !Schema::hasTable('inbound_calls')) {
            $this->line('incoming_vmn_logs / inbound_calls table missing: nothing to do.');
            return self::SUCCESS;
        }
        $service = new InboundCallService();
        $counts = [];
        DB::table('incoming_vmn_logs')
            ->where(fn ($q) => $q->where('a_start_time', '>=', now()->subDays(max(1, (int)$this->option('days'))))->orWhereNull('a_start_time'))
            ->whereNotNull('call_id')->where('call_id', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($service, &$counts) {
                foreach ($rows as $row) {
                    $r = $service->record((array)$row + ['event_type' => 'Call End'], 'rabbitmq');
                    $counts[$r['result']] = ($counts[$r['result']] ?? 0) + 1;
                }
            });
        $this->line('incoming_vmn_logs -> inbound_calls: ' . ($counts ? json_encode($counts) : 'no rows in the period'));
        return self::SUCCESS;
    }
}
