<?php

namespace App\Http\Controllers\APIs;

use App\Http\Controllers\Controller;
use App\Services\InboundCallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Provider events for inbound calls to the company's VMNs (ringing / answered / ended).
 * POST /api/telephony/inbound-call with the provider's Secret-Key header (or X-Webhook-Secret, or ?secret=).
 * Idempotent: every event of a call_id updates the same inbound_calls row.
 */
class InboundCallWebhookController extends Controller
{
    public function handle(Request $request, InboundCallService $service)
    {
        $allowed = array_filter(array_map('trim', explode(',', (string)config('services.telephony.inbound_ips'))));
        if ($allowed && !in_array($request->ip(), $allowed, true)) {
            Log::warning('[InboundCalls] Webhook refused: IP not allowed', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Forbidden'], 403);
        }

        $secret = (string)config('services.telephony.secret');
        $given = (string)($request->header('Secret-Key') ?? $request->header('X-Webhook-Secret') ?? $request->query('secret', ''));
        if ($secret === '' || $given === '' || !hash_equals($secret, $given)) {
            Log::warning('[InboundCalls] Webhook refused: missing or wrong secret', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->json()->all() ?: $request->except('secret');
        $events = array_is_list($payload) ? $payload : [$payload];   // one event or a batch
        $results = [];
        foreach (array_slice($events, 0, 200) as $event) {
            $r = is_array($event) ? $service->record($event, 'webhook') : ['result' => 'invalid', 'call' => null];
            $results[] = ['result' => $r['result'], 'call_id' => $r['call']->call_id ?? (is_array($event) ? ($event['call_id'] ?? null) : null)];
        }

        $invalid = collect($results)->every(fn ($r) => $r['result'] === 'invalid');
        return response()->json(['status' => $invalid ? 'error' : 'ok', 'results' => $results], $invalid ? 422 : 200);
    }
}
