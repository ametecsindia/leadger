/**
 * webrtc-softphone.js  � compact draggable right-side panel
 * No overlay. No centered modal. Small floating widget, right-middle.
 */

import {
    initSip,
    makeCall,
    hangupCall,
    muteCall,
    holdCall,
    onCallEnded,
    onCallEstablished,
} from './sip-client.js';

// Re-exported so pages register listeners on the SAME sip-client instance that places the calls
export { hangupCall, muteCall, holdCall, onCallEnded, onCallEstablished, onCallRinging, hasActiveCall } from './sip-client.js';

/* --- STATE --------------------------------------------------------------- */
let _registered  = false;
let _timerHandle = null;
let _elapsedSecs = 0;
let _isMuted     = false;
let _isHold      = false;

/* --- IDs ------------------------------------------------------------------ */
const PANEL_ID      = 'sp-panel';
const TIMER_ID      = 'sp-timer';
const STATUS_ID     = 'sp-status';
const BTN_MUTE_ID   = 'sp-btn-mute';
const BTN_HOLD_ID   = 'sp-btn-hold';
const BTN_HANGUP_ID = 'sp-btn-hangup';

/* --- PUBLIC --------------------------------------------------------------- */
export async function initSoftphone(config) {
    window.__SIP_DOMAIN__ = config.domain;
    await initSip(config);
    _registered = true;
    onCallEnded(_onCallEnded);
    onCallEstablished(_onCallEstablished);
}

export async function makeSoftphoneCall(number, meta = {}) {
    if (!_registered) throw new Error('SIP not registered. Call initSoftphone() first.');
    _buildPanel(number);
    _setStatus('Calling�');
    await makeCall(number, meta);
}

export function destroySoftphone() {
    hangupCall();
    _removePanel();
    _registered = false;
}

/* --- STYLES --------------------------------------------------------------- */
function _injectStyles() {
    if (document.getElementById('sp-panel-styles')) return;
    const s = document.createElement('style');
    s.id = 'sp-panel-styles';
    s.textContent = `
        #sp-panel {
            position: fixed !important;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 230px;
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid rgba(38,95,156,0.25);
            box-shadow: 0 8px 28px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.10);
            z-index: 999999 !important;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
            user-select: none;
            -webkit-user-select: none;
        }
        #sp-panel.sp-dragging {
            transform: none !important;
            transition: none !important;
        }

        /* -- header -- */
        #sp-panel .sp-hdr {
            background: #265F9C;
            padding: 10px 13px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: grab;
        }
        #sp-panel .sp-hdr:active { cursor: grabbing; }
        #sp-panel .sp-hdr-left {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
        }
        #sp-panel .sp-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #4caf50;
            animation: sp-blink 2s ease-in-out infinite;
            flex-shrink: 0;
        }
        @keyframes sp-blink {
            0%,100% { box-shadow: 0 0 0 0 rgba(76,175,80,0.6); }
            50%      { box-shadow: 0 0 0 5px rgba(76,175,80,0); }
        }
        #sp-panel .sp-badge {
            background: rgba(255,255,255,0.2);
            color: #fff;
            border-radius: 20px;
            padding: 2px 9px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            border: 1px solid rgba(255,255,255,0.25);
        }

        /* -- body -- */
        #sp-panel .sp-body {
            padding: 13px 13px 15px;
            background: #ffffff;
        }

        /* number box */
        #sp-panel .sp-numbox {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f0f4fa;
            border: 1px solid rgba(38,95,156,0.15);
            border-radius: 9px;
            padding: 8px 11px;
            margin-bottom: 11px;
        }
        #sp-panel .sp-numbox svg {
            flex-shrink: 0;
            color: #265F9C;
        }
        #sp-panel .sp-numtext {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            letter-spacing: 0.3px;
        }

        /* timer row */
        #sp-panel .sp-timerrow {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2px;
            margin-bottom: 11px;
        }
        #sp-panel .sp-timerlabel {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #sp-panel .sp-timerval {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: 2px;
            font-variant-numeric: tabular-nums;
        }

        /* divider */
        #sp-panel .sp-hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 0 0 11px;
        }

        /* control buttons row */
        #sp-panel .sp-ctrlrow {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 9px;
        }
        #sp-panel .sp-ctrl {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border: 1px solid #ddd;
            border-radius: 9px;
            background: #f7f7f7;
            color: #444;
            padding: 9px 0 8px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
            font-family: Arial, sans-serif;
            line-height: 1;
        }
        #sp-panel .sp-ctrl svg { display: block; }
        #sp-panel .sp-ctrl:hover  { background: #eef2fa; border-color: #265F9C; color: #265F9C; }
        #sp-panel .sp-ctrl:active { transform: scale(0.96); }
        #sp-panel .sp-ctrl.active {
            background: #fff0f0;
            border-color: #e53935;
            color: #e53935;
        }

        /* hangup */
        #sp-panel .sp-hangup {
            width: 100%;
            border: none;
            border-radius: 9px;
            background: #e53935;
            color: #fff;
            padding: 10px 0;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: background 0.15s, opacity 0.15s;
            font-family: Arial, sans-serif;
            letter-spacing: 0.2px;
        }
        #sp-panel .sp-hangup:hover  { background: #c62828; }
        #sp-panel .sp-hangup:active { transform: scale(0.98); }
    `;
    document.head.appendChild(s);
}

/* --- BUILD PANEL ----------------------------------------------------------- */
function _buildPanel(number) {
    _removePanel();
    _injectStyles();

    const panel = document.createElement('div');
    panel.id = PANEL_ID;

    panel.innerHTML = `
        <div class="sp-hdr" id="sp-drag-handle">
            <div class="sp-hdr-left">
                <span class="sp-dot"></span>
                <span>WebRTC Softphone</span>
            </div>
            <span class="sp-badge" id="${STATUS_ID}">Calling�</span>
        </div>
        <div class="sp-body">

            <div class="sp-numbox">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#265F9C" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8a19.79 19.79 0 01-3.07-8.68A2 2 0 012 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16z"/>
                </svg>
                <span class="sp-numtext">${number}</span>
            </div>

            <div class="sp-timerrow">
                <span class="sp-timerlabel">Duration</span>
                <span class="sp-timerval" id="${TIMER_ID}">00:00</span>
            </div>

            <hr class="sp-hr">

            <div class="sp-ctrlrow">
                <button id="${BTN_MUTE_ID}" class="sp-ctrl" type="button" title="Mute">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/>
                        <path d="M19 10v2a7 7 0 01-14 0v-2M12 19v4M8 23h8"/>
                    </svg>
                    <span>Mute</span>
                </button>
                <button id="${BTN_HOLD_ID}" class="sp-ctrl" type="button" title="Hold">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="6" y="4" width="4" height="16"/>
                        <rect x="14" y="4" width="4" height="16"/>
                    </svg>
                    <span>Hold</span>
                </button>
            </div>

            <button id="${BTN_HANGUP_ID}" class="sp-hangup" type="button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.68 13.31a16 16 0 003.41 2.6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7 2 2 0 011.72 2v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.42 19.42 0 013.07 9.68 19.79 19.79 0 010 1.05 2 2 0 012 -1h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91" transform="rotate(135 12 12)"/>
                </svg>
                End call
            </button>

        </div>
    `;

    document.body.appendChild(panel);

    document.getElementById(BTN_MUTE_ID).addEventListener('click',   _handleMute);
    document.getElementById(BTN_HOLD_ID).addEventListener('click',   _handleHold);
    document.getElementById(BTN_HANGUP_ID).addEventListener('click', _handleHangup);

    _makeDraggable(panel, document.getElementById('sp-drag-handle'));
}

/* --- DRAG ----------------------------------------------------------------- */
function _makeDraggable(panel, handle) {
    let active = false, ox, oy, pl, pt;

    function onMouseMove(e) {
        if (!active) return;
        let nl = pl + (e.clientX - ox);
        let nt = pt + (e.clientY - oy);
        nl = Math.max(0, Math.min(nl, window.innerWidth  - panel.offsetWidth));
        nt = Math.max(0, Math.min(nt, window.innerHeight - panel.offsetHeight));
        panel.style.left = nl + 'px';
        panel.style.top  = nt + 'px';
    }

    function onMouseUp() {
        if (!active) return;
        active = false;
        document.body.style.userSelect = '';
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup',   onMouseUp);
    }

    handle.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return;
        e.preventDefault();

        // 1. Read the true visual position before touching anything
        const r = panel.getBoundingClientRect();

        // 2. Override ALL positional CSS with inline styles
        //    (inline styles beat everything except !important in stylesheet)
        panel.style.setProperty('top',       r.top  + 'px', 'important');
        panel.style.setProperty('left',      r.left + 'px', 'important');
        panel.style.setProperty('right',     'auto',        'important');
        panel.style.setProperty('bottom',    'auto',        'important');
        panel.style.setProperty('transform', 'none',        'important');
        panel.style.setProperty('margin',    '0',           'important');

        // 3. Add dragging class (now safe � inline styles already won)
        panel.classList.add('sp-dragging');

        active = true;
        ox = e.clientX;
        oy = e.clientY;
        pl = r.left;
        pt = r.top;

        document.body.style.userSelect = 'none';

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup',   onMouseUp);
    });
}
function _removePanel() {
    document.getElementById(PANEL_ID)?.remove();
}

/* --- HANDLERS ------------------------------------------------------------- */
function _handleMute() {
    _isMuted = muteCall();
    const btn = document.getElementById(BTN_MUTE_ID);
    if (!btn) return;
    btn.classList.toggle('active', _isMuted);
    btn.title = _isMuted ? 'Unmute' : 'Mute';
    btn.querySelector('span').textContent = _isMuted ? 'Unmute' : 'Mute';
    /* swap SVG path for muted mic */
    btn.querySelector('svg').innerHTML = _isMuted
        ? `<line x1="1" y1="1" x2="23" y2="23"/>
           <path d="M9 9v3a3 3 0 005.12 2.12M15 9.34V4a3 3 0 00-5.94-.6"/>
           <path d="M17 16.95A7 7 0 015 12v-2M19 10v2a7 7 0 01-.11 1.23M12 19v4M8 23h8"/>`
        : `<path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/>
           <path d="M19 10v2a7 7 0 01-14 0v-2M12 19v4M8 23h8"/>`;
}

function _handleHold() {
    _isHold = holdCall();
    const btn = document.getElementById(BTN_HOLD_ID);
    if (!btn) return;
    btn.classList.toggle('active', _isHold);
    btn.title = _isHold ? 'Resume' : 'Hold';
    btn.querySelector('span').textContent = _isHold ? 'Resume' : 'Hold';
    btn.querySelector('svg').innerHTML = _isHold
        ? `<polygon points="5 3 19 12 5 21 5 3"/>`
        : `<rect x="6" y="4" width="4" height="16"/>
           <rect x="14" y="4" width="4" height="16"/>`;
}

async function _handleHangup() {
    _stopTimer();
    await hangupCall();
    _removePanel();
}

/* --- SIP CALLBACKS -------------------------------------------------------- */
function _onCallEstablished() {
    _setStatus('On call');
    _startTimer();
}

function _onCallEnded(info) {
    _stopTimer();
    const labels = { completed: 'Call Ended', busy: 'Busy', 'no-answer': 'No Answer', cancelled: 'Cancelled', failed: 'Failed' };
    _setStatus(labels[info?.outcome] || 'Call Ended');
    setTimeout(_removePanel, 1500);
}

/* --- TIMER ---------------------------------------------------------------- */
function _startTimer() {
    _stopTimer();
    _elapsedSecs = 0;
    _timerHandle = setInterval(() => {
        _elapsedSecs++;
        const m = String(Math.floor(_elapsedSecs / 60)).padStart(2, '0');
        const s = String(_elapsedSecs % 60).padStart(2, '0');
        const el = document.getElementById(TIMER_ID);
        if (el) el.textContent = `${m}:${s}`;
    }, 1000);
}

function _stopTimer() {
    if (_timerHandle) { clearInterval(_timerHandle); _timerHandle = null; }
}

function _setStatus(text) {
    const el = document.getElementById(STATUS_ID);
    if (el) el.textContent = text;
}