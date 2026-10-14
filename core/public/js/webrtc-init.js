/**
 * public/js/webrtc-init.js
 *
 * PURPOSE
 * -------
 * ES modules are deferred + scoped — their exports are NOT on window.
 * onclick="openSignupModal(event)" needs a real window.* global.
 * This file:
 *   1. Imports everything from sip-registration.js
 *   2. Pins each function onto window SYNCHRONOUSLY within the module
 *   3. Runs the on-load state restore (green icon, logout btn, auto-register)
 *
 * Load order in the Blade template (important):
 *   <script> window.__SIP_AUTO_REGISTER__ = …  </script>   ? inline, runs first
 *   <script type="module" src="…/webrtc-init.js"></script>  ? deferred, runs after DOM
 */

import {
    initSip,
    makeCall,
    hangupCall,
    muteCall,
    holdCall,
    closeSipDialerUI,
    onCallEnded,
} from "./sip-registration.js";

/* -------------------------------------------------------------
   INTERNAL STATE  (shared between the two flows)
------------------------------------------------------------- */
let isSSOProcessing        = false;
let isSSOLogoutProcessing  = false;

/* -------------------------------------------------------------
   HELPERS
------------------------------------------------------------- */
function setWebRTCIcon(status) {
    const icon = document.getElementById("webrtc-icon");
    if (!icon) return;
    icon.classList.remove("success", "error", "default");
    icon.classList.add(status === "success" ? "success"
                     : status === "error"   ? "error"
                     : "default");
}

function ensureSSOContainerWithClose() {
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
            boxShadow: "0 4px 12px rgba(0,0,0,0.3)",
            zIndex: "9999"
        });
        const iframe = document.createElement("iframe");
        iframe.id      = "sso-iframe";
        iframe.allow   = "microphone; camera";
        iframe.sandbox = "allow-scripts allow-same-origin";
        Object.assign(iframe.style, { width: "100%", height: "100%", border: "none" });
        container.appendChild(iframe);
        document.body.appendChild(container);
    }
    if (!container.querySelector(".sso-close-btn")) {
        const btn = document.createElement("button");
        btn.className = "sso-close-btn";
        btn.innerHTML = "&times;";
        Object.assign(btn.style, {
            position: "absolute", top: "20px", right: "20px",
            background: "transparent", border: "none",
            color: "black", fontSize: "24px", cursor: "pointer"
        });
        btn.onclick = () => { container.style.display = "none"; };
        container.appendChild(btn);
    }
    return container;
}

const isCrmAllowed = () => !!window.__SIP_AUTO_REGISTER__;

/* -------------------------------------------------------------
   openSignupModal  — called by onclick in navbar
------------------------------------------------------------- */
async function openSignupModal(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation(); // prevent Materialize modal trigger
    }

    if (isCrmAllowed()) {
        await _handleSIPFlow(e);
    } else {
        await _handleSSOFlow(e);
    }
}

/* -------------------------------------------------------------
   handleSSOLogout  — called by onclick in navbar
------------------------------------------------------------- */
async function handleSSOLogout(e) {
    if (e) e.preventDefault();
    if (isSSOLogoutProcessing) return;
    isSSOLogoutProcessing = true;

    const btn = e?.currentTarget;
    if (btn) btn.style.pointerEvents = "none";

    try {
        if (isCrmAllowed()) {
            /* -- SIP unregister -- */
            // initSip exposes ua/registerer through the module;
            // simplest clean-up: reload flags and tell the user
            localStorage.removeItem("sipRegistered");
            // The module manages its own ua/registerer — call the
            // exported hangup to cleanly tear down any live session
            hangupCall();
            setWebRTCIcon("default");
            toastr.success("Disconnected from SIP / WebRTC.");
        } else {
            /* -- SSO logout -- */
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
            const res  = await fetch("/sso-logout", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": csrf, "Accept": "application/json" },
                credentials: "include"
            });
            const data = await res.json();
            if (data.success) {
                localStorage.removeItem("ssoData");
                localStorage.removeItem("ssoAuthenticated");
                toastr.success("Logged out successfully from WebRTC.");
                setWebRTCIcon("default");
            } else {
                toastr.error(data.message ?? "Logout failed. Try again.");
                setWebRTCIcon("error");
                return;
            }
        }

        const logoutBtn = document.getElementById("webrtc-logout-btn");
        const loginBtn  = document.getElementById("webrtc-login-btn");
        if (logoutBtn) logoutBtn.style.display = "none";
        if (loginBtn) {
            loginBtn.style.display = "none";
            setTimeout(() => { loginBtn.style.display = "inline-block"; }, 5000);
        }

    } catch (err) {
        console.error("Logout error:", err);
        toastr.error(`Logout failed: ${err.message}`);
        setWebRTCIcon("error");
    } finally {
        setTimeout(() => {
            isSSOLogoutProcessing = false;
            if (btn) btn.style.pointerEvents = "auto";
        }, 5000);
    }
}

/* -------------------------------------------------------------
   _handleSIPFlow  (CRM-matched users)
------------------------------------------------------------- */
async function _handleSIPFlow(e) {
    // For SIP users the button is hidden after auto-register,
    // but if someone calls this manually we still handle it gracefully.
    const btn = e?.currentTarget;
    if (btn) btn.style.pointerEvents = "none";

    try {
        const cfg = window.__SIP_CONFIG__;
        if (!cfg) {
            toastr.error("SIP config missing. Check server env vars.");
            return;
        }

        await initSip(cfg);
        localStorage.setItem("sipRegistered", "true");

        const logoutBtn = document.getElementById("webrtc-logout-btn");
        if (logoutBtn) {
            setTimeout(() => { logoutBtn.style.display = "inline-block"; }, 2000);
        }

        toastr.success("SIP / WebRTC connected successfully.");
        setWebRTCIcon("success");

    } catch (err) {
        console.error("SIP init error:", err);
        toastr.error(`SIP connection failed: ${err.message}`);
        setWebRTCIcon("error");
    } finally {
        setTimeout(() => {
            if (btn) btn.style.pointerEvents = "auto";
        }, 5000);
    }
}

/* -------------------------------------------------------------
   _handleSSOFlow  (non-CRM users — original behaviour)
------------------------------------------------------------- */
async function _handleSSOFlow(e) {
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
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
            const res  = await fetch("/sso-login", {
                headers: {
                    "X-CSRF-TOKEN": csrf,
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                credentials: "include"
            });
            const data = await res.json();

            if (data.redirect_url && data.token) {
                const iframe    = document.getElementById("sso-iframe");
                const container = ensureSSOContainerWithClose();
                if (iframe) iframe.src = data.redirect_url;

                localStorage.setItem("ssoData",          JSON.stringify(data));
                localStorage.setItem("ssoAuthenticated", "true");

                if (logoutBtn) {
                    setTimeout(() => { logoutBtn.style.display = "inline-block"; }, 5000);
                }

                toastr.success("Successfully logged into the WebRTC.");
                setWebRTCIcon("success");
            } else {
                toastr.error("SSO login failed – no redirect URL received.");
                setWebRTCIcon("error");
            }
        }
    } catch (err) {
        console.error("SSO Login Error:", err);
        toastr.error(`Failed to initiate SSO login: ${err.message}`);
        setWebRTCIcon("error");
    } finally {
        setTimeout(() => {
            isSSOProcessing = false;
            if (btn) btn.style.pointerEvents = "auto";
        }, 5000);
    }
}

/* -------------------------------------------------------------
   PIN TO window  — must happen before any onclick fires
   ES modules execute after DOMContentLoaded, which is fine
   because onclick handlers only fire on user interaction.
------------------------------------------------------------- */
window.openSignupModal = openSignupModal;
window.handleSSOLogout = handleSSOLogout;
window.setWebRTCIcon   = setWebRTCIcon;
window.makeCall        = makeCall;
window.hangupCall      = hangupCall;
window.muteCall        = muteCall;
window.holdCall        = holdCall;
window.onCallEnded     = onCallEnded;

/* -------------------------------------------------------------
   DOM READY  — restore state on every page load
------------------------------------------------------------- */
document.addEventListener("DOMContentLoaded", () => {

    /* clear everything on app logout */
    document.querySelector("#logout-link")?.addEventListener("click", () => {
        localStorage.removeItem("ssoData");
        localStorage.removeItem("ssoAuthenticated");
        localStorage.removeItem("sipRegistered");
        document.getElementById("sso-container")?.style.setProperty("display", "none");
    });

    setTimeout(async () => {
        const logoutBtn = document.getElementById("webrtc-logout-btn");
        const loginBtn  = document.getElementById("webrtc-login-btn");

        if (isCrmAllowed()) {
            /* -- CRM user: auto-register SIP on every page load -- */
            const cfg = window.__SIP_CONFIG__;
            if (cfg) {
                try {
                    await initSip(cfg);
                    localStorage.setItem("sipRegistered", "true");
                    setWebRTCIcon("success");
                    if (logoutBtn) logoutBtn.style.display = "inline-block";
                    // login btn is already hidden server-side; keep it hidden
                    if (loginBtn) loginBtn.style.display = "none";
                    console.log("? SIP auto-registered on page load");
                } catch (err) {
                    console.error("SIP auto-register failed:", err);
                    setWebRTCIcon("error");
                }
            }
        } else {
            /* -- Non-CRM user: restore SSO iframe if previously logged in -- */
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