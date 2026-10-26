<?php

namespace App\Services;

use App\Models\ObdCreditsHistory;
use App\Models\ObdDID;
use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * License Info tab actions: "Sync Did" and "Delete License". Each returns what was done, step by step, so the page
 * shows the real result instead of an unconditional "success".
 */
class LicenseService
{
    private const LICENSE_SERVER = 'https://lc.ametecsindia.com';

    /** The decrypted license data of this company (settings.full_response "data"), or null. */
    public static function licenseData(): ?array
    {
        $row = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $json = $row && !empty($row->full_response) ? json_decode($row->full_response, true) : null;
        return is_array($json['data'] ?? null) ? $json['data'] : null;
    }

    /**
     * This company has a stored license (the same test as the login: settings.full_response is not empty).
     * Each company has its own settings table, so this never looks at another company.
     */
    public static function hasLicense(): bool
    {
        return DB::table('settings')->whereNotNull('full_response')->where('full_response', '!=', '')->exists();
    }

    /**
     * Sync Did:
     *  1. DIDs: the DID voucher is checked with the license server; DIDs the server reports as removed are deleted
     *     (a DID whose voucher no longer exists here is removed, as before).
     *  2. Smart31 VMNs: the CRM's VMN groups are fetched and new ones added (also when there is no DID yet).
     *
     * @return array{ok: bool, message: string, steps: array}
     */
    public static function syncDids(): array
    {
        $data = self::licenseData();
        $crmId = $data['crm_id'] ?? null;
        if (!$data || !$crmId) {
            return ['ok' => false, 'message' => 'No active license data found. Activate the license first.', 'steps' => []];
        }
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'localhost';   // the license helper reads it
        }
        require_once base_path('vendor/ezywing/Helper.php');

        $steps = [];

        // 1. DIDs
        $didsBefore = ObdDID::count();
        $did = ObdDID::first();
        if (!$did) {
            $steps['dids'] = ['ok' => true, 'text' => 'No DIDs to check'];
        } else {
            $voucher = ObdCreditsHistory::find($did->voucher_id);
            if (!$voucher) {
                ObdDID::query()->delete();
                $steps['dids'] = ['ok' => true, 'text' => "DID voucher not found: {$didsBefore} DID(s) removed"];
            } else {
                $checked = false;
                try {
                    $checked = \License\Helpers\Helper::didVoucherDailyCheck($voucher->voucher_code, $crmId);
                } catch (\Throwable $e) {
                    Log::warning('[SyncDid] DID check failed: ' . $e->getMessage());
                }
                $removed = $didsBefore - ObdDID::count();
                $steps['dids'] = $checked
                    ? ['ok' => true, 'text' => $removed > 0 ? "DIDs checked: {$removed} removed by the license server" : 'DIDs checked: all ' . $didsBefore . ' still active']
                    : ['ok' => false, 'text' => 'The license server did not confirm the DIDs. Try again later.'];
            }
        }

        // 2. Smart31 VMNs
        try {
            $http = Http::timeout(30)->connectTimeout(15)->acceptJson();
            if (is_file(public_path('ssl/cacert.pem'))) {
                $http = $http->withOptions(['verify' => public_path('ssl/cacert.pem')]);   // the bundled CA list (PHP has none here)
            }
            $res = $http->get(self::LICENSE_SERVER . '/api/smart31-details/' . rawurlencode($crmId));
            if (!$res->successful()) {
                $steps['smart31'] = ['ok' => false, 'text' => 'Smart31 VMNs: the license server answered HTTP ' . $res->status()];
            } else {
                $added = 0;
                foreach ((array)($res->object()->vmn_details ?? []) as $group) {
                    if (empty($group->vmn)) continue;
                    $exists = DB::table('smart31_vmn_numbers')->where('vmn', $group->vmn)->whereDate('usage_date', $group->usage_date ?? null)->exists();
                    if ($exists) continue;
                    DB::table('smart31_vmn_numbers')->insert([
                        'vmn' => $group->vmn,
                        'usage_date' => $group->usage_date ?? null,
                        'webrtc' => $group->webrtc ?? 0,
                        'c2c' => $group->c2c ?? 0,
                        'obd' => $group->obd ?? 0,
                        'verified' => $group->verified ?? 0,
                        'group_id' => $group->group_id ?? null,
                        'type' => $group->type ?? null,
                        'created_at' => $group->created_at ?? now(),
                        'updated_at' => $group->updated_at ?? now(),
                        'group_name' => $group->group_name ?? null,
                        'assigned_groups' => $group->assigned_groups ?? null,
                    ]);
                    $added++;
                }
                $steps['smart31'] = ['ok' => true, 'text' => $added > 0 ? "Smart31 VMNs: {$added} added" : 'Smart31 VMNs: up to date'];
            }
        } catch (\Throwable $e) {
            Log::warning('[SyncDid] Smart31 fetch failed: ' . $e->getMessage());
            $steps['smart31'] = ['ok' => false, 'text' => 'Smart31 VMNs: could not reach the license server'];
        }

        DB::table('settings')->update(['daily_check' => date('Y-m-d')]);

        $ok = collect($steps)->every(fn ($s) => $s['ok']);
        $summary = implode('. ', array_column($steps, 'text')) . '.';
        return ['ok' => $ok, 'message' => ($ok ? 'DID sync completed. ' : 'DID sync finished with problems. ') . $summary, 'steps' => $steps];
    }

    /**
     * Delete License: this company's stored license is cleared. The license key in .env belongs to the installation,
     * so it is cleared only from the main installation's own page (never from another company's site), by replacing
     * exactly the "license=" line, written atomically.
     *
     * @return array{ok: bool, message: string, env_cleared: bool}
     */
    public static function deleteLicense(?string $envPath = null): array
    {
        $updated = DB::table('settings')->update(['full_response' => null, 'user_count' => null, 'features' => null]);

        $envCleared = false;
        $context = Tenancy::context();
        $isMainInstallation = isset($context['database']) && $context['database'] === env('DB_DATABASE');
        if ($isMainInstallation) {
            $envCleared = self::clearEnvLicense($envPath ?? base_path('.env'));
        }
        return [
            'ok' => true,
            'message' => 'License deleted. Activate a license to use LeadGER again.',
            'env_cleared' => $envCleared,
            'settings_rows' => $updated,
        ];
    }

    /** Set license="" in an .env file, touching only the line that starts with license= (atomic write). */
    public static function clearEnvLicense(string $path): bool
    {
        if (!is_file($path) || !is_writable($path)) {
            return false;
        }
        $content = file_get_contents($path);
        $count = 0;
        $new = preg_replace('/^license=[^\r\n]*/m', 'license=""', $content, -1, $count);
        if ($new === null || $count === 0 || $new === $content) {
            return $count > 0;   // already empty, or no license line
        }
        $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
        if (file_put_contents($tmp, $new, LOCK_EX) === false) {
            @unlink($tmp);
            return false;
        }
        if (!@rename($tmp, $path)) {
            // Windows cannot always replace a file that is open: fall back to writing in place
            @unlink($tmp);
            return file_put_contents($path, $new, LOCK_EX) !== false;
        }
        return true;
    }
}
