<?php

namespace App\Services\Click2Call;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The two provider APIs LeadGER uses.
 *
 * start():    POST {start_url}  JSON {did, a_party_no, b_party_no, ref_id, record, recording, record_call}
 *             LeadGER: Helper::click2callProviderRequest. Success = JSON without an "error"/"errors" key.
 *             Added here: TLS verification, a timeout, and the HTTP status must be 2xx.
 * callData(): GET {cdr_url}/{ref_id}/{vmn} with header Secret-Key -> {status: 200, data: {response: CDR, recording_url}}
 *             LeadGER: DialerController::fetchGatewayCallRecord / FetchPendingRecordingsCommand.
 */
class Click2CallGateway
{
    /** @return array{ok: bool, provider_call_id: ?string, message: string} */
    public function start(string $vmn, string $agentNumber, string $customerNumber, string $refId): array
    {
        try {
            $res = Http::timeout((int) config('click2call.timeout'))->connectTimeout(5)->acceptJson()
                ->post((string) config('click2call.start_url'), [
                    'did' => VmnService::normalize($vmn),
                    'a_party_no' => VmnService::normalize($agentNumber),
                    'b_party_no' => VmnService::normalize($customerNumber),
                    'ref_id' => $refId,
                    'record' => 1, 'recording' => 1, 'record_call' => 1,   // LeadGER sends all three
                ]);
        } catch (\Throwable $e) {
            Log::warning('[Click2Call] gateway connection failed', ['ref_id' => $refId, 'error' => $e->getMessage()]);
            return ['ok' => false, 'provider_call_id' => null, 'message' => 'Click2Call gateway connection failed. Please try again.'];
        }

        $body = $res->json();
        if (!is_array($body)) {
            Log::warning('[Click2Call] invalid start response', ['ref_id' => $refId, 'http' => $res->status()]);
            return ['ok' => false, 'provider_call_id' => null, 'message' => 'Invalid JSON response from initiate-call'];
        }
        if (!$res->successful() || isset($body['error']) || isset($body['errors'])) {
            Log::warning('[Click2Call] start refused', ['ref_id' => $refId, 'http' => $res->status()]);
            $why = is_string($body['error'] ?? null) ? $body['error'] : (is_string($body['message'] ?? null) ? $body['message'] : null);
            return ['ok' => false, 'provider_call_id' => null, 'message' => $why ?: 'Error indicated in response'];
        }

        $id = $body['call_id'] ?? $body['callId'] ?? $body['uuid'] ?? ($body['response']['call_id'] ?? $refId);
        return ['ok' => true, 'provider_call_id' => (string) $id, 'message' => 'Call initiated successfully'];
    }

    /** @return array{cdr: array, recording_url: ?string}|null  null = nothing (yet) or the gateway did not answer */
    public function callData(string $refId, string $vmn, int $timeout = 3): ?array
    {
        try {
            $res = Http::timeout($timeout)->acceptJson()
                ->withHeaders(['Secret-Key' => (string) config('click2call.gateway_secret')])
                ->get(rtrim((string) config('click2call.cdr_url'), '/') . '/' . rawurlencode($refId) . '/' . rawurlencode(VmnService::normalize($vmn) ?: 'default'));
        } catch (\Throwable $e) {
            return null;
        }
        $json = $res->json();
        if (!is_array($json) || (int) ($json['status'] ?? 0) !== 200 || !isset($json['data'])) {
            return null;
        }
        $data = (array) $json['data'];
        $cdr = $data['response'] ?? [];
        if (is_string($cdr)) {
            $cdr = json_decode($cdr, true) ?: [];
        }
        $cdr = (array) $cdr;
        $recording = CallResult::recordingUrl($data) ?? CallResult::recordingUrl($cdr);
        return ['cdr' => $cdr, 'recording_url' => $recording];
    }
}
