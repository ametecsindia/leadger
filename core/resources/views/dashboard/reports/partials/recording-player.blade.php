{{-- Recording player popup + Download / Play actions (Call Recordings) --}}
<div id="lgDlPlayer" class="lg-dl-player-overlay" onclick="if (event.target === this) lgDlClosePlayer()">
    <div class="lg-dl-player" role="dialog" aria-modal="true" aria-labelledby="lgDlPlayerTitle">
        <div class="lg-dl-player-head">
            <h3 class="lg-dl-player-title" id="lgDlPlayerTitle">Call Recording</h3>
            <button type="button" class="lg-dl-player-close" onclick="lgDlClosePlayer()" title="Close" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="lg-dl-player-body">
            <div class="lg-dl-player-meta">
                <div>Agent: <strong id="lgDlMetaAgent">-</strong></div>
                <div>Number: <strong id="lgDlMetaNumber">-</strong></div>
                <div>Date: <strong id="lgDlMetaDate">-</strong></div>
                <div>Duration: <strong id="lgDlMetaDuration">-</strong></div>
            </div>
            <div class="lg-dl-player-bar">
                <button type="button" class="lg-dl-player-toggle" id="lgDlToggle" onclick="lgDlToggle()" aria-label="Play or pause"><i class="fas fa-play"></i></button>
                <div class="lg-dl-player-track" id="lgDlTrack" onclick="lgDlSeek(event)"><div class="lg-dl-player-progress" id="lgDlProgress"></div></div>
                <span class="lg-dl-player-time" id="lgDlTime">00:00 / 00:00</span>
            </div>
            <div class="lg-dl-player-status" id="lgDlStatus"></div>
        </div>
    </div>
</div>
<script>
    /* Recording Download / Play, used by Call Recordings */
    let currentDialerAudio = null;

    function lgDlToast(type, msg) {
        if (typeof toastr !== 'undefined' && toastr[type]) { toastr[type](msg); } else { alert(msg); }
    }

    function lgDlFmt(sec) {
        sec = Math.max(0, Math.floor(sec || 0));
        const h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s2 = sec % 60;
        const p = n => String(n).padStart(2, '0');
        return h ? h + ':' + p(m) + ':' + p(s2) : p(m) + ':' + p(s2);
    }

    // Ask the protected recording route whether this recording can be served, without downloading it
    function lgDlCheck(url) {
        return fetch(url, { method: 'HEAD', credentials: 'same-origin', redirect: 'manual' }).then(function (res) {
            if (res.type === 'opaqueredirect' || (res.status >= 200 && res.status < 400)) return null; // ok (a redirect = provider copy)
            if (res.status === 401 || res.status === 419) return 'Your session has expired. Please refresh the page and log in again.';
            if (res.status === 403) return 'You are not allowed to access this recording.';
            if (res.status === 404) return 'The recording file is not available (missing or deleted).';
            return 'The recording could not be loaded (server error ' + res.status + ').';
        }).catch(function () { return 'Network error - the recording could not be reached.'; });
    }

    function lgDlDownload(btn) {
        const url = btn.getAttribute('data-url');
        btn.disabled = true;
        lgDlCheck(url).then(function (err) {
            btn.disabled = false;
            if (err) { lgDlToast('error', err); return; }
            // same-origin, authenticated download of this row's recording (Content-Disposition: attachment)
            const a = document.createElement('a');
            a.href = url; a.setAttribute('download', '');
            document.body.appendChild(a); a.click(); a.remove();
        });
    }

    function lgDlPlay(btn) {
        lgDlStopAudio();
        const url = btn.getAttribute('data-url') + '?play=1';
        $('#lgDlMetaAgent').text(btn.getAttribute('data-agent') || '-');
        $('#lgDlMetaNumber').text(btn.getAttribute('data-number') || '-');
        $('#lgDlMetaDate').text(btn.getAttribute('data-date') || '-');
        $('#lgDlMetaDuration').text(btn.getAttribute('data-duration') || '-');
        $('#lgDlProgress').css('width', '0%');
        $('#lgDlTime').text('00:00 / 00:00');
        lgDlSetStatus('Loading recording...', false);
        $('#lgDlToggle').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $('#lgDlPlayer').addClass('active');

        // Start streaming at once (the server answers Range requests); the HEAD check that used to come first cost a
        // full extra request before any sound. It now only runs when playback fails, to say exactly why.
        {
            const audio = currentDialerAudio = new Audio(url);
            audio.preload = 'metadata';
            audio.addEventListener('loadedmetadata', function () {
                if (audio !== currentDialerAudio) return;
                $('#lgDlTime').text('00:00 / ' + lgDlFmt(audio.duration));
            });
            audio.addEventListener('timeupdate', function () {
                if (audio !== currentDialerAudio || !audio.duration) return;
                $('#lgDlProgress').css('width', (audio.currentTime / audio.duration * 100) + '%');
                $('#lgDlTime').text(lgDlFmt(audio.currentTime) + ' / ' + lgDlFmt(audio.duration));
            });
            // "Playing" only once audio is really playing
            audio.addEventListener('playing', function () {
                if (audio !== currentDialerAudio) return;
                $('#lgDlToggle').prop('disabled', false).html('<i class="fas fa-pause"></i>');
                lgDlSetStatus('Playing', false);
            });
            audio.addEventListener('pause', function () {
                if (audio !== currentDialerAudio) return;
                $('#lgDlToggle').prop('disabled', false).html('<i class="fas fa-play"></i>');
                if (!audio.ended) lgDlSetStatus('Paused', false);
            });
            audio.addEventListener('ended', function () {
                if (audio !== currentDialerAudio) return;
                $('#lgDlProgress').css('width', '0%');
                lgDlSetStatus('Finished', false);
            });
            audio.addEventListener('error', function () {
                if (audio !== currentDialerAudio) return;
                const code = audio.error ? audio.error.code : 0;
                // missing / not allowed / session expired answer like an unplayable file: ask the server which it is
                lgDlCheck(btn.getAttribute('data-url')).then(function (err) {
                    if (audio !== currentDialerAudio) return;
                    lgDlFail(err || (code === 4 ? 'This recording format cannot be played in this browser. Use Download instead.'
                                                : 'The recording could not be played.'));
                });
            });
            audio.play().catch(function (e) {
                if (audio !== currentDialerAudio) return;
                if (e && e.name === 'NotAllowedError') {
                    $('#lgDlToggle').prop('disabled', false).html('<i class="fas fa-play"></i>');
                    lgDlSetStatus('Press play to start.', false);
                } // other failures are reported by the 'error' event
            });
        }
    }

    function lgDlSetStatus(msg, isError) {
        $('#lgDlStatus').text(msg).toggleClass('error', !!isError);
    }

    function lgDlFail(msg) {
        lgDlStopAudio();
        $('#lgDlToggle').prop('disabled', true).html('<i class="fas fa-play"></i>');
        lgDlSetStatus(msg, true);
    }

    function lgDlToggle() {
        const a = currentDialerAudio;
        if (!a) return;
        if (a.paused) { a.play().catch(function () {}); } else { a.pause(); }
    }

    function lgDlSeek(e) {
        const a = currentDialerAudio;
        if (!a || !a.duration) return;
        const r = e.currentTarget.getBoundingClientRect();
        a.currentTime = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width)) * a.duration;
    }

    function lgDlStopAudio() {
        if (currentDialerAudio) {
            const a = currentDialerAudio;
            currentDialerAudio = null;
            a.pause();
            a.removeAttribute('src');
            a.load();
        }
    }

    function lgDlClosePlayer() {
        lgDlStopAudio();
        $('#lgDlPlayer').removeClass('active');
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && $('#lgDlPlayer').hasClass('active')) lgDlClosePlayer();
    });
</script>
