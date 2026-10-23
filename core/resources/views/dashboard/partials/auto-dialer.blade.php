{{--
    Auto Dialer (Calling Workspace, Campaign Queue tab only): a floating button and popup that dial the selected
    campaign's queue one lead after another with the page's own campaign calling code (placeCampaignCall), so the
    calling toast, timer, call logs, recordings and Call Outcome saving are the normal ones. The session (one per
    agent) lives on the server: AutoDialerController (routes autoDialer*).
--}}
@php
    $adUser = Auth::user();
    $adC2cOn = (int)$adUser->c2c_channel === 1;
    $adAgentNumber = $adUser->click2CallAgentNumber() !== null;
    $adC2cVmn = collect($vmnList ?? [])->where('available', true)->count() > 0;
    $adWebOn = (int)$adUser->webrtc_channel === 1;
    $adWebVmn = collect($wvmnList ?? [])->where('available', true)->count() > 0;
    $adNotes = [
        'click2call' => !$adC2cOn ? 'Click2Call is not enabled for your account'
            : (!$adAgentNumber ? 'Your agent mobile number is not configured' : (!$adC2cVmn ? 'No Click2Call VMN is assigned to you' : null)),
        'webrtc' => !$adWebOn ? 'WebRTC is not enabled for your account' : (!$adWebVmn ? 'No WebRTC VMN is assigned to you' : null),
    ];
@endphp
<style>
/* Auto Dialer: fixed colors in every theme (navy idle, green running, amber paused) */
.lg-ad { position: fixed; right: 20px; bottom: 20px; z-index: 1990; font-family: var(--lg-font-sans, inherit); }
.lg-ad[hidden] { display: none; }
.lg-ad-btn { width: 78px; height: 70px; border-radius: 16px; border: none; background: #0B1730; color: #FFFFFF; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; padding: 6px 4px; cursor: pointer; box-shadow: 0 8px 20px rgba(11, 23, 48, 0.28); transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease; position: relative; }
.lg-ad-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(11, 23, 48, 0.36); }
.lg-ad-btn:focus { outline: none; }
.lg-ad-btn:focus-visible { box-shadow: 0 0 0 3px #FFFFFF, 0 0 0 6px #1D63D6, 0 8px 20px rgba(11, 23, 48, 0.28); }
.lg-ad-icon { width: 30px; height: 30px; border-radius: 50%; background: #FFFFFF; color: #0B1730; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.lg-ad-label { font-size: 10.5px; font-weight: 700; line-height: 1.15; text-align: center; letter-spacing: .1px; }
.lg-ad-sub { font-size: 9.5px; font-weight: 600; opacity: .9; line-height: 1; }
.lg-ad-sub:empty { display: none; }
.lg-ad.is-running .lg-ad-btn { background: #12A66A; box-shadow: 0 8px 20px rgba(18, 166, 106, 0.32); }
.lg-ad.is-running .lg-ad-icon { color: #12A66A; }
.lg-ad.is-paused .lg-ad-btn { background: #D97706; box-shadow: 0 8px 20px rgba(217, 119, 6, 0.32); }
.lg-ad.is-paused .lg-ad-icon { color: #D97706; }
.lg-ad-dot { position: absolute; top: 8px; right: 8px; width: 8px; height: 8px; border-radius: 50%; background: #FFFFFF; display: none; }
.lg-ad.is-running .lg-ad-dot { display: block; animation: lgAdPulse 1.4s ease-in-out infinite; }
@keyframes lgAdPulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .35; transform: scale(.7); } }

.lg-ad-pop { position: absolute; right: 0; bottom: calc(100% + 10px); width: 220px; background: var(--t-bg-card, #FFFFFF); color: var(--t-text, #0F172A); border-radius: 12px; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.20); padding: 14px; box-sizing: border-box; animation: lgAdPopIn .16s ease-out; }
.lg-ad-pop[hidden] { display: none; }
@keyframes lgAdPopIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.lg-ad-pop-title { font-size: 13px; font-weight: 700; margin: 0 0 10px; color: var(--t-text, #0F172A); }
.lg-ad-field-label { display: block; font-size: 11.5px; font-weight: 600; color: var(--t-text-muted, #64748B); margin-bottom: 2px; }
.lg-ad-select { width: 100%; height: 32px; border: none; border-bottom: 1.5px solid #CBD5E1; border-radius: 0; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--t-text, #0F172A); padding: 0 2px; outline: none; cursor: pointer; }
.lg-ad-select:focus { border-bottom-color: #1D63D6; box-shadow: 0 1px 0 0 #1D63D6; }
.lg-ad-note { font-size: 11.5px; color: #B45309; margin-top: 6px; line-height: 1.35; }
.lg-ad-note:empty { display: none; }
.lg-ad-actions { display: flex; gap: 8px; margin-top: 12px; }
.lg-ad-start, .lg-ad-act { flex: 1; height: 36px; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 7px; cursor: pointer; transition: filter .15s ease; }
.lg-ad-start { background: #1D63D6; color: #FFFFFF; box-shadow: 0 2px 6px rgba(29, 99, 214, 0.30); }
.lg-ad-start:hover:not(:disabled), .lg-ad-act:hover:not(:disabled) { filter: brightness(.94); }
.lg-ad-start:disabled, .lg-ad-act:disabled { opacity: .55; cursor: not-allowed; }
.lg-ad-start:focus-visible, .lg-ad-act:focus-visible, .lg-ad-select:focus-visible { outline: 2px solid #1D63D6; outline-offset: 2px; }
.lg-ad-act.pause { background: #FEF3C7; color: #92400E; }
.lg-ad-act.resume { background: #1D63D6; color: #FFFFFF; }
.lg-ad-act.stop { background: #DC2626; color: #FFFFFF; }
.lg-ad-act.secondary { background: var(--t-bg-alt, #F1F5F9); color: var(--t-text-body, #334155); }
.lg-ad-info { font-size: 12.5px; color: var(--t-text-body, #334155); line-height: 1.5; }
.lg-ad-info strong { color: var(--t-text, #0F172A); }
.lg-ad-type-ro { font-size: 13.5px; font-weight: 700; padding: 4px 2px 6px; border-bottom: 1.5px solid #E2E8F0; margin-bottom: 8px; }
.lg-ad-confirm { margin-top: 10px; font-size: 12.5px; font-weight: 600; }

.lg-ad-next { position: absolute; right: 0; bottom: calc(100% + 10px); background: #0F172A; color: #FFFFFF; border-radius: 10px; padding: 8px 10px 8px 12px; display: flex; align-items: center; gap: 10px; white-space: nowrap; font-size: 12.5px; font-weight: 600; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.25); }
.lg-ad-next[hidden] { display: none; }
.lg-ad-next button { border: none; background: #FFFFFF; color: #0F172A; border-radius: 6px; font-size: 12px; font-weight: 700; padding: 4px 10px; cursor: pointer; }
.lg-ad-sr { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0 0 0 0); border: 0; }
/* room so the floating button never sits on the Call Outcome buttons at the bottom of the page */
body.lg-ad-visible #tab-campaign-queue { padding-bottom: 96px; }
@media (prefers-reduced-motion: reduce) {
    .lg-ad-btn, .lg-ad-pop { transition: none; animation: none; }
    .lg-ad-btn:hover { transform: none; }
    .lg-ad.is-running .lg-ad-dot { animation: none; }
}
</style>

<div class="lg-ad" id="lgAd" data-theme-keep hidden>
    <div class="lg-ad-sr" id="lgAdLive" aria-live="polite" aria-atomic="true"></div>

    <div class="lg-ad-next" id="lgAdNext" role="status" hidden>
        <span id="lgAdNextText">Next call in 5s</span>
        <button type="button" id="lgAdNextPause">Pause</button>
    </div>

    <div class="lg-ad-pop" id="lgAdPop" role="dialog" aria-modal="false" aria-labelledby="lgAdPopTitle" hidden>
        {{-- Idle: choose the dialer type and start --}}
        <div id="lgAdIdle">
            <h3 class="lg-ad-pop-title lg-ad-sr" id="lgAdPopTitle">Auto Dialer</h3>
            <label class="lg-ad-field-label" for="lgAdType">Dialer Type</label>
            <select class="lg-ad-select" id="lgAdType" aria-describedby="lgAdTypeNote">
                <option value="click2call" {{ $adNotes['click2call'] ? 'disabled' : '' }}>Click2Call</option>
                <option value="webrtc" {{ $adNotes['webrtc'] ? 'disabled' : '' }}>WebRTC</option>
            </select>
            <div class="lg-ad-note" id="lgAdTypeNote"></div>
            <div class="lg-ad-actions">
                <button type="button" class="lg-ad-start" id="lgAdStart">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="6 4 20 12 6 20 6 4"/></svg>
                    Start
                </button>
            </div>
        </div>
        {{-- Session running / paused --}}
        <div id="lgAdSession" hidden>
            <span class="lg-ad-field-label">Dialer Type</span>
            <div class="lg-ad-type-ro" id="lgAdSessType"></div>
            <div class="lg-ad-info">
                <div><strong id="lgAdSessCampaign"></strong></div>
                <div id="lgAdSessCounts">Calls made: 0</div>
                <div id="lgAdSessState"></div>
            </div>
            <div class="lg-ad-actions" id="lgAdSessActions">
                <button type="button" class="lg-ad-act pause" id="lgAdPause">Pause</button>
                <button type="button" class="lg-ad-act resume" id="lgAdResume" hidden>Resume</button>
                <button type="button" class="lg-ad-act stop" id="lgAdStop">Stop</button>
            </div>
            <div id="lgAdConfirm" hidden>
                <div class="lg-ad-confirm">Stop auto dialing?</div>
                <div class="lg-ad-actions">
                    <button type="button" class="lg-ad-act secondary" id="lgAdConfirmNo">No</button>
                    <button type="button" class="lg-ad-act stop" id="lgAdConfirmYes">Yes, stop</button>
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="lg-ad-btn" id="lgAdBtn" aria-label="Auto Dialer" aria-haspopup="dialog" aria-expanded="false" aria-controls="lgAdPop">
        <span class="lg-ad-dot" aria-hidden="true"></span>
        <span class="lg-ad-icon" aria-hidden="true">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
        </span>
        <span class="lg-ad-label" id="lgAdLabel">Auto Dialer</span>
        <span class="lg-ad-sub" id="lgAdSub"></span>
    </button>
</div>

<script>
(function () {
    'use strict';

    var NEXT_CALL_DELAY_SECONDS = 5;      // countdown between an outcome save and the next call
    var HEARTBEAT_MS = 10000;
    var CONNECTION_LOST_MS = 30000;       // no server contact for this long ends the session
    var DIAL_WATCHDOG_MS = 25000;         // a call that neither starts nor fails in this time counts as failed
    var WAIT_RETRY_MS = 10000;            // only leads other agents are calling are left: look again after this

    var URLS = {
        status: "{{ route('autoDialerStatus') }}",
        start: "{{ route('autoDialerStart') }}",
        next: "{{ route('autoDialerNext') }}",
        event: "{{ route('autoDialerEvent') }}",
        pause: "{{ route('autoDialerPause') }}",
        resume: "{{ route('autoDialerResume') }}",
        stop: "{{ route('autoDialerStop') }}"
    };
    var TOKEN = "{{ csrf_token() }}";
    var NOTES = @json($adNotes);
    var TYPE_LABEL = { click2call: 'Click2Call', webrtc: 'WebRTC' };
    var STORE_KEY = 'lg_autodial_type_{{ $adUser->id }}';

    var el = {
        root: document.getElementById('lgAd'), btn: document.getElementById('lgAdBtn'), label: document.getElementById('lgAdLabel'),
        sub: document.getElementById('lgAdSub'), pop: document.getElementById('lgAdPop'), live: document.getElementById('lgAdLive'),
        idle: document.getElementById('lgAdIdle'), sess: document.getElementById('lgAdSession'), type: document.getElementById('lgAdType'),
        note: document.getElementById('lgAdTypeNote'), start: document.getElementById('lgAdStart'),
        sessType: document.getElementById('lgAdSessType'), sessCampaign: document.getElementById('lgAdSessCampaign'),
        sessCounts: document.getElementById('lgAdSessCounts'), sessState: document.getElementById('lgAdSessState'),
        pause: document.getElementById('lgAdPause'), resume: document.getElementById('lgAdResume'), stop: document.getElementById('lgAdStop'),
        actions: document.getElementById('lgAdSessActions'), confirm: document.getElementById('lgAdConfirm'),
        confirmYes: document.getElementById('lgAdConfirmYes'), confirmNo: document.getElementById('lgAdConfirmNo'),
        next: document.getElementById('lgAdNext'), nextText: document.getElementById('lgAdNextText'), nextPause: document.getElementById('lgAdNextPause')
    };

    /*
     * session: the server's session (null = none). phase: where the current lead is:
     *   ready (nothing in progress) | loading (next lead being fetched / shown) | dialing (call requested) |
     *   in_call | awaiting_outcome (call over, outcome not saved) | countdown | waiting (leads busy elsewhere)
     */
    var session = null;
    var phase = 'ready';
    var undialedLeadId = null;     // lead shown but not called because the session was paused meanwhile
    var outcomeSavedEarly = false; // outcome saved while the call was still live
    var stopAfterCall = false;
    var remaining = null;
    var timers = { countdown: null, watchdog: null, wait: null, heartbeat: null };
    var lastServerOk = Date.now();

    function toast(type, msg) {
        if (window.toastr) window.toastr[type](msg); else console.log('[AutoDialer] ' + msg);
    }
    function announce(msg) {
        el.live.textContent = '';
        setTimeout(function () { el.live.textContent = msg; }, 50);
    }
    function api(url, data, method) {
        return $.ajax({ url: url, type: method || 'POST', dataType: 'json', data: $.extend({ _token: TOKEN }, data || {}) })
            .done(function () { lastServerOk = Date.now(); });
    }
    function errMsg(xhr, fallback) {
        return (xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback;
    }
    function isActive() { return !!session && (session.status === 'running' || session.status === 'paused'); }
    function isRunning() { return !!session && session.status === 'running'; }
    // A live call: WebRTC session, or a Click2Call call still being followed. (_activeCallIsCampaign is not used:
    // a Click2Call that fails to start leaves it set.)
    function callBusy() {
        return !!(window._sipCallInProgress || (typeof _callPollInterval !== 'undefined' && _callPollInterval));
    }
    function clearTimer(name) { if (timers[name]) { clearTimeout(timers[name]); clearInterval(timers[name]); timers[name] = null; } }

    /* ---------- visibility: Campaign Queue tab only ---------- */
    function onCampaignTab() {
        return (typeof activeWorkspaceTab !== 'undefined' ? activeWorkspaceTab : 'campaign') === 'campaign';
    }
    function syncVisibility() {
        var show = onCampaignTab();
        el.root.hidden = !show;
        document.body.classList.toggle('lg-ad-visible', show);
        if (!show) closePop(false);
    }

    /* ---------- button + popup rendering ---------- */
    function render() {
        el.root.classList.toggle('is-running', isRunning());
        el.root.classList.toggle('is-paused', !!session && session.status === 'paused');
        var calls = session ? session.calls_made : 0;
        var callsText = calls + (calls === 1 ? ' call' : ' calls');
        if (!isActive()) {
            el.label.textContent = 'Auto Dialer';
            el.sub.textContent = '';
            el.btn.setAttribute('aria-label', 'Auto Dialer');
        } else if (session.status === 'paused') {
            el.label.textContent = 'Paused';
            el.sub.textContent = callsText;
            el.btn.setAttribute('aria-label', 'Auto Dialer paused, ' + callsText);
        } else if (phase === 'countdown') {
            el.label.textContent = 'Next in ' + countdownLeft + 's';
            el.sub.textContent = callsText;
            el.btn.setAttribute('aria-label', 'Auto Dialing, next call in ' + countdownLeft + ' seconds');
        } else {
            el.label.textContent = 'Auto Dialing';
            el.sub.textContent = callsText;
            el.btn.setAttribute('aria-label', 'Auto Dialing, ' + callsText);
        }
        // campaign stays fixed while a session is active
        $('#cw-workspace-campaign-select').prop('disabled', isActive()).attr('title', isActive() ? 'Stop the auto dialer to change the campaign' : null);
        renderPop();
    }
    function renderPop() {
        el.idle.hidden = isActive();
        el.sess.hidden = !isActive();
        if (!isActive()) {
            var note = NOTES[el.type.value];
            el.note.textContent = note || '';
            el.start.disabled = !!note || !!(el.type.options[el.type.selectedIndex] || { disabled: true }).disabled;
            return;
        }
        el.sessType.textContent = TYPE_LABEL[session.dialer_type] || session.dialer_type;
        el.sessCampaign.textContent = session.campaign_name || ('Campaign #' + session.campaign_id);
        el.sessCounts.textContent = 'Calls made: ' + session.calls_made + (remaining !== null ? ' · Remaining: ' + remaining : '');
        el.sessState.textContent = stateText();
        el.pause.hidden = session.status !== 'running';
        el.resume.hidden = session.status !== 'paused';
    }
    function stateText() {
        if (stopAfterCall) return 'Stopping after the current call';
        if (session.status === 'paused') return phase === 'in_call' || phase === 'dialing' ? 'Paused (current call continues)' : 'Paused';
        return {
            loading: 'Loading the next lead…', dialing: 'Placing the call…', in_call: 'On call',
            awaiting_outcome: 'Save the call outcome to continue', countdown: 'Next call in ' + countdownLeft + 's',
            waiting: 'Waiting for leads other agents are calling', ready: 'Running'
        }[phase] || 'Running';
    }

    function openPop() {
        if (isActive()) {
            remaining = null;
            api(URLS.status, { details: 1 }, 'GET').done(function (r) {
                if (r.session) { mergeSession(r.session); remaining = r.session.remaining; renderPop(); }
            });
        }
        hideConfirm();
        renderPop();
        el.pop.hidden = false;
        el.btn.setAttribute('aria-expanded', 'true');
        var first = isActive() ? (session.status === 'running' ? el.pause : el.resume) : el.type;
        setTimeout(function () { first.focus(); }, 30);
    }
    function closePop(focusBtn) {
        if (el.pop.hidden) return;
        el.pop.hidden = true;
        el.btn.setAttribute('aria-expanded', 'false');
        if (focusBtn) el.btn.focus();
    }
    function showConfirm() { el.actions.hidden = true; el.confirm.hidden = false; el.confirmNo.focus(); }
    function hideConfirm() { el.actions.hidden = false; el.confirm.hidden = true; }

    function mergeSession(s) {
        session = s;
        if (s && s.remaining !== undefined) remaining = s.remaining;
    }

    /* ---------- start ---------- */
    function startSession() {
        var type = el.type.value;
        var campaignId = $('#cw-workspace-campaign-select').val() || (typeof currentCampaignId !== 'undefined' ? currentCampaignId : 0);
        if (!campaignId || campaignId === '0') return toast('error', 'Select a campaign to start auto dialing');
        if (typeof campaignQueue !== 'undefined' && campaignQueue.length === 0) return toast('error', 'No leads in the queue');
        if (window.callingHoursClosed) return toast('error', 'Calling hours are over. ' + (window.callingHoursNotice || ''));
        if (NOTES[type]) return toast('error', NOTES[type]);
        if (callBusy()) return toast('error', 'Finish the current call before starting the auto dialer.');

        el.start.disabled = true;
        api(URLS.start, { campaign_id: campaignId, dialer_type: type }).done(function (r) {
            try { localStorage.setItem(STORE_KEY, type); } catch (e) {}
            mergeSession(r.session);
            phase = 'ready';
            stopAfterCall = false;
            closePop(false);
            startHeartbeat();
            announce('Auto dialing started with ' + TYPE_LABEL[type]);
            toast('success', 'Auto dialing started (' + TYPE_LABEL[type] + ')');
            render();
            dialNext();
        }).fail(function (xhr) {
            toast('error', errMsg(xhr, 'The auto dialer could not start. Please try again.'));
        }).always(function () { renderPop(); });
    }

    /* ---------- the loop ---------- */
    function dialNext() {
        clearTimer('countdown'); clearTimer('wait');
        el.next.hidden = true;
        if (!isRunning() || stopAfterCall) return;
        if (callBusy()) { phase = 'in_call'; outcomeSavedEarly = true; render(); return; }   // continues when the call ends
        phase = 'loading';
        render();
        api(URLS.next, {}).done(function (r) {
            if (r.skipped && r.skipped.length) {
                var why = { retry_limit: 'maximum attempts reached', invalid_number: 'invalid number', do_not_call: 'do not call' };
                toast('info', 'Skipped ' + r.skipped.length + ' lead' + (r.skipped.length === 1 ? '' : 's') + ': ' +
                    r.skipped.map(function (s) { return '#' + s.lead_id + ' (' + (why[s.reason] || s.reason) + ')'; }).join(', '));
            }
            if (r.session) mergeSession(r.session);
            if (r.status === 'ended') return finish(r.reason, r.session);
            if (r.status === 'paused') { phase = 'ready'; render(); return; }
            if (r.status === 'wait') {
                phase = 'waiting'; render();
                timers.wait = setTimeout(dialNext, WAIT_RETRY_MS);
                return;
            }
            if (r.session && r.session.remaining !== undefined) remaining = r.session.remaining;
            showAndCall(r.lead_id);
        }).fail(function (xhr) {
            if (xhr.status === 409 && !(xhr.responseJSON && /outcome/i.test(xhr.responseJSON.message || ''))) {
                return finish('ended', session);
            }
            phase = 'ready';
            render();
            toast('error', errMsg(xhr, 'Could not get the next lead.'));
            pauseSession('Auto dialer paused: the next lead could not be loaded.');
        });
    }

    function showAndCall(leadId) {
        // The session's campaign is the one shown in the Campaign Queue tab
        if (typeof currentCampaignId !== 'undefined' && String(currentCampaignId) !== String(session.campaign_id)) {
            currentCampaignId = session.campaign_id;
            $('#current_campaign_id').val(session.campaign_id);
            $('#cw-workspace-campaign-select').val(String(session.campaign_id));
        }
        loadLead(leadId, function (ok) {
            if (!ok) { onCallFailed({ reason: 'load', message: 'The lead could not be loaded' }); return; }
            if (!isRunning() || stopAfterCall) { undialedLeadId = leadId; phase = 'ready'; render(); return; }
            placeCall(leadId);
        });
    }

    function placeCall(leadId) {
        undialedLeadId = null;
        outcomeSavedEarly = false;
        phase = 'dialing';
        render();
        clearTimer('watchdog');
        timers.watchdog = setTimeout(function () {
            if (phase === 'dialing') onCallFailed({ reason: 'timeout', message: 'The call did not start' });
        }, DIAL_WATCHDOG_MS);
        placeCampaignCall(session.dialer_type);
    }

    /* events from the page's campaign calling code (lgAdNotify) */
    function notify(evt, data) {
        if (!isActive()) return;
        if (evt === 'call_started' && phase === 'dialing') {
            clearTimer('watchdog');
            phase = 'in_call';
            session.calls_made++;
            render();
            api(URLS.event, { type: 'call_started', call_log_id: data.call_log_id || '' }).done(function (r) { if (r.session) mergeSession(r.session); render(); });
        } else if (evt === 'call_failed' && (phase === 'dialing' || phase === 'in_call')) {
            onCallFailed(data);
        } else if (evt === 'call_ended' && phase === 'in_call') {
            phase = 'awaiting_outcome';
            if (stopAfterCall) return stopNow();
            if (outcomeSavedEarly) { outcomeSavedEarly = false; return afterOutcome(); }
            announce('Call ended. Save the call outcome to continue auto dialing.');
            render();
        }
    }

    function onCallFailed(data) {
        clearTimer('watchdog');
        phase = 'ready';
        render();
        api(URLS.event, { type: 'call_failed', reason: data.reason || 'gateway', message: data.message || '' }).done(function (r) {
            if (r.session) mergeSession(r.session);
            if (r.status === 'ended') return finish(r.reason, r.session);
            if (r.action === 'pause') {
                toast('warning', r.message || 'Auto dialer paused after repeated failures');
                announce(r.message || 'Auto dialer paused after repeated failures');
                render();
                return;
            }
            if (stopAfterCall) return stopNow();
            if (isRunning()) startCountdown();
        }).fail(function () {
            pauseSession('Auto dialer paused: the call failed and the server could not be reached.');
        });
    }

    // Called by saveCampaignOutcome after the outcome is saved; true = the auto dialer continues (countdown, next lead)
    function onOutcomeSaved(leadId) {
        if (!isActive()) return false;
        api(URLS.event, { type: 'outcome_saved', lead_id: leadId }).done(function (r) { if (r.session) mergeSession(r.session); render(); });
        session.pending_outcome = false;
        if (!isRunning() || stopAfterCall) return false;
        if (phase === 'in_call' || phase === 'dialing') { outcomeSavedEarly = true; return true; }
        afterOutcome();
        return true;
    }
    function afterOutcome() {
        if (!isRunning()) { phase = 'ready'; render(); return; }
        startCountdown();
    }

    var countdownLeft = NEXT_CALL_DELAY_SECONDS;
    function startCountdown() {
        clearTimer('countdown');
        countdownLeft = NEXT_CALL_DELAY_SECONDS;
        phase = 'countdown';
        el.nextText.textContent = 'Next call in ' + countdownLeft + 's';
        el.next.hidden = !el.pop.hidden;
        render();
        announce('Next call in ' + countdownLeft + ' seconds');
        timers.countdown = setInterval(function () {
            countdownLeft--;
            if (countdownLeft <= 0) { clearTimer('countdown'); el.next.hidden = true; dialNext(); return; }
            el.nextText.textContent = 'Next call in ' + countdownLeft + 's';
            render();
        }, 1000);
    }

    /* ---------- pause / resume / stop / end ---------- */
    function pauseSession(message) {
        if (!isActive()) return;
        clearTimer('countdown'); clearTimer('wait');
        el.next.hidden = true;
        if (phase === 'countdown' || phase === 'waiting' || phase === 'loading') phase = 'ready';
        session.status = 'paused';
        render();
        api(URLS.pause, {}).done(function (r) { if (r.session) mergeSession(r.session); render(); });
        var msg = message || 'Auto dialer paused';
        toast(message ? 'warning' : 'info', msg);
        announce(msg);
    }

    function resumeSession() {
        el.resume.disabled = true;
        api(URLS.resume, {}).done(function (r) {
            if (r.status === 'ended') return finish(r.reason, r.session);
            mergeSession(r.session);
            announce('Auto dialing resumed');
            render();
            if (phase === 'in_call' || phase === 'dialing') return;               // continues after this call
            if (session.pending_outcome) { phase = 'awaiting_outcome'; render(); toast('info', 'Save the call outcome to continue auto dialing.'); return; }
            if (undialedLeadId && typeof currentLeadId !== 'undefined' && String(currentLeadId) === String(undialedLeadId)) return placeCall(undialedLeadId);
            dialNext();
        }).fail(function (xhr) {
            toast('error', errMsg(xhr, 'The auto dialer could not resume.'));
        }).always(function () { el.resume.disabled = false; });
    }

    function requestStop() {
        hideConfirm();
        if (phase === 'dialing' || phase === 'in_call' || callBusy()) {
            // never cut a live call: stop once it is over
            stopAfterCall = true;
            clearTimer('countdown'); el.next.hidden = true;
            announce('Auto dialing will stop after the current call');
            toast('info', 'Auto dialing will stop after the current call.');
            render();
            return;
        }
        stopNow();
    }
    function stopNow() {
        api(URLS.stop, {}).always(function (r) { finish('stopped', (r && r.session) || session); });
    }

    function finish(reason, s) {
        var calls = s ? s.calls_made : (session ? session.calls_made : 0);
        session = null;
        phase = 'ready';
        stopAfterCall = false;
        undialedLeadId = null;
        remaining = null;
        ['countdown', 'watchdog', 'wait', 'heartbeat'].forEach(clearTimer);
        el.next.hidden = true;
        var callsText = calls + ' call' + (calls === 1 ? '' : 's') + ' made';
        var msg = {
            queue_completed: ['success', 'Queue completed. ' + callsText + '.'],
            calling_hours_over: ['warning', 'Calling hours are over. Auto dialing stopped (' + callsText + ').'],
            stopped: ['info', 'Auto dialing stopped. ' + callsText + '.'],
            connection_lost: ['error', 'Connection to the server was lost. Auto dialing stopped.'],
            logout: ['info', 'Auto dialing ended.']
        }[reason] || ['info', 'Auto dialing ended. ' + callsText + '.'];
        toast(msg[0], msg[1]);
        announce(msg[1]);
        render();
        // the agent continues by hand: Next lead / Skip work again
        if (typeof lgUpdateOutcomeButtons === 'function') lgUpdateOutcomeButtons();
    }

    /* ---------- heartbeat (connection check) ---------- */
    function startHeartbeat() {
        clearTimer('heartbeat');
        lastServerOk = Date.now();
        timers.heartbeat = setInterval(function () {
            if (!isActive()) return clearTimer('heartbeat');
            api(URLS.status, {}, 'GET').done(function (r) {
                if (!r.session) return finish('ended', session);
                var localCalls = session.calls_made;
                mergeSession(r.session);
                session.calls_made = Math.max(localCalls, r.session.calls_made);
                render();
            }).fail(function () {
                if (Date.now() - lastServerOk > CONNECTION_LOST_MS) {
                    $.ajax({ url: URLS.stop, type: 'POST', data: { _token: TOKEN, reason: 'connection_lost' } });
                    finish('connection_lost', session);
                }
            });
        }, HEARTBEAT_MS);
    }

    /* ---------- restore after a page refresh: always paused ---------- */
    function restore() {
        api(URLS.status, {}, 'GET').done(function (r) {
            if (!r.session) return;
            mergeSession(r.session);
            phase = 'ready';
            var announcePaused = function () {
                toast('info', 'Auto dialer paused. Press Resume to continue');
                announce('Auto dialer paused. Press Resume to continue');
            };
            if (session.status === 'running') {
                session.status = 'paused';
                api(URLS.pause, {}).done(function (p) { if (p.session) mergeSession(p.session); render(); });
            }
            if (session.pending_outcome && session.current_lead_id) {
                phase = 'awaiting_outcome';
                if (typeof currentLeadId !== 'undefined' && String(currentLeadId) !== String(session.current_lead_id)) {
                    currentCampaignId = session.campaign_id;
                    $('#cw-workspace-campaign-select').val(String(session.campaign_id));
                    loadLead(session.current_lead_id);
                }
            }
            startHeartbeat();
            render();
            announcePaused();
        });
    }

    /* ---------- wiring ---------- */
    el.btn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (el.pop.hidden) openPop(); else closePop(false);
    });
    el.type.addEventListener('change', function () { renderPop(); });
    el.start.addEventListener('click', startSession);
    el.pause.addEventListener('click', function () { pauseSession(); });
    el.nextPause.addEventListener('click', function () { pauseSession(); });
    el.resume.addEventListener('click', resumeSession);
    el.stop.addEventListener('click', showConfirm);
    el.confirmNo.addEventListener('click', function () { hideConfirm(); el.stop.focus(); });
    el.confirmYes.addEventListener('click', function () { closePop(true); requestStop(); });
    document.addEventListener('click', function (e) { if (!el.root.contains(e.target)) closePop(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !el.pop.hidden) { closePop(true); } });

    // Leaving or refreshing the page during a session: browser confirmation; the session is kept paused
    window.addEventListener('beforeunload', function (e) {
        if (isActive()) { e.preventDefault(); e.returnValue = ''; }
    });
    window.addEventListener('pagehide', function () {
        if (isRunning() && navigator.sendBeacon) {
            var fd = new FormData();
            fd.append('_token', TOKEN);
            fd.append('reason', 'page_unload');
            navigator.sendBeacon(URLS.pause, fd);
        }
    });

    // Default dialer type: the last choice, else the agent's calling option, else Click2Call (only enabled ones)
    (function initType() {
        var saved = null;
        try { saved = localStorage.getItem(STORE_KEY); } catch (e) {}
        var own = typeof lgUserCallType === 'function' ? lgUserCallType() : '';
        var order = [saved, own === 'webrtc' ? 'webrtc' : 'click2call', 'click2call', 'webrtc'];
        for (var i = 0; i < order.length; i++) {
            if (order[i] && TYPE_LABEL[order[i]] && !NOTES[order[i]]) { el.type.value = order[i]; break; }
        }
    })();

    /*
     * Never cover the Call Outcome buttons (Skip / Save / Next lead): when the floating button would overlap them, it
     * moves just above the row (or below it when there is no room above), otherwise it sits in its corner.
     */
    var BASE_BOTTOM = 20, overlapQueued = false;
    function avoidOverlap() {
        overlapQueued = false;
        el.root.style.bottom = BASE_BOTTOM + 'px';
        var row = document.getElementById('cw-camp-outcome-btns');
        if (el.root.hidden || !row || !row.offsetParent) return;
        var r = row.getBoundingClientRect(), b = el.btn.getBoundingClientRect(), vh = window.innerHeight;
        var overlaps = b.left < r.right + 8 && b.right > r.left - 8 && b.top < r.bottom + 8 && b.bottom > r.top - 8;
        if (!overlaps) return;
        var above = vh - r.top + 12;                      // bottom offset that puts the button just above the row
        if (above + b.height < vh - 72) {                 // still clear of the top header
            el.root.style.bottom = above + 'px';
        } else {
            el.root.style.bottom = Math.max(BASE_BOTTOM, vh - r.bottom - b.height - 12) + 'px';
        }
    }
    function queueOverlapCheck() {
        if (overlapQueued) return;
        overlapQueued = true;
        requestAnimationFrame(avoidOverlap);
    }
    window.addEventListener('scroll', queueOverlapCheck, { passive: true, capture: true });
    window.addEventListener('resize', queueOverlapCheck);
    if (window.ResizeObserver) new ResizeObserver(queueOverlapCheck).observe(document.body);

    window.lgAutoDialer = {
        notify: notify,
        onOutcomeSaved: onOutcomeSaved,
        isRunning: isRunning,
        // "Next lead" during the countdown: the next call starts now (true = handled here)
        nextNow: function () {
            if (!isRunning() || phase !== 'countdown') return false;
            clearTimer('countdown');
            el.next.hidden = true;
            dialNext();
            return true;
        },
        onTabChange: function (tab) {
            syncVisibility();
            queueOverlapCheck();
            if (tab !== 'campaign' && isRunning()) pauseSession('Auto dialer paused while you are on Manual Dial.');
        }
    };

    syncVisibility();
    render();
    queueOverlapCheck();
    $(restore);
})();
</script>
