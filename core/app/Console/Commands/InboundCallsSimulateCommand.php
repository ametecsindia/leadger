<?php

namespace App\Console\Commands;

use App\Services\InboundCallService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Test helper: sends ringing -> answered -> ended events of one inbound call through the same code as the webhook.
 * php artisan inbound-calls:simulate --vmn=8045678901 --customer=+919958887262 --agent=7386566634
 */
class InboundCallsSimulateCommand extends Command
{
    protected $signature = 'inbound-calls:simulate {--vmn= : a VMN of this company} {--customer=} {--agent= : agent number (empty = missed call)}
        {--seconds=95 : talk time} {--call-id=}';
    protected $description = 'Simulate the provider events of one inbound call';

    public function handle(InboundCallService $service): int
    {
        $callId = $this->option('call-id') ?: 'SIM-' . strtoupper(Str::random(10));
        $start = Carbon::now()->subSeconds((int)$this->option('seconds') + 10);
        $base = ['call_id' => $callId, 'dni' => (string)$this->option('vmn'), 'cli' => (string)$this->option('customer')];
        $agent = (string)$this->option('agent');

        $events = [$base + ['event' => 'ringing', 'start_time' => $start->format('Y-m-d H:i:s')]];
        if ($agent !== '') {
            $events[] = $base + ['event' => 'answered', 'a_party_no' => $agent];
            $events[] = $base + ['event' => 'ended', 'a_party_no' => $agent, 'end_time' => Carbon::now()->format('Y-m-d H:i:s'), 'duration' => (int)$this->option('seconds')];
        } else {
            $events[] = $base + ['event' => 'no-answer', 'end_time' => Carbon::now()->format('Y-m-d H:i:s'), 'duration' => 0];
        }

        foreach ($events as $event) {
            $r = $service->record($event, 'simulate');
            $this->line(str_pad($event['event'], 10) . $r['result'] . ($r['result'] === 'ignored' ? ' (' . $r['message'] . ')' : ''));
        }
        $call = \App\Models\InboundCall::where('call_id', $callId)->first();
        if ($call) {
            $this->info("call_id {$callId}: status {$call->status}, lead " . ($call->lead_id ?: '-') . ', agent user ' . ($call->agent_user_id ?: '-'));
        }
        return self::SUCCESS;
    }
}
