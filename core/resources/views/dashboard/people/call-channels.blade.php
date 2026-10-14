@extends('dashboard.layouts.master')
@section('title', 'Call Channels - LeadGER')

@push("after-styles")
<style>
    .lg-channels-page-wrap {
        font-family: var(--lg-font-sans);
        color: #0F172A;
        background: #F8FAFC;
        padding: 24px 30px;
        box-sizing: border-box;
        min-height: calc(100vh - 72px);
    }

    /* Breadcrumbs & Header */
    .lg-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .lg-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .lg-header-icon-box {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--lg-accent);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        flex-shrink: 0;
    }

    .lg-title-main {
        font-size: 22px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        line-height: 1.25;
    }

    .lg-breadcrumb-nav {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 500;
        color: #64748B;
        margin-top: 4px;
    }

    .lg-breadcrumb-nav a {
        color: #64748B;
        text-decoration: none;
    }

    .lg-breadcrumb-nav a:hover {
        color: var(--lg-accent);
    }

    /* TOAST ALERT NOTIFICATION */
    .lg-toast-alert {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        display: none;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 600;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        animation: lgSlideUp 0.3s ease;
    }

    .lg-toast-success {
        background: #0F172A;
        color: #F8FAFC;
        border: 1px solid #334155;
    }

    .lg-toast-error {
        background: #FEF2F2;
        color: #B91C1C;
        border: 1px solid #FECACA;
    }

    @keyframes lgSlideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    /* DETAILS WORKSPACE PANEL (BELOW SELECTOR) */
    .lg-workspace-panel {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 26px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        margin-top: 10px;
        transition: all 0.25s ease;
    }

    .lg-panel-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .lg-panel-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .lg-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .lg-status-active {
        background: #DCFCE7;
        color: #15803D;
    }

    .lg-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #16A34A;
        box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
    }

    .lg-status-inactive {
        background: #F1F5F9;
        color: #64748B;
    }

    .lg-status-inactive-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #94A3B8;
    }

    /* STATS COUNTERS */
    .lg-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .lg-stat-card {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .lg-stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .lg-stat-val {
        font-size: 24px;
        font-weight: 800;
        color: #0F172A;
    }

    /* ACTION BUTTONS */
    .lg-action-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--lg-accent);
        color: #FFFFFF !important;
        font-size: 13.5px;
        font-weight: 600;
        padding: 9px 18px;
        border-radius: 8px;
        text-decoration: none !important;
        box-shadow: 0 2px 6px rgba(20, 100, 244, 0.2);
        transition: all 0.15s ease;
    }

    .lg-action-btn-primary:hover {
        background: #0E52D0;
    }

    /* ALERT CONTAINER WHEN NONE ENABLED */
    .lg-alert-none {
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 12px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        color: #92400E;
    }

    .lg-alert-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #FEF3C7;
        color: #D97706;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="lg-channels-page-wrap">

    <!-- Top Header & Breadcrumb -->
    <div class="lg-header-row">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/>
                    <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                </svg>
            </div>
            <div>
                <h1 class="lg-title-main">Call Channels</h1>
                <div class="lg-breadcrumb-nav">
                    <a href="{{ route('adminHome') }}">Dashboard</a>
                    <span>/</span>
                    <span>People</span>
                    <span>/</span>
                    <span style="color: #0F172A; font-weight: 600;">Call Channels</span>
                </div>
            </div>
        </div>
    </div>



    <!-- TOAST ALERT CONTAINER -->
    <div id="lgToastAlert" class="lg-toast-alert lg-toast-success">
        <svg id="lgToastIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span id="lgToastMessage">Settings updated successfully.</span>
    </div>

    <!-- WORKSPACE PANEL 1: WebRTC Configuration (Shown when WebRTC is active) -->
    <div id="panel-webrtc" class="lg-workspace-panel" style="{{ $activeChannel === 'webrtc' ? '' : 'display: none;' }}">
        <div class="lg-panel-top-row">
            <div class="lg-panel-header-left">
                <span class="lg-status-pill lg-status-active">
                    <span class="lg-status-dot"></span>
                    Active Channel
                </span>
                <h3 style="font-size: 17px; font-weight: 700; color: #0F172A; margin: 0;">WebRTC Browser Softphone</h3>
            </div>
            <a href="{{ route('webrtc') }}" class="lg-action-btn-primary">
                Manage WebRTC Channels
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

        <div class="lg-stats-grid">
            <div class="lg-stat-card">
                <span class="lg-stat-label">License Channels</span>
                <span class="lg-stat-val">{{ $webrtcLicense ?? 20 }}</span>
            </div>
            <div class="lg-stat-card">
                <span class="lg-stat-label">Assigned Agents</span>
                <span class="lg-stat-val" style="color: var(--lg-accent);">{{ $webrtcCount ?? 0 }}</span>
            </div>
            <div class="lg-stat-card">
                <span class="lg-stat-label">Available Channels</span>
                <span class="lg-stat-val" style="color: #16A34A;">{{ max(0, ($webrtcLicense ?? 20) - ($webrtcCount ?? 0)) }}</span>
            </div>
        </div>

        <p style="font-size: 13.5px; color: #64748B; margin: 0 0 16px 0;">
            WebRTC enables real-time high-fidelity voice calls directly through agents' web browsers using secure SIP/WebSockets without requiring mobile forwarding or telephony hardware.
        </p>

        @if(!empty($webrtcUsers) && count($webrtcUsers) > 0)
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 14px 18px;">
            <div style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.04em;">Assigned WebRTC Agents ({{ count($webrtcUsers) }})</div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach($webrtcUsers->take(8) as $usr)
                <span style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #1E293B;">
                    {{ $usr->name }} @if(!empty($usr->webrtc_default_number)) <span style="color: #64748B; font-weight: 400;">({{ $usr->webrtc_default_number }})</span> @endif
                </span>
                @endforeach
                @if(count($webrtcUsers) > 8)
                <span style="background: #E2E8F0; padding: 4px 8px; border-radius: 6px; font-size: 12px; color: #475569; font-weight: 600;">+{{ count($webrtcUsers) - 8 }} more</span>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- WORKSPACE PANEL 2: Click2Call Configuration (Shown when Click2Call is active) -->
    <div id="panel-click2call" class="lg-workspace-panel" style="{{ $activeChannel === 'click2call' ? '' : 'display: none;' }}">
        <div class="lg-panel-top-row">
            <div class="lg-panel-header-left">
                <span class="lg-status-pill lg-status-active">
                    <span class="lg-status-dot"></span>
                    Active Channel
                </span>
                <h3 style="font-size: 17px; font-weight: 700; color: #0F172A; margin: 0;">Click2Call Telephony Gateway</h3>
            </div>
            <a href="{{ route('click2Call') }}" class="lg-action-btn-primary">
                Manage Click2Call Channels
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

        <div class="lg-stats-grid">
            <div class="lg-stat-card">
                <span class="lg-stat-label">License Channels</span>
                <span class="lg-stat-val">{{ $c2cLicense ?? 50 }}</span>
            </div>
            <div class="lg-stat-card">
                <span class="lg-stat-label">Assigned Agents</span>
                <span class="lg-stat-val" style="color: var(--lg-accent);">{{ $c2cCount ?? 0 }}</span>
            </div>
            <div class="lg-stat-card">
                <span class="lg-stat-label">Available Channels</span>
                <span class="lg-stat-val" style="color: #16A34A;">{{ max(0, ($c2cLicense ?? 50) - ($c2cCount ?? 0)) }}</span>
            </div>
        </div>

        <p style="font-size: 13.5px; color: #64748B; margin: 0 0 16px 0;">
            Click2Call bridges calls between your CRM agents and leads via telecom gateways, ringing the agent's mapped mobile phone before dialing the customer number.
        </p>

        @if(!empty($c2cUsers) && count($c2cUsers) > 0)
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 14px 18px;">
            <div style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.04em;">Assigned Click2Call Agents ({{ count($c2cUsers) }})</div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach($c2cUsers->take(8) as $usr)
                <span style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #1E293B;">
                    {{ $usr->name }} @if(!empty($usr->click_agent_number)) <span style="color: #64748B; font-weight: 400;">({{ $usr->click_agent_number }})</span> @endif
                </span>
                @endforeach
                @if(count($c2cUsers) > 8)
                <span style="background: #E2E8F0; padding: 4px 8px; border-radius: 6px; font-size: 12px; color: #475569; font-weight: 600;">+{{ count($c2cUsers) - 8 }} more</span>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- WORKSPACE PANEL 3: Neither Enabled Alert (Shown when both are disabled) -->
    <div id="panel-none" class="lg-alert-none" style="{{ $activeChannel === 'none' ? '' : 'display: none;' }}; margin-top: 10px;">
        <div class="lg-alert-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
            <h4 style="font-size: 15px; font-weight: 700; margin: 0 0 4px 0; color: #78350F;">No Call Channel Is Currently Enabled</h4>
            <p style="font-size: 13px; margin: 0; color: #92400E;">
                Please toggle on either <strong>WebRTC</strong> or <strong>Click2Call</strong> in the <strong>People &rarr; Call channels</strong> sidebar menu so agents can make calls.
            </p>
        </div>
    </div>

</div>
@endsection

@push("after-scripts")
<script>
function updateWorkspaces(activeChannel) {
    const pWebRTC = document.getElementById('panel-webrtc');
    const pC2C = document.getElementById('panel-click2call');
    const pNone = document.getElementById('panel-none');

    if (pWebRTC && pC2C && pNone) {
        if (activeChannel === 'webrtc') {
            pWebRTC.style.display = 'block';
            pC2C.style.display = 'none';
            pNone.style.display = 'none';
        } else if (activeChannel === 'click2call') {
            pWebRTC.style.display = 'none';
            pC2C.style.display = 'block';
            pNone.style.display = 'none';
        } else {
            pWebRTC.style.display = 'none';
            pC2C.style.display = 'none';
            pNone.style.display = 'flex';
        }
    }
}

function revertToggles() {
    window.location.reload();
}

function showToast(message, type) {
    const toast = document.getElementById('lgToastAlert');
    const msgEl = document.getElementById('lgToastMessage');
    
    if (!toast || !msgEl) return;

    msgEl.textContent = message;
    toast.className = 'lg-toast-alert ' + (type === 'error' ? 'lg-toast-error' : 'lg-toast-success');
    toast.style.display = 'flex';

    setTimeout(() => {
        toast.style.display = 'none';
    }, 4000);
}
</script>
@endpush
