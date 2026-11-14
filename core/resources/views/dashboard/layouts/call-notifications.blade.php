{{-- Inbound call notifications: "Incoming call" popup (top right) + the Calls list of the bell (header); polled every 5 s
     while the tab is visible, every 30 s in the background (no websocket server needed). Notifications are created on the
     server when the inbound event arrives (InboundCallNotifier), so a call that came while the agent was away is waiting
     in the bell. --}}
<div id="lgCallToasts" style="position: fixed; top: 76px; right: 18px; z-index: 2000; display: flex; flex-direction: column; gap: 10px; width: 340px; max-width: calc(100vw - 36px);"></div>
<script>
(function () {
    var POLL = @json(route('notificationsPoll')), READ = @json(route('notificationsReadCalls')), TOKEN = @json(csrf_token());
    var since = 0, seen = {}, items = {}, toasts = {}, timer = null, busy = false, first = true;
    var store = {
        get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    };
    var ENT = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ENT[c]; }); }
    function clock(iso) { var d = new Date(iso); return isNaN(d) ? '' : d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }

    function renderBell(unread) {
        var badge = document.getElementById('lgCallBadge');
        if (badge) { badge.style.display = unread > 0 ? 'inline-block' : 'none'; badge.textContent = unread > 99 ? '99+' : unread; }
        var list = document.getElementById('lgCallNotifList');
        if (!list) return;
        var rows = Object.keys(items).map(function (k) { return items[k]; })
            .sort(function (a, b) { return b.updated - a.updated || b.id - a.id; }).slice(0, 15);
        list.innerHTML = rows.length ? rows.map(function (n) {
            var d = n.data || {};
            var color = n.type === 'inbound_missed' ? '#F87171' : (d.state === 'ringing' ? '#34D399' : '#60A5FA');
            return '<li style="padding: 7px 8px; margin-bottom: 4px; border-radius: 6px; background: rgba(255,255,255,' + (n.read ? '0.03' : '0.08') + '); font-size: 12px; color: #fff;">'
                + '<span style="display: block; font-weight: 700; color: ' + color + ';">' + esc(n.title)
                + ' <span style="font-weight: 400; color: rgba(255,255,255,0.55);">' + esc(clock(d.at)) + '</span></span>'
                + '<span style="display: block; color: rgba(255,255,255,0.85);">' + esc(n.body) + '</span>'
                + (d.url ? '<a href="' + esc(d.url) + '" onclick="lgCallNotifRead(' + n.id + ')" style="font-weight: 600; color: #60A5FA;">'
                    + (n.type === 'inbound_missed' ? 'Call back' : 'Open lead') + '</a>' : '')
                + '</li>';
        }).join('') : '<li style="padding: 8px 4px; font-size: 12px; color: rgba(255,255,255,0.6);">No call notifications</li>';
    }

    function closeToast(id) { var t = toasts[id]; if (t) { t.remove(); delete toasts[id]; } }

    function showToast(n) {
        var d = n.data || {}, box = document.getElementById('lgCallToasts');
        if (!box) return;
        var t = toasts[n.id];
        if (!t) { t = document.createElement('div'); toasts[n.id] = t; box.appendChild(t); }
        var on = d.state === 'answered';
        t.setAttribute('role', 'alert');
        t.style.cssText = 'background: #FFFFFF; border: 1px solid ' + (on ? '#BFDBFE' : '#A7F3D0') + '; border-left: 4px solid ' + (on ? '#1464F4' : '#10B981')
            + '; border-radius: 10px; box-shadow: 0 12px 28px rgba(15,23,42,0.18); padding: 12px 14px; font-size: 13px; color: #0F172A;';
        t.innerHTML = '<div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">'
            + '<strong style="color: ' + (on ? '#1464F4' : '#047857') + ';">' + (on ? 'On a call' : 'Incoming call') + '</strong>'
            + '<button type="button" aria-label="Dismiss" data-dismiss-call style="border: none; background: transparent; font-size: 18px; line-height: 1; color: #64748B; cursor: pointer;">&times;</button></div>'
            + '<div style="font-size: 15px; font-weight: 700; margin-top: 4px;">' + esc(d.number ? '+91 ' + d.number : 'Unknown number') + '</div>'
            + '<div style="color: #475569; font-size: 12.5px;">' + esc([d.lead, d.campaign].filter(Boolean).join(' · ') || 'Not a lead yet')
            + (d.vmn ? ' · VMN ' + esc(d.vmn) : '') + '</div>'
            + '<div style="display: flex; gap: 8px; margin-top: 10px;">'
            + (d.url ? '<a href="' + esc(d.url) + '" style="flex: 1; text-align: center; background: #10B981; color: #fff; border-radius: 6px; padding: 6px 10px; font-weight: 600; text-decoration: none;">Open lead</a>' : '')
            + '<button type="button" data-dismiss-call style="flex: 1; border: 1px solid #E2E8F0; background: #fff; border-radius: 6px; padding: 6px 10px; font-weight: 600; color: #475569; cursor: pointer;">Dismiss</button></div>';
        t.querySelectorAll('[data-dismiss-call]').forEach(function (b) { b.onclick = function () { closeToast(n.id); }; });
    }

    // short ring, off by default (toggle in the bell)
    function ring() {
        if (store.get('lg_call_sound') !== '1') return;
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext, ctx = new Ctx(), o = ctx.createOscillator(), g = ctx.createGain();
            o.type = 'sine'; o.frequency.value = 880; g.gain.value = 0.08;
            o.connect(g); g.connect(ctx.destination);
            o.start(); o.stop(ctx.currentTime + 0.35);
            setTimeout(function () { try { ctx.close(); } catch (e) {} }, 600);
        } catch (e) {}
    }

    // desktop notification while the tab is in the background (HTTPS, permission given on a click in the bell)
    function desktop(n) {
        if (!document.hidden || !('Notification' in window) || !window.isSecureContext || Notification.permission !== 'granted') return;
        try {
            var x = new Notification(n.title, { body: n.body, tag: 'lg-call-' + n.id });
            x.onclick = function () { window.focus(); if (n.data && n.data.url) location.href = n.data.url; };
        } catch (e) {}
    }

    function apply(res) {
        (res.items || []).forEach(function (n) {
            var key = n.id + ':' + n.updated + ':' + n.type, isNew = !seen[key];
            seen[key] = 1;
            items[n.id] = n;
            var d = n.data || {}, fresh = (res.now - n.updated) < 90;
            if (d.state === 'ringing' || d.state === 'answered') {
                if (fresh) showToast(n);
            } else {
                closeToast(n.id);   // ended / missed: the popup closes
            }
            if (isNew && !first && (d.state === 'ringing' || n.type === 'inbound_missed')) {
                if (d.state === 'ringing') ring();
                desktop(n);
            }
        });
        first = false;
        since = Math.max(0, (res.now || 0) - 2);
        renderBell(res.unread || 0);
    }

    function schedule() { clearTimeout(timer); timer = setTimeout(poll, document.hidden ? 30000 : 5000); }
    function poll() {
        if (busy) return;
        busy = true;
        fetch(POLL + (since ? '?since=' + since : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (res) { if (res) apply(res); })
            .catch(function () {})
            .then(function () { busy = false; schedule(); });
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { clearTimeout(timer); poll(); } });

    function post(data) {
        var body = new URLSearchParams(Object.assign({ _token: TOKEN }, data || {}));
        return fetch(READ, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(function () {});
    }
    function desktopBtn() {
        var b = document.getElementById('lgDesktopAlertsBtn');
        if (b) b.style.display = ('Notification' in window) && window.isSecureContext && Notification.permission === 'default' ? 'inline' : 'none';
    }
    window.lgCallNotifReadAll = function () {
        post().then(function () { Object.keys(items).forEach(function (k) { items[k].read = true; }); renderBell(0); });
    };
    window.lgCallNotifRead = function (id) { post({ id: id }); };
    window.lgCallSoundSet = function (on) { store.set('lg_call_sound', on ? '1' : '0'); };
    window.lgDesktopAlertsAsk = function () {
        if (!('Notification' in window) || !window.isSecureContext) return;
        Notification.requestPermission().then(desktopBtn);
    };

    function start() {
        var s = document.getElementById('lgCallSoundToggle');
        if (s) s.checked = store.get('lg_call_sound') === '1';
        desktopBtn();
        poll();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
</script>
