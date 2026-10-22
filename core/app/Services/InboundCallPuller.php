<?php

namespace App\Services;

use App\Models\InboundWebhookEvent;
use App\Support\Tenancy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The provider's inbound call logs (incoming_data: the same endpoint and Secret-Key as the Incoming VMN fetch) for
 * the current company: every Add Voucher VMN and the company's CRM id. Rows are upserted by call_id; a row that
 * cannot be saved is kept in inbound_webhook_events (source "pull") for inbound:backfill.
 */
class InboundCallPuller
{
    public const HEARTBEAT_KEY = 'inbound_calls:last_pull:';

    /** @return array counts (fetched, created, updated, ignored, invalid, failed) or ['skipped' => reason] / ['error' => message] */
    public function pull(Carbon $from, Carbon $to, int $maxPages = 50): array
    {
        $request = $this->requestBody();
        if (isset($request['skipped'])) {
            return $this->heartbeat($request);
        }

        $service = new InboundCallService();
        $counts = ['fetched' => 0, 'created' => 0, 'updated' => 0, 'ignored' => 0, 'invalid' => 0, 'failed' => 0];
        for ($page = 1; $page <= $maxPages; $page++) {
            $res = $this->fetch($request + $this->window($from, $to) + ['page' => $page]);
            if (isset($res['error'])) {
                Log::warning('[InboundCalls] Provider returned an error', ['page' => $page, 'error' => $res['error']]);
                return $this->heartbeat($counts + ['error' => $res['error']]);
            }
            foreach ($res['rows'] as $row) {
                $counts['fetched']++;
                try {
                    $r = $service->record($row, 'pull');
                } catch (\Throwable $e) {
                    Log::error('[InboundCalls] Pulled call could not be saved', ['error' => $e->getMessage()]);
                    $r = ['result' => 'failed', 'message' => mb_substr($e->getMessage(), 0, 200)];
                }
                $counts[$r['result']]++;
                if (!in_array($r['result'], ['created', 'updated'], true)) {
                    $this->keep($row, $r['result'], (string)($r['message'] ?? ''));
                }
            }
            if (!$res['more']) {
                break;
            }
        }
        Log::info('[InboundCalls] Pull run', $counts + ['from' => $from->format('Y-m-d H:i'), 'to' => $to->format('Y-m-d H:i')]);
        return $this->heartbeat($counts);
    }

    /** What the provider answers for the window (diagnose): HTTP status, keys, row count; no phone numbers. */
    public function probe(Carbon $from, Carbon $to): array
    {
        $request = $this->requestBody();
        if (isset($request['skipped'])) {
            return $request;
        }
        $out = ['vmns_sent' => count($request['vmn'])];
        foreach (['with dates' => $request + $this->window($from, $to) + ['page' => 1], 'without dates (old Incoming VMN fetch)' => $request] as $name => $body) {
            $res = $this->fetch($body);
            $service = new InboundCallService();
            $rows = $res['rows'] ?? [];
            $out[$name] = isset($res['error']) ? ['error' => $res['error']] : [
                'rows' => count($rows),
                'row_fields' => $rows ? array_keys((array)$rows[0]) : [],
                'rows_with_call_id' => collect($rows)->filter(fn ($r) => InboundCallService::extract((array)$r)['call_id'] !== '')->count(),
                'rows_whose_vmn_is_in_add_voucher' => collect($rows)->filter(fn ($r) => $service->isCompanyVmn(InboundCallService::extract((array)$r)['vmn']))->count(),
                'vmns_in_rows' => collect($rows)->map(fn ($r) => InboundCallService::extract((array)$r)['vmn'])->filter()->unique()->values()->all(),
                'start_times' => collect($rows)->map(fn ($r) => optional(InboundCallService::extract((array)$r)['start_time'])->format('Y-m-d H:i'))->filter()->sort()->values()
                    ->pipe(fn ($c) => $c->isEmpty() ? '-' : $c->first() . ' .. ' . $c->last()),
            ];
        }
        return $out;
    }

    /** Last run of the scheduled pull for the current company: ['at' => 'Y-m-d H:i:s', ...counts / error] or null. */
    public static function lastRun(): ?array
    {
        return Cache::get(self::HEARTBEAT_KEY . Tenancy::key());
    }

    /* ------------------------------------------------------------------ */

    private function requestBody(): array
    {
        $vmns = DB::table('obd_did')->pluck('virtual_number')->filter()->unique()->values()->all();
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $crm = json_decode((string)($settings->full_response ?? ''), true)['data']['crm_id'] ?? null;
        if (!$vmns || !$crm) {
            return ['skipped' => !$vmns ? 'No VMNs in Add Voucher' : 'No CRM id in the license settings'];
        }
        return ['vmn' => $vmns, 'crm' => [$crm]];
    }

    /** from / to in the provider's timezone */
    private function window(Carbon $from, Carbon $to): array
    {
        $tz = config('services.telephony.timezone') ?: config('app.timezone');
        return ['from' => $from->copy()->setTimezone($tz)->format('Y-m-d H:i:s'), 'to' => $to->copy()->setTimezone($tz)->format('Y-m-d H:i:s')];
    }

    /** @return array ['rows' => list, 'more' => bool] or ['error' => message] */
    private function fetch(array $body): array
    {
        try {
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
                ->post((string)config('services.telephony.incoming_url'), $body);
        } catch (\Throwable $e) {
            return ['error' => 'Provider not reachable: ' . mb_substr($e->getMessage(), 0, 160)];
        }

        $json = $response->json();
        if (!$response->successful() || !is_array($json)) {
            return ['error' => 'HTTP ' . $response->status() . ($json === null ? ', not JSON: ' . mb_substr(strip_tags((string)$response->body()), 0, 120) : '')];
        }
        $status = $json['status'] ?? 200;
        if (!in_array(strtolower((string)$status), ['200', 'success', 'ok', '1', 'true'], true)) {
            return ['error' => 'Provider status ' . json_encode($status) . (is_string($json['message'] ?? null) ? ': ' . mb_substr($json['message'], 0, 160) : '')];
        }

        // data: a list of call logs, or a page object {data: [...], last_page / next_page_url}
        $data = $json['data'] ?? [];
        $paging = $json;
        if (is_array($data) && !array_is_list($data) && isset($data['data'])) {
            $paging = $data;
            $data = $data['data'];
        }
        $rows = array_values(array_filter(array_map(fn ($r) => is_object($r) ? (array)$r : $r, (array)$data), 'is_array'));
        $page = (int)($body['page'] ?? 1);
        $last = (int)($paging['last_page'] ?? $paging['total_pages'] ?? 0);
        $more = isset($body['page']) && $rows && ($last > $page || !empty($paging['next_page']) || !empty($paging['next_page_url']));
        return ['rows' => $rows, 'more' => $more];
    }

    /** A pulled row that was not saved, once per call (re-processed by inbound:backfill). */
    private function keep(array $row, string $result, string $message): void
    {
        $callId = InboundCallService::extract($row)['call_id'] ?: null;
        $raw = json_encode(InboundCallService::withoutSecrets($row), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $saved = InboundWebhookEvent::where('source', 'pull')->where('call_id', $callId)
            ->when(!$callId, fn ($q) => $q->where('raw_body', $raw))->first();
        $fields = ['processing_status' => $result === 'failed' ? 'failed' : 'ignored', 'failure_reason' => mb_substr($message ?: $result, 0, 255)];
        $saved ? $saved->update($fields) : InboundWebhookEvent::create($fields + [
            'received_at' => now(), 'source' => 'pull', 'raw_body' => $raw, 'signature_valid' => true, 'call_id' => $callId,
        ]);
    }

    private function heartbeat(array $result): array
    {
        Cache::put(self::HEARTBEAT_KEY . Tenancy::key(), ['at' => now()->format('Y-m-d H:i:s')] + $result, now()->addDays(7));
        return $result;
    }
}
