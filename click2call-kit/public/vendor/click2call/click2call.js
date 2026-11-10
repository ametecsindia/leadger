/*
 * Click2Call kit - calling page. No framework needed.
 * Same flow as LeadGER's calling workspace (startClick2Call / pollClick2CallStatus / showLiveCallStatus):
 *   number (>= 10 digits) + usable VMN -> POST start -> "Calling..." -> poll status every 2 s
 *   -> Ringing -> Connected (timer) -> Ended (talk time) | Busy / No answer / Failed -> outcome form.
 *   Polling stops on a final state or after 15 minutes. The chosen VMN is remembered (localStorage).
 */
(function () {
    'use strict';
    var root = document.getElementById('c2c-dialer');
    if (!root) return;

    var $ = function (id) { return document.getElementById(id); };
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var input = $('c2c-mobile'), vmnSelect = $('c2c-vmn'), btn = $('c2c-call');
    var box = $('c2c-status'), boxText = $('c2c-status-text'), timerEl = $('c2c-timer'), form = $('c2c-outcome');
    var pollMs = (parseInt(root.dataset.pollSeconds, 10) || 2) * 1000;
    var pollMaxMs = (parseInt(root.dataset.pollMaxMinutes, 10) || 15) * 60000;
    var VMN_KEY = 'c2c_vmn';
    var KIND = { initiated: 'calling', ringing: 'calling', connected: 'connected', completed: 'ended', busy: 'failed', 'no-answer': 'failed', failed: 'failed' };
    var state = { busy: false, callId: null, pollTimer: null, tickTimer: null, connectedAt: null };
    var voucherOptions = vmnSelect.innerHTML;   // the voucher VMN list, to restore when switching back

    function digits() { return input.value.replace(/\D/g, ''); }
    function pool() { var r = document.querySelector('input[name="c2c-pool"]:checked'); return r ? r.value : ''; }
    function usableVmn() {
        var o = vmnSelect.options[vmnSelect.selectedIndex];
        return o && o.value && o.dataset.available !== '0' ? o.value : '';
    }
    function refreshButton() { btn.disabled = state.busy || digits().length < 10 || !usableVmn(); }
    function fmt(s) { s = Math.max(0, Math.floor(s)); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }

    function show(kind, text) {
        box.hidden = false;
        box.className = 'c2c-status is-' + kind;
        boxText.textContent = text;
        if (kind !== 'connected') { clearInterval(state.tickTimer); timerEl.textContent = ''; }
    }

    // remember the VMN (LeadGER: localStorage 'leadger_c2c_vmn')
    try { var saved = localStorage.getItem(VMN_KEY); if (saved && vmnSelect.querySelector('option[value="' + saved + '"]')) vmnSelect.value = saved; } catch (e) {}
    vmnSelect.addEventListener('change', function () { try { localStorage.setItem(VMN_KEY, vmnSelect.value); } catch (e) {} refreshButton(); });
    input.addEventListener('input', refreshButton);

    // Number type: voucher VMNs, or the Smart31 / 1600 pool (the server checks the number belongs to it)
    document.querySelectorAll('input[name="c2c-pool"]').forEach(function (r) {
        r.addEventListener('change', function () {
            if (!pool()) { vmnSelect.innerHTML = voucherOptions; refreshButton(); return; }
            vmnSelect.innerHTML = '<option value="">Loading...</option>';
            fetch(root.dataset.poolUrl.replace('__POOL__', pool()), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var list = (d && d.vmns) || [];
                    vmnSelect.innerHTML = list.length
                        ? list.map(function (v) { return '<option value="' + v.virtual_number + '" data-available="' + (v.available ? 1 : 0) + '">' + v.display + '</option>'; }).join('')
                        : '<option value="">No numbers of this type</option>';
                    refreshButton();
                })
                .catch(function () { vmnSelect.innerHTML = '<option value="">Could not load the numbers</option>'; refreshButton(); });
        });
    });

    btn.addEventListener('click', function () {
        if (state.busy) return;                         // no double calls
        state.busy = true; refreshButton();
        form.hidden = true;
        show('calling', 'Starting the call...');
        fetch(root.dataset.startUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ mobile: digits(), vmn: usableVmn(), pool: pool() || null, source: 'manual' })
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, d: d }; });
        }).then(function (res) {
            var d = res.d;
            if (!res.ok || !d.success) {
                var msg = d.message || (d.errors && Object.values(d.errors)[0][0]) || 'The call could not be started.';
                throw new Error(msg);
            }
            state.callId = d.id;
            show('calling', 'Calling... answer your phone');   // NOT connected: nobody has answered yet
            poll();
        }).catch(function (e) {
            show('error', e.message);
            state.busy = false; refreshButton();
        });
    });

    function poll() {
        var started = Date.now(), inFlight = false;
        clearInterval(state.pollTimer);
        state.pollTimer = setInterval(function () {
            if (inFlight) return;
            if (Date.now() - started > pollMaxMs) { finish('error', 'Call result not available'); return; }
            inFlight = true;
            fetch(root.dataset.statusUrl.replace('__ID__', state.callId), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (s) {
                    var kind = KIND[s.status] || 'calling';
                    if (s.final) {
                        finish(kind, s.status === 'completed' ? 'Call ended - talk time ' + fmt(s.talk_seconds) : s.message);
                    } else if (s.status === 'connected') {
                        if (!state.connectedAt) startTimer(s.connected_for || 0);
                        show('connected', s.message);
                        timerEl.textContent = fmt((Date.now() - state.connectedAt) / 1000);
                    } else {
                        show('calling', s.message);
                    }
                })
                .catch(function () { /* a temporary network problem: try again on the next tick */ })
                .then(function () { inFlight = false; });
        }, pollMs);
    }

    function startTimer(already) {
        state.connectedAt = Date.now() - already * 1000;
        clearInterval(state.tickTimer);
        state.tickTimer = setInterval(function () { timerEl.textContent = fmt((Date.now() - state.connectedAt) / 1000); }, 1000);
    }

    function finish(kind, text) {
        clearInterval(state.pollTimer);
        show(kind, text);
        state.busy = false; state.connectedAt = null; refreshButton();
        if (state.callId && kind !== 'error') form.hidden = false;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!state.callId) return;
        fetch(root.dataset.outcomeUrl.replace('__ID__', state.callId), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ outcome: $('c2c-outcome-value').value, notes: $('c2c-notes').value, follow_up_at: $('c2c-follow-up').value || null })
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, d: d }; });
        }).then(function (res) {
            if (!res.ok) throw new Error(res.d.message || 'The outcome could not be saved.');
            form.reset(); form.hidden = true; state.callId = null;
            show('ended', 'Outcome saved.');
        }).catch(function (err) { show('error', err.message); });
    });

    // a ?number= link fills the field once (never dials); remove it so a refresh does not refill it
    if (new URLSearchParams(location.search).has('number')) {
        var u = new URL(location.href); u.searchParams.delete('number');
        history.replaceState(history.state, '', u.pathname + u.search + u.hash);
    }
    refreshButton();
    if (digits().length >= 10) input.focus();
})();
