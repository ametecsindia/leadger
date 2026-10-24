<?php

namespace App\Console\Commands;

use App\Models\InboundCall;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * One-time correction of inbound call times saved before the provider's "UTC" times were read as local time.
 * The provider sends 11:28 India time as "11:28Z", so those calls were saved 5:30 h late (16:58). A saved start time
 * after the moment the call reached us (created_at), or end time after its last update (updated_at), cannot be real:
 * it is moved back by the timezone difference. Other values are not touched. --dry-run only lists.
 */
class InboundFixTimesCommand extends Command
{
    protected $signature = 'inbound:fix-times {--dry-run : Only list what would change}';
    protected $description = 'Correct inbound call times saved 5:30 h late (provider local times marked as UTC)';

    public function handle(): int
    {
        $appTz = config('app.timezone');
        $providerTz = config('services.telephony.timezone') ?: $appTz;
        // stored value (app timezone) of a provider "HH:MM Z" -> the same wall clock in the provider's timezone
        $fix = fn ($stored) => Carbon::parse(Carbon::parse($stored, $appTz)->setTimezone('UTC')->format('Y-m-d H:i:s'), $providerTz)->setTimezone($appTz);
        $late = fn ($time, $ref) => $time && $ref && Carbon::parse($time, $appTz)->gt(Carbon::parse($ref, $appTz)->addMinutes(2));

        $changed = 0;
        InboundCall::query()->orderBy('id')->chunkById(500, function ($calls) use ($fix, $late, &$changed) {
            foreach ($calls as $call) {
                $start = $late($call->getRawOriginal('start_time'), $call->getRawOriginal('created_at')) ? $fix($call->getRawOriginal('start_time')) : null;
                $end = $late($call->getRawOriginal('end_time'), $call->getRawOriginal('updated_at')) ? $fix($call->getRawOriginal('end_time')) : null;
                if (!$start && !$end) {
                    continue;
                }
                $changed++;
                $this->line("#{$call->id}: start " . $call->getRawOriginal('start_time') . ($start ? ' -> ' . $start->format('Y-m-d H:i:s') : ' (kept)')
                    . ', end ' . ($call->getRawOriginal('end_time') ?? '-') . ($end ? ' -> ' . $end->format('Y-m-d H:i:s') : ' (kept)'));
                if ($this->option('dry-run')) {
                    continue;
                }
                if ($start) $call->start_time = $start;
                if ($end) $call->end_time = $end;
                $call->timestamps = false;   // created_at / updated_at stay as they are
                $call->save();
            }
        });
        $this->info(($this->option('dry-run') ? 'Would correct ' : 'Corrected ') . "{$changed} inbound call(s).");
        return self::SUCCESS;
    }
}
