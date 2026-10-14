# Click2Call (C2C) Integration Guide

This guide explains how LeadGER places Click2Call calls, and gives code you can copy into another Laravel project.
Secrets are never written here. Each one is read from `.env`, shown as `<SECRET>`.

---

## 1. How a Click2Call works

```
 Agent clicks "Call"          Your app                         Provider (telephony gateway)
 ───────────────────          ────────                         ────────────────────────────
 POST /c2c/start  ─────────►  validate, check rules,
                              create call row (initiated)
                              POST initiate API  ────────────► rings AGENT phone (A party)
                              ◄──────────── {call_id}          agent answers → rings CUSTOMER (B party)
 ◄── {id, uuid}                                                 caller ID shown = VMN / DID
 poll GET /c2c/status (2 s) ► read stored CDR, else
                              GET CDR API  ─────────────────►  returns call detail record (CDR)
 ◄── calling / ringing /                                        
     connected / ended ...                                      call ends → POST webhook
                              /api/c2c/callback  ◄────────────  {ref_id, A/B_DIAL_STATUS, times, recording_url}
                              update row, create recording,
                              queue download job
 Agent saves outcome ───────► POST /c2c/disposition
```

- **A party** is the agent's phone. It rings first.
- **B party** is the customer. The provider rings the customer only after the agent answers.
- **DID / VMN** is the virtual number shown to the customer as caller ID.
- **ref_id** is your unique id for the call. Send it to the provider, and use it to match every status, webhook and recording back to your row.

The browser never talks to the provider. The provider only rings phones, and every result reaches your app either through the status API or through the webhook.

---

## 2. Provider APIs

### 2.1 Start a call

```
POST {C2C_INITIATE_URL}                    (LeadGER: https://sip.telozar.com/api/webrtc/c2c_call)
Content-Type: application/json

{
  "did":         "8012345678",      // VMN, 10 digits, no 91
  "a_party_no":  "9876500001",      // agent phone
  "b_party_no":  "9876500002",      // customer phone
  "ref_id":      "6f1c...-..._d_123_45_1",  // your unique reference
  "record": 1, "recording": 1, "record_call": 1
}
```

Response (success): JSON that has a call id and **no** `error` / `errors` key, for example `{"call_id":"abc123", ...}`.
LeadGER reads the id from `call_id`, then `callId`, then `uuid`, then `response.call_id`, and falls back to `ref_id`.

Response (failure): JSON with `error` or `errors`, a non-2xx HTTP status, or a body that is not JSON.

> LeadGER sends no auth header to this endpoint. If your provider issues a key, add it from `.env`.

### 2.2 Call detail record (CDR) and recording lookup

```
GET {C2C_CDR_URL}/{ref_id}/{vmn}           (LeadGER: https://obd.ametecsindia.com/admin/click2call_data/{ref}/{vmn})
Secret-Key: <SECRET>                       (from .env, never hard-coded)
```

Response:
```json
{
  "status": 200,
  "data": {
    "response": "{\"A_DIAL_STATUS\":\"ANSWER\",\"B_DIAL_STATUS\":\"ANSWER\",\"B_PARTY_CONNECTED_TIME\":\"14102026153012\", ...}",
    "recording_url": "https://.../rec.wav",
    "duration": 42
  }
}
```
`data.response` can be a JSON string or an object, so decode it when it is a string.

### 2.3 Webhook (the provider calls you when the call ends)

Register this URL with the provider: `https://your-app/api/c2c/callback` (GET or POST, form or JSON body).

| Field (first non-empty alias wins) | Meaning |
|---|---|
| `ref_id`, `REF_ID`, `reference_id`, `refId` | your reference |
| `call_id`, `CALL_ID`, `uniqueid`, `uuid` | provider call id |
| `b_party_no`, `customer_number`, `phone_number` | customer number |
| `recording_url`, `RecordVoice`, `record_url`, `audio_url` | recording file URL |
| `A_DIAL_STATUS` | agent leg: `ANSWER`, `NOANSWER`, `BUSY`, `CANCEL`, `CONGESTION`, `CHANUNAVAIL` |
| `B_DIAL_STATUS` | customer leg (same values) |
| `A_PARTY_CONNECTED_TIME`, `A_PARTY_END_TIME` | agent leg times, format **`dmYHis`** (e.g. `14102026153012`) |
| `B_PARTY_CONNECTED_TIME`, `B_PARTY_END_TIME` | customer leg times, `dmYHis` |
| `EVENT_TYPE` | contains `end` / `hangup` when finished |
| `DISCONNECTED_BY` | which side hung up |
| `talk_duration`, `billsec`, `duration` | talk seconds (fallbacks) |

Authentication: the provider sends `Secret-Key` (or `X-Webhook-Secret`, or `?secret=`). Compare it with `hash_equals`, and **reject requests that have no secret**. LeadGER currently accepts a missing secret; do not copy that.

---

## 3. Mapping the status

### 3.1 Normalising a dial status

Lower-case the value and keep only letters, then apply the first matching rule:

| Contains | Result |
|---|---|
| `busy` | `busy` |
| `noanswer`, `unanswer`, `notanswered`, `missed` | `no-answer` |
| `fail`, `cancel`, `reject`, `congestion`, `chanunavail` | `failed` |
| `answer`, `connect`, `completed`, `success` | `completed` |
| anything else | `null` (unknown) |

### 3.2 Turning a CDR into call facts

1. **Outcome:**
   - If `B_DIAL_STATUS` is present, it decides the outcome.
   - Otherwise, if the agent did not answer, the outcome is `failed`.
   - Otherwise, if `B_PARTY_CONNECTED_TIME` is set, the outcome is `completed`.
2. **Talk time:** `B_PARTY_END_TIME − B_PARTY_CONNECTED_TIME`. If that is not available, use `talk_duration`, then `billsec`, then `duration`. Talk time is `0` unless the outcome is `completed`.
3. **Ended:** the call has ended if any of these is true:
   - `EVENT_TYPE` contains `end` or `hangup`;
   - either end time is set;
   - the outcome is a failure.
4. **Agent answered:** `A_DIAL_STATUS` maps to `completed`.

### 3.3 Status returned to the browser

| Facts | `status` | `final` |
|---|---|---|
| no CDR yet | `calling` | false |
| agent answered, customer not yet | `ringing` | false |
| `B_PARTY_CONNECTED_TIME` set, not ended | `connected` (+ `connected_for` seconds) | false |
| ended, `completed` | `ended` (+ `duration`) | true |
| ended, `busy` | `busy` | true |
| ended, `no-answer` | `no_answer` | true |
| ended, `failed` | `failed` ("Your phone did not answer" when the agent leg failed) | true |

---

## 4. Business rules (checked before dialling)

Run them in this order. Any failure stops the call:

1. **Validation:** `mobile` is required, digits only, 10–20 characters; `vmn` is required. Otherwise **422**.
2. **Permission:** the user has C2C enabled (`c2c_channel = 1`) and an agent number (`click_agent_number ?: mobile`). Otherwise **403**.
3. **Calling hours:** `calling_hours_from`–`calling_hours_to` in the company timezone. Overnight windows are allowed. Otherwise **403** with `reason: outside_hours`.
4. **Daily retry limit per customer number:** `daily_phone_call_attempts(phone, date, count)`. When `count >= daily_call_limit`, return **403** with `reason: retry_limit`. Log every blocked attempt to `blocked_call_logs`.
5. **Per-agent lock:** `Cache::lock("c2c_agent_{id}", N)`. If the lock is held, return **429**. Make N longer than the provider timeout.
6. **VMN check:** the VMN must be assigned to the user's team for C2C and must not be expired. Otherwise **422**.
7. **Normalise numbers:** keep digits only; turn `91XXXXXXXXXX` and `0XXXXXXXXXX` into 10 digits.
8. **Reserve the attempt** (atomic increment inside `lockForUpdate`) **just before** calling the provider. **Release** it (decrement) if the provider fails.

---

## 5. Database

```php
// database/migrations/xxxx_create_c2c_tables.php
Schema::create('c2c_calls', function (Blueprint $t) {
    $t->id();
    $t->foreignId('user_id')->index();
    $t->unsignedBigInteger('lead_id')->nullable()->index();
    $t->unsignedBigInteger('campaign_id')->nullable();
    $t->string('source', 20)->default('manual');          // manual | campaign
    $t->string('customer_number', 20)->index();
    $t->string('agent_number', 20);
    $t->string('vmn', 20);
    $t->string('reference_id', 120)->unique();
    $t->string('provider_call_id', 120)->nullable()->index();
    $t->string('call_state', 20)->default('initiated');   // initiated|ringing|connected|completed|busy|no-answer|failed
    $t->unsignedInteger('talk_seconds')->default(0);
    $t->string('disconnected_by', 20)->nullable();
    $t->json('provider_response')->nullable();            // last CDR / webhook payload
    $t->text('recording_url')->nullable();
    $t->unsignedBigInteger('status_id')->nullable();      // disposition
    $t->unsignedBigInteger('substatus_id')->nullable();
    $t->text('remarks')->nullable();
    $t->timestamp('follow_up_on')->nullable();
    $t->timestamp('start_time')->nullable();
    $t->timestamp('answer_time')->nullable();
    $t->timestamp('end_time')->nullable();
    $t->timestamps();
});

Schema::create('c2c_recordings', function (Blueprint $t) {
    $t->id();
    $t->foreignId('c2c_call_id')->unique();
    $t->text('recording_url')->nullable();
    $t->string('download_status', 20)->default('awaiting'); // awaiting|pending|downloading|completed|failed|unavailable
    $t->unsignedTinyInteger('download_attempts')->default(0);
    $t->string('file_path')->nullable();
    $t->unsignedBigInteger('file_size')->nullable();
    $t->unsignedInteger('duration')->nullable();
    $t->timestamps();
});

Schema::create('daily_phone_call_attempts', function (Blueprint $t) {
    $t->id();
    $t->string('phone_normalized', 20);
    $t->date('call_date');
    $t->unsignedInteger('attempt_count')->default(0);
    $t->unique(['phone_normalized', 'call_date']);
});

Schema::create('blocked_call_logs', function (Blueprint $t) {
    $t->id();
    $t->string('phone_number', 20);
    $t->foreignId('user_id')->nullable();
    $t->string('reason', 30);          // outside_hours | retry_limit | invalid_number
    $t->string('message')->nullable();
    $t->timestamp('blocked_at');
});

// users: c2c_channel (tinyint), click_agent_number (string, nullable)
// vmns:  number, c2c (tinyint), team_id, expiry (date)
// settings: calling_hours_from ('09:30'), calling_hours_to ('19:30'), daily_call_limit (100)
```

> LeadGER keeps campaign calls in `call_logs` and manual calls in `dialer_logs`. Their ids overlap, which caused updates to land on the wrong row. In a new project, use **one** table with a `source` column, as shown above.

---

## 6. Configuration

```dotenv
C2C_INITIATE_URL=https://sip.telozar.com/api/webrtc/c2c_call
C2C_CDR_URL=https://obd.ametecsindia.com/admin/click2call_data
C2C_SECRET_KEY=<SECRET>          # Secret-Key header for the CDR API
C2C_WEBHOOK_SECRET=<SECRET>      # what the provider sends to your webhook
C2C_TIMEOUT=15
```

```php
// config/services.php
'c2c' => [
    'initiate_url'   => env('C2C_INITIATE_URL'),
    'cdr_url'        => env('C2C_CDR_URL'),
    'secret_key'     => env('C2C_SECRET_KEY'),
    'webhook_secret' => env('C2C_WEBHOOK_SECRET'),
    'timeout'        => (int) env('C2C_TIMEOUT', 15),
],
```

Requirements:
- A queue worker (`php artisan queue:work`) for recording downloads.
- The scheduler (`php artisan schedule:work`, or cron) for the recording sweep.
- A cache store that supports locks (database, redis or file).

---

## 7. Backend code

### 7.0 Model: `app/Models/C2cCall.php`

```php
class C2cCall extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'provider_response' => 'array',
        'start_time' => 'datetime', 'answer_time' => 'datetime', 'end_time' => 'datetime', 'follow_up_on' => 'datetime',
    ];
}
```
Change `$user->isAdmin()`, `team_id` and the `vmns` table in 7.4 to match your project's roles and VMN storage.

### 7.1 Gateway client: `app/Services/C2c/C2cGateway.php`

```php
<?php

namespace App\Services\C2c;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class C2cGateway
{
    /** @return array{ok: bool, provider_call_id: ?string, message: string} */
    public function initiate(string $did, string $agent, string $customer, string $refId): array
    {
        $body = ['did' => $did, 'a_party_no' => $agent, 'b_party_no' => $customer, 'ref_id' => $refId,
                 'record' => 1, 'recording' => 1, 'record_call' => 1];
        try {
            $res = Http::timeout(config('services.c2c.timeout'))->connectTimeout(5)->acceptJson()
                ->post(config('services.c2c.initiate_url'), $body);
        } catch (\Throwable $e) {
            Log::warning('[C2C] initiate unreachable', ['ref' => $refId, 'error' => $e->getMessage()]);
            return ['ok' => false, 'provider_call_id' => null, 'message' => 'Call gateway is not reachable'];
        }
        $json = $res->json();
        Log::info('[C2C] initiate', ['ref' => $refId, 'http' => $res->status()]);   // never log secrets

        if (!$res->successful() || !is_array($json) || isset($json['error']) || isset($json['errors'])) {
            $msg = is_array($json) ? ($json['message'] ?? (is_string($json['error'] ?? null) ? $json['error'] : null)) : null;
            return ['ok' => false, 'provider_call_id' => null, 'message' => $msg ?: 'Call failed to start at the gateway'];
        }
        $id = $json['call_id'] ?? $json['callId'] ?? $json['uuid'] ?? ($json['response']['call_id'] ?? null) ?? $refId;
        return ['ok' => true, 'provider_call_id' => (string) $id, 'message' => 'Call initiated'];
    }

    /** The provider's CDR for a call, flattened (with ref_id and recording_url), or null. */
    public function fetchCdr(string $refId, string $vmn): ?array
    {
        try {
            $res = Http::timeout(5)->withHeaders(['Secret-Key' => config('services.c2c.secret_key')])
                ->get(rtrim(config('services.c2c.cdr_url'), '/') . '/' . rawurlencode($refId) . '/' . rawurlencode($vmn));
        } catch (\Throwable $e) {
            return null;
        }
        $data = $res->json('data');
        if (!$res->successful() || !is_array($data) || empty($data['response'])) {
            return null;
        }
        $cdr = is_string($data['response']) ? json_decode($data['response'], true) : (array) $data['response'];
        if (!is_array($cdr)) {
            return null;
        }
        $cdr['ref_id'] = $refId;
        $url = $data['recording_url'] ?? $cdr['RecordVoice'] ?? null;
        if ($url && preg_match('#^https?://#i', $url)) {
            $cdr['recording_url'] = $url;
        }
        if (isset($data['duration']) && !isset($cdr['talk_duration'])) {
            $cdr['talk_duration'] = $data['duration'];
        }
        return $cdr;
    }
}
```

### 7.2 CDR mapper: `app/Services/C2c/CallFacts.php`

```php
<?php

namespace App\Services\C2c;

use Carbon\Carbon;

class CallFacts
{
    public static function dialState(?string $s): ?string
    {
        $s = preg_replace('/[^a-z]/', '', strtolower((string) $s));
        if ($s === '') return null;
        if (str_contains($s, 'busy')) return 'busy';
        foreach (['noanswer', 'unanswer', 'notanswered', 'missed'] as $k) if (str_contains($s, $k)) return 'no-answer';
        foreach (['fail', 'cancel', 'reject', 'congestion', 'chanunavail'] as $k) if (str_contains($s, $k)) return 'failed';
        foreach (['answer', 'connect', 'completed', 'success'] as $k) if (str_contains($s, $k)) return 'completed';
        return null;
    }

    private static function time(array $p, string $key): ?Carbon
    {
        $v = $p[$key] ?? $p[strtolower($key)] ?? null;
        if (!$v) return null;
        try { return Carbon::createFromFormat('dmYHis', (string) $v); } catch (\Throwable) {}
        try { return Carbon::parse($v); } catch (\Throwable) { return null; }
    }

    /** @return array{outcome: ?string, talk: int, ended: bool, agent_answered: ?bool, customer_connected_at: ?Carbon, ended_at: ?Carbon, disconnected_by: ?string} */
    public static function from(array $p): array
    {
        $a = self::dialState($p['A_DIAL_STATUS'] ?? $p['a_dial_status'] ?? null);
        $b = self::dialState($p['B_DIAL_STATUS'] ?? $p['b_dial_status'] ?? null);
        $bStart = self::time($p, 'B_PARTY_CONNECTED_TIME');
        $bEnd   = self::time($p, 'B_PARTY_END_TIME');
        $aEnd   = self::time($p, 'A_PARTY_END_TIME');

        $outcome = $b
            ?? ($a !== null && $a !== 'completed' ? 'failed' : null)
            ?? ($bStart ? 'completed' : null);

        $talk = 0;
        if ($outcome === 'completed') {
            $talk = ($bStart && $bEnd) ? max(0, $bEnd->diffInSeconds($bStart, true))
                : (int) ($p['talk_duration'] ?? $p['billsec'] ?? ($b === null ? ($p['duration'] ?? 0) : 0));
        }
        $event = strtolower((string) ($p['EVENT_TYPE'] ?? $p['event_type'] ?? ''));
        $ended = str_contains($event, 'end') || str_contains($event, 'hangup') || $bEnd || $aEnd
            || in_array($outcome, ['busy', 'no-answer', 'failed'], true);

        return [
            'outcome' => $outcome,
            'talk' => (int) $talk,
            'ended' => (bool) $ended,
            'agent_answered' => $a === null ? null : $a === 'completed',
            'customer_connected_at' => $bStart,
            'ended_at' => $bEnd ?? $aEnd,
            'disconnected_by' => $p['DISCONNECTED_BY'] ?? null,
        ];
    }
}
```

### 7.3 Calling rules: `app/Services/C2c/CallingRules.php`

```php
<?php

namespace App\Services\C2c;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CallingRules
{
    public static function normalize(string $n): string
    {
        $d = preg_replace('/\D/', '', $n);
        if (strlen($d) === 12 && str_starts_with($d, '91')) $d = substr($d, 2);
        if (strlen($d) === 11 && str_starts_with($d, '0'))  $d = substr($d, 1);
        if (strlen($d) === 13 && str_starts_with($d, '091')) $d = substr($d, 3);
        return $d;
    }

    /** null = allowed; otherwise [code, reason, message]. $reserve = count this attempt. */
    public static function check(string $phone, $user, bool $reserve): ?array
    {
        $phone = self::normalize($phone);
        if (strlen($phone) < 7) return [422, 'invalid_number', 'Invalid phone number.'];

        $s = DB::table('settings')->first();
        $from = $s->calling_hours_from ?? '09:30';
        $to   = $s->calling_hours_to ?? '19:30';
        $now  = Carbon::now(config('app.timezone'))->format('H:i');
        $open = $from <= $to ? ($now >= $from && $now <= $to) : ($now >= $from || $now <= $to); // overnight window
        if (!$open) return self::block($phone, $user, 'outside_hours', "Calls are allowed only between $from and $to.", 403);

        $limit = (int) ($s->daily_call_limit ?? 100);
        $today = Carbon::today()->toDateString();
        $blocked = DB::transaction(function () use ($phone, $today, $limit, $reserve) {
            DB::table('daily_phone_call_attempts')->insertOrIgnore(['phone_normalized' => $phone, 'call_date' => $today, 'attempt_count' => 0]);
            $row = DB::table('daily_phone_call_attempts')->where(['phone_normalized' => $phone, 'call_date' => $today])->lockForUpdate()->first();
            if ($row->attempt_count >= $limit) return true;
            if ($reserve) DB::table('daily_phone_call_attempts')->where('id', $row->id)->increment('attempt_count');
            return false;
        }, 3);
        return $blocked
            ? self::block($phone, $user, 'retry_limit', "Maximum call attempts reached for this number today ($limit).", 403)
            : null;
    }

    public static function release(string $phone): void
    {
        DB::table('daily_phone_call_attempts')
            ->where(['phone_normalized' => self::normalize($phone), 'call_date' => Carbon::today()->toDateString()])
            ->where('attempt_count', '>', 0)->decrement('attempt_count');
    }

    private static function block(string $phone, $user, string $reason, string $msg, int $code): array
    {
        DB::table('blocked_call_logs')->insert(['phone_number' => $phone, 'user_id' => $user?->id, 'reason' => $reason, 'message' => $msg, 'blocked_at' => now()]);
        return [$code, $reason, $msg];
    }
}
```

### 7.4 Controller: `app/Http/Controllers/C2cController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\C2cCall;
use App\Services\C2c\{C2cGateway, CallFacts, CallingRules, C2cResultService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB};
use Illuminate\Support\Str;

class C2cController extends Controller
{
    public function start(Request $request, C2cGateway $gateway)
    {
        $data = $request->validate([
            'mobile' => ['required', 'regex:/^[0-9]+$/', 'min:10', 'max:20'],
            'vmn' => ['required', 'string'],
            'lead_id' => ['nullable', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
            'source' => ['nullable', 'in:manual,campaign'],
        ]);
        $user = $request->user();
        $agent = CallingRules::normalize((string) ($user->click_agent_number ?: $user->mobile));
        if (!$user->c2c_channel || $agent === '') {
            return response()->json(['success' => false, 'message' => 'Click2Call is not enabled for your account.'], 403);
        }
        $customer = CallingRules::normalize($data['mobile']);
        $vmn = CallingRules::normalize($data['vmn']);

        if ($block = CallingRules::check($customer, $user, false)) {
            return response()->json(['success' => false, 'reason' => $block[1], 'message' => $block[2]], $block[0]);
        }
        // The VMN must be assigned to the user's team for C2C, and not expired.
        $vmnOk = DB::table('vmns')->where('number', $vmn)->where('c2c', 1)
            ->where(fn ($q) => $q->whereNull('expiry')->orWhere('expiry', '>=', today()))
            ->when(!$user->isAdmin(), fn ($q) => $q->whereRaw('FIND_IN_SET(?, team_id)', [$user->team_id]))
            ->exists();
        if (!$vmnOk) {
            return response()->json(['success' => false, 'message' => "+91$vmn is not available for Click2Call."], 422);
        }

        $lock = Cache::lock("c2c_agent_{$user->id}", config('services.c2c.timeout') + 10);
        if (!$lock->get()) {
            return response()->json(['success' => false, 'message' => 'Another call is being placed. Please wait.'], 429);
        }
        try {
            $uuid = (string) Str::uuid();
            $call = C2cCall::create([
                'user_id' => $user->id,
                'lead_id' => $data['lead_id'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'source' => ($data['source'] ?? null) === 'campaign' ? 'campaign' : 'manual',
                'customer_number' => $customer, 'agent_number' => $agent, 'vmn' => $vmn,
                'reference_id' => "{$uuid}_{$user->id}",
                'call_state' => 'initiated', 'start_time' => now(),
            ]);

            // Count the attempt just before dialling, and give it back if the gateway fails.
            if ($block = CallingRules::check($customer, $user, true)) {
                $call->update(['call_state' => 'failed', 'end_time' => now()]);
                return response()->json(['success' => false, 'reason' => $block[1], 'message' => $block[2]], $block[0]);
            }
            $res = $gateway->initiate($vmn, $agent, $customer, $call->reference_id);
            if (!$res['ok']) {
                CallingRules::release($customer);
                $call->update(['call_state' => 'failed', 'end_time' => now()]);
                return response()->json(['success' => false, 'reason' => 'gateway', 'message' => $res['message']], 400);
            }
            $call->update(['provider_call_id' => $res['provider_call_id']]);

            return response()->json([
                'success' => true, 'id' => $call->id, 'uuid' => $uuid,
                'reference_id' => $call->reference_id, 'provider_call_id' => $call->provider_call_id,
                'message' => 'Call initiated successfully',
            ]);
        } finally {
            $lock->release();
        }
    }

    public function status(Request $request, C2cGateway $gateway, C2cResultService $results)
    {
        $call = C2cCall::where('id', $request->integer('id'))->where('user_id', $request->user()->id)->first();
        if (!$call) {
            return response()->json(['status' => 'calling', 'final' => false]);
        }
        if (in_array($call->call_state, ['completed', 'busy', 'no-answer', 'failed'], true) && $call->end_time) {
            return response()->json($this->finalPayload($call->call_state, $call->talk_seconds, $call->disconnected_by, null));
        }

        $cdr = $call->provider_response;   // a webhook may already have stored it
        $hasCdr = is_array($cdr) && (isset($cdr['A_DIAL_STATUS']) || isset($cdr['B_DIAL_STATUS']) || isset($cdr['EVENT_TYPE']));
        if (!$hasCdr || !CallFacts::from($cdr)['ended']) {
            $cdr = $gateway->fetchCdr($call->reference_id, $call->vmn) ?? ($hasCdr ? $cdr : null);
        }
        if (!$cdr) {
            return response()->json(['status' => 'calling', 'final' => false]);
        }

        $facts = CallFacts::from($cdr);
        if ($facts['ended']) {
            $results->apply($cdr);   // same code path as the webhook (idempotent)
            return response()->json($this->finalPayload($facts['outcome'] ?? 'completed', $facts['talk'], $facts['disconnected_by'], $facts['agent_answered']));
        }
        if ($facts['customer_connected_at']) {
            $call->update(['call_state' => 'connected', 'answer_time' => $call->answer_time ?? $facts['customer_connected_at']]);
            return response()->json(['status' => 'connected', 'final' => false,
                'connected_for' => (int) $facts['customer_connected_at']->diffInSeconds(now(), true)]);
        }
        if ($facts['agent_answered']) {
            return response()->json(['status' => 'ringing', 'final' => false, 'message' => 'Ringing customer...']);
        }
        return response()->json(['status' => 'calling', 'final' => false]);
    }

    private function finalPayload(string $outcome, int $talk, ?string $by, ?bool $agentAnswered): array
    {
        [$status, $msg] = match ($outcome) {
            'busy' => ['busy', 'Customer line is busy'],
            'no-answer' => ['no_answer', 'Customer did not answer'],
            'failed' => ['failed', $agentAnswered === false ? 'Your phone did not answer, the customer was not dialled' : 'Call failed'],
            default => ['ended', 'Call Ended'],
        };
        return ['status' => $status, 'message' => $msg, 'final' => true, 'duration' => $talk,
                'duration_formatted' => sprintf('%02d:%02d', intdiv($talk, 60), $talk % 60), 'disconnected_by' => $by];
    }

    public function disposition(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'status_id' => ['required', 'exists:statuses,id'],
            'substatus_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'follow_up_on' => ['nullable', 'date'],
        ]);
        $call = C2cCall::where('id', $data['id'])->where('user_id', $request->user()->id)->firstOrFail();
        DB::transaction(function () use ($call, $data) {
            $call->update([
                'status_id' => $data['status_id'], 'substatus_id' => $data['substatus_id'] ?? null,
                'remarks' => $data['remarks'] ?? null, 'follow_up_on' => $data['follow_up_on'] ?? null,
                'call_state' => in_array($call->call_state, ['initiated', 'ringing', 'connected']) ? 'completed' : $call->call_state,
                'end_time' => $call->end_time ?? now(),
            ]);
            // Update the lead here: status, remarks, last call time, follow-up.
        });
        return response()->json(['success' => true, 'message' => 'Call outcome saved']);
    }
}
```

### 7.5 Result handling, shared by the webhook and polling: `app/Services/C2c/C2cResultService.php`

```php
<?php

namespace App\Services\C2c;

use App\Jobs\DownloadC2cRecording;
use App\Models\C2cCall;
use Illuminate\Support\Facades\DB;

class C2cResultService
{
    /** Apply a CDR / webhook payload to its call. Safe to run many times for the same call. */
    public function apply(array $p): array
    {
        $ref    = $p['ref_id'] ?? $p['REF_ID'] ?? $p['reference_id'] ?? $p['refId'] ?? null;
        $callId = $p['call_id'] ?? $p['CALL_ID'] ?? $p['uniqueid'] ?? $p['uuid'] ?? null;
        $url    = $p['recording_url'] ?? $p['RecordVoice'] ?? $p['record_url'] ?? $p['audio_url'] ?? null;
        $url    = ($url && preg_match('#^https?://#i', $url)) ? $url : null;

        $call = ($ref ? C2cCall::where('reference_id', $ref)->first() : null)
            ?? ($callId ? C2cCall::where('provider_call_id', $callId)->first() : null);
        if (!$call) {
            return ['status' => 'not_found', 'code' => 404];
        }
        $f = CallFacts::from($p);

        return DB::transaction(function () use ($call, $p, $f, $url) {
            $call = C2cCall::lockForUpdate()->find($call->id);
            $call->provider_response = $p;
            $call->call_state = $f['outcome'] ?? 'completed';
            $call->talk_seconds = $f['talk'];
            $call->disconnected_by = $f['disconnected_by'];
            $call->answer_time = $call->answer_time ?? $f['customer_connected_at'];
            $call->end_time = $call->end_time ?? $f['ended_at'] ?? now();
            if ($url) $call->recording_url = $url;
            $call->save();

            if ($call->call_state !== 'completed' || ($f['talk'] === 0 && !$url)) {
                return ['status' => 'ignored', 'code' => 200];          // not connected: no recording
            }
            $rec = DB::table('c2c_recordings')->where('c2c_call_id', $call->id)->first();
            if (!$rec) {
                $id = DB::table('c2c_recordings')->insertGetId([
                    'c2c_call_id' => $call->id, 'recording_url' => $url, 'duration' => $f['talk'],
                    'download_status' => $url ? 'pending' : 'awaiting', 'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                $id = $rec->id;
                if ($rec->download_status !== 'completed' && $url) {
                    DB::table('c2c_recordings')->where('id', $id)->update(['recording_url' => $url, 'download_status' => 'pending', 'updated_at' => now()]);
                }
            }
            if ($url) DownloadC2cRecording::dispatch($id)->afterCommit();
            return ['status' => 'success', 'code' => 200, 'recording_id' => $id];
        });
    }
}
```

### 7.6 Webhook: `app/Http/Controllers/C2cWebhookController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\C2c\C2cResultService;
use Illuminate\Http\Request;

class C2cWebhookController extends Controller
{
    public function __invoke(Request $request, C2cResultService $results)
    {
        $given = $request->header('Secret-Key') ?? $request->header('X-Webhook-Secret') ?? $request->input('secret');
        $expected = (string) config('services.c2c.webhook_secret');
        if ($expected === '' || !is_string($given) || !hash_equals($expected, $given)) {
            return response()->json(['status' => 'unauthorized'], 401);    // a missing secret is rejected too
        }
        $payload = $request->all() ?: (json_decode($request->getContent(), true) ?: []);
        $result = $results->apply($payload);
        return response()->json($result, $result['code']);
    }
}
```

### 7.7 Recording download job: `app/Jobs/DownloadC2cRecording.php`

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{DB, Http, Storage};

class DownloadC2cRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [5, 15, 30, 60, 120];

    public function __construct(public int $recordingId) {}

    public function handle(): void
    {
        $rec = DB::table('c2c_recordings')->find($this->recordingId);
        if (!$rec || !$rec->recording_url || $rec->download_status === 'completed') return;

        DB::table('c2c_recordings')->where('id', $rec->id)->update(['download_status' => 'downloading', 'download_attempts' => $rec->download_attempts + 1]);
        $res = Http::timeout(60)->connectTimeout(15)->get($rec->recording_url);
        if (!$res->successful() || strlen($res->body()) === 0) {
            throw new \RuntimeException("Recording download failed (HTTP {$res->status()})");   // the queue retries
        }
        $ext = strtolower(pathinfo(parse_url($rec->recording_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $ext = in_array($ext, ['wav', 'mp3', 'ogg', 'm4a', 'aac']) ? $ext : 'wav';
        $path = 'recordings/' . date('Ymd_His') . "_{$rec->id}.{$ext}";
        Storage::disk('local')->put($path, $res->body());

        DB::table('c2c_recordings')->where('id', $rec->id)->update([
            'file_path' => $path, 'file_size' => strlen($res->body()), 'download_status' => 'completed', 'updated_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        DB::table('c2c_recordings')->where('id', $this->recordingId)->update(['download_status' => 'failed']);
        // Playback can still use recording_url (the provider's copy).
    }
}
```

### 7.8 Recording sweep (scheduler): finds recordings that never arrived

```php
// app/Console/Commands/C2cFetchPending.php  — signature: c2c:fetch-pending
public function handle(C2cGateway $gw, C2cResultService $results): void
{
    // Calls that connected but have no recording URL yet, from the last 24 hours.
    $calls = C2cCall::where('call_state', 'completed')->whereNull('recording_url')
        ->where('created_at', '>=', now()->subDay())->limit(100)->get();
    foreach ($calls as $call) {
        if ($cdr = $gw->fetchCdr($call->reference_id, $call->vmn)) {
            $results->apply($cdr);
        }
    }
    // Give up after 60 minutes.
    DB::table('c2c_recordings')->where('download_status', 'awaiting')
        ->where('created_at', '<', now()->subHour())->update(['download_status' => 'unavailable']);
}

// routes/console.php (Laravel 11+) or app/Console/Kernel.php
Schedule::command('c2c:fetch-pending')->everyMinute()->withoutOverlapping();
```

### 7.9 Routes

```php
// routes/web.php (session auth)
Route::middleware('auth')->prefix('c2c')->group(function () {
    Route::post('start', [C2cController::class, 'start'])->middleware('throttle:20,1')->name('c2c.start');
    Route::get('status', [C2cController::class, 'status'])->name('c2c.status');
    Route::post('disposition', [C2cController::class, 'disposition'])->name('c2c.disposition');
});

// routes/api.php (no CSRF; protected by the shared secret)
Route::match(['get', 'post'], 'c2c/callback', C2cWebhookController::class)->name('c2c.callback');
```

---

## 8. Front-end code (jQuery)

```html
<input id="c2c-mobile" placeholder="Customer number">
<select id="c2c-vmn"><!-- options: value="8012345678" --></select>
<button id="c2c-call">Call</button>
<div id="c2c-status"></div>
```

```js
let c2cTimer = null;

$('#c2c-call').on('click', function () {
  const $btn = $(this);
  const mobile = ($('#c2c-mobile').val() || '').replace(/\D/g, '');
  let vmn = ($('#c2c-vmn').val() || '').replace(/\D/g, '');
  if (mobile.length < 10) return toastr.error('Enter a valid number');
  if (!vmn) return toastr.error('Select a caller number');
  if (vmn.length === 12 && vmn.startsWith('91')) vmn = vmn.slice(2);

  $btn.prop('disabled', true);
  setStatus('Calling... your phone will ring first');

  $.post('/c2c/start', {
    _token: $('meta[name="csrf-token"]').attr('content'),
    mobile, vmn,
    lead_id: $('#lead_id').val() || '', campaign_id: $('#campaign_id').val() || '',
    source: $('#campaign_id').val() ? 'campaign' : 'manual'
  }).done(res => {
    if (!res.success) { fail(res.message); return; }
    $('#current_call_id').val(res.id);
    pollStatus(res.id, $btn);
  }).fail(xhr => fail((xhr.responseJSON && xhr.responseJSON.message) || 'Call could not be started'));

  function fail(msg) { toastr.error(msg, 'Call Blocked'); setStatus(''); $btn.prop('disabled', false); }
});

function pollStatus(id, $btn) {
  const started = Date.now();
  let inFlight = false, connectedShown = false;
  clearInterval(c2cTimer);
  c2cTimer = setInterval(() => {
    if (Date.now() - started > 15 * 60 * 1000) { return finish('Call result not available', true); }
    if (inFlight) return;
    inFlight = true;
    $.get('/c2c/status', { id }).done(res => {
      switch (res.status) {
        case 'calling':   setStatus('Calling your phone...'); break;
        case 'ringing':   setStatus('Ringing customer...'); break;
        case 'connected':
          if (!connectedShown) { toastr.success('Connected'); connectedShown = true; }
          setStatus('Connected ' + fmt(res.connected_for || 0));
          break;
        case 'ended':     finish('Call ended (' + res.duration_formatted + ')', false); break;
        case 'busy': case 'no_answer': case 'failed':
          finish(res.message, true); break;
      }
    }).always(() => { inFlight = false; });
  }, 2000);

  function finish(msg, isError) {
    clearInterval(c2cTimer);
    setStatus(msg);
    isError ? toastr.error(msg) : toastr.info(msg);
    $btn.prop('disabled', false);
    // Now show the disposition form; it posts to /c2c/disposition with id = #current_call_id.
  }
}

function setStatus(t) { $('#c2c-status').text(t); }
function fmt(s) { return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }
```

---

## 9. Error responses

| HTTP | `reason` | When | Show the user |
|---|---|---|---|
| 422 | – | invalid number / VMN missing, not assigned or expired | the `message` |
| 403 | – | C2C not enabled or no agent number | "Click2Call is not enabled for your account" |
| 403 | `outside_hours` | outside calling hours | "Calls are allowed only between X and Y" |
| 403 | `retry_limit` | daily limit for this number reached | "Maximum call attempts reached…" |
| 429 | – | the agent is already placing a call | "Another call is being placed" |
| 400 | `gateway` | the provider refused or could not be reached | the provider's message |
| 401 | – | webhook with a wrong or missing secret | – |

---

## 10. Testing checklist

- [ ] `Http::fake()` the initiate URL with success, `{"error":...}`, HTTP 500 and a timeout. On failure, the attempt counter goes back down and the call is `failed`.
- [ ] A webhook with no secret, or a wrong one, returns 401 and changes nothing.
- [ ] Webhook with `B_DIAL_STATUS=BUSY` → `busy`, talk 0, no recording.
- [ ] Webhook with `A_DIAL_STATUS=NOANSWER` → `failed`, with "Your phone did not answer".
- [ ] Webhook answered, with times `14102026153000` → `14102026153042` → talk 42 s, a recording row, and the job queued.
- [ ] The same webhook sent twice → one recording row (idempotent).
- [ ] Polling and the webhook arriving together → one result.
- [ ] Outside calling hours and the retry limit are blocked and logged to `blocked_call_logs`.
- [ ] Two clicks within a second → the second gets 429.
- [ ] One agent cannot read another agent's call through `/c2c/status`.

---

## 11. LeadGER issues not to copy

1. The webhook accepts requests with **no** secret. Section 7.6 rejects them.
2. Campaign and manual calls live in two tables whose ids overlap, so some updates hit the wrong row. Section 5 uses one table with `source`.
3. The provider initiate request has **no timeout**, while the agent lock lasts only 6 s. Section 7.1 sets timeouts, and the lock lasts longer than the timeout.
4. Initiate success ignores the HTTP status. Section 7.1 checks `successful()`.
5. The provider Secret-Key and the MySQL AES key are hard-coded in several files. Keep them in `.env` only.
6. The status endpoint does not check that the call belongs to the agent. Section 7.4 filters by `user_id`.
