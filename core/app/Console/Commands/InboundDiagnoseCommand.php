<?php

namespace App\Console\Commands;

use App\Models\InboundWebhookEvent;
use App\Services\InboundCallPuller;
use App\Services\InboundCallService;
use App\Support\Tenancy;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Why are there no inbound calls? Prints the webhook route and URL, which .env keys are set (never their values),
 * the VMNs, raw events of the last 24 hours, the last scheduled pull, the queue worker / scheduler state and the last
 * inbound call. --probe also asks the provider for the last hours (counts and field names only, no phone numbers).
 */
class InboundDiagnoseCommand extends Command
{
    protected $signature = 'inbound:diagnose {--probe : ask the provider for the last hours} {--hours=24}';
    protected $description = 'Check every step of the Inbound Calls pipeline';

    private const ENV_KEYS = ['APP_URL', 'TIMEZONE', 'QUEUE_CONNECTION', 'TELEPHONY_WEBHOOK_SECRET', 'INBOUND_CALLS_WEBHOOK_SECRET',
        'INBOUND_CALLS_WEBHOOK_IPS', 'INBOUND_CALLS_SINGLE_COMPANY', 'TELEPHONY_INCOMING_URL', 'TELEPHONY_TIMEZONE'];

    public function handle(): int
    {
        $this->section('Webhook');
        $route = Route::getRoutes()->getByName('api.telephony.inboundCall');
        $this->check((bool)$route, 'Route api.telephony.inboundCall', $route ? implode('|', $route->methods()) . ' /' . $route->uri() : 'MISSING');
        if ($route) {
            $url = route('api.telephony.inboundCall');
            $this->line('  URL for the provider: ' . $url . '   (secret in the Secret-Key header, or ' . $url . '?secret=...)');
            $this->check(!preg_match('#//(localhost|127\.)#', $url), 'APP_URL is the public address', parse_url($url, PHP_URL_HOST));
        }
        $this->check(!app()->routesAreCached(), 'Routes not cached', app()->routesAreCached() ? 'cached: run php artisan route:clear' : 'ok');
        $this->check(!app()->configurationIsCached(), 'Config not cached', app()->configurationIsCached() ? 'cached: run php artisan config:clear (this app reads env() directly)' : 'ok');

        $this->section('.env keys (set / not set; values never shown)');
        foreach (self::ENV_KEYS as $k) {
            $this->line(sprintf('  %-30s %s', $k, env($k) !== null && env($k) !== '' ? 'set' : 'not set'));
        }
        $this->line('  Webhook secret in use: ' . (config('services.telephony.inbound_secret') ? 'INBOUND_CALLS_WEBHOOK_SECRET'
                : (env('TELEPHONY_WEBHOOK_SECRET') ? 'TELEPHONY_WEBHOOK_SECRET' : 'the built-in provider Secret-Key')));
        $this->line('  App timezone: ' . config('app.timezone') . '; provider times read as ' . config('services.telephony.timezone'));
        $this->line('  Unmapped VMNs: ' . (InboundCallService::singleCompany() ? 'saved (one company in this installation)' : 'kept as "ignored" (several companies)'));

        $this->section('Queue worker and scheduler');
        $this->workerAndScheduler();

        foreach (InboundCallsPullCommand::companies() as $company) {
            $label = $company['tenant'] ?? $company['database'];
            try {
                Tenancy::apply($company);
                $this->company($label);
            } catch (\Throwable $e) {
                $this->error($label . ': ' . $e->getMessage());
            }
        }
        Tenancy::apply(InboundCallsPullCommand::companies()[0]);
        return self::SUCCESS;
    }

    private function company(string $label): void
    {
        $this->section('Company: ' . $label);
        if (!Schema::hasTable('inbound_calls') || !Schema::hasTable('inbound_webhook_events')) {
            $this->check(false, 'Tables inbound_calls / inbound_webhook_events', 'missing: run php artisan migrate --force');
            return;
        }

        $vmns = DB::table('obd_did')->get(['virtual_number', 'click2call']);
        $this->check($vmns->isNotEmpty(), 'VMNs in Add Voucher (obd_did)', $vmns->count() . ': ' . $vmns->map(fn ($v) => $v->virtual_number
            . ' -> ' . InboundCallService::normalizeMobile($v->virtual_number))->implode(', '));
        if (Schema::hasTable('smart31_vmn_numbers')) {
            $this->line('  Smart31 / 1600 VMNs: ' . DB::table('smart31_vmn_numbers')->count());
        }
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $this->check((bool)(json_decode((string)($settings->full_response ?? ''), true)['data']['crm_id'] ?? null), 'CRM id in the license settings', 'needed by the pull');

        $last = DB::table('inbound_calls')->orderByDesc('id')->first(['start_time', 'created_at']);
        $this->line('  inbound_calls rows: ' . DB::table('inbound_calls')->count()
            . ($last ? '; last call ' . $last->start_time . ' (saved ' . $last->created_at . ')' : '; none yet'));

        $since = now()->subDay();
        $events = InboundWebhookEvent::where('received_at', '>=', $since)->selectRaw('source, processing_status, COUNT(*) c, MAX(received_at) last')
            ->groupBy('source', 'processing_status')->get();
        $this->line('  Raw events, last 24 h: ' . ($events->isEmpty() ? 'none' : ''));
        foreach ($events as $e) {
            $this->line(sprintf('    %-8s %-10s %5d  (last %s)', $e->source, $e->processing_status, $e->c, $e->last));
        }
        $lastEvent = InboundWebhookEvent::where('source', 'webhook')->max('received_at');
        $this->line('  Last webhook request ever: ' . ($lastEvent ?: 'never: the provider is not calling the webhook URL'));
        foreach (InboundWebhookEvent::whereIn('processing_status', ['failed', 'ignored'])->where('received_at', '>=', $since)
                     ->select('failure_reason')->distinct()->limit(5)->pluck('failure_reason') as $reason) {
            $this->line('    reason: ' . $reason);
        }

        $run = InboundCallPuller::lastRun();
        if (!$run) {
            $this->check(false, 'Scheduled pull (inbound-calls:pull)', 'has never run here: the cron schedule:run is missing, or run it once by hand');
        } else {
            $age = Carbon::parse($run['at'])->diffInMinutes(now(), true);
            $this->check($age <= 10 && !isset($run['error']), 'Scheduled pull (inbound-calls:pull)', 'last run ' . $run['at'] . ' (' . (int)$age . ' min ago): '
                . json_encode(array_diff_key($run, ['at' => 1])));
        }

        if ($this->option('probe')) {
            $hours = max(1, (int)$this->option('hours'));
            $this->line('  Provider answer for the last ' . $hours . ' h:');
            foreach ((new InboundCallPuller())->probe(now()->subHours($hours), now()) as $k => $v) {
                $this->line('    ' . $k . ': ' . (is_array($v) ? json_encode($v, JSON_UNESCAPED_SLASHES) : $v));
            }
        }
    }

    private function workerAndScheduler(): void
    {
        $this->line('  QUEUE_CONNECTION: ' . config('queue.default') . ' (inbound calls are processed without the queue)');
        if (config('queue.default') === 'database' && Schema::hasTable('jobs')) {
            $oldest = DB::table('jobs')->whereNull('reserved_at')->min('available_at');
            $waiting = $oldest ? (int)((time() - $oldest) / 60) : 0;
            $this->check($waiting < 10, 'Queue jobs waiting', DB::table('jobs')->count() . ' job(s)' . ($oldest ? ', oldest waiting ' . $waiting . ' min' : ''));
        }
        if (PHP_OS_FAMILY !== 'Windows' && function_exists('shell_exec')) {
            $worker = trim((string)@shell_exec('pgrep -fa "artisan queue:work" 2>/dev/null | grep -v pgrep | wc -l'));
            $this->check((int)$worker > 0, 'Queue worker process', $worker . ' running');
            $cron = trim((string)@shell_exec('pgrep -fa "artisan schedule" 2>/dev/null | grep -v pgrep | wc -l'));
            $this->line('  schedule:run process right now: ' . $cron . ' (it lives only a few seconds each minute; see "Scheduled pull" below)');
        }
        $this->line('  Cron line needed: * * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1');
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line('<comment>' . $title . '</comment>');
    }

    private function check(bool $ok, string $what, ?string $detail = null): void
    {
        $this->line(sprintf('  %s %s%s', $ok ? '<info>[ok]</info>' : '<error>[!!]</error>', $what, $detail ? ': ' . $detail : ''));
    }
}
