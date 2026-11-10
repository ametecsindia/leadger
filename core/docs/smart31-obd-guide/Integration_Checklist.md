# Smart31 & OBD Gateway – Integration Checklist

**Accounts and credentials**
- [ ] Get from the provider: call gateway access, `<COMPANY_ID>`, OBD / CDR `Secret-Key`, Vi OBD login, your CRM id.
- [ ] Put every secret in `.env`; none in code, documents or chat.
- [ ] Give the provider the two webhook URLs (https) with their secrets.

**Numbers (VMNs)**
- [ ] Table for Add Voucher numbers with per-channel flags, team list and expiry.
- [ ] Table for the Smart31 pool with `usage_date`, flags, group and team list; unique key `(vmn, usage_date)`.
- [ ] Daily sync of the Smart31 list and the DID voucher check.
- [ ] One VMN check for every channel (user's teams, channel flag, pool, not expired).

**Click2Call and WebRTC**
- [ ] Unique ref_id per call, stored before calling the gateway.
- [ ] Start Click2Call (A party = agent, B party = customer); store the provider call id.
- [ ] Start WebRTC with the agent's WebRTC id; create agents with the gateway's add-agent API.
- [ ] Status endpoint for the dialer that reads the stored record first, then the gateway.
- [ ] Timeouts, TLS verification and retries on every gateway call.

**Call results and recordings**
- [ ] Call-end webhook that **requires** the secret.
- [ ] Outcome from the customer leg; talk time only for connected calls.
- [ ] Recording row unique on the provider call id; background download with retries.
- [ ] Keep the ringing recording of unanswered calls when the provider sends one.
- [ ] Scheduled fetch of missing recordings and a sweep for missed webhooks.

**Inbound calls**
- [ ] Inbound webhook with secret (and optional IP allow-list); log every request without secrets.
- [ ] One row per call id; status only moves forward.
- [ ] Match lead by customer number, agent by mobile, Click2Call number or WebRTC id.
- [ ] Read India times marked as UTC as local time.
- [ ] Pull inbound logs every minute as a safety net.

**OBD campaigns**
- [ ] Credit vouchers and transfers in one transaction, balance checked.
- [ ] Credits per number computed on the server (audio seconds ÷ 30, rounded up).
- [ ] Executor in the scheduler (not a public URL); Vi steps: login, create, voice, numbers.
- [ ] Results from `obd_data` into the detail rows; campaign completed when all numbers have a result.

**Operations**
- [ ] Cron `schedule:run` every minute and a queue worker.
- [ ] One host per company if the project serves several companies.
- [ ] Test each flow with a real call: connected, customer no answer, agent no answer, busy, inbound.
