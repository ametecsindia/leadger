<?php

namespace App\Console\Commands;

use App\Jobs\C2c\DownloadRecording;
use App\Models\C2c\Call;
use App\Services\Click2Call\CallResult;
use App\Services\Click2Call\Click2CallGateway;
use Illuminate\Console\Command;

/**
 * Catches results and recordings the webhook never delivered. Same rules as LeadGER's recordings:fetch-pending:
 *  - calls of the last 24 h that are still live, or ended without a recording ("awaiting"): ask the gateway again
 *  - no recording 60 minutes after the call ended            -> "unavailable"
 *  - failed downloads are retried (at most 20 attempts, 10 minutes apart)
 * Schedule: every minute (routes/console.php: Schedule::command('c2c:sweep')->everyMinute()->withoutOverlapping()).
 */
class C2cSweepCommand extends Command
{
    protected $signature = 'c2c:sweep';
    protected $description = 'Click2Call: fetch missing call results and recordings from the gateway';

    public function handle(Click2CallGateway $gateway, CallResult $result): int
    {
        $waitMinutes = (int) config('click2call.recording_wait_minutes', 60);
        $checked = 0;

        Call::where('created_at', '>=', now()->subDay())
            ->where(fn ($q) => $q->whereIn('call_state', ['initiated', 'ringing', 'connected'])
                ->orWhere(fn ($w) => $w->where('call_state', 'completed')->where('recording_status', 'awaiting')))
            ->orderBy('id')
            ->chunkById(100, function ($calls) use ($gateway, $result, $waitMinutes, &$checked) {
                foreach ($calls as $call) {
                    if ($call->recording_status === 'awaiting' && $call->ended_at && $call->ended_at->lt(now()->subMinutes($waitMinutes))) {
                        $call->update(['recording_status' => 'unavailable']);
                        continue;
                    }
                    if ($data = $gateway->callData($call->ref_id, $call->vmn, 5)) {
                        $result->apply($call, $data['cdr'], $data['recording_url']);
                    }
                    $checked++;
                }
            });

        // calls that never got any result: after 15 minutes they are failed, not "calling" forever
        Call::whereIn('call_state', ['initiated', 'ringing'])->where('created_at', '<', now()->subMinutes(15))
            ->update(['call_state' => 'failed', 'error' => 'No call result was received from the provider.', 'ended_at' => now()]);

        Call::where('recording_status', 'failed')->where('recording_attempts', '<', 20)
            ->where('updated_at', '<', now()->subMinutes(10))->whereNotNull('recording_url')
            ->pluck('id')->each(function ($id) {
                Call::whereKey($id)->update(['recording_status' => 'pending']);
                DownloadRecording::dispatch($id);
            });

        $this->info("Checked {$checked} call(s).");
        return self::SUCCESS;
    }
}
