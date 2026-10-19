<?php

namespace App\Console\Commands;

use App\Models\TenantSetup;
use App\Services\InboundCallService;
use App\Support\Tenancy;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Pull the inbound call logs of the last few hours from the provider (incoming_data, the same endpoint and
 * credentials as the Incoming VMN fetch) and upsert them into inbound_calls by call_id, for the main database and
 * every company in the tenants table. Scheduled every 5 minutes; the webhook delivers the same calls live.
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

        $companies = [['database' => (string)config('database.connections.mysql.database')]];
        if (Schema::hasTable('tenants')) {
            foreach (TenantSetup::on('mysql')->pluck('subdomain') as $host) {
                $companies[] = ['tenant' => $host];
            }
        }

        foreach ($companies as $company) {
            $label = $company['tenant'] ?? $company['database'];
            try {
                Tenancy::apply($company);
                if (!Schema::hasTable('inbound_calls')) {
                    continue;   // company database not migrated yet
                }
                $this->info($label . ': ' . json_encode($this->pull()));
            } catch (\Throwable $e) {
                Log::error('[InboundCalls] Pull failed', ['company' => $label, 'error' => $e->getMessage()]);
                $this->error($label . ': ' . $e->getMessage());
            }
        }
        Tenancy::apply($companies[0]);
        return self::SUCCESS;
    }

    /** @return array counts of created / updated / ignored rows for the current company */
    private function pull(): array
    {
        $vmns = DB::table('obd_did')->pluck('virtual_number')->filter()->unique()->values()->all();
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $crm = json_decode((string)($settings->full_response ?? ''), true)['data']['crm_id'] ?? null;
        if (!$vmns || !$crm) {
            return ['skipped' => !$vmns ? 'no VMNs' : 'no CRM id'];
        }

        $service = new InboundCallService();
        $counts = ['created' => 0, 'updated' => 0, 'ignored' => 0, 'invalid' => 0];
        $to = Carbon::now();
        $from = $to->copy()->subHours(max(1, (int)$this->option('hours')));

        for ($page = 1; $page <= (int)$this->option('max-pages'); $page++) {
            $response = Http::withHeaders(['Secret-Key' => (string)config('services.telephony.secret')])
                ->acceptJson()
                ->timeout(30)
                // network errors, 429 (after Retry-After) and 5xx are retried 3 times
                ->retry(3, function (int $attempt, \Throwable $e) {
                    $wait = $e instanceof \Illuminate\Http\Client\RequestException ? (int)$e->response->header('Retry-After') : 0;
                    return min(30, max($wait, $attempt * 2)) * 1000;
                }, function (\Throwable $e) {
                    return !$e instanceof \Illuminate\Http\Client\RequestException
                        || $e->response->status() === 429 || $e->response->serverError();
                }, false)
                ->post((string)config('services.telephony.incoming_url'), [
                    'vmn' => $vmns,
                    'crm' => [$crm],
                    'from' => $from->format('Y-m-d H:i:s'),
                    'to' => $to->format('Y-m-d H:i:s'),
                    'page' => $page,
                ]);

            $body = $response->json();
            if (!$response->successful() || !is_array($body) || (int)($body['status'] ?? 200) !== 200 || !isset($body['data'])) {
                Log::warning('[InboundCalls] Provider returned an error', ['http' => $response->status(), 'page' => $page,
                    'message' => is_array($body) ? ($body['message'] ?? null) : mb_substr((string)$response->body(), 0, 200)]);
                break;
            }

            foreach ((array)$body['data'] as $log) {
                $counts[$service->record((array)$log, 'pull')['result']]++;
            }

            $last = (int)($body['last_page'] ?? $body['total_pages'] ?? 0);
            if (!($last > $page || !empty($body['next_page']) || !empty($body['next_page_url']))) {
                break;
            }
        }

        return $counts;
    }
}
