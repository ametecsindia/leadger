# Call Recording – Integration Checklist

Tick each step in order. Section numbers refer to the *LeadGER – Call Recording Integration Guide*.

### A. Provider and preparation
- [ ] Provider API docs at hand: dial with `ref_id` + record flags, end-of-call webhook, call lookup by reference (2)
- [ ] One real webhook payload saved (numbers masked) to map field names (6.3)
- [ ] All provider URLs are **https** (3)
- [ ] Queue worker under Supervisor/systemd and the `schedule:run` cron available (3)
- [ ] Disk space estimated (≈ 1 MB per call minute for WAV) and a retention period decided (3, 14)
- [ ] Optional: ffmpeg installed (`which ffmpeg`) (3)

### B. Configuration
- [ ] `config/telephony.php` + `.env` keys of section 13; no secret or provider URL in code (13)
- [ ] `recordings` disk in `config/filesystems.php` (9)
- [ ] Queue `retry_after` larger than the download job timeout (16)

### C. Database
- [ ] `recordings` table with unique `provider_call_id` and unique call link (8.1)
- [ ] Calls table: `reference_id` (unique), `provider_call_id`, `recording_id` (8.1)

### D. Placing calls
- [ ] Call row saved with `reference_id` **before** the dial request (5.1)
- [ ] Dial request sends `ref_id` and every record flag the provider documents (5.3)
- [ ] Provider `call_id` saved as `provider_call_id`; HTTP status logged, no phone numbers (5.3)

### E. Webhook
- [ ] One https route, no trailing slash, throttled, CSRF-free (6.1)
- [ ] Secret required, `hash_equals`, 401 when wrong or missing (6.2)
- [ ] Field names read case-insensitively; provider times in the provider timezone (6.3)
- [ ] Not connected / 0 s → outcome only, no recording (6.4)
- [ ] Answers fast; no download in the request (6.5)
- [ ] Optional: raw capture table + replay (6.6)

### F. Service
- [ ] Match by `ref_id`, then provider `call_id`; phone fallback off by default (7.1)
- [ ] One recording per call, transaction + `lockForUpdate`, idempotent (7.2, 7.3)
- [ ] `deleted` rows are never revived (7.3)
- [ ] Jobs dispatched `afterCommit` (7.3)
- [ ] No `recording_url` accepted from the browser (7.4)

### G. Download job
- [ ] 5 tries, backoff, timeout; streams to a temp file (9)
- [ ] Rejects HTTP errors, empty files, HTML, > 200 MB; TLS verification on; host allow-list (9, 14)
- [ ] Saves to the recordings disk, relative path, no phone number in the name (9)
- [ ] `failed()` marks `failed` and keeps the provider link (9)
- [ ] Duration probed with ffmpeg when 0 (9)

### H. Pull and sweeps
- [ ] Lookup by reference for `awaiting` rows; `unavailable` after 60 min (10)
- [ ] Re-queue stuck `pending`/`downloading`; retry `failed` (< 20 attempts, 24 h) (10)
- [ ] Ask the provider for recent calls with no recording (10)
- [ ] Each schedule `withoutOverlapping(10)` with its own lock (10)

### I. Playback
- [ ] One authenticated route; same visibility rule as the list (11)
- [ ] Inline with `Accept-Ranges` for play / Range; attachment for download (11)
- [ ] Provider file streamed through the server when not downloaded yet (11)
- [ ] Player plays immediately; HEAD only on error to explain 401 / 403 / 404 / format (11)

### J. Recordings page
- [ ] Server-side list: date range, campaign, source, agent, search; sort whitelist (12.2)
- [ ] Role scope: own / team / all; masked numbers where required (12.2)
- [ ] Status badges Waiting / Ready / Not available (12.3)
- [ ] Delete with permission, folder check, rollback, audit log (12.4)
- [ ] Bulk ZIP: ≤ 500 items, access-checked, one-time user-bound link, temp files purged (12.5)

### K. Tests and go-live
- [ ] The 16 tests of section 15 pass with `Http::fake()` / `Storage::fake()`
- [ ] Deploy steps of section 16; workers restarted after every deploy
- [ ] Provider has the webhook URL + secret; one test callback received
- [ ] One real call recorded, listed as "Ready", plays and seeks, downloads
