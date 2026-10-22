<?php

namespace App\Http\Controllers\APIs;

use App\Http\Controllers\Controller;
use App\Models\InboundWebhookEvent;
use App\Services\InboundCallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Provider events for inbound calls to the company's VMNs (ringing / answered / ended).
 * GET or POST /api/telephony/inbound-call with the secret in the Secret-Key header (or X-Webhook-Secret, X-Api-Key,
 * or ?secret= when the provider cannot send headers). JSON, form or query string; one event or a batch.
 * Every request is first stored in inbound_webhook_events (secrets removed), also when it is refused, then processed
 * right away (no queue). Idempotent: every event of a call_id updates the same inbound_calls row.
 */
class InboundCallWebhookController extends Controller
{
    private const SECRET_FIELDS = ['secret', 'secret_key', 'token', 'api_key'];

    public function handle(Request $request, InboundCallService $service)
    {
        $event = $this->capture($request);

        $allowed = array_filter(array_map('trim', explode(',', (string)config('services.telephony.inbound_ips'))));
        if ($allowed && !in_array($request->ip(), $allowed, true)) {
            Log::warning('[InboundCalls] Webhook refused: IP not allowed', ['ip' => $request->ip(), 'event' => $event?->id]);
            $this->finish($event, 'failed', 'IP not allowed', false);
            return response()->json(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $secret = (string)(config('services.telephony.inbound_secret') ?: config('services.telephony.secret'));
        $given = (string)($request->header('Secret-Key') ?? $request->header('X-Webhook-Secret') ?? $request->header('X-Api-Key')
            ?? $request->input('secret') ?? $request->input('secret_key') ?? '');
        if ($secret === '' || $given === '' || !hash_equals($secret, $given)) {
            Log::warning('[InboundCalls] Webhook refused: ' . ($given === '' ? 'no secret sent' : 'wrong secret'), ['ip' => $request->ip(), 'event' => $event?->id]);
            $this->finish($event, 'failed', $given === '' ? 'No secret sent' : 'Wrong secret', false);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $body = $request->json()->all() ?: $request->except(self::SECRET_FIELDS);
        $results = [];
        foreach (array_slice(InboundCallService::events($body), 0, 200) as $payload) {
            try {
                $r = $service->record($payload, 'webhook');
            } catch (\Throwable $e) {
                Log::error('[InboundCalls] Webhook event failed', ['event' => $event?->id, 'error' => $e->getMessage()]);
                $r = ['result' => 'failed', 'call' => null, 'message' => 'Processing error'];
            }
            $results[] = ['result' => $r['result'], 'call_id' => $r['call']->call_id ?? (InboundCallService::extract($payload)['call_id'] ?: null),
                'message' => $r['message'] ?? null, 'id' => $r['call']->id ?? null];
        }

        $saved = collect($results)->whereIn('result', ['created', 'updated']);
        $status = $saved->isNotEmpty() ? 'processed' : (collect($results)->contains('result', 'failed') ? 'failed'
            : (collect($results)->contains('result', 'ignored') ? 'ignored' : 'failed'));
        $reason = $status === 'processed' ? collect($results)->pluck('message')->first(fn ($m) => str_contains((string)$m, 'unmapped'))
            : (collect($results)->pluck('message')->filter()->first() ?? 'No call_id in the request');
        $this->finish($event, $status, $reason, true, $saved->first()['call_id'] ?? ($results[0]['call_id'] ?? null), $saved->first()['id'] ?? null);

        // 200 for every authenticated event that has a call_id (also "ignored"), so the provider does not retry it
        $invalid = !$results || collect($results)->every(fn ($r) => $r['result'] === 'invalid');
        $failed = !$invalid && collect($results)->every(fn ($r) => $r['result'] === 'failed');
        return response()->json([
            'status' => $invalid || $failed ? 'error' : 'ok',
            'results' => collect($results)->map(fn ($r) => ['result' => $r['result'], 'call_id' => $r['call_id']])->all(),
        ], $invalid ? 422 : ($failed ? 500 : 200));
    }

    /** Store the request as received (headers and body without secrets). Never stops the webhook. */
    private function capture(Request $request): ?InboundWebhookEvent
    {
        try {
            $headers = [];
            foreach ($request->headers->all() as $name => $values) {
                $headers[$name] = preg_match('/secret|token|key|auth|cookie|signature|password/i', $name) ? '[removed]' : implode(', ', $values);
            }
            $body = (string)$request->getContent();
            if ($body === '' || $request->isMethod('GET')) {
                $body = json_encode($request->except(self::SECRET_FIELDS), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $json = json_decode($body, true);
                if (is_array($json)) {
                    $body = json_encode(InboundCallService::withoutSecrets($json), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } else {
                    parse_str($body, $form);
                    $body = $form ? http_build_query(array_diff_key($form, array_flip(self::SECRET_FIELDS))) : $body;
                }
            }
            return InboundWebhookEvent::create([
                'received_at' => now(), 'source' => 'webhook', 'http_method' => $request->method(), 'source_ip' => $request->ip(),
                'headers' => $headers, 'raw_body' => mb_substr($body, 0, 65000), 'processing_status' => 'received',
            ]);
        } catch (\Throwable $e) {
            Log::error('[InboundCalls] Could not store the webhook request', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function finish(?InboundWebhookEvent $event, string $status, ?string $reason, bool $valid, ?string $callId = null, ?int $inboundCallId = null): void
    {
        if (!$event) return;
        try {
            $event->update(['processing_status' => $status, 'failure_reason' => $reason ? mb_substr($reason, 0, 255) : null,
                'signature_valid' => $valid, 'call_id' => $callId, 'inbound_call_id' => $inboundCallId]);
        } catch (\Throwable $e) {
            Log::error('[InboundCalls] Could not update the webhook event', ['event' => $event->id, 'error' => $e->getMessage()]);
        }
    }
}
