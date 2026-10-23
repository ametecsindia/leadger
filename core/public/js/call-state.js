/**
 * call-state.js - the WebRTC call's true state, independent of SIP.js and the page (unit-tested in tests/js).
 *
 *   connecting -> ringing (180/183) -> answered (200 OK, checking audio) -> connected -> ended
 *
 * "connected" needs ALL of: 200 OK for our INVITE (session established, ACK sent), ICE connected/completed,
 * and inbound RTP packets increasing after the 200 OK. A gateway that answers the browser leg but never reaches
 * the customer therefore never shows "Connected": after audioWait it warns, after audioGrace more it hangs up.
 *
 * handle(event) returns the actions the caller must perform:
 *   {type:'ringing'} {type:'connected'} {type:'warn', message}
 *   {type:'cancel', reason} (CANCEL the INVITE)  {type:'hangup', reason} (end an answered call)
 *   {type:'ended', outcome, reason, sipCode, sipReason, duration}   (once)
 * outcome: completed | busy | no-answer | cancelled | failed
 */

export const PHASE = Object.freeze({
    CONNECTING: 'connecting', RINGING: 'ringing', ANSWERED: 'answered', CONNECTED: 'connected', ENDED: 'ended',
});

const SIP_REASONS = {
    403: 'not allowed by the provider', 404: 'number not found', 407: 'provider login failed',
    408: 'no answer', 410: 'number not in service', 480: 'customer unavailable', 484: 'incomplete number',
    486: 'customer busy', 487: 'cancelled', 488: 'audio format not accepted', 500: 'provider error',
    502: 'provider network error', 503: 'provider unavailable', 600: 'customer busy', 603: 'customer declined',
};

/** Short reason for the agent (no technical text). */
export function sipReasonText(code, reason) {
    if (SIP_REASONS[code]) return SIP_REASONS[code];
    if (code >= 300 && code < 400) return 'number redirected by the provider';
    return code ? 'provider error ' + code : (reason || 'unknown error');
}

export function createCallMachine(options = {}) {
    const o = { ringTimeoutMs: 45000, audioWaitMs: 5000, audioGraceMs: 10000, ...options };
    const s = {
        phase: PHASE.CONNECTING, invitedAt: null, okAt: null, connectedAt: null,
        ice: null, rtpBaseline: null, rtpPackets: 0,
        sipCode: null, sipReason: '', cancelledByAgent: false, failReason: null, warned: false, stopping: false,
        result: null,
    };

    const ended = () => s.phase === PHASE.ENDED;
    const early = () => s.phase === PHASE.CONNECTING || s.phase === PHASE.RINGING;

    /** End the call from our side once: CANCEL before the 200 OK, BYE after. */
    function stop(reason) {
        if (s.stopping || ended()) return [];
        s.stopping = true;
        if (!s.failReason && reason) s.failReason = reason;
        return [{ type: early() ? 'cancel' : 'hangup', reason }];
    }

    function tryConnect(at) {
        if (s.phase !== PHASE.ANSWERED || s.stopping) return [];
        const iceOk = s.ice === 'connected' || s.ice === 'completed';
        const audio = s.rtpBaseline !== null && s.rtpPackets > s.rtpBaseline;
        if (!iceOk || !audio) return [];
        s.phase = PHASE.CONNECTED;
        s.connectedAt = at;
        return [{ type: 'connected' }];
    }

    function finish(at, code) {
        if (ended()) return [];
        if (code && !s.sipCode) s.sipCode = code;
        const c = s.sipCode;
        let outcome, reason = '';
        if (s.connectedAt !== null) {
            outcome = 'completed';
        } else if (s.failReason === 'ring-timeout') {
            outcome = 'no-answer'; reason = 'no answer';
        } else if (s.failReason === 'no-audio') {
            outcome = 'failed'; reason = 'no audio';
        } else if (s.failReason === 'ice') {
            outcome = 'failed'; reason = 'network problem (media could not connect)';
        } else if (s.cancelledByAgent || c === 487) {
            outcome = 'cancelled'; reason = 'cancelled';
        } else if (c === 486 || c === 600 || c === 603) {
            outcome = 'busy'; reason = sipReasonText(c);
        } else if (c === 408 || c === 480) {
            outcome = 'no-answer'; reason = sipReasonText(c);
        } else {
            outcome = 'failed'; reason = c ? sipReasonText(c, s.sipReason) : (s.failReason || 'the call was not connected');
        }
        s.phase = PHASE.ENDED;
        s.result = {
            type: 'ended', outcome, reason, sipCode: c, sipReason: s.sipReason || (c ? sipReasonText(c) : s.failReason || ''),
            duration: s.connectedAt !== null ? Math.max(0, Math.round((at - s.connectedAt) / 1000)) : 0,
        };
        return [s.result];
    }

    function handle(ev) {
        if (ended()) return [];
        const at = ev.at ?? Date.now();
        switch (ev.type) {
            case 'invite':
                s.invitedAt = at;
                return [];
            case 'response': {
                const code = ev.code | 0;
                if (code === 180 || code === 183) {
                    if (s.phase !== PHASE.CONNECTING) return [];
                    s.phase = PHASE.RINGING;
                    return [{ type: 'ringing' }];
                }
                if (code >= 200 && code < 300) {
                    if (!early()) return [];
                    s.phase = PHASE.ANSWERED;
                    s.okAt = at;
                    return tryConnect(at);
                }
                if (code >= 300) {                       // final failure for our INVITE
                    s.sipCode = code;
                    s.sipReason = ev.reason || '';
                }
                return [];
            }
            case 'ice':
                s.ice = ev.state;
                if (ev.state === 'failed') return s.connectedAt === null ? stop('ice') : stop();
                return tryConnect(at);
            case 'stats':
                if (s.phase !== PHASE.ANSWERED) {
                    s.rtpPackets = ev.packetsReceived | 0;
                    return [];
                }
                if (s.rtpBaseline === null) {            // first sample after the 200 OK
                    s.rtpBaseline = ev.packetsReceived | 0;
                    s.rtpPackets = s.rtpBaseline;
                    return [];
                }
                s.rtpPackets = ev.packetsReceived | 0;
                return tryConnect(at);
            case 'tick':
                if (early() && s.invitedAt !== null && at - s.invitedAt >= o.ringTimeoutMs) return stop('ring-timeout');
                if (s.phase === PHASE.ANSWERED && !s.stopping) {
                    if (at - s.okAt >= o.audioWaitMs + o.audioGraceMs) return stop('no-audio');
                    if (!s.warned && at - s.okAt >= o.audioWaitMs) {
                        s.warned = true;
                        return [{ type: 'warn', message: 'Connected to the gateway, but no audio from the customer yet' }];
                    }
                }
                return [];
            case 'agentHangup':
                if (early()) s.cancelledByAgent = true;
                return stop();
            case 'terminated':
                return finish(at, ev.code);
            default:
                return [];
        }
    }

    return { state: s, handle, options: o };
}
