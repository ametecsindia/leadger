<?php

namespace App\Console\Commands;

use App\Services\InboundCallService;
use Illuminate\Console\Command;

/** Nightly: link inbound calls saved before their lead / agent existed (leads added or user numbers set later). */
class InboundCallsRematchCommand extends Command
{
    protected $signature = 'inbound-calls:rematch {--days=30}';
    protected $description = 'Match unmatched inbound calls to leads and agents again';

    public function handle(InboundCallService $service): int
    {
        $this->info($service->rematch(max(1, (int)$this->option('days'))) . ' inbound call(s) updated.');
        return self::SUCCESS;
    }
}
