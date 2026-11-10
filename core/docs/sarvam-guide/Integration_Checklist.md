# Sarvam AI Transcription & Analysis – Integration Checklist

Tick each step in order. Section numbers refer to the *AMETECS – Sarvam AI Call Transcription & AI Analysis Integration Guide*.

### A. Preparation
- [ ] Sarvam account for each company; API key created at dashboard.sarvam.ai (4)
- [ ] Current Sarvam prices checked on https://www.sarvam.ai/api-pricing and written down with the date (7.2)
- [ ] Decide: client price (if you bill clients) separate from provider cost (7.3)
- [ ] ffmpeg installed on the server (`which ffmpeg`) (3)
- [ ] Queue (database or Redis) and a process manager (Supervisor/systemd) available (3)

### B. Configuration
- [ ] `config/sarvam.php`: base URL, models (`saaras:v3`, `sarvam-105b`), modes, prices + `verified_on` (4.1, 7.4)
- [ ] `.env`: model / price keys only – **no company keys in `.env`** (16)
- [ ] Queue `retry_after` larger than the job timeout (900 s → 960 s) (3)

### C. Database
- [ ] `company_sarvam_settings` (encrypted key, last4, status, enabled, language, auto) (11.2)
- [ ] `call_analyses` (one row per recording, unique) (11.2)
- [ ] `transcription_jobs` (status, estimated / actual cost, request ids) (11.2)
- [ ] `ai_usage_logs` (11.2)
- [ ] Optional: key audit log table (5.1)

### D. Company Settings (admin)
- [ ] Model with `encrypted` cast, `$hidden`, masked key, `usable()` (5.2)
- [ ] Free key test (job-status probe; 401/403 = invalid) (5.2)
- [ ] Save: validate format, test first, invalid → 422 and nothing saved (5.2)
- [ ] Remove key → status not configured, auto off, jobs wait (5.2)
- [ ] Admin-only gate, CSRF, throttle on all key endpoints (5.2, 14)
- [ ] Card UI: Enable AI Transcript, key (password field, never pre-filled), Test, Save, status, language, auto-transcribe (5.2)

### E. Recordings page
- [ ] AI column with states: Completed / Transcribing / Pending / Waiting for key / Failed + Retry / Not set up / Transcribe (6.1)
- [ ] Eligibility check on the server: enabled, key usable, permission, file exists, format, duration > 0, not already processing (6.4)
- [ ] Completed → open from the database, no new request; "Regenerate" is a separate, confirmed action (6.4)

### F. Cost estimate
- [ ] Estimate endpoint using `CostEstimator` (per service + total, tax if configured) (7.4)
- [ ] "Cost estimate unavailable" when a price is missing – never a made-up number (7.4)
- [ ] Popup: recording info, each cost line, total, "estimate" note, Cancel / Generate (7.6)
- [ ] Start endpoint requires the confirmation and re-checks eligibility (9.3)

### G. Processing
- [ ] Job row (QUEUED) + analysis row (pending) in one transaction; one active job per recording (9.3)
- [ ] Queue job: 2 slots per company, `retryUntil`, 3 API tries with 60 s / 300 s back-off, heartbeat, `failed()` handler (9.4)
- [ ] Pipeline order: start batch job (translate + speakers) → original transcript (REST, 29 s parts) → collect conversation → analysis → save (9.5)
- [ ] 401/403 → key invalid + waiting for key; 429/5xx → retry; other → failed, cost 0 (8.4)
- [ ] Batch polling deadline below the job timeout (13)

### H. AI analysis
- [ ] `/v1/chat/completions` with `sarvam-105b`, JSON response, "only what is said" prompt (12.2)
- [ ] Whitelist sentiment / interest values; cap text lengths (12.3)
- [ ] Optional: requirements + follow-up date fields (12.3)
- [ ] Keyword fallback when the model fails (12.1)

### I. Results and usage
- [ ] Status endpoint: queued / processing with seconds; stalled → failed with Retry (6.3)
- [ ] Popup polling 3 s → 5 s → 10 s, stop at 20 min (10.2)
- [ ] Result view: conversation by speaker, original transcript, agent/customer roles, analysis, copy / download (10.3)
- [ ] Actual cost + usage log per completed job; failed jobs cost 0 (9.5)

### J. Security
- [ ] Key never in responses, page source, logs or queue payloads (14)
- [ ] TLS verification on for every Sarvam request (14)
- [ ] Ownership checks on every endpoint that takes a recording / job / batch id (14)
- [ ] Company isolation on every query (14)

### K. Tests and go-live
- [ ] All 20 tests of section 15 pass with `Http::fake()` (no real Sarvam calls)
- [ ] Workers under Supervisor; scheduler cron; restart workers after every deploy (16)
- [ ] Admin saves the real key → Test → one short and one long real call transcribed (16)
- [ ] Compare the first month's estimates with the Sarvam dashboard (7.5)
