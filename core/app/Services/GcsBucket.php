<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Call recordings in a private Google Cloud Storage bucket, in plain PHP (curl + openssl, no extra package).
 *
 * - recordings:to-bucket copies each recording's audio into the bucket (recordings/<month>/<CRM ID>/<call date>/) and notes it in the
 *   company's own table recording_bucket_files (source_code + log_id, like the Recordings page rows).
 * - Play / Download of such a recording (access rules are checked by LeadGER first): with a key file, a redirect to a
 *   signed link that works for 10 minutes only; with the server's own account, the audio is sent through LeadGER.
 *
 * Login: GCS_KEY_FILE (service account JSON key, outside the web folder), or GCS_USE_SERVER_ACCOUNT=true on a Google
 * Cloud server (the VM's own service account, no key file). Off (everything works as before) until
 * GCS_RECORDINGS_BUCKET and one of the two are set.
 */
class GcsBucket
{
    public const TABLE = 'recording_bucket_files';
    private const API = 'https://storage.googleapis.com';
    private const METADATA_TOKEN = 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token';

    public static function enabled(): bool
    {
        return (string)config('services.gcs.bucket') !== '' && (self::hasKeyFile() || self::serverAccount());
    }

    private static function hasKeyFile(): bool
    {
        $key = (string)config('services.gcs.key_file');
        return $key !== '' && is_readable($key);
    }

    /** The Google Cloud server's own service account is used (no key file). */
    private static function serverAccount(): bool
    {
        return !self::hasKeyFile() && (bool)config('services.gcs.server_account');
    }

    public static function bucket(): string
    {
        return (string)config('services.gcs.bucket');
    }

    /** The company's table that lists what is in the bucket (created on first use, in that company's database). */
    public static function ensureTable(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }
        Schema::create(self::TABLE, function ($t) {
            $t->id();
            $t->unsignedTinyInteger('source_code');            // 0 campaign call, 2 manual dial call, 3 standalone recording
            $t->unsignedBigInteger('log_id');                  // the call / recording row of that source
            $t->string('object', 500)->nullable();             // object name inside the bucket
            $t->unsignedBigInteger('size')->nullable();
            $t->string('status', 10)->default('done');         // done | failed
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->string('error', 255)->nullable();
            $t->timestamps();
            $t->unique(['source_code', 'log_id']);
        });
    }

    /** The bucket copy of one Recordings page row, or null. */
    public static function find(int $sourceCode, int $logId): ?object
    {
        if (!self::enabled()) {
            return null;
        }
        try {
            return DB::table(self::TABLE)->where('source_code', $sourceCode)->where('log_id', $logId)
                ->where('status', 'done')->first();
        } catch (\Throwable $e) {
            return null;   // table not created yet in this company
        }
    }

    /**
     * The bucket copy for Play / Download, or null (then the server / provider copy is used as before):
     * a 10-minute signed link (key file), or the audio sent through LeadGER (server account; Range is passed on, so
     * the player can seek).
     */
    public static function redirect(int $sourceCode, int $logId, bool $play)
    {
        $row = self::find($sourceCode, $logId);
        if (!$row || !$row->object) {
            return null;
        }
        $name = basename($row->object);
        try {
            if (!self::serverAccount()) {
                return redirect()->away(self::signedUrl($row->object, 600, $play ? null : $name));
            }
            $range = request()->header('Range');
            $res = self::http()->withToken(self::accessToken())->withOptions(['stream' => true])->timeout(120)
                ->withHeaders($range ? ['Range' => $range] : [])->get(self::objectUrl($row->object) . '?alt=media');
            if (!in_array($res->status(), [200, 206], true)) {
                throw new \RuntimeException('HTTP ' . $res->status());
            }
            $headers = array_filter([
                'Content-Type' => $res->header('Content-Type') ?: 'audio/wav',
                'Content-Length' => $res->header('Content-Length'),
                'Content-Range' => $res->header('Content-Range'),
                'Accept-Ranges' => 'bytes',
                'Content-Disposition' => ($play ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $name) . '"',
                'Cache-Control' => 'private, no-store',
            ]);
            $body = $res->toPsrResponse()->getBody();
            return response()->stream(function () use ($body) {
                while (!$body->eof()) {
                    echo $body->read(65536);
                    flush();
                }
            }, $res->status(), $headers);
        } catch (\Throwable $e) {
            Log::warning("[GcsBucket] Bucket copy could not be served for {$row->object}: " . $e->getMessage());
            return null;   // fall back to the server / provider copy
        }
    }

    /** Upload a local file or a string. Never overwrites an object that already exists. Returns the stored size. */
    public static function upload(string $object, ?string $path, ?string $contents, string $contentType): int
    {
        $body = $path !== null ? \GuzzleHttp\Psr7\Utils::streamFor(fopen($path, 'rb')) : (string)$contents;
        $expected = $path !== null ? (int)filesize($path) : strlen((string)$contents);
        $url = self::API . '/upload/storage/v1/b/' . rawurlencode(self::bucket()) . '/o?' . http_build_query([
            'uploadType' => 'media', 'name' => $object, 'ifGenerationMatch' => 0,
        ], '', '&', PHP_QUERY_RFC3986);
        $res = self::http()->withToken(self::accessToken())->withBody($body, $contentType)->timeout(300)->post($url);

        if ($res->status() === 412) {   // already in the bucket (an earlier run): keep it as it is
            $meta = self::http()->withToken(self::accessToken())->get(self::objectUrl($object));
            if ($meta->successful()) {
                return (int)$meta->json('size');
            }
        }
        if (!$res->successful()) {
            throw new \RuntimeException('Bucket upload failed: HTTP ' . $res->status() . ' ' . substr((string)$res->json('error.message'), 0, 150));
        }
        $size = (int)$res->json('size');
        if ($size !== $expected) {
            throw new \RuntimeException("Bucket upload incomplete: {$size} of {$expected} bytes");
        }
        return $size;
    }

    /** The audio of an object (for the bulk ZIP). */
    public static function contents(string $object): ?string
    {
        $res = self::http()->withToken(self::accessToken())->timeout(120)->get(self::objectUrl($object) . '?alt=media');
        return $res->successful() ? $res->body() : null;
    }

    /** Remove the bucket copy of a deleted recording (the row first, so it can never be served again). */
    public static function forget(int $sourceCode, int $logId): void
    {
        try {
            $row = DB::table(self::TABLE)->where('source_code', $sourceCode)->where('log_id', $logId)->first();
        } catch (\Throwable $e) {
            return;   // no table: nothing was ever copied
        }
        if (!$row) {
            return;
        }
        DB::table(self::TABLE)->where('id', $row->id)->delete();
        if ($row->object && self::enabled()) {
            try {
                $res = self::http()->withToken(self::accessToken())->delete(self::objectUrl($row->object));
                if (!$res->successful() && $res->status() !== 404) {
                    Log::warning("[GcsBucket] Deleted recording, but its bucket copy {$row->object} could not be removed: HTTP " . $res->status());
                }
            } catch (\Throwable $e) {
                Log::warning("[GcsBucket] Deleted recording, but its bucket copy {$row->object} could not be removed: " . $e->getMessage());
            }
        }
    }

    /**
     * V4 signed GET link (GOOG4-RSA-SHA256). $downloadName set = "Save as" that name; null = play in the browser.
     */
    public static function signedUrl(string $object, int $seconds, ?string $downloadName = null): string
    {
        $key = self::key();
        $now = gmdate('Ymd\THis\Z');
        $scope = substr($now, 0, 8) . '/auto/storage/goog4_request';
        $path = '/' . rawurlencode(self::bucket()) . '/' . implode('/', array_map('rawurlencode', explode('/', $object)));

        $query = [
            'X-Goog-Algorithm' => 'GOOG4-RSA-SHA256',
            'X-Goog-Credential' => $key['client_email'] . '/' . $scope,
            'X-Goog-Date' => $now,
            'X-Goog-Expires' => (string)$seconds,
            'X-Goog-SignedHeaders' => 'host',
        ];
        if ($downloadName !== null) {
            $query['response-content-disposition'] = 'attachment; filename="' . str_replace('"', '', $downloadName) . '"';
        }
        ksort($query);
        $canonicalQuery = implode('&', array_map(fn ($k, $v) => rawurlencode($k) . '=' . rawurlencode($v), array_keys($query), $query));

        $canonical = "GET\n{$path}\n{$canonicalQuery}\nhost:storage.googleapis.com\n\nhost\nUNSIGNED-PAYLOAD";
        $toSign = "GOOG4-RSA-SHA256\n{$now}\n{$scope}\n" . hash('sha256', $canonical);
        if (!openssl_sign($toSign, $signature, $key['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Could not sign the link (check GCS_KEY_FILE).');
        }
        return self::API . $path . '?' . $canonicalQuery . '&X-Goog-Signature=' . bin2hex($signature);
    }

    /** OAuth access token: of the key file's service account (cached 50 minutes) or of the server's own account. */
    private static function accessToken(): string
    {
        if (self::serverAccount()) {
            $cacheKey = 'gcs_server_access_token';
            if ($token = Cache::get($cacheKey)) {
                return $token;
            }
            // Google Cloud metadata server: only reachable from the server itself
            $res = Http::timeout(10)->withHeaders(['Metadata-Flavor' => 'Google'])->get(self::METADATA_TOKEN);
            if (!$res->successful() || !$res->json('access_token')) {
                throw new \RuntimeException('Server account login failed: HTTP ' . $res->status() . ' (is this a Google Cloud server?)');
            }
            $token = (string)$res->json('access_token');
            Cache::put($cacheKey, $token, max(60, min(3000, (int)$res->json('expires_in') - 120)));
            return $token;
        }
        return Cache::remember('gcs_access_token_' . md5(self::bucket() . config('services.gcs.key_file')), 3000, function () {
            $key = self::key();
            $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $now = time();
            $unsigned = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $b64(json_encode([
                'iss' => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/devstorage.read_write',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            if (!openssl_sign($unsigned, $sig, $key['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new \RuntimeException('Could not sign the login token (check GCS_KEY_FILE).');
            }
            $res = self::http()->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned . '.' . $b64($sig),
            ]);
            if (!$res->successful() || !$res->json('access_token')) {
                throw new \RuntimeException('Google login failed: HTTP ' . $res->status() . ' ' . substr((string)$res->json('error_description'), 0, 150));
            }
            return (string)$res->json('access_token');
        });
    }

    /** client_email + private_key of the service account JSON key. */
    private static function key(): array
    {
        $key = json_decode((string)@file_get_contents((string)config('services.gcs.key_file')), true);
        if (empty($key['client_email']) || empty($key['private_key'])) {
            throw new \RuntimeException('GCS_KEY_FILE is not a service account JSON key.');
        }
        return $key;
    }

    private static function objectUrl(string $object): string
    {
        return self::API . '/storage/v1/b/' . rawurlencode(self::bucket()) . '/o/' . rawurlencode($object);
    }

    /** Certificate checking stays on, with the CA bundle shipped with the app when PHP has none configured. */
    private static function http()
    {
        $http = Http::timeout(60);
        $caBundle = public_path('ssl/cacert.pem');
        return is_file($caBundle) ? $http->withOptions(['verify' => $caBundle]) : $http;
    }
}
