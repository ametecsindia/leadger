import {
    UserAgent,
    Registerer,
    Inviter,
    SessionState,
    URI,
    Web
} from "https://cdn.jsdelivr.net/npm/sip.js@0.20.0/+esm";

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
        if (state === "Connected" && registerer) {
            try   { await registerer.register(); }
            catch (e) { console.warn("Re-register failed:", e); }
        }
    });

    await ua.start();

    registerer = new Registerer(ua, { expires: 120, refreshFrequency: 80 });
    await registerer.register();

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

    // Accept both "7702004422" and "sip:7702004422@domain"
    let targetURI;
    if (target.startsWith("sip:")) {
        targetURI = UserAgent.makeURI(target);
    } else {
        const clean = target.replace(/[^\d+]/g, "");
        targetURI   = UserAgent.makeURI(`sip:${clean}@${ua.configuration.uri.host}`);
    }
    if (!targetURI) throw new Error(`Cannot build SIP URI for: ${target}`);

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

    currentCall = { session, establishedAt: null, sipCode: null, cancelledByAgent: false, ended: false, ringing: false };
    attachSessionEvents(session);

    try {
        await session.invite({
            requestDelegate: {
                onProgress(response) {
                    const code = response?.message?.statusCode;
                    // 180 Ringing / 183 Session Progress = the destination is really ringing
                    if ((code === 180 || code === 183) && currentCall && !currentCall.ringing) {
                        currentCall.ringing = true;
                        notify(callRingingListeners, { sipCode: code });
                    }
                },
                onReject(response) {
                    if (currentCall) currentCall.sipCode = response?.message?.statusCode || null;
                },
            },
        });
        console.log("?? Outgoing INVITE sent ?", targetURI.toString());
    } catch (err) {
        session = null;
        window._sipCallInProgress = false;
        throw new Error(`INVITE failed: ${err.message}`);
    }
};

/* -----------------------------------------------------------------------------
 *  HANGUP
 * ------------------------------------------------------------------------- */
/* -----------------------------------------------------------------------------
 *  HANGUP
 * ------------------------------------------------------------------------- */
export const hangupCall = async () => {
    if (!session) { closeSipDialerUI(); return; }

    const currentState = session.state;
    console.log("hangupCall � session state:", currentState);

    try {
        switch (currentState) {

            case SessionState.Initial:
            case SessionState.Establishing:
                if (typeof session.cancel === "function") {
                    if (currentCall) currentCall.cancelledByAgent = true;
                    await session.cancel();
                    console.log("?? INVITE cancelled");
                }
                break;

            case SessionState.Established:
                   try {
                        await fetch("https://sip.telozar.com/api/webrtc/calls/hangup", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                agent_id: currentAgentId
                            })
                        });

                        console.log("Hangup API called");
                    } catch (err) {
                        console.error("Hangup API failed:", err);
                    }
                    console.log("?? BYE sent");
                    // Safety net � if Terminated never fires force cleanup
                    setTimeout(() => {
                        if (session) {
                            console.warn("?? Safety net cleanup");
                            cleanupCall();
                        }
                    }, 3000);
                    break;

            case SessionState.Terminating:
                console.log("?? Already terminating");
                break;

            case SessionState.Terminated:
                cleanupCall();
                break;

            default:
                try { session.dispose(); } catch (_) {}
                cleanupCall();
        }
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
            console.log("?? PC track event:", event.track.kind, event.track.readyState);
            const stream = event.streams?.[0] ?? new MediaStream([event.track]);
            if (remoteAudio) {
                remoteAudio.srcObject = stream;
                remoteAudio.play().catch(e => console.warn("remoteAudio.play() [track]:", e));
                console.log("?? Audio attached via PC track event");
            }
        });

        // -- ICE connection state ----------------------------------------
        pc.addEventListener("iceconnectionstatechange", () => {
            const s = pc.iceConnectionState;
            console.log(`?? ICE state: ${s}`);
            if (s === "connected" || s === "completed") {
                attachRemoteAudio(sess);
            }
        });

        // -- General connection state ------------------------------------
        pc.addEventListener("connectionstatechange", () => {
            const s = pc.connectionState;
            console.log(`?? Connection state: ${s}`);
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
        onReject()  { console.log("? Rejected");   cleanupCall(); },
        onCancel()  { console.log("? Cancelled");  cleanupCall(); },
        onBye()     { console.log("?? Remote BYE"); cleanupCall(); },
        onTrack()   { attachRemoteAudio(sess); },
    };

    sess.stateChange.addListener((state) => {
        console.log("Session state ?", state);

        if (state === SessionState.Establishing) {
            console.log("?? Ringing�");

            // Start a ringing-phase timer so the UI shows something
            clearInterval(timerInterval);
            seconds = 0;
            timerInterval = setInterval(() => {
                seconds++;
                const m  = String(Math.floor(seconds / 60)).padStart(2, "0");
                const s  = String(seconds % 60).padStart(2, "0");
                const el = document.getElementById("sip-timer");
                if (el) el.innerText = `${m}:${s}`;
            }, 1000);

            // Wire PC listeners again here in case SDH wasn't ready during the
            // initial poll above (some sip.js versions create it lazily)
            wirePeerConnectionListeners(sess);
        }

        if (state === SessionState.Established) {
            console.log("? Call connected");

            // Reset timer to 0 now that call is truly connected
            if (sess._pendingHangup) {
                console.warn("?? Established after pending hangup � sending BYE now");
                sess.bye().catch(e => { console.warn("Pending hangup BYE error:", e); cleanupCall(); });
                return;
            }
            clearInterval(timerInterval);
            seconds = 0;
            timerInterval = setInterval(() => {
                seconds++;
                const m  = String(Math.floor(seconds / 60)).padStart(2, "0");
                const s  = String(seconds % 60).padStart(2, "0");
                const el = document.getElementById("sip-timer");
                if (el) el.innerText = `${m}:${s}`;
            }, 1000);

            // Retry audio � RTP may arrive slightly after Established fires
            [0, 300, 700, 1500, 3000].forEach(ms =>
                setTimeout(() => attachRemoteAudio(sess), ms)
            );

            if (currentCall && !currentCall.establishedAt) {
                currentCall.establishedAt = Date.now();
                notify(callEstablishedListeners, {});
            }
        }
        if (state === SessionState.Terminating) {
            console.log("?? Terminating � BYE sent, awaiting 200");
            // ? Mark pending so onAccept knows to re-BYE if late 200 OK arrives
            sess._pendingHangup = true;
            clearInterval(timerInterval);
        }
        if (state === SessionState.Terminated) {
            console.log("?? Terminated");
            cleanupCall();
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
const cleanupCall = () => {
    clearInterval(timerInterval);
    timerInterval = null;
    seconds       = 0;
    window._sipCallInProgress = false;

    if (remoteAudio) {
        remoteAudio.pause();
        remoteAudio.srcObject = null;
    }

    const timerEl = document.getElementById("sip-timer");
    if (timerEl) timerEl.innerText = "00:00";

    closeSipDialerUI();
    session = null;

    if (currentCall && !currentCall.ended) {
        currentCall.ended = true;
        const wasEstablished = !!currentCall.establishedAt;
        notify(callEndListeners, {
            outcome:  outcomeFromSipCode(currentCall.sipCode, wasEstablished, currentCall.cancelledByAgent),
            sipCode:  currentCall.sipCode,
            duration: wasEstablished ? Math.round((Date.now() - currentCall.establishedAt) / 1000) : 0,
        });
    }
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