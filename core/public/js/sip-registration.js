/**
 * sip-registration.js
 *
 * Handles WebRTC / SIP registration with CRM-ID gating.
 *
 * Expects window.__SIP_CONFIG__ to be set by the Blade template:
 * {
 *   domain:      "voip.ametecsindia.com",
 *   websocket:   "wss://voip.ametecsindia.com:7443",
 *   username:    "<smartping_user_id>",
 *   password:    "<smartping_password>",
 *   crmId:       12345,                  // current user's CRM ID (int)
 *   allowedIds:  [7777, 8888, 9999],     // from WEBRTC_CRM_IDS env
 * }
 */

import {
    UserAgent,
    Registerer,
    Inviter,
    SessionState,
    URI,
    Web
} from "https://cdn.jsdelivr.net/npm/sip.js@0.20.0/+esm";

/* ---------------------------------------------
   STATE
--------------------------------------------- */
let ua;
let registerer;
let session;
let remoteAudio;
let timerInterval;
let seconds       = 0;
let callEndCallback = null;
let ringtone;
let isOnHold      = false;
let isMuted       = false;
let audioCtx      = null;

/* ---------------------------------------------
   CRM-ID GATE
   Returns true only when the current user's
   crm_id is in the WEBRTC_CRM_IDS list.
--------------------------------------------- */
const isCrmAllowed = () => {
    const cfg = window.__SIP_CONFIG__;
    if (!cfg) return false;
    const id  = Number(cfg.crmId);
    const ids = (cfg.allowedIds || []).map(Number);
    return ids.includes(id);
};

/* ---------------------------------------------
   INIT  (only when CRM matches)
--------------------------------------------- */
export const initSip = async (config) => {

    remoteAudio = document.getElementById("remoteAudio");

    const baseUrl = window.location.origin;
    ringtone = new Audio(`${baseUrl}/audio/ringtone.mp3`);
    ringtone.loop    = true;
    ringtone.preload = "auto";
    ringtone.volume  = 1;

    // Unlock audio on first user gesture
    const unlockAudio = () => {
        ringtone.play()
            .then(() => { ringtone.pause(); ringtone.currentTime = 0; })
            .catch(() => {});
    };
    document.addEventListener("click",   unlockAudio, { once: true });
    document.addEventListener("keydown", unlockAudio, { once: true });

    const uri = new URI("sip", config.username, config.domain);

    ua = new UserAgent({
        uri,
        transportOptions: { server: config.websocket },
        authorizationUsername: config.username,
        authorizationPassword: config.password,
        sessionDescriptionHandlerFactoryOptions: {
            constraints: { audio: true, video: false },
            peerConnectionOptions: {
                rtcConfiguration: {
                    iceServers:       [],
                    iceTransportPolicy: "all",
                    bundlePolicy:     "max-bundle",
                    rtcpMuxPolicy:    "require"
                }
            }
        }
    });

    await ua.start();

    registerer = new Registerer(ua);
    await registerer.register();

    console.log("SIP Registered ? (CRM ID allowed)");
};

/* ---------------------------------------------
   openSignupModal  —  main entry point
   Called from the navbar WebRTC button.
--------------------------------------------- */
export const openSignupModal = async (e) => {
    if (e) e.preventDefault();

    /* -- NOT in allowed list ? original SSO flow -- */
    if (!isCrmAllowed()) {
        await _handleSSOFlow(e);
        return;
    }

    /* -- In allowed list ? SIP WebSocket flow -- */
    await _handleSIPFlow(e);
};

/* ---------------------------------------------
   INTERNAL: original SSO / iframe flow
   (unchanged from your existing code)
--------------------------------------------- */
let isSSOProcessing = false;

const _handleSSOFlow = async (e) => {
    if (isSSOProcessing) return;
    isSSOProcessing = true;

    const btn = e?.currentTarget;
    if (btn) btn.style.pointerEvents = "none";

    try {
        const savedData = localStorage.getItem("ssoData");
        const logoutBtn = document.getElementById("webrtc-logout-btn");
        if (logoutBtn) logoutBtn.style.display = "none";

        if (savedData) {
            const iframe    = document.getElementById("sso-iframe");
            const container = ensureSSOContainerWithClose();
            if (iframe) iframe.src = "https://smartcloud.smartping.io/agent/dashboard";
            container.style.display = "block";
        } else {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content || "";

            const response = await fetch("/sso-login", {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                credentials: "include"
            });

            const data = await response.json();

            if (data.redirect_url && data.token) {
                const iframe    = document.getElementById("sso-iframe");
                const container = ensureSSOContainerWithClose();
                if (iframe) iframe.src = data.redirect_url;

                localStorage.setItem("ssoData",          JSON.stringify(data));
                localStorage.setItem("ssoAuthenticated", "true");

                if (logoutBtn) {
                    logoutBtn.style.display = "none";
                    setTimeout(() => { logoutBtn.style.display = "inline-block"; }, 5000);
                }

                toastr.success("Successfully logged into the WebRTC");
                setWebRTCIcon("success");
            } else {
                toastr.error("SSO login failed – no redirect URL received");
                setWebRTCIcon("error");
            }
        }
    } catch (error) {
        console.error("SSO Login Error:", error);
        toastr.error(`Failed to initiate SSO login: ${error.message}`);
        setWebRTCIcon("error");
    } finally {
        setTimeout(() => {
            isSSOProcessing = false;
            if (btn) btn.style.pointerEvents = "auto";
        }, 5000);
    }
};

/* ---------------------------------------------
   INTERNAL: SIP WebSocket flow (CRM allowed)
--------------------------------------------- */
let isSIPProcessing = false;

const _handleSIPFlow = async (e) => {
    if (isSIPProcessing) return;
    isSIPProcessing = true;

    const btn = e?.currentTarget;
    if (btn) btn.style.pointerEvents = "none";

    try {
        // Already registered?
        if (ua) {
            console.log("SIP already initialised");
            toastr.info("SIP already connected");
            setWebRTCIcon("success");
            return;
        }

        const cfg = window.__SIP_CONFIG__;

        await initSip({
            domain:    cfg.domain,
            websocket: cfg.websocket,
            username:  cfg.username,
            password:  cfg.password,
        });

        // Persist so page refreshes keep the indicator green
        localStorage.setItem("sipRegistered", "true");

        const logoutBtn = document.getElementById("webrtc-logout-btn");
        if (logoutBtn) {
            logoutBtn.style.display = "none";
            setTimeout(() => { logoutBtn.style.display = "inline-block"; }, 5000);
        }

        toastr.success("SIP / WebRTC connected successfully");
        setWebRTCIcon("success");

    } catch (error) {
        console.error("SIP init error:", error);
        toastr.error(`SIP connection failed: ${error.message}`);
        setWebRTCIcon("error");
    } finally {
        setTimeout(() => {
            isSIPProcessing = false;
            if (btn) btn.style.pointerEvents = "auto";
        }, 5000);
    }
};

/* ---------------------------------------------
   MAKE CALL
--------------------------------------------- */
export const makeCall = async (target, meta = {}) => {

    if (session && session.state !== "Terminated") {
        alert("Agent already on call");
        return;
    }
    if (session) {
        try { session.dispose(); } catch (_) {}
        session = null;
    }

    const targetURI    = UserAgent.makeURI(target);
    const extraHeaders = [];

    if (meta.refId) extraHeaders.push(`X-REF-ID: ${meta.refId}`);
    if (meta.agent) extraHeaders.push(`X-AGENT: ${meta.agent}`);
    if (meta.vmn)   extraHeaders.push(`X-VMN: ${meta.vmn}`);

    session   = new Inviter(ua, targetURI, { extraHeaders });
    isOnHold  = false;
    isMuted   = false;

    attachSessionEvents(session);
    await session.invite();
};

/* ---------------------------------------------
   HANGUP
--------------------------------------------- */
export const hangupCall = () => {
    if (!session) { closeSipDialerUI(); return; }

    const state = session.state;
    console.log("hangupCall() — state:", state);

    try {
        if (state === SessionState.Established) {
            stopLocalBusyTone();
            session.bye();
        } else if (
            state === SessionState.Initial ||
            state === SessionState.Establishing
        ) {
            session.cancel();
        } else if (state === SessionState.Terminating) {
            console.log("Already terminating — waiting");
        } else {
            try { session.dispose(); } catch (_) {}
            session = null;
            closeSipDialerUI();
        }
    } catch (e) {
        console.error("hangupCall error:", e);
        session = null;
        closeSipDialerUI();
    }
};

/* ---------------------------------------------
   SSO LOGOUT  (for non-CRM users)
--------------------------------------------- */
let isSSOLogoutProcessing = false;

export const handleSSOLogout = async (e) => {
    if (e) e.preventDefault();
    if (isSSOLogoutProcessing) return;
    isSSOLogoutProcessing = true;

    const btn = e?.currentTarget;
    if (btn) btn.style.pointerEvents = "none";

    try {
        /* -- CRM-allowed users: unregister SIP -- */
        if (isCrmAllowed()) {
            if (registerer) {
                await registerer.unregister();
                registerer = null;
            }
            if (ua) {
                await ua.stop();
                ua = null;
            }
            localStorage.removeItem("sipRegistered");
            toastr.success("Disconnected from SIP / WebRTC.");
        } else {
            /* -- Others: SSO logout -- */
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content || "";
            const response = await fetch("/sso-logout", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                credentials: "include"
            });
            const data = await response.json();

            if (data.success) {
                localStorage.removeItem("ssoData");
                localStorage.removeItem("ssoAuthenticated");
                toastr.success("Logged out successfully from WebRTC.");
            } else {
                toastr.error(data.message || "Logout failed. Try again.");
                setWebRTCIcon("error");
                return;
            }
        }

        const logoutBtn = document.getElementById("webrtc-logout-btn");
        const loginBtn  = document.getElementById("webrtc-login-btn");

        if (logoutBtn) logoutBtn.style.display = "none";
        setWebRTCIcon("default");

        if (loginBtn) {
            loginBtn.style.display = "none";
            setTimeout(() => { loginBtn.style.display = "inline-block"; }, 5000);
        }

    } catch (error) {
        console.error("Logout Error:", error);
        toastr.error(`Logout failed: ${error.message}`);
        setWebRTCIcon("error");
    } finally {
        setTimeout(() => {
            isSSOLogoutProcessing = false;
            if (btn) btn.style.pointerEvents = "auto";
        }, 5000);
    }
};

/* ---------------------------------------------
   DOMContentLoaded  — restore state on page load
--------------------------------------------- */
document.addEventListener("DOMContentLoaded", () => {

    // Logout link always clears everything
    document.querySelector("#logout-link")?.addEventListener("click", () => {
        localStorage.removeItem("ssoData");
        localStorage.removeItem("ssoAuthenticated");
        localStorage.removeItem("sipRegistered");
        document.getElementById("sso-container")?.style.setProperty("display", "none");
    });

    setTimeout(() => {
        const logoutBtn = document.getElementById("webrtc-logout-btn");

        if (isCrmAllowed()) {
            /* -- CRM user: restore SIP if previously registered -- */
            if (localStorage.getItem("sipRegistered") === "true") {
                const cfg = window.__SIP_CONFIG__;
                initSip({
                    domain:    cfg.domain,
                    websocket: cfg.websocket,
                    username:  cfg.username,
                    password:  cfg.password,
                }).then(() => {
                    setWebRTCIcon("success");
                    if (logoutBtn) logoutBtn.style.display = "inline-block";
                }).catch(err => {
                    console.error("Auto re-register failed:", err);
                    setWebRTCIcon("error");
                });
            } else {
                setWebRTCIcon("default");
                if (logoutBtn) logoutBtn.style.display = "none";
            }
        } else {
            /* -- Non-CRM user: restore SSO state -- */
            const savedData = localStorage.getItem("ssoData");
            if (savedData) {
                setWebRTCIcon("success");
                if (logoutBtn) logoutBtn.style.display = "inline-block";
                const iframe = document.getElementById("sso-iframe");
                if (iframe) iframe.src = "https://smartcloud.smartping.io/agent/dashboard";
            } else {
                setWebRTCIcon("default");
                if (logoutBtn) logoutBtn.style.display = "none";
            }
        }
    }, 300);
});

/* ---------------------------------------------
   MUTE / HOLD / AUDIO / UI helpers
   (unchanged from original)
--------------------------------------------- */
export const muteCall = () => {
    if (!session) return;
    const pc = session?.sessionDescriptionHandler?.peerConnection;
    if (!pc) return;
    isMuted = !isMuted;
    pc.getSenders().forEach(s => {
        if (s.track?.kind === "audio") s.track.enabled = !isMuted;
    });
    const btn = document.querySelector(".sip-btn.mute");
    if (btn) {
        btn.textContent   = isMuted ? "Unmute" : "Mute";
        btn.style.background = isMuted ? "#c62828" : "#ff9800";
    }
};

export const holdCall = async () => {
    if (!session || session.state !== SessionState.Established) return;
    try {
        isOnHold = !isOnHold;
        await session.invite({
            sessionDescriptionHandlerModifiers: isOnHold ? [Web.holdModifier] : []
        });
        const btn = document.querySelector(".sip-btn.hold");
        if (btn) {
            btn.textContent      = isOnHold ? "Unhold" : "Hold";
            btn.style.background = isOnHold ? "#c62828" : "#3f51b5";
        }
    } catch (e) { console.error("Hold error:", e); }
};

const attachRemoteAudio = (sess) => {
    const pc = sess?.sessionDescriptionHandler?.peerConnection;
    if (!pc) return;
    pc.ontrack = (event) => {
        if (event.streams?.[0]) {
            remoteAudio.srcObject = event.streams[0];
            remoteAudio.play().catch(() => {});
        }
    };
    pc.getReceivers().forEach(r => {
        if (r.track?.kind === "audio") {
            remoteAudio.srcObject = new MediaStream([r.track]);
            remoteAudio.play().catch(() => {});
        }
    });
};

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
    } catch (e) { console.log("Busy tone error:", e); }
};

const stopLocalBusyTone = () => {
    if (audioCtx) { try { audioCtx.close(); } catch (_) {} audioCtx = null; }
};

const stopRingtone = () => {
    if (ringtone) { ringtone.pause(); ringtone.currentTime = 0; }
};

const cleanupCall = () => {
    clearInterval(timerInterval);
    seconds = 0;
    const timerEl = document.getElementById("sip-timer");
    if (timerEl) timerEl.innerText = "00:00";
    closeSipDialerUI();
    session = null;
    if (callEndCallback) callEndCallback();
};

const attachSessionEvents = (sess) => {
    sess.delegate = {
        onProgress:  (r) => {
            if (r?.message?.statusCode === 183) console.log("Early media");
            ringtone?.play().catch(() => {});
        },
        onReject:  () => { stopRingtone(); cleanupCall(); },
        onCancel:  () => { stopRingtone(); cleanupCall(); },
        onBye:     () => { stopRingtone(); cleanupCall(); },
        onTrack:   () => attachRemoteAudio(sess)
    };

    sess.stateChange.addListener((state) => {
        console.log("Session state ?", state);
        if (state === SessionState.Establishing) {
            ringtone?.play().catch(() => {});
            seconds = 0;
            timerInterval = setInterval(() => {
                seconds++;
                const timerEl = document.getElementById("sip-timer");
                if (timerEl) timerEl.innerText =
                    String(Math.floor(seconds / 60)).padStart(2, "0") + ":" +
                    String(seconds % 60).padStart(2, "0");
            }, 1000);
        }
        if (state === SessionState.Established) {
            stopRingtone();
            attachRemoteAudio(sess);
        }
        if (state === SessionState.Terminated) {
            stopRingtone();
            clearInterval(timerInterval);
            seconds = 0;
            const timerEl = document.getElementById("sip-timer");
            if (timerEl) timerEl.innerText = "00:00";
            closeSipDialerUI();
            session = null;
            if (callEndCallback) callEndCallback();
        }
    });
};

export const onCallEnded = (cb) => { callEndCallback = cb; };

/* ---------------------------------------------
   SSO container helper (unchanged)
--------------------------------------------- */
const ensureSSOContainerWithClose = () => {
    let container = document.getElementById("sso-container");
    if (!container) {
        container = document.createElement("div");
        container.id = "sso-container";
        Object.assign(container.style, {
            display: "none", position: "fixed",
            top: "50%", left: "50%",
            transform: "translate(-50%,-50%)",
            width: "400px", height: "300px",
            background: "#fff", borderRadius: "8px",
            boxShadow: "0 4px 12px rgba(0,0,0,0.3)", zIndex: "9999"
        });
        const iframe = document.createElement("iframe");
        iframe.id = "sso-iframe";
        iframe.allow    = "microphone; camera";
        iframe.sandbox  = "allow-scripts allow-same-origin";
        Object.assign(iframe.style, { width: "100%", height: "100%", border: "none" });
        container.appendChild(iframe);
        document.body.appendChild(container);
    }
    if (!container.querySelector(".sso-close-btn")) {
        const btn = document.createElement("button");
        btn.className   = "sso-close-btn";
        btn.innerHTML   = "&times;";
        Object.assign(btn.style, {
            position: "absolute", top: "20px", right: "20px",
            background: "transparent", border: "none",
            color: "black", fontSize: "24px", cursor: "pointer"
        });
        btn.onclick = () => { container.style.display = "none"; };
        container.appendChild(btn);
    }
    return container;
};

export const setWebRTCIcon = (status) => {
    const icon = document.getElementById("webrtc-icon");
    if (!icon) return;
    icon.classList.remove("success", "error", "default");
    icon.classList.add(status === "success" ? "success" : status === "error" ? "error" : "default");
};