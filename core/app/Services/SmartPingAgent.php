<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The user's WebRTC agent at SmartPing (smartping_user_id / smartping_password). Every WebRTC call is placed for
 * this agent: without it the gateway refuses the call ("agent_id must be a string").
 * The company account comes from SMARTPING_COMPANY_ID (the code used two different literals; live, 23 Oct 2026,
 * SmartPing answered "account does not exist" to both).
 */
class SmartPingAgent
{
    /** @return array{ok: bool, message: string, agent_id: ?string} */
    public static function create(User $user, ?string $password = null): array
    {
        $mobile = \App\Http\Controllers\Dashboard\WebRTCController::nationalNumber($user->mobile);
        if (!$mobile) {
            return ['ok' => false, 'message' => 'the user has no valid 10-digit mobile number', 'agent_id' => null];
        }
        $company = trim((string)config('services.smartping.company_id'));
        if ($company === '') {
            return ['ok' => false, 'message' => 'SMARTPING_COMPANY_ID is not set in .env', 'agent_id' => null];
        }

        try {
            $response = Http::acceptJson()->timeout(30)->post(rtrim((string)config('services.smartping.url'), '/') . '/api/add-agent', [
                'full_name' => $user->name,
                'mobile_number' => $mobile,
                'password' => $password ?: Str::random(14),   // the agent's own password, not the LeadGER login
                'company_id' => $company,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[SmartPing] add-agent not reachable', ['user' => $user->id, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'SmartPing not reachable', 'agent_id' => null];
        }

        $json = $response->json();
        if (is_array($json) && isset($json['user_id'], $json['password'])) {
            $user->forceFill(['smartping_user_id' => (string)$json['user_id'], 'smartping_password' => (string)$json['password']])->save();
            Log::info('[SmartPing] agent created', ['user' => $user->id, 'agent' => (string)$json['user_id']]);
            return ['ok' => true, 'message' => 'agent created', 'agent_id' => (string)$json['user_id']];
        }

        $why = is_array($json) ? (string)($json['error'] ?? $json['message'] ?? json_encode($json)) : mb_substr(strip_tags((string)$response->body()), 0, 160);
        $why = trim(preg_replace('/^SQLSTATE\[\w+\]: <<Unknown error>>: \d+ (Error: )?/', '', $why));
        Log::warning('[SmartPing] add-agent refused', ['user' => $user->id, 'http' => $response->status(), 'reason' => $why]);
        return ['ok' => false, 'message' => 'SmartPing: ' . $why . ' (HTTP ' . $response->status() . ')', 'agent_id' => null];
    }
}
