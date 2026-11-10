<?php

namespace App\Http\Controllers\Click2Call;

use App\Http\Controllers\Controller;
use App\Models\C2c\Call;
use App\Services\Click2Call\CallResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/click2call/webhook - the provider sends the call result and recording URL.
 * Fields accepted (as in LeadGER's CallRecordingService::processWebhook): ref_id / REF_ID / reference_id,
 * call_id / CALL_ID / uuid, A_DIAL_STATUS, B_DIAL_STATUS, B_PARTY_CONNECTED_TIME, B_PARTY_END_TIME,
 * talk_duration / billsec / duration, recording_url / RecordVoice / ...
 *
 * Differences from LeadGER (on purpose): the secret is REQUIRED, and a call is matched only by our ref_id or the
 * provider's call id - never by "the latest call to this number".
 */
class WebhookController extends Controller
{
    public function handle(Request $request, CallResult $result)
    {
        $ips = (array) config('click2call.webhook_ips');
        if ($ips && !in_array($request->ip(), $ips, true)) {
            Log::warning('[Click2Call] webhook refused: IP not allowed', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Forbidden'], 403);
        }
        $secret = (string) config('click2call.webhook_secret');
        $given = (string) ($request->header('Secret-Key') ?? $request->header('X-Webhook-Secret') ?? $request->header('X-Api-Key') ?? '');
        if ($secret === '' || $given === '' || !hash_equals($secret, $given)) {
            Log::warning('[Click2Call] webhook refused: missing or wrong secret', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized: Invalid webhook secret'], 401);
        }

        $p = $request->json()->all() ?: $request->except('secret');
        $ref = $p['ref_id'] ?? $p['REF_ID'] ?? $p['reference_id'] ?? $p['refId'] ?? null;
        $providerId = $p['call_id'] ?? $p['CALL_ID'] ?? $p['CallId'] ?? $p['uniqueid'] ?? $p['unique_id'] ?? $p['uuid'] ?? null;

        $call = ($ref ? Call::where('ref_id', $ref)->first() : null)
            ?? ($providerId ? Call::where('provider_call_id', $providerId)->first() : null);
        if (!$call) {
            Log::info('[Click2Call] webhook for an unknown call', ['ref_id' => $ref]);   // no phone numbers in logs
            return response()->json(['status' => 'ignored', 'message' => 'Unknown call'], 200);   // 200: no endless retries
        }

        $call = $result->apply($call, $p, null, true);   // the provider posts the result when the call has ended
        return response()->json(['status' => 'success', 'call_state' => $call->call_state]);
    }
}
