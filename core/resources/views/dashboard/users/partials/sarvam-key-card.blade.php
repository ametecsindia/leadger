{{--
    Transcription · Sarvam AI (License Info tab): this company's own Sarvam API key and transcription settings.
    The key is never sent to the browser: only its status and last 4 characters. Admins manage it
    (SarvamKeyController, admin only on the server too); other roles see the status only.
--}}
@php
    $svSettings = \App\Services\SarvamKeyService::settingsOrNull();
    $svIsAdmin = optional(Auth::user()->permissionsGroup)->name === 'Admin';
    $svState = $svSettings ? \App\Http\Controllers\Dashboard\SarvamKeyController::state($svSettings) : null;
    $svStatus = $svState['status'] ?? 'not_configured';
    $svBadge = ['not_configured' => ['Not configured', 'is-warn'], 'connected' => ['Connected', 'is-ok'], 'invalid' => ['Key invalid', 'is-bad']][$svStatus] ?? ['Not configured', 'is-warn'];
@endphp
<style>
.lg-sv-card .lg-section-header { justify-content: space-between; }
.lg-sv-head-left { display: flex; align-items: center; gap: 10px; }
.lg-sv-sub { font-size: 12.5px; font-weight: 500; color: #64748B; margin-top: 2px; }
.lg-sv-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.lg-sv-badge::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.lg-sv-badge.is-warn { background: #FFF7ED; color: #C2410C; }
.lg-sv-badge.is-ok { background: #ECFDF5; color: #047857; }
.lg-sv-badge.is-bad { background: #FEF2F2; color: #B91C1C; }
.lg-sv-keywrap { position: relative; }
.lg-sv-keywrap .lg-input { padding-right: 44px; font-family: inherit; }
.lg-sv-eye { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 32px; height: 32px; border: none; background: transparent; color: #64748B; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.lg-sv-eye:hover { background: #F1F5F9; color: #0F172A; }
.lg-sv-masked { display: flex; align-items: center; justify-content: space-between; gap: 10px; height: 42px; padding: 0 6px 0 14px; background: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; letter-spacing: 1px; color: #0F172A; font-variant-numeric: tabular-nums; }
.lg-sv-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 720px) { .lg-sv-grid2 { grid-template-columns: 1fr; } }
.lg-sv-select { appearance: auto; cursor: pointer; }
.lg-sv-error { color: #DC2626; font-size: 12.5px; font-weight: 500; margin-top: 6px; }
.lg-sv-error:empty { display: none; }
.lg-sv-toggle { display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; font-size: 13.5px; font-weight: 600; color: #0F172A; }
.lg-sv-toggle input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.lg-sv-track { width: 40px; height: 22px; border-radius: 999px; background: #CBD5E1; position: relative; transition: background .15s ease; flex-shrink: 0; }
.lg-sv-track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #FFFFFF; transition: transform .15s ease; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.lg-sv-toggle input:checked + .lg-sv-track { background: var(--lg-accent); }
.lg-sv-toggle input:checked + .lg-sv-track::after { transform: translateX(18px); }
.lg-sv-toggle input:focus-visible + .lg-sv-track { box-shadow: 0 0 0 3px rgba(20, 100, 244, .25); }
.lg-sv-toggle input:disabled + .lg-sv-track { opacity: .5; cursor: not-allowed; }
.lg-sv-note { display: flex; gap: 8px; align-items: flex-start; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px; font-size: 12.5px; color: #475569; line-height: 1.45; }
.lg-sv-note svg { flex-shrink: 0; margin-top: 1px; color: #64748B; }
.lg-sv-verified { font-size: 12.5px; font-weight: 600; color: #047857; margin-top: 6px; }
.lg-sv-verified.is-unverified { color: #B45309; }
.lg-sv-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.lg-sv-stat { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 12px 14px; }
.lg-sv-stat-label { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #64748B; }
.lg-sv-stat-value { font-size: 20px; font-weight: 800; color: #0F172A; margin-top: 4px; }
.lg-sv-actions { display: flex; justify-content: flex-end; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 20px; }
.lg-sv-btn-remove { display: inline-flex; align-items: center; gap: 8px; background: #FFFFFF; color: #DC2626 !important; border: 1.5px solid #DC2626; font-size: 13.5px; font-weight: 600; height: 40px; padding: 0 18px; border-radius: 10px; cursor: pointer; }
.lg-sv-btn-remove:hover { background: #FEF2F2; }
.lg-sv-card button:disabled { opacity: .55; cursor: not-allowed; }
.lg-sv-spin { width: 14px; height: 14px; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; display: inline-block; animation: lgSvSpin .7s linear infinite; }
@keyframes lgSvSpin { to { transform: rotate(360deg); } }
.lg-sv-modal { position: fixed; inset: 0; z-index: 2100; background: rgba(15, 23, 42, .45); display: flex; align-items: center; justify-content: center; padding: 16px; }
.lg-sv-modal[hidden] { display: none; }
.lg-sv-modal-card { background: #FFFFFF; border-radius: 14px; box-shadow: 0 20px 40px rgba(15, 23, 42, .25); padding: 22px; width: 100%; max-width: 380px; }
.lg-sv-modal-card h4 { margin: 0 0 6px; font-size: 16px; font-weight: 800; color: #0F172A; }
.lg-sv-modal-card p { margin: 0 0 18px; font-size: 13.5px; color: #475569; }
.lg-sv-section-gap > * + * { margin-top: 18px; }
</style>

<div class="lg-card-section lg-sv-card" id="sarvamKeyCard">
    <div class="lg-section-header">
        <div class="lg-sv-head-left">
            <div class="lg-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
            </div>
            <div>
                <div>Transcription · Sarvam AI</div>
                <div class="lg-sv-sub">Add your company's Sarvam API key to run call transcripts</div>
            </div>
        </div>
        <span class="lg-sv-badge {{ $svBadge[1] }}" id="svBadge" role="status">{{ $svBadge[0] }}</span>
    </div>

    @if(!$svIsAdmin)
        {{-- other roles: the status only, never the key or the controls --}}
        <div class="lg-sv-note">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>{{ $svStatus === 'connected' ? 'Call transcription is set up for this company.' : 'Transcription is not set up. Please ask your admin.' }} Only admins can add, change or remove the Sarvam API key.</span>
        </div>
    @elseif(!$svSettings)
        <div class="lg-sv-note"><span>The transcription settings are not available yet. Run the database migrations, then reload this page.</span></div>
    @else
        <div class="lg-sv-section-gap">
            {{-- Key: input (no key yet / replacing) or the masked key --}}
            <div class="lg-field-group" style="margin-bottom:0">
                <label class="lg-field-label" for="svKeyInput">Sarvam API key <span style="color:#EF4444">*</span></label>
                <div id="svMaskedRow" class="lg-sv-masked" {{ $svState['masked_key'] ? '' : 'hidden' }}>
                    <span id="svMaskedKey" aria-label="Saved key ending in {{ $svState['last4'] }}">{{ $svState['masked_key'] }}</span>
                    <button type="button" class="lg-sv-eye" id="svReplaceBtn" title="Replace the key" aria-label="Replace the key">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </button>
                </div>
                <div class="lg-sv-keywrap" id="svKeyRow" {{ $svState['masked_key'] ? 'hidden' : '' }}>
                    <input type="password" id="svKeyInput" class="lg-input" placeholder="Paste your Sarvam API key here" autocomplete="off" spellcheck="false" aria-describedby="svKeyError">
                    <button type="button" class="lg-sv-eye" id="svEyeBtn" title="Show key" aria-label="Show key" aria-pressed="false">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="lg-sv-error" id="svKeyError" role="alert"></div>
                <div class="lg-sv-verified {{ $svState['verified_at'] ? '' : 'is-unverified' }}" id="svVerified" {{ $svState['masked_key'] ? '' : 'hidden' }}>
                    {{ $svState['verified_at'] ? 'Verified on ' . $svState['verified_at'] : 'Not verified yet' }}
                </div>
            </div>

            <div class="lg-sv-grid2">
                <div class="lg-field-group" style="margin-bottom:0">
                    <label class="lg-field-label" for="svLanguage">Transcription language</label>
                    <select id="svLanguage" class="lg-input lg-sv-select">
                        @foreach(\App\Models\CompanySarvamSetting::LANGUAGES as $code => $label)
                            <option value="{{ $code }}" {{ $svState['language'] === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg-field-group" style="margin-bottom:0">
                    <label class="lg-field-label" for="svScope">Recordings to transcribe</label>
                    <select id="svScope" class="lg-input lg-sv-select">
                        @foreach(\App\Models\CompanySarvamSetting::SCOPES as $code => $label)
                            <option value="{{ $code }}" {{ $svState['scope'] === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label class="lg-sv-toggle" for="svAuto">
                <input type="checkbox" id="svAuto" {{ $svState['auto_transcribe'] ? 'checked' : '' }}>
                <span class="lg-sv-track" aria-hidden="true"></span>
                <span>Transcribe new recordings automatically</span>
            </label>

            <div class="lg-sv-note">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>The key is stored encrypted for this company only. It is never shown in full after saving, and only admins can add, change, or remove it.</span>
            </div>

            <div class="lg-sv-stats" id="svUsage" {{ $svState['masked_key'] ? '' : 'hidden' }}>
                <div class="lg-sv-stat"><div class="lg-sv-stat-label">Transcribed this month</div><div class="lg-sv-stat-value"><span id="svUseCalls">{{ $svState['usage']['transcribed'] }}</span> <small style="font-size:12px;font-weight:600;color:#64748B">calls</small></div></div>
                <div class="lg-sv-stat"><div class="lg-sv-stat-label">Audio processed</div><div class="lg-sv-stat-value"><span id="svUseMin">{{ $svState['usage']['minutes'] }}</span> <small style="font-size:12px;font-weight:600;color:#64748B">min</small></div></div>
                <div class="lg-sv-stat"><div class="lg-sv-stat-label">Failed</div><div class="lg-sv-stat-value" id="svUseFailed">{{ $svState['usage']['failed'] }}</div></div>
            </div>
        </div>

        <div class="lg-sv-actions">
            <button type="button" class="lg-sv-btn-remove" id="svRemoveBtn" {{ $svState['masked_key'] ? '' : 'hidden' }}>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                <span>Remove key</span>
            </button>
            <button type="button" class="lg-btn-outline-primary" id="svTestBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a6 6 0 0 1-12 0V8z"/></svg>
                <span>Test key</span>
            </button>
            <button type="button" class="lg-btn-save" id="svSaveBtn" disabled>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span id="svSaveLabel">{{ $svState['masked_key'] ? 'Save changes' : 'Save key' }}</span>
            </button>
        </div>

        <div class="lg-sv-modal" id="svRemoveModal" role="alertdialog" aria-modal="true" aria-labelledby="svRemoveTitle" hidden>
            <div class="lg-sv-modal-card">
                <h4 id="svRemoveTitle">Remove the Sarvam key?</h4>
                <p>Transcription will stop for this company.</p>
                <div style="display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="lg-btn-outline-primary" id="svRemoveCancel">Cancel</button>
                    <button type="button" class="lg-btn-danger" id="svRemoveConfirm">Remove key</button>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            'use strict';
            var URLS = { show: "{{ route('sarvamKeyShow') }}", test: "{{ route('sarvamKeyTest') }}", save: "{{ route('sarvamKeySave') }}", del: "{{ route('sarvamKeyDelete') }}" };
            var TOKEN = "{{ csrf_token() }}";
            var $ = function (id) { return document.getElementById(id); };
            var el = {
                badge: $('svBadge'), maskedRow: $('svMaskedRow'), masked: $('svMaskedKey'), keyRow: $('svKeyRow'), key: $('svKeyInput'),
                eye: $('svEyeBtn'), replace: $('svReplaceBtn'), err: $('svKeyError'), verified: $('svVerified'), lang: $('svLanguage'),
                scope: $('svScope'), auto: $('svAuto'), usage: $('svUsage'), test: $('svTestBtn'), save: $('svSaveBtn'), saveLabel: $('svSaveLabel'),
                remove: $('svRemoveBtn'), modal: $('svRemoveModal'), mCancel: $('svRemoveCancel'), mConfirm: $('svRemoveConfirm')
            };
            var BADGES = { not_configured: ['Not configured', 'is-warn'], connected: ['Connected', 'is-ok'], invalid: ['Key invalid', 'is-bad'] };
            var state = @json($svState);
            var saved = { language: state.language, scope: state.scope, auto: !!state.auto_transcribe };

            function toast(type, msg) { if (window.toastr) toastr[type](msg); }
            function typedKey() { return (el.key.value || '').trim(); }
            function dirty() {
                return typedKey() !== '' || el.lang.value !== saved.language || el.scope.value !== saved.scope || el.auto.checked !== saved.auto;
            }
            function refreshSave() { el.save.disabled = !dirty(); }
            function setErr(msg) {
                el.err.textContent = msg || '';
                el.key.setAttribute('aria-invalid', msg ? 'true' : 'false');
            }
            function busy(btn, on, label) {
                if (on) {
                    btn.dataset.html = btn.innerHTML;
                    btn.innerHTML = '<span class="lg-sv-spin" aria-hidden="true"></span><span>' + label + '</span>';
                    btn.disabled = true;
                } else if (btn.dataset.html) {
                    btn.innerHTML = btn.dataset.html;
                    btn.disabled = false;
                    delete btn.dataset.html;
                }
            }
            // Render the card from the server state (never contains the key)
            function render(s) {
                state = s;
                saved = { language: s.language, scope: s.scope, auto: !!s.auto_transcribe };
                var b = BADGES[s.status] || BADGES.not_configured;
                el.badge.textContent = b[0];
                el.badge.className = 'lg-sv-badge ' + b[1];
                var hasKey = !!s.masked_key;
                el.maskedRow.hidden = !hasKey;
                el.keyRow.hidden = hasKey;
                el.masked.textContent = s.masked_key || '';
                el.masked.setAttribute('aria-label', hasKey ? 'Saved key ending in ' + s.last4 : '');
                el.verified.hidden = !hasKey;
                el.verified.textContent = s.verified_at ? 'Verified on ' + s.verified_at : (s.status === 'invalid' ? 'Sarvam rejected this key' : 'Not verified yet');
                el.verified.className = 'lg-sv-verified' + (s.verified_at && s.status !== 'invalid' ? '' : ' is-unverified');
                el.usage.hidden = !hasKey;
                if (s.usage) {
                    $('svUseCalls').textContent = s.usage.transcribed;
                    $('svUseMin').textContent = s.usage.minutes;
                    $('svUseFailed').textContent = s.usage.failed;
                }
                el.remove.hidden = !hasKey;
                el.saveLabel.textContent = hasKey ? 'Save changes' : 'Save key';
                el.lang.value = s.language;
                el.scope.value = s.scope;
                el.auto.checked = !!s.auto_transcribe;
                el.key.value = '';
                el.key.type = 'password';
                setErr('');
                refreshSave();
            }
            function send(method, url, body) {
                return fetch(url, {
                    method: method, credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
                    body: body ? JSON.stringify(body) : undefined
                }).then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (j) { j._status = res.status; return j; });
                });
            }
            function failMessage(j) {
                if (j._status === 429) return 'Too many attempts. Wait a minute and try again.';
                if (j._status === 403) return j.message || 'Only an admin can manage the Sarvam API key.';
                if (j._status === 419) return 'Your session has expired. Reload the page.';
                return j.message || 'Something went wrong. Try again.';
            }

            // Key input: trimmed on paste, show / hide, Enter does not submit the profile form
            el.key.addEventListener('paste', function (e) {
                var text = (e.clipboardData || window.clipboardData).getData('text');
                if (text != null) { e.preventDefault(); el.key.value = String(text).trim(); refreshSave(); setErr(''); }
            });
            el.key.addEventListener('input', function () { setErr(''); refreshSave(); });
            [el.key, el.lang, el.scope, el.auto].forEach(function (x) {
                x.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); if (!el.save.disabled) el.save.click(); } });
            });
            [el.lang, el.scope, el.auto].forEach(function (x) { x.addEventListener('change', refreshSave); });
            el.eye.addEventListener('click', function () {
                var show = el.key.type === 'password';
                el.key.type = show ? 'text' : 'password';
                el.eye.setAttribute('aria-pressed', show ? 'true' : 'false');
                el.eye.title = show ? 'Hide key' : 'Show key';
            });
            el.replace.addEventListener('click', function () {
                el.maskedRow.hidden = true;
                el.keyRow.hidden = false;
                el.key.focus();
            });

            function validKeyFormat(k) {
                if (/\s/.test(k)) return 'The key cannot contain spaces.';
                if (k.length < 20 || k.length > 200) return 'The key must be 20 to 200 characters long.';
                return '';
            }

            el.test.addEventListener('click', function () {
                var k = typedKey();
                if (k) { var f = validKeyFormat(k); if (f) { setErr(f); return; } }
                else if (!state.masked_key) { setErr('Paste your Sarvam API key first.'); el.key.focus(); return; }
                setErr('');
                busy(el.test, true, 'Testing...');
                send('POST', URLS.test, k ? { key: k } : {}).then(function (j) {
                    busy(el.test, false);
                    if (j.success) { toast('success', 'Key verified'); if (!k) render(j); return; }
                    if (j.result === 'invalid') { setErr('Key is invalid'); toast('error', 'Key is invalid'); if (!k) render(j); return; }
                    toast('error', failMessage(j));
                }).catch(function () { busy(el.test, false); toast('error', 'Could not reach the server. Try again.'); });
            });

            el.save.addEventListener('click', function () {
                if (el.save.disabled) return;
                var k = typedKey();
                if (!k && !state.masked_key) { setErr('Paste your Sarvam API key first.'); el.key.focus(); return; }
                if (k) { var f = validKeyFormat(k); if (f) { setErr(f); return; } }
                setErr('');
                busy(el.save, true, 'Saving...');
                el.test.disabled = true;
                var body = { language: el.lang.value, scope: el.scope.value, auto_transcribe: el.auto.checked };
                if (k) body.key = k;
                send('POST', URLS.save, body).then(function (j) {
                    busy(el.save, false);
                    el.test.disabled = false;
                    if (j.success) {
                        render(j);
                        if (j.warning) toast('warning', j.warning); else toast('success', j.message || 'Saved');
                        if (j.resumed) toast('info', j.resumed + ' waiting transcript' + (j.resumed === 1 ? '' : 's') + ' resumed');
                        return;
                    }
                    if (j.errors && j.errors.key) setErr(j.errors.key);
                    toast('error', failMessage(j));
                    refreshSave();
                }).catch(function () { busy(el.save, false); el.test.disabled = false; refreshSave(); toast('error', 'Could not reach the server. Try again.'); });
            });

            function closeModal() { el.modal.hidden = true; el.remove.focus(); }
            el.remove.addEventListener('click', function () { el.modal.hidden = false; el.mCancel.focus(); });
            el.mCancel.addEventListener('click', closeModal);
            el.modal.addEventListener('click', function (e) { if (e.target === el.modal) closeModal(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !el.modal.hidden) closeModal(); });
            el.mConfirm.addEventListener('click', function () {
                busy(el.mConfirm, true, 'Removing...');
                send('DELETE', URLS.del).then(function (j) {
                    busy(el.mConfirm, false);
                    el.modal.hidden = true;
                    if (j.success) { render(j); toast('success', 'Sarvam key removed'); return; }
                    toast('error', failMessage(j));
                }).catch(function () { busy(el.mConfirm, false); toast('error', 'Could not reach the server. Try again.'); });
            });

            refreshSave();
        });
        </script>
    @endif
</div>
