import {
    UserAgent,
    Registerer,
    Inviter,
    SessionState,
    URI,
    Web
} from "https://cdn.jsdelivr.net/npm/sip.js@0.20.0/+esm";
import { createCallMachine } from "./call-state.js?v=20261023b";

/* -----------------------------------------------------------------------------
 *  DEBUG TRACE (SIP_DEBUG=true): every SIP / WebRTC event to the console and, in
 *  batches, to the server log. Never passwords, Authorization headers or tokens.
 * ------------------------------------------------------------------------- */
const sipOptions = () => window.__SIP_OPTIONS__ || {};
let debugBuffer = [];
let debugTimer  = null;
const dbg = (event, data = {}) => {
    if (!sipOptions().debug) return;
    const clean = JSON.parse(JSON.stringify(data, (k, v) => /pass|auth|token|secret|nonce|response$/i.test(k) ? undefined : v));
    console.info("[SIP]", event, clean);
    debugBuffer.push({ t: new Date().toISOString(), ev: event, data: clean });
    clearTimeout(debugTimer);
    debugTimer = setTimeout(flushDebug, debugBuffer.length >= 40 ? 0 : 2000);
};
const flushDebug = () => {
    const opts = sipOptions();
    if (!debugBuffer.length || !opts.debugUrl) return;
    const events = debugBuffer.splice(0, 200);
    fetch(opts.debugUrl, {
        method: "POST", credentials: "same-origin", keepalive: true,
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": opts.csrf || "", "Accept": "application/json" },
        body: JSON.stringify({ call_id: currentCall?.meta?.callId || null, events }),
    }).catch(() => {});
};

/* -----------------------------------------------------------------------------
 *  STATE
 * ------------------------------------------------------------------------- */
let ua                      = null;
let registerer              = null;
let session                 = null;
let remoteAudio             = null;
let timerInterval           = null;
let seconds                 = 0;
const callEndListeners         = new Set();
const callEstablishedListeners = new Set();
const callRingingListeners     = new Set();
const callWarningListeners     = new Set();
let callTick                   = null;   // 500 ms: timeouts + inbound RTP stats while a call is up
// Current call: real SIP outcome, connect time, and an 'end already reported' guard
let currentCall             = null;
let isOnHold                = false;
let isMuted                 = false;
let audioCtx                = null;
let _registeredConfig       = null;

/* -----------------------------------------------------------------------------
 *  INIT / REGISTER
 *  Safe to call multiple times � tears down the old UA first.
 *  If called with the same config that is already registered, returns early.
 * ------------------------------------------------------------------------- */
export const initSip = async (config) => {

    // If already registered with the exact same credentials, skip re-init
    if (
        ua &&
        registerer?.state === "Registered" &&
        _registeredConfig?.username  === config.username  &&
        _registeredConfig?.domain    === config.domain    &&
        _registeredConfig?.websocket === config.websocket
    ) {
        console.log("? SIP already registered � reusing UA");
        return;
    }

    // Tear down any existing UA cleanly
    if (ua) {
        try { await registerer?.unregister(); } catch (_) {}
        try { await ua.stop();               } catch (_) {}
        ua                = null;
        registerer        = null;
        session           = null;
        _registeredConfig = null;
    }

    remoteAudio = document.getElementById("remoteAudio");
    if (!remoteAudio) throw new Error("No #remoteAudio element found in DOM");

    const uri = new URI("sip", config.username, config.domain);

    ua = new UserAgent({
        uri,
        transportOptions: {
            server:            config.websocket,
            keepAliveInterval: 20,
            traceSip:          true,
        },
        authorizationUsername: config.username,
        authorizationPassword: config.password,
        // SIP_DEBUG: start line of every SIP message in / out (+ CSeq, Reason, Warning, X- headers; never Authorization)
        logConnector: (level, category, label, content) => {
            if (!sipOptions().debug || typeof content !== "string" || !content.includes("SIP/2.0")) return;
            const lines = content.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
            const start = lines.find(l => /SIP\/2\.0/.test(l));
            if (!start) return;
            dbg(/^Sending/i.test(lines[0]) ? "sip-out" : "sip-in", {
                line: start,
                headers: lines.filter(l => /^(CSeq|Reason|Warning|X-[\w-]+):/i.test(l)),
            });
        },
        contactParams:         { transport: "ws" },
        hackWssInTransport:    true,
        forceRport:            true,
	    sessionTimersExpires: 0,
    	noAnswerTimeout:      0,
	    hackIpInContact:      false,
        sessionDescriptionHandlerFactoryOptions: {
            constraints: { audio: true, video: false },
            peerConnectionOptions: {
                rtcConfiguration: {
                    iceServers: [
                        { urls: "stun:stun.l.google.com:19302"  },
                        { urls: "stun:stun1.l.google.com:19302" },
                    ],
                    iceTransportPolicy: "all",
                    bundlePolicy:       "balanced",
                    rtcpMuxPolicy:      "require",
                    sdpSemantics:       "unified-plan",
                },
            },
        },
    });

    // Re-register automatically on reconnect
    ua.transport.stateChange.addListener(async (state) => {
        dbg("transport", { state });
        if (state === "Connected" && registerer) {
            try   { await registerer.register(); }
            catch (e) { console.warn("Re-register failed:", e); }
        }
    });

    dbg("ua-start", { user: config.username, domain: config.domain, websocket: config.websocket });
    await ua.start();

    registerer = new Registerer(ua, { expires: 120, refreshFrequency: 80 });
    registerer.stateChange.addListener((state) => dbg("registration", { state }));
    let rejected = null;
    await registerer.register({
        requestDelegate: {
            onReject(response) {
                rejected = response?.message?.statusCode || 0;
                dbg("register-rejected", { code: rejected, reason: response?.message?.reasonPhrase });
            },
        },
    });

    // register() resolves when REGISTER is sent: wait for the gateway to accept it before any call
    const registered = await new Promise((resolve) => {
        if (registerer.state === "Registered") return resolve(true);
        const t = setTimeout(() => resolve(registerer.state === "Registered"), 8000);
        registerer.stateChange.addListener((state) => {
            if (state === "Registered") { clearTimeout(t); resolve(true); }
            if (state === "Unregistered" && rejected) { clearTimeout(t); resolve(false); }
        });
    });
    if (!registered) {
        _registeredConfig = null;
        throw new Error(rejected === 401 || rejected === 403
            ? "Softphone login was refused by the provider (check the WebRTC username / password)"
            : "Softphone could not register with the provider");
    }

    _registeredConfig = { ...config };
    console.log("? SIP Registered �", config.username, "@", config.domain);
};

/* -----------------------------------------------------------------------------
 *  MAKE CALL
 *  target : plain number string e.g. "7702004422"
 *           OR full sip URI "sip:7702004422@domain"
 *  meta   : { callId, refId, agent, did, gateway, prefix }
 * ------------------------------------------------------------------------- */
let currentAgentId = null;
export const makeCall = async (target, meta = {}) => {

    currentAgentId = meta.agent;
    if (!ua) throw new Error("SIP UA not initialized. Call initSip() first.");

    // Clean up a stale terminated session
    if (session) {
        if (session.state !== SessionState.Terminated) {
            console.warn("Already on a call � ignoring makeCall");
            alert("Agent already on a call");
            return;
        }
        try { session.dispose(); } catch (_) {}
        session = null;
    }

    if (registerer?.state !== "Registered") {
        dbg("call-blocked", { reason: "not registered", registration: registerer?.state });
        throw new Error("Softphone is not registered with the provider");
    }

    // Microphone first: a missing / blocked microphone must fail clearly, not as a silent "connected" call
    try {
        const probe = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        probe.getTracks().forEach(t => t.stop());
    } catch (e) {
        dbg("microphone-error", { name: e?.name });
        throw new Error(e?.name === "NotAllowedError" || e?.name === "SecurityError"
            ? "Microphone permission is blocked - allow the microphone for this site"
            : e?.name === "NotFoundError" ? "No microphone found on this computer" : "Microphone could not be opened");
    }

    // Accept both "7702004422" and "sip:7702004422@domain"
    let targetURI;
    if (target.startsWith("sip:")) {
        targetURI = UserAgent.makeURI(target);
    } else {
        const clean = target.replace(/[^\d+]/g, "");
        targetURI   = UserAgent.makeURI(`sip:${clean}@${ua.configuration.uri.host}`);
    }
    if (!targetURI) throw new Error(`Cannot build SIP URI for: ${target}`);
    dbg("dial", { uri: targetURI.toString(), user: targetURI.user, host: targetURI.host, did: meta.did, gateway: meta.gateway, prefix: meta.prefix, refId: meta.refId });

    const extraHeaders = [];
    if (meta.callId)  extraHeaders.push(`X-CALL-ID: ${meta.callId}`);
    if (meta.refId)   extraHeaders.push(`X-REF-ID: ${meta.refId}`);
    if (meta.agent)   extraHeaders.push(`X-AGENT: ${meta.agent}`);
    if (meta.did)     extraHeaders.push(`X-DID: ${meta.did}`);
    if (meta.gateway) extraHeaders.push(`X-GATEWAY: ${meta.gateway}`);
    if (meta.prefix)  extraHeaders.push(`X-PREFIX: ${meta.prefix}`);

    session = new Inviter(ua, targetURI, {
     extraHeaders,
        earlyMedia: true,
        sessionDescriptionHandlerOptions: {
            constraints: { audio: true, video: false },
        },
    });

    isOnHold = false;
    isMuted  = false;

    const opts = sipOptions();
    const machine = createCallMachine({
        ringTimeoutMs: (opts.ringTimeout || 45) * 1000,
        audioWaitMs:   (opts.audioWait   || 5)  * 1000,
        audioGraceMs:  (opts.audioGrace  || 10) * 1000,
    });
    currentCall = { session, machine, meta, establishedAt: null, sipCode: null, ended: false };
    attachSessionEvents(session);

    // Every SIP response of our INVITE goes to the state machine
    const onResponse = (kind) => (response) => {
        const code = response?.message?.statusCode;
        const reason = response?.message?.reasonPhrase || "";
        dbg("invite-response", { kind, code, reason });
        if (code >= 300 && currentCall) currentCall.sipCode = code;
        if (kind === "accept") dbg("ack", { sent: true });
        runActions(machine.handle({ type: "response", code, reason }));
    };

    try {
        runActions(machine.handle({ type: "invite" }));
        startCallTick(session);
        await session.invite({
            requestDelegate: {
                onTrying:   onResponse("trying"),
                onProgress: onResponse("progress"),
                onAccept:   onResponse("accept"),
                onRedirect: onResponse("redirect"),
                onReject:   onResponse("reject"),
            },
        });
        dbg("invite-sent", { uri: targetURI.toString() });
    } catch (err) {
        dbg("invite-error", { message: err?.message });
        stopCallTick();
        session = null;
        currentCall = null;
        window._sipCallInProgress = false;
        throw new Error("The call could not be started");
    }
};

/* Perform what the state machine decided (see call-state.js). */
const runActions = (actions) => {
    for (const a of actions) {
        dbg("state", { action: a.type, phase: currentCall?.machine?.state?.phase, reason: a.reason, outcome: a.outcome });
        if (a.type === "ringing") notify(callRingingListeners, {});
        if (a.type === "warn") notify(callWarningListeners, { message: a.message });
        if (a.type === "connected") {
            if (currentCall) currentCall.establishedAt = Date.now();
            startLegacyTimer();
            notify(callEstablishedListeners, {});
        }
        if (a.type === "cancel") cancelInvite();
        if (a.type === "hangup") byeCall();
        if (a.type === "ended") cleanupCall(a);
    }
};

/* Timeouts and the inbound-audio check (getStats: inbound-rtp audio packetsReceived). */
const startCallTick = (sess) => {
    stopCallTick();
    callTick = setInterval(async () => {
        const m = currentCall?.machine;
        if (!m) return stopCallTick();
        const pc = sess?.sessionDescriptionHandler?.peerConnection;
        if (pc && m.state.phase === "answered") {
            try {
                let packets = 0, energy = 0, hasEnergy = false;
                (await pc.getStats()).forEach(r => {
                    if (r.type === "inbound-rtp" && (r.kind === "audio" || r.mediaType === "audio")) {
                        packets += r.packetsReceived || 0;
                        if (typeof r.totalAudioEnergy === "number") { energy += r.totalAudioEnergy; hasEnergy = true; }
                    }
                });
                dbg("rtp", { packetsReceived: packets, audioEnergy: hasEnergy ? Math.round(energy * 1e6) / 1e6 : null, ice: pc.iceConnectionState });
                runActions(m.handle({ type: "stats", packetsReceived: packets, audioEnergy: hasEnergy ? energy : null }));
            } catch (_) {}
        }
        runActions(m.handle({ type: "tick" }));
    }, 500);
};
const stopCallTick = () => { if (callTick) { clearInterval(callTick); callTick = null; } };

const startLegacyTimer = () => {
    clearInterval(timerInterval);
    seconds = 0;
    timerInterval = setInterval(() => {
        seconds++;
        const el = document.getElementById("sip-timer");
        if (el) el.innerText = `${String(Math.floor(seconds / 60)).padStart(2, "0")}:${String(seconds % 60).padStart(2, "0")}`;
    }, 1000);
};

/* CANCEL an INVITE that has no 200 OK yet (ringing timeout, End call while ringing, ICE failure). */
const cancelInvite = async () => {
    const sess = session;
    if (!sess) return;
    try {
        if (sess.state === SessionState.Initial || sess.state === SessionState.Establishing) {
            await sess.cancel();
            dbg("cancel-sent", {});
        } else if (sess.state === SessionState.Established) {
            byeCall();
        }
    } catch (e) {
        dbg("cancel-error", { message: e?.message });
        forceEnd();
    }
};

/* End an answered call: the provider's hangup API (as before) and a BYE if the gateway does not end it. */
const byeCall = async () => {
    const sess = session;
    if (!sess) return;
    try {
        await fetch("https://sip.telozar.com/api/webrtc/calls/hangup", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ agent_id: currentAgentId }),
        });
        dbg("hangup-api", { ok: true });
    } catch (err) {
        dbg("hangup-api", { ok: false, message: err?.message });
    }
    setTimeout(() => {
        if (session === sess && sess.state === SessionState.Established) {
            sess.bye().then(() => dbg("bye-sent", {})).catch(e => { dbg("bye-error", { message: e?.message }); forceEnd(); });
        }
    }, 1500);
    setTimeout(() => { if (session === sess) { dbg("safety-net", {}); forceEnd(); } }, 4000);
};

/* The session did not terminate by itself: end it locally and report the result. */
const forceEnd = () => {
    const m = currentCall?.machine;
    try { session?.dispose(); } catch (_) {}
    if (m) runActions(m.handle({ type: "terminated", code: currentCall?.sipCode }));
    else cleanupCall();
};

/* -----------------------------------------------------------------------------
 *  HANGUP (End call): CANCEL while ringing -> "Cancelled"; BYE once answered
 * ------------------------------------------------------------------------- */
export const hangupCall = async () => {
    if (!session) { closeSipDialerUI(); return; }
    dbg("agent-hangup", { state: session.state, phase: currentCall?.machine?.state?.phase });

    const m = currentCall?.machine;
    if (m) {
        runActions(m.handle({ type: "agentHangup" }));
        return;
    }
    // no tracked call (should not happen): end whatever is there
    try {
        if (session.state === SessionState.Initial || session.state === SessionState.Establishing) await session.cancel();
        else if (session.state === SessionState.Established) await session.bye();
        else { try { session.dispose(); } catch (_) {} cleanupCall(); }
    } catch (e) {
        console.error("hangupCall error:", e);
        cleanupCall();
    }
};
/* -----------------------------------------------------------------------------
 *  MUTE / UNMUTE
 * ------------------------------------------------------------------------- */
export const muteCall = () => {
    if (!session) return false;
    const pc = session?.sessionDescriptionHandler?.peerConnection;
    if (!pc) { console.warn("No peer connection for mute"); return false; }

    isMuted = !isMuted;
    pc.getSenders().forEach(sender => {
        if (sender.track?.kind === "audio") sender.track.enabled = !isMuted;
    });

    const btn = document.querySelector(".sip-btn.mute");
    if (btn) {
        btn.textContent      = isMuted ? "Unmute" : "Mute";
        btn.style.background = isMuted ? "#c62828" : "#ff9800";
    }
    return isMuted;
};

/* -----------------------------------------------------------------------------
 *  HOLD / UNHOLD  � local track-based implementation
 *
 *  Your FreeSWITCH/Kamailio setup rejects every re-INVITE with a non-2xx,
 *  so SIP-signalled hold (sendonly/recvonly via re-INVITE) cannot work here.
 *
 *  Instead we implement hold entirely on the WebRTC side:
 *    Hold   ? disable all local audio senders  +  pause remoteAudio playback
 *    Unhold ? re-enable all local audio senders + resume remoteAudio playback
 *
 *  The RTP stream stays up; the server never needs to know.
 *  The button UI in both sip-dialer (blade) and webrtc-softphone modal is
 *  updated identically to before so callers of holdCall() need no changes.
 * ------------------------------------------------------------------------- */
export const holdCall = () => {
    if (!session || session.state !== SessionState.Established) return isOnHold;

    const pc = session?.sessionDescriptionHandler?.peerConnection;
    if (!pc) {
        console.warn("holdCall: no peer connection");
        return isOnHold;
    }

    isOnHold = !isOnHold;

    // Silence / restore the local microphone track
    pc.getSenders().forEach(sender => {
        if (sender.track?.kind === "audio") {
            sender.track.enabled = !isOnHold;
        }
    });

    // Pause / resume the remote audio element so the agent hears silence on hold
    if (remoteAudio) {
        if (isOnHold) {
            remoteAudio.pause();
        } else {
            remoteAudio.play().catch(e => console.warn("remoteAudio.play() [unhold]:", e));
        }
    }

    console.log(isOnHold ? "? Local hold (track muted + audio paused)" : "? Local unhold (track restored + audio resumed)");

    // Update blade sip-dialer button if present
    const btn = document.querySelector(".sip-btn.hold");
    if (btn) {
        btn.textContent      = isOnHold ? "Unhold" : "Hold";
        btn.style.background = isOnHold ? "#c62828" : "#3f51b5";
    }

    return isOnHold;
};

/* -----------------------------------------------------------------------------
 *  PUBLIC CALLBACKS
 * ------------------------------------------------------------------------- */
export const onCallEnded       = (cb) => { callEndListeners.add(cb); };
export const onCallEstablished = (cb) => { callEstablishedListeners.add(cb); };
export const onCallRinging     = (cb) => { callRingingListeners.add(cb); };
export const onCallWarning     = (cb) => { callWarningListeners.add(cb); };
export const hasActiveCall     = ()   => !!session && session.state !== SessionState.Terminated;
const notify = (set, payload) => set.forEach(cb => { try { cb(payload); } catch (e) { console.error('SIP listener error:', e); } });

/* Final outcome from the real SIP result: 486/600 busy, 408/480 no answer, 487 cancelled, other 4xx-6xx failed */
export const outcomeFromSipCode = (code, wasEstablished, cancelledByAgent) => {
    if (wasEstablished) return 'completed';
    if (cancelledByAgent || code === 487) return 'cancelled';
    if (code === 486 || code === 600) return 'busy';
    if (code === 408 || code === 480) return 'no-answer';
    return 'failed';
};
export const isSipRegistered   = ()   =>
    !!(registerer && registerer.state === "Registered");

/* -----------------------------------------------------------------------------
 *  CLOSE DIALER UI
 * ------------------------------------------------------------------------- */
export const closeSipDialerUI = () => {
    const dialer = document.getElementById("sip-dialer");
    if (dialer) dialer.style.display = "none";

    const muteBtn = document.querySelector(".sip-btn.mute");
    if (muteBtn) { muteBtn.textContent = "Mute"; muteBtn.style.background = "#ff9800"; }

    const holdBtn = document.querySelector(".sip-btn.hold");
    if (holdBtn) { holdBtn.textContent = "Hold"; holdBtn.style.background = "#3f51b5"; }

    isMuted  = false;
    isOnHold = false;
};

/* -----------------------------------------------------------------------------
 *  INTERNAL � wire peer connection listeners as soon as the PC exists
 *  Polls every 100 ms until the SDH creates the RTCPeerConnection, then
 *  attaches ICE-state and track listeners exactly once.
 * ------------------------------------------------------------------------- */
const wirePeerConnectionListeners = (sess) => {
    let attempts = 0;
    const MAX    = 80; // 8 seconds total

    const poll = setInterval(() => {
        attempts++;
        const pc = sess?.sessionDescriptionHandler?.peerConnection;

        if (!pc) {
            if (attempts >= MAX) {
                clearInterval(poll);
                console.warn("?? Gave up waiting for RTCPeerConnection");
            }
            return;
        }

        clearInterval(poll);

        // -- track event (use addEventListener, NOT pc.ontrack) ----------
        // Assigning pc.ontrack overwrites sip.js's own handler; addEventListener
        // stacks safely alongside it.
        pc.addEventListener("track", (event) => {
            dbg("remote-track", { kind: event.track.kind, state: event.track.readyState, muted: event.track.muted });
            const stream = event.streams?.[0] ?? new MediaStream([event.track]);
            if (remoteAudio) {
                remoteAudio.srcObject = stream;
                remoteAudio.play().catch(e => console.warn("remoteAudio.play() [track]:", e));
                console.log("?? Audio attached via PC track event");
            }
        });

        // -- ICE connection state (also decides "connected" / ICE failure in the state machine)
        pc.addEventListener("iceconnectionstatechange", () => {
            const s = pc.iceConnectionState;
            dbg("ice", { state: s });
            if (s === "connected" || s === "completed") {
                attachRemoteAudio(sess);
            }
            if (currentCall?.session === sess) runActions(currentCall.machine.handle({ type: "ice", state: s }));
        });

        // -- General connection state ------------------------------------
        pc.addEventListener("connectionstatechange", () => {
            const s = pc.connectionState;
            dbg("peer-connection", { state: s });
            if (s === "connected") {
                attachRemoteAudio(sess);
            }
        });

        console.log("? PC listeners wired");

        // Attempt immediate audio attach in case tracks already exist
        attachRemoteAudio(sess);

    }, 100);
};

/* -----------------------------------------------------------------------------
 *  INTERNAL � session lifecycle
 * ------------------------------------------------------------------------- */
const attachSessionEvents = (sess) => {
   sess._pendingHangup = false;
    // Start wiring PC listeners immediately � before any state changes fire
    wirePeerConnectionListeners(sess);

    sess.delegate = {
        onProgress(response) {
            const status = response?.message?.statusCode;
            console.log("?? Progress:", status);

            // Attempt early media audio attach on 183
            if (status === 183) {
                console.log("?? 183 Session Progress � attempting early media attach");
                [0, 300, 700, 1500, 3000].forEach(ms =>
                    setTimeout(() => attachRemoteAudio(sess), ms)
                );
            }
        },
        onAccept() {
            console.log("?? onAccept fired � session:", sess.state, "pendingHangup:", sess._pendingHangup);
            if (sess._pendingHangup) {
                console.warn("?? Late 200 OK after BYE � sending immediate BYE for new dialog");
                setTimeout(async () => {
                    try {
                        if (sess.state === SessionState.Established) {
                            await sess.bye();
                            console.log("?? Second BYE sent for late-200 dialog");
                        }
                    } catch (e) {
                        console.warn("Second BYE error:", e);
                        cleanupCall();
                    }
                }, 100); // small delay to let ACK complete first
            }
        },
        onReinvite(request) {
            console.log("?? re-INVITE / 200 retransmit received � accepting immediately");
            try {
                request.accept({
                    sessionDescriptionHandlerOptions: {
                        constraints: { audio: true, video: false },
                    },
                }).catch(e => {
                    console.warn("re-INVITE accept error:", e);
                    // If accept fails, try reject so FreeSWITCH at least gets a response
                    try { request.reject({ statusCode: 488 }); } catch (_) {}
                });
            } catch (e) {
                console.warn("re-INVITE handler error:", e);
            }
        },
        // the end is reported once, from the Terminated state below
        onReject()  { dbg("session-reject", {}); },
        onCancel()  { dbg("session-cancel", {}); },
        onBye()     { dbg("remote-bye", {}); },
        onTrack()   { attachRemoteAudio(sess); },
    };

    sess.stateChange.addListener((state) => {
        dbg("session", { state });

        if (state === SessionState.Establishing) {
            // No timer while ringing: the duration starts only when the customer is really connected.
            // Wire PC listeners again in case the SDH was created lazily
            wirePeerConnectionListeners(sess);
        }

        if (state === SessionState.Established) {
            if (sess._pendingHangup) {
                dbg("established-after-hangup", {});
                sess.bye().catch(e => { dbg("bye-error", { message: e?.message }); forceEnd(); });
                return;
            }
            // 200 OK + ACK only: "connected" also needs ICE and inbound audio (call-state.js, checked every 500 ms)
            [0, 300, 700, 1500, 3000].forEach(ms =>
                setTimeout(() => attachRemoteAudio(sess), ms)
            );
        }
        if (state === SessionState.Terminating) {
            // Mark pending so onAccept knows to re-BYE if a late 200 OK arrives
            sess._pendingHangup = true;
            clearInterval(timerInterval);
        }
        if (state === SessionState.Terminated) {
            if (currentCall?.session === sess) runActions(currentCall.machine.handle({ type: "terminated", code: currentCall.sipCode }));
            else if (session === sess) cleanupCall();
        }
    });
};

/* -----------------------------------------------------------------------------
 *  INTERNAL � wire remote audio to <audio id="remoteAudio">
 *  Uses addEventListener so it never overwrites sip.js internals.
 *  A guard flag prevents duplicate listeners on repeated calls.
 * ------------------------------------------------------------------------- */
const attachRemoteAudio = (sess) => {
    if (!sess || !remoteAudio) return;

    const pc = sess?.sessionDescriptionHandler?.peerConnection;
    if (!pc) return;

    // Wire track listener once per PC instance
    if (!pc._sipAudioListenerAdded) {
        pc._sipAudioListenerAdded = true;

        pc.addEventListener("track", (event) => {
            console.log("?? [attachRemoteAudio] track event:", event.track.kind);
            const stream = event.streams?.[0] ?? new MediaStream([event.track]);
            remoteAudio.srcObject = stream;
            remoteAudio.play().catch(e => console.warn("remoteAudio.play() [attach]:", e));
            console.log("?? Audio attached via attachRemoteAudio track listener");
        });
    }

    // Also wire any tracks already present on the PC right now
    const receivers = pc.getReceivers().filter(r => r.track?.kind === "audio");
    if (receivers.length > 0) {
        const stream = new MediaStream(receivers.map(r => r.track));
        remoteAudio.srcObject = stream;
        remoteAudio.play().catch(e => console.warn("remoteAudio.play() [receivers]:", e));
        console.log("?? Audio attached via existing receivers");
    }
};

/* -----------------------------------------------------------------------------
 *  INTERNAL � cleanup after call ends
 * ------------------------------------------------------------------------- */
const cleanupCall = (result = null) => {
    stopCallTick();
    clearInterval(timerInterval);
    timerInterval = null;
    seconds       = 0;
    window._sipCallInProgress = false;

    if (remoteAudio) {
        remoteAudio.pause();
        remoteAudio.srcObject = null;
    }

    // Always release the microphone and close the media path
    const pc = session?.sessionDescriptionHandler?.peerConnection;
    if (pc) {
        try { pc.getSenders().forEach(s => s.track && s.track.stop()); } catch (_) {}
        try { if (pc.signalingState !== "closed") pc.close(); } catch (_) {}
    }

    const timerEl = document.getElementById("sip-timer");
    if (timerEl) timerEl.innerText = "00:00";

    closeSipDialerUI();
    session = null;

    if (currentCall && !currentCall.ended) {
        currentCall.ended = true;
        const wasEstablished = !!currentCall.establishedAt;
        const info = result || {
            outcome:  outcomeFromSipCode(currentCall.sipCode, wasEstablished, false),
            sipCode:  currentCall.sipCode,
            duration: wasEstablished ? Math.round((Date.now() - currentCall.establishedAt) / 1000) : 0,
        };
        dbg("ended", { outcome: info.outcome, reason: info.reason, sipCode: info.sipCode, duration: info.duration });
        notify(callEndListeners, info);
        flushDebug();
    }
    currentCall = null;
};

/* -----------------------------------------------------------------------------
 *  INTERNAL � busy tone helpers
 * ------------------------------------------------------------------------- */
const playLocalBusyTone = () => {
    try {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const gain = audioCtx.createGain();
        gain.gain.value = 0.3;
        gain.connect(audioCtx.destination);
        for (let i = 0; i < 4; i++) {
            const osc = audioCtx.createOscillator();
            osc.type = "sine"; osc.frequency.value = 400;
            osc.connect(gain);
            osc.start(i * 1.5); osc.stop(i * 1.5 + 0.75);
        }
        setTimeout(() => stopLocalBusyTone(), 6000);
    } catch (e) { console.warn("Busy tone error:", e); }
};

const stopLocalBusyTone = () => {
    if (audioCtx) { try { audioCtx.close(); } catch (_) {} audioCtx = null; }
};