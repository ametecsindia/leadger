<?php

namespace App\Services\Click2Call;

use App\Models\C2c\Vmn;
use App\Models\C2c\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Add Voucher: a voucher code from Ametecs gives the company its VMN numbers. Same logic as LeadGER
 * (ObdController::did_add + License\Helpers\Helper::didVoucherValidate / didVoucherUsed):
 *
 *  1. GET {license_url}/api/did_voucher/{code}/{crm_id}   (header Secret-Key) -> must answer status 200
 *  2. GET {license_url}/api/did_voucher_used/{code}/{ip}  -> marks the voucher as used
 *  3. In ONE transaction: the current numbers go to c2c_vmn_history, then are REPLACED by the voucher's numbers:
 *       data.click2call_did, data.obd_did, data.webrtc_did  (comma-separated)  -> flags per number
 *       data.tc_verified (comma-separated), data.expiry, data.smart_groups (Smart31 numbers, today's only)
 *     Teams are empty afterwards: an admin assigns each number to teams.
 */
class VoucherService
{
    /** @return array{ok: bool, message: string, voucher?: Voucher} */
    public function add(string $code, int $userId, string $ip): array
    {
        $code = trim($code);
        if ($code === '' || !preg_match('/^[A-Za-z0-9_-]{4,100}$/', $code)) {
            return ['ok' => false, 'message' => 'Enter a valid voucher code.'];
        }

        $result = $this->validate($code);
        if ($result === null) {
            return ['ok' => false, 'message' => 'Something went wrong'];   // LeadGER's message when the server does not answer
        }
        if ((int) ($result['status'] ?? 0) !== 200) {
            return ['ok' => false, 'message' => (string) ($result['message'] ?? 'This voucher is not valid.')];
        }
        $this->markUsed($code, $ip);
        $data = (array) ($result['data'] ?? []);

        try {
            $voucher = DB::transaction(fn () => $this->import($code, $data, $userId));
        } catch (\Throwable $e) {
            Log::error('[Click2Call] Failed to store voucher: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Error saving voucher data. Please try again.'];
        }
        return ['ok' => true, 'message' => "DID's added Successfully", 'voucher' => $voucher];
    }

    /** The voucher's data from the license server, or null when it cannot be reached. */
    public function validate(string $code): ?array
    {
        try {
            $res = $this->license()->get('/api/did_voucher/' . rawurlencode($code) . '/' . rawurlencode((string) config('click2call.crm_id')));
            $json = $res->json();
            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            Log::warning('[Click2Call] voucher server unreachable: ' . $e->getMessage());
            return null;
        }
    }

    public function markUsed(string $code, string $ip): bool
    {
        try {
            $json = $this->license()->get('/api/did_voucher_used/' . rawurlencode($code) . '/' . rawurlencode($ip))->json();
            return is_array($json) && (int) ($json['status'] ?? 0) === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function license()
    {
        return Http::baseUrl(rtrim((string) config('click2call.license_url'), '/'))
            ->withHeaders(['Secret-Key' => (string) config('click2call.license_secret')])
            ->acceptJson()
            ->timeout(30);
    }

    /** Replace the numbers with the voucher's (inside the caller's transaction). */
    private function import(string $code, array $data, int $userId): Voucher
    {
        $list = fn ($key) => array_values(array_filter(array_map(
            fn ($n) => VmnService::normalize($n), explode(',', (string) ($data[$key] ?? ''))
        )));
        $click2call = $list('click2call_did');
        $obd = $list('obd_did');
        $webrtc = $list('webrtc_did');
        $verified = $list('tc_verified');
        $all = array_values(array_unique(array_merge($click2call, $obd, $webrtc)));
        $expiry = !empty($data['expiry']) ? date('Y-m-d', strtotime((string) $data['expiry'])) : null;
        $now = now();

        // 1. keep the old numbers (with their team assignment) in the history, then remove them
        Vmn::query()->orderBy('id')->each(function (Vmn $v) use ($now) {
            DB::table('c2c_vmn_history')->insert([
                'virtual_number' => $v->virtual_number, 'click2call' => $v->click2call, 'webrtc' => $v->webrtc, 'obd' => $v->obd,
                'tc_verified' => $v->tc_verified, 'team_ids' => json_encode($v->team_ids ?? []),
                'expires_at' => $v->expires_at, 'voucher_id' => $v->voucher_id, 'replaced_at' => $now,
                'created_at' => $v->created_at, 'updated_at' => $now,
            ]);
        });
        Vmn::query()->delete();
        DB::table('c2c_smart31_numbers')->delete();

        // 2. the voucher's numbers
        $voucher = Voucher::create(['voucher_code' => $code, 'numbers' => count($all), 'expires_at' => $expiry, 'created_by' => $userId]);
        foreach ($all as $number) {
            Vmn::create([
                'virtual_number' => $number,
                'click2call' => in_array($number, $click2call, true),
                'obd' => in_array($number, $obd, true),
                'webrtc' => in_array($number, $webrtc, true),
                'tc_verified' => in_array($number, $verified, true),
                'team_ids' => [],                 // the admin assigns teams afterwards
                'expires_at' => $expiry,
                'voucher_id' => $voucher->id,
                'created_by' => $userId,
            ]);
        }

        // 3. Smart31 numbers: only those for today (LeadGER: usage_date === today)
        $today = $now->toDateString();
        foreach ((array) ($data['smart_groups'] ?? []) as $g) {
            $g = (array) $g;
            if (($g['usage_date'] ?? null) !== $today || empty($g['vmn'])) {
                continue;
            }
            DB::table('c2c_smart31_numbers')->updateOrInsert(
                ['vmn' => VmnService::normalize($g['vmn']), 'usage_date' => $today],
                ['c2c' => (bool) ($g['c2c'] ?? false), 'webrtc' => (bool) ($g['webrtc'] ?? false), 'obd' => (bool) ($g['obd'] ?? false),
                 'verified' => (bool) ($g['verified'] ?? false), 'group_id' => $g['group_id'] ?? null, 'group_name' => $g['group_name'] ?? null,
                 'type' => $g['type'] ?? null, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        return $voucher;
    }
}
