# Click2Call kit – LeadGER's Click2Call + Add Voucher for another Laravel project

This folder holds the complete Click2Call logic of LeadGER, as ready-to-copy Laravel files:

- **Add Voucher:** a voucher code from Ametecs gives you your VMN numbers. The admin then assigns them to teams.
- **Click2Call users:** switch Click2Call on per user and save the agent's own phone number.
- **Calling page:** customer number, VMN selector (voucher / Smart31 / 1600), the Click2Call button, and live status: Calling, Ringing, Connected, Ended, Busy, No answer, Failed. After the call, the agent fills in the outcome form.
- **Backend:** the same checks as LeadGER: VMN ownership and expiry, calling hours, daily limit per number, and one call start at a time per agent. It also includes the provider request, the call-record polling, the webhook, the recording download, and a sweep for missing results.

The kit was tested by installing it into a fresh Laravel 13 app: 17 feature tests passed, with the providers faked.

Requires Laravel 11, 12 or 13; PHP 8.2+; a database; and a queue worker plus the scheduler.

---

## 1. Before you start: get these from Ametecs / your provider

| What | Used for | `.env` key |
|---|---|---|
| CRM id | Add Voucher (`/api/did_voucher/{code}/{crm_id}`) | `C2C_CRM_ID` |
| License `Secret-Key` | Add Voucher requests | `C2C_LICENSE_SECRET` |
| A voucher code | Your VMN numbers | entered on the Add Voucher page |
| Gateway `Secret-Key` | Call records + recordings (`click2call_data`) | `C2C_GATEWAY_SECRET` |
| Access to the start-call API | Starting calls (Telozar `c2c_call`); LeadGER sends no credentials, so ask which server IP to register | – |
| Webhook registration | Give them `https://YOUR-DOMAIN/api/click2call/webhook` and agree on a secret | `C2C_WEBHOOK_SECRET` |

Never put these values in code or commit them to git.

## 2. Copy the files

Copy the folders of this kit into your Laravel project root. None of the files overwrite a standard Laravel file:

```
app/Console/Commands/C2cSweepCommand.php
app/Http/Controllers/Click2Call/{CallController,VoucherController,AgentController,WebhookController}.php
app/Jobs/C2c/DownloadRecording.php
app/Models/C2c/{Vmn,Voucher,Call}.php
app/Models/Concerns/HasClick2Call.php
app/Providers/Click2CallServiceProvider.php
app/Services/Click2Call/{VoucherService,VmnService,CallingRules,Click2CallGateway,CallResult}.php
config/click2call.php
database/migrations/2026_01_01_000001_create_click2call_tables.php
public/vendor/click2call/{click2call.js,click2call.css}
resources/views/click2call/{layout,dialer,vouchers,agents}.blade.php + partials/team-picker.blade.php
routes/click2call.php
tests/Feature/Click2CallKitTest.php
```

## 3. Three small code changes

**a) User model.** Add the trait:

```php
class User extends Authenticatable
{
    use HasFactory, Notifiable, \App\Models\Concerns\HasClick2Call;
```

Then adapt two methods in `app/Models/Concerns/HasClick2Call.php` to your project:
- `isC2cAdmin()`: who manages vouchers and users. The default reads an `is_admin` column.
- `c2cTeamIds()`: the user's teams. The default reads a `team_id` column, either one id or a list like `"1,8"`.

If you have no teams, make `c2cTeamIds()` return `[1]` and assign every number to team `1`.

**b) Register the provider.** Laravel 11+ uses `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Click2CallServiceProvider::class,
];
```

On Laravel 10 and older, add it to `providers` in `config/app.php` instead.

**c) Schedule the sweep.** Add this to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('c2c:sweep')->everyMinute()->withoutOverlapping();
```

## 4. `.env`

```
C2C_LICENSE_URL=https://lc.ametecsindia.com
C2C_LICENSE_SECRET=<from Ametecs>
C2C_CRM_ID=<from Ametecs>
C2C_START_URL=https://sip.telozar.com/api/webrtc/c2c_call
C2C_CDR_URL=https://obd.ametecsindia.com/admin/click2call_data
C2C_GATEWAY_SECRET=<from Ametecs>
C2C_WEBHOOK_SECRET=<a long random string, also given to the provider>
C2C_WEBHOOK_IPS=                      # optional: provider IPs, comma-separated
C2C_TIMEZONE=Asia/Kolkata
C2C_CALLING_HOURS_FROM=09:30
C2C_CALLING_HOURS_TO=19:30
C2C_DAILY_LIMIT_PER_NUMBER=100
C2C_MAX_USERS=0                       # licensed Click2Call users, 0 = no limit
C2C_TEAM_MODEL=App\Models\Team        # optional: a model with id + name
C2C_LAYOUT=click2call.layout          # or your own layout (see 7.)
QUEUE_CONNECTION=database
```

## 5. Database

```
php artisan migrate
```

This creates the following tables, and adds `c2c_channel`, `click_agent_number` and `c2c_default_number` to `users`.

| Table | LeadGER equivalent |
|---|---|
| `c2c_vouchers` | `obd_credits_history` |
| `c2c_vmns` | `obd_did` |
| `c2c_vmn_history` | `obd_did_history` |
| `c2c_smart31_numbers` | `smart31_vmn_numbers` |
| `c2c_calls` | `dialer_logs` + `call_logs` |
| `c2c_daily_attempts` | `daily_phone_call_attempts` |
| `c2c_blocked_calls` | `blocked_call_logs` |

If you use `QUEUE_CONNECTION=database` and have no `jobs` table yet, run `php artisan queue:table` (Laravel 10) first; Laravel 11+ includes it.

## 6. Run the background workers (production)

- **Queue worker:** `php artisan queue:work --tries=3`, kept running by Supervisor or systemd. It downloads the recordings.
- **Scheduler:** a cron line, `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`. It runs the sweep.
- **After every deploy:** restart the worker (`php artisan queue:restart`, or restart it in Supervisor). Otherwise it keeps running the old code.

## 7. Pages and routes

| URL | Who | What |
|---|---|---|
| `/click2call` | users with Click2Call on | Calling page |
| `/click2call/vouchers` | admins (`c2c-admin` gate) | Add Voucher + numbers, team assignment, usage, bulk update/delete |
| `/click2call/agents` | admins | Click2Call on/off per user, agent number, default VMN |
| `POST /click2call/call` | users | Start a call (JSON) |
| `GET /click2call/call/{id}/status` | the call's agent or an admin | Live status (polled every 2 s) |
| `POST /click2call/call/{id}/outcome` | the call's agent or an admin | Save the outcome |
| `GET /click2call/call/{id}/recording` | the call's agent or an admin | Play (`?play=1`) or download the recording |
| `POST /api/click2call/webhook` | the provider (secret required) | Call result + recording URL |

To link to the calling page with a number already filled in (it is never dialled automatically): `/click2call?number=9876543210`.

**Using your own layout:** set `C2C_LAYOUT=layouts.app`. Your layout must contain:
- `@yield('content')`
- `@stack('scripts')`
- `<meta name="csrf-token" content="{{ csrf_token() }}">`
- `<link rel="stylesheet" href="{{ asset('vendor/click2call/click2call.css') }}">`

## 8. First test

1. Run `php artisan test --filter=Click2CallKitTest`. All 17 tests should pass; no real call is made.
2. Log in as an admin, open **Add Voucher**, and enter your voucher code. The numbers appear. Assign each to a team.
3. Open **Click2Call Users** and switch Click2Call on for yourself, with your own mobile as the agent number.
4. Open **Click2Call**, enter a second phone you own as the customer, pick the VMN, and press **Click2Call**:
   1. Your phone rings. Answer it.
   2. The page shows "Ringing customer...", and the second phone rings with the VMN as caller ID. Answer it.
   3. The page shows **Connected** with a timer. Hang up.
   4. The page shows **Call ended**, with the talk time, and the outcome form.
5. Within a few minutes the recording is downloaded (`c2c_calls.recording_status = completed`).

## 9. How it works (same as LeadGER)

**Add Voucher** (`VoucherService`)
1. Validate the code: `GET {license_url}/api/did_voucher/{code}/{crm_id}` (`Secret-Key`). The answer must have `status: 200`.
2. Mark it used: `GET {license_url}/api/did_voucher_used/{code}/{ip}`.
3. In one database transaction:
   - Copy the current numbers to `c2c_vmn_history`, then delete them.
   - Insert the voucher's numbers. A number in `click2call_did` gets the Click2Call flag; `obd_did` sets OBD; `webrtc_did` sets WebRTC; `tc_verified` sets the verified flag; `expiry` is the expiry date.
   - Insert today's `smart_groups` as Smart31 numbers.

   Teams start empty, and the admin assigns them.

**Starting a call** (`CallController::start`), checked in this order:
1. Validate the request.
2. Click2Call is on for the user and the user has an agent number.
3. Calling hours, and the daily limit per number.
4. The VMN belongs to the user's team and has not expired (or is in the selected Smart31 / 1600 list).
5. The per-agent lock (6 s).
6. Count the attempt, create the call row (`initiated`, unique `ref_id`), and call the provider: `POST start_url {did, a_party_no, b_party_no, ref_id, record: 1, recording: 1, record_call: 1}`.
7. If the provider fails, the call is marked failed and the attempt is given back.

**Call status** (`CallResult`)
- Status arrives three ways: the webhook, the 2-second poll (which asks `GET cdr_url/{ref_id}/{vmn}`), and the sweep.
- The customer leg `B_DIAL_STATUS` decides the outcome: busy, no-answer, failed or completed.
- With no B status but an agent leg that didn't answer, the call is "failed: your phone did not answer".
- `B_PARTY_CONNECTED_TIME` means connected.
- Talk time is `B_PARTY_END_TIME − B_PARTY_CONNECTED_TIME`, else `talk_duration` / `billsec`.
- **A successful start request is only "initiated", never "connected".**

**Recordings**
- Only completed calls with talk time get one.
- `DownloadRecording` retries 5 times, after 5, 15, 30, 60 and 120 s, and saves to the private disk.
- The sweep asks again for missing recordings, marks them unavailable after 60 minutes, and retries failed downloads up to 20 times.

## 10. Differences from LeadGER (deliberate)

- The provider URLs and secrets live in `.env` / config. LeadGER hard-codes some of them.
- The webhook **requires** the secret, and matches calls only by `ref_id` or the provider call id. LeadGER accepts a webhook without a secret, and falls back to "the latest call to this number".
- Requests verify TLS and have timeouts. A start response must be HTTP 2xx **and** have no `error` key.
- Every endpoint that takes a call id checks that the call belongs to the user, or that the user is an admin.
- There is one `c2c_calls` table instead of two (`dialer_logs` / `call_logs`) with overlapping ids.
- Voucher expiry is stored as a plain date. LeadGER encrypts it with a fixed key, which adds no protection.
- Calls with no result after 15 minutes are marked failed by the sweep. LeadGER's page only stops waiting.
- Not included: LeadGER's multi-company databases, its license blob, campaigns, leads and statuses. `c2c_calls` has `lead_id` / `campaign_id` columns for you to link your own records.
