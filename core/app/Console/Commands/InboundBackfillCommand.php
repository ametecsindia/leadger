<?php

namespace App\Console\Commands;

use App\Models\InboundWebhookEvent;
use App\Services\InboundCallPuller;
use App\Services\InboundCallService;
use App\Support\Tenancy;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound calls that were missed: asks the provider for every day of the range (upsert by call_id, nothing is
 * duplicated), then processes again the stored events that failed or were ignored (e.g. a VMN added to Add Voucher
 * since). Requests refused for a wrong secret are never replayed. Creates no demo rows.
 */
class InboundBackfillCommand extends Command
{
    protected $signature = 'inbound:backfill {--from= : first day, Y-m-d} {--to= : last day, Y-m-d (default today)}
        {--company= : only this company (tenant host or database name)} {--skip-provider : only re-process stored events} {--max-pages=50}';
    protected $description = 'Fetch missed inbound calls from the provider and re-process failed / ignored inbound events';

    public function handle(): int
    {
        $tz = config('services.telephony.timezone') ?: config('app.timezone');
        try {
            $from = Carbon::createFromFormat('!Y-m-d', (string)$this->option('from'), $tz);
            $to = Carbon::createFromFormat('!Y-m-d', (string)($this->option('to') ?: now($tz)->format('Y-m-d')), $tz)->endOfDay();
        } catch (\Throwable $e) {
            $this->error('Use --from=YYYY-MM-DD (and optionally --to=YYYY-MM-DD).');
            return self::INVALID;
        }
        if ($from->gt($to) || $from->diffInDays($to) > 92) {
            $this->error('--from must be before --to, at most 92 days apart.');
            return self::INVALID;
        }

        $only = (string)$this->option('company');
        foreach (InboundCallsPullCommand::companies() as $company) {
            if ($only !== '' && !in_array($only, $company, true)) {
                continue;
            }
            $label = $company['tenant'] ?? $company['database'];
            try {
                Tenancy::apply($company);
                if (!Schema::hasTable('inbound_calls') || !Schema::hasTable('inbound_webhook_events')) {
                    $this->warn($label . ': not migrated, skipped (run php artisan migrate).');
                    continue;
                }
                $this->line("<info>{$label}</info>  {$from->format('Y-m-d')} .. {$to->format('Y-m-d')}");
                if (!$this->option('skip-provider')) {
                    $this->fromProvider($from, $to);
                }
                $this->reprocess($from, $to);
            } catch (\Throwable $e) {
                $this->error($label . ': ' . $e->getMessage());
            }
        }
        Tenancy::apply(InboundCallsPullCommand::companies()[0]);
        return self::SUCCESS;
    }

    private function fromProvider(Carbon $from, Carbon $to): void
    {
        $puller = new InboundCallPuller();
        $rows = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $r = $puller->pull($day->copy()->startOfDay(), $day->copy()->endOfDay(), (int)$this->option('max-pages'));
            $rows[] = [$day->format('Y-m-d'), $r['fetched'] ?? 0, $r['created'] ?? 0, $r['updated'] ?? 0,
                ($r['ignored'] ?? 0) + ($r['invalid'] ?? 0), $r['failed'] ?? 0, $r['error'] ?? $r['skipped'] ?? ''];
            if (isset($r['skipped'])) break;
        }
        $this->line('Provider:');
        $this->table(['Day', 'Fetched', 'Created', 'Updated', 'Ignored', 'Failed', 'Note'], $rows);
    }

    private function reprocess(Carbon $from, Carbon $to): void
    {
        $service = new InboundCallService();
        $counts = ['events' => 0, 'processed' => 0, 'still ignored' => 0, 'still failed' => 0];
        InboundWebhookEvent::whereIn('processing_status', ['failed', 'ignored'])
            ->where('signature_valid', true)
            ->whereBetween('received_at', [$from->copy()->setTimezone(config('app.timezone')), $to->copy()->setTimezone(config('app.timezone'))])
            ->orderBy('id')
            ->chunkById(200, function ($events) use ($service, &$counts) {
                foreach ($events as $event) {
                    $counts['events']++;
                    $saved = null;
                    $reason = 'No call in the stored body';
                    foreach ($event->payloads() as $payload) {
                        try {
                            $r = $service->record($payload, $event->source === 'pull' ? 'pull' : 'webhook');
                        } catch (\Throwable $e) {
                            $r = ['result' => 'failed', 'call' => null, 'message' => mb_substr($e->getMessage(), 0, 200)];
                        }
                        if (in_array($r['result'], ['created', 'updated'], true)) {
                            $saved ??= $r['call'];
                        } else {
                            $reason = $r['message'] ?? $r['result'];
                        }
                    }
                    $status = $saved ? 'processed' : ($event->processing_status === 'failed' && !str_starts_with($reason, 'Unmapped') ? 'failed' : 'ignored');
                    $event->update(['processing_status' => $status, 'failure_reason' => $saved ? 'Re-processed by inbound:backfill' : mb_substr($reason, 0, 255),
                        'inbound_call_id' => $saved->id ?? $event->inbound_call_id, 'call_id' => $saved->call_id ?? $event->call_id]);
                    $counts[$status === 'processed' ? 'processed' : 'still ' . $status]++;
                }
            });
        $this->line('Stored events re-processed:');
        $this->table(array_keys($counts), [array_values($counts)]);
    }
}
