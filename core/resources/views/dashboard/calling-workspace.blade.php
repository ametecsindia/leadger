@extends('dashboard.layouts.master')
@section('title', 'LeadGER - Calling Workspace')

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ==========================================================================
   LeadGER Unified Calling Workspace Design System (Corporate Navy & Blue)
   ========================================================================== */
:root {
    --cw-navy: #0B1630;
    --cw-navy-light: #16264C;
    --cw-blue: #1F5FFF;
    --cw-blue-hover: #0046E5;
    --cw-blue-light: #EFF4FF;
    --cw-green: #0FB77A;
    --cw-green-hover: #0B9864;
    --cw-green-light: #E6F8F2;
    --cw-red: #EF4444;
    --cw-red-light: #FEF2F2;
    --cw-amber: #F59E0B;
    --cw-amber-light: #FFFBEB;
    --cw-bg: #F4F7FB;
    --cw-card-bg: #FFFFFF;
    --cw-border: #E2E8F0;
    --cw-text-main: #0B1630;
    --cw-text-sub: #64748B;
    --cw-radius: 14px;
    --cw-shadow: 0 4px 16px rgba(11, 22, 48, 0.04);
}

.cw-container {
    padding: 16px 20px 28px 20px;
    background-color: var(--cw-bg);
    min-height: calc(100vh - 65px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--cw-text-main);
    box-sizing: border-box;
}

/* ── Page Header & Segmented Switch ──────────────────────── */
.cw-header {
    background: var(--cw-card-bg);
    border: 1px solid var(--cw-border);
    border-radius: var(--cw-radius);
    padding: 14px 20px;
    box-shadow: var(--cw-shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    gap: 16px;
    flex-wrap: wrap;
}

.cw-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.cw-header-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--cw-navy) 0%, var(--cw-navy-light) 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 4px 12px rgba(11, 22, 48, 0.2);
    flex-shrink: 0;
}

.cw-header-title {
    margin: 0;
    font-weight: 800;
    color: var(--cw-navy);
    font-size: 20px;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.cw-header-subtitle {
    margin: 2px 0 0 0;
    font-size: 13px;
    color: var(--cw-text-sub);
    font-weight: 500;
}

.cw-segmented-switch {
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 3px;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.cw-switch-btn {
    border: none;
    background: transparent;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    color: var(--cw-text-sub);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    user-select: none;
}

.cw-switch-btn:hover {
    color: var(--cw-navy);
}

.cw-switch-btn.active {
    background: #FFFFFF;
    color: var(--cw-blue);
    box-shadow: 0 2px 8px rgba(31, 95, 255, 0.12);
}

.cw-switch-badge {
    background: var(--cw-blue-light);
    color: var(--cw-blue);
    font-size: 11px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 20px;
}

.cw-switch-btn.active .cw-switch-badge {
    background: var(--cw-blue);
    color: #FFFFFF;
}

/* ── Topbar (Queue Info & Status) ───────────────────────── */
.cw-topbar {
    background: var(--cw-card-bg);
    border: 1px solid var(--cw-border);
    border-radius: 12px;
    padding: 10px 18px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(11, 22, 48, 0.02);
}

.cw-drawer-trigger-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 700;
    color: var(--cw-navy);
    cursor: pointer;
    transition: all 0.15s ease;
}

/* Campaign Queue: "Select Campaign" sits right of "Next leads" (same button look, slightly larger) */
#cw-workspace-campaign-select {
    appearance: none;
    -webkit-appearance: none;
    height: 42px;
    min-width: 230px;
    max-width: 100%;
    padding: 0 40px 0 16px;
    font-size: 13.5px;
    outline: none;
}
/* app.js turns every <select> into Select2: give its box the same look (one arrow: ours) */
#cw-campaign-select-wrap .select2-container { width: auto !important; min-width: 230px; max-width: 100%; }
/* lg-design-system.css sizes every control from these variables; leadger-theme.css forces white with !important */
#cw-campaign-select-wrap { --lg-control-h: 42px; --lg-radius-control: 9px; }
#cw-campaign-select-wrap .select2-selection--single {
    display: flex;
    align-items: center;
    padding: 0 !important;
    background-color: #F8FAFC !important;
    border: 1px solid #CBD5E1 !important;
}
#cw-campaign-select-wrap .select2-selection--single:hover,
#cw-campaign-select-wrap .select2-container--open .select2-selection--single { background-color: #FFFFFF !important; border-color: var(--cw-blue) !important; }
/* keyboard focus: the theme's focus ring (leadger-theme.css), which the border rule above would otherwise hide */
#cw-campaign-select-wrap .select2-selection--single:focus,
#cw-campaign-select-wrap .select2-container--focus .select2-selection--single {
    outline: none;
    border-color: var(--lg-accent) !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
}
#cw-campaign-select-wrap .select2-selection__rendered {
    padding: 0 40px 0 16px !important;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--cw-navy) !important;
    line-height: 40px !important;
}
#cw-campaign-select-wrap .select2-selection__arrow { display: none !important; }   /* the single arrow is .cw-campaign-select-arrow */
.cw-campaign-select-arrow {
    position: absolute;
    right: 15px;
    pointer-events: none;
    font-size: 11px;
    color: var(--cw-navy);
}
@media (max-width: 576px) {
    #cw-campaign-select-wrap, #cw-workspace-campaign-select, #cw-campaign-select-wrap .select2-container { width: 100% !important; }
}

.cw-drawer-trigger-btn:hover {
    background: #FFFFFF;
    border-color: var(--cw-blue);
    color: var(--cw-blue);
}

.cw-badge-pill {
    background: var(--cw-blue-light);
    color: var(--cw-blue);
    font-size: 11.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
}

.cw-status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--cw-text-sub);
}

.cw-live-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--cw-green);
    box-shadow: 0 0 0 3px rgba(15, 183, 122, 0.25);
    animation: cwPulse 1.5s infinite;
}

@keyframes cwPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(15, 183, 122, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(15, 183, 122, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(15, 183, 122, 0); }
}

/* ── Main Row of 2 Equal-Height Cards ────────────────────── */
.cw-main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
    align-items: stretch;
}

@media (max-width: 991px) {
    .cw-main-grid {
        grid-template-columns: 1fr !important;
        gap: 16px;
    }
}

.cw-card {
    background: var(--cw-card-bg);
    border: 1px solid var(--cw-border);
    border-radius: var(--cw-radius);
    padding: 20px;
    box-shadow: var(--cw-shadow);
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    box-sizing: border-box;
}

.cw-main-grid > .cw-card {
    height: 100%;
    min-height: 520px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.cw-main-grid .cw-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.cw-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #F1F5F9;
}

.cw-card-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--cw-navy);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cw-status-badge-chip {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.cw-status-badge-chip.green { background: var(--cw-green-light); color: var(--cw-green); border: 1px solid rgba(15, 183, 122, 0.3); }
.cw-status-badge-chip.red { background: var(--cw-red-light); color: var(--cw-red); border: 1px solid rgba(239, 68, 68, 0.3); }
.cw-status-badge-chip.blue { background: var(--cw-blue-light); color: var(--cw-blue); border: 1px solid rgba(31, 95, 255, 0.3); }

/* ── Lead Identity Header in Left Card ────────────────────── */
.cw-lead-identity {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.cw-lead-id-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.cw-avatar-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--cw-blue);
    color: #FFFFFF;
    font-size: 16px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(31, 95, 255, 0.25);
}

.cw-lead-name-text {
    font-size: 17px;
    font-weight: 800;
    color: var(--cw-navy);
    margin: 0 0 2px 0;
    line-height: 1.2;
}

.cw-lead-phone-text {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--cw-text-sub);
    margin: 0;
}

.cw-call-timer-box {
    background: #0B1630;
    color: #0FB77A;
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-size: 18px;
    font-weight: 800;
    padding: 6px 16px;
    border-radius: 10px;
    letter-spacing: 1px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
}

/* ── 2-Column Info Grid ─────────────────────────────────── */
.cw-info-grid-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 16px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 14px;
}

.cw-info-item {
    font-size: 12.5px;
}

.cw-info-label {
    color: var(--cw-text-sub);
    font-weight: 600;
    display: block;
    margin-bottom: 1px;
}

.cw-info-val {
    color: var(--cw-navy);
    font-weight: 700;
    word-break: break-word;
}

/* ── Call Action Buttons & Chips ─────────────────────────── */
.cw-call-options-row {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.cw-call-option-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 36px;
    padding: 0 12px;
    border: 1px solid #E3E8F2;
    border-radius: 10px;
    background: #FFFFFF;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    user-select: none;
    transition: background 0.15s ease, border-color 0.15s ease;
    position: relative;
}

.cw-call-option-label input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    border: 2px solid #CBD5E1;
    border-radius: 4px;
    background: #FFFFFF;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.15s ease;
    position: relative;
}

.cw-call-option-label input[type="checkbox"]:checked {
    background: var(--cw-blue);
    border-color: var(--cw-blue);
}

.cw-call-option-label input[type="checkbox"]:checked::after {
    content: '';
    position: absolute;
    left: 3px;
    top: 1px;
    width: 5px;
    height: 9px;
    border: 2px solid #FFFFFF;
    border-top: none;
    border-left: none;
    transform: rotate(45deg);
}

.cw-call-option-label:has(input:checked) {
    background: #E8EFFF;
    border-color: var(--cw-blue);
    color: var(--cw-blue);
}

.cw-call-option-label:has(input:disabled) {
    opacity: 0.55;
    cursor: not-allowed;
    pointer-events: none;
}

.cw-action-btns-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

@media (max-width: 1000px) {
    .cw-call-options-row { gap: 10px; }
    .cw-action-btns-row { flex-wrap: wrap; }
    .cw-btn-call-start { min-width: 120px; }
}

.cw-btn-call-start {
    flex: 1;
    height: 42px;
    background: var(--cw-green);
    color: #FFFFFF !important;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(15, 183, 122, 0.3);
    transition: all 0.15s ease;
}

.cw-btn-call-start:hover {
    background: var(--cw-green-hover);
}

.cw-btn-call-start.on-call {
    background: var(--cw-red) !important;
    box-shadow: 0 3px 10px rgba(239, 68, 68, 0.3) !important;
}

.cw-btn-call-start.disabled-type,
.cw-btn-call-start:disabled {
    background: #94A3B8 !important;
    border-color: #94A3B8 !important;
    cursor: not-allowed !important;
    box-shadow: none !important;
    opacity: 0.75;
}

.cw-btn-icon-sec {
    height: 42px;
    padding: 0 14px;
    background: #F1F5F9;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    color: var(--cw-navy);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.cw-btn-icon-sec:hover {
    background: #FFFFFF;
    border-color: var(--cw-blue);
    color: var(--cw-blue);
}

.cw-chips-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.cw-chip-btn {
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    padding: 4px 11px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--cw-text-sub);
    cursor: pointer;
    transition: all 0.15s ease;
}

.cw-chip-btn:hover {
    background: var(--cw-blue-light);
    border-color: var(--cw-blue);
    color: var(--cw-blue);
}

/* ── Right Card: Call Outcome Form ───────────────────────── */
.cw-status-chips-wrap {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 14px;
    max-height: 36px;          /* one row, as before: further rows scroll inside, the card keeps its size */
    overflow-y: auto;
    scrollbar-width: thin;
}
.cw-status-chips-empty { grid-column: 1 / -1; font-size: 12.5px; color: var(--cw-text-sub); padding: 8px 0; }

.cw-status-chip-opt {
    font-family: inherit;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    background: #F8FAFC;
    border: 1.5px solid #CBD5E1;
    border-radius: 8px;
    padding: 8px 4px;
    text-align: center;
    font-size: 12px;
    font-weight: 700;
    color: var(--cw-text-sub);
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
}

.cw-status-chip-opt:hover {
    border-color: var(--cw-blue);
    color: var(--cw-blue);
}

.cw-status-chip-opt.selected {
    background: var(--cw-blue-light);
    border-color: var(--cw-blue);
    color: var(--cw-blue);
}

.cw-field-group {
    margin-bottom: 12px;
}

.cw-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--cw-text-sub);
    margin-bottom: 4px;
    display: block;
}

.cw-input, .cw-select, .cw-textarea {
    width: 100%;
    padding: 8px 12px;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    color: var(--cw-navy);
    font-family: inherit;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
}

.cw-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px 16px;
    padding-right: 36px;
    cursor: pointer;
}

.cw-input:focus, .cw-select:focus, .cw-textarea:focus {
    background: #FFFFFF;
    border-color: var(--cw-blue);
    box-shadow: 0 0 0 3px rgba(31, 95, 255, 0.12);
}

.cw-outcome-btns-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 14px;
}

.cw-btn-save-next {
    height: 40px;
    background: var(--cw-blue);
    color: #FFFFFF !important;
    border: none;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 800;
    padding: 0 20px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(31, 95, 255, 0.25);
    transition: all 0.15s ease;
}

.cw-btn-save-next:hover {
    background: var(--cw-blue-hover);
}

/* Call Outcome: Save (above) / Next lead; disabled states and the brief "Saved" tick */
.cw-btn-tip { display: inline-flex; }
.cw-btn-next {
    height: 40px;
    background: #FFFFFF;
    color: var(--cw-blue);
    border: 1px solid var(--cw-blue);
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 800;
    padding: 0 18px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.cw-btn-next:hover:not(:disabled) { background: var(--cw-blue-light); }
.cw-btn-save-next:disabled, .cw-btn-next:disabled, .cw-btn-skip:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; }
.cw-btn-save-next:disabled:hover { background: var(--cw-blue); }
.cw-btn-save-next.is-saved, .cw-btn-save-next.is-saved:disabled { background: #12A66A; opacity: 1; }

.cw-btn-skip {
    height: 40px;
    background: #FFFFFF;
    color: var(--cw-text-sub);
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 700;
    padding: 0 18px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.cw-btn-skip:hover {
    background: #F8FAFC;
    color: var(--cw-navy);
}

/* ── History Table Card ─────────────────────────────────── */
.cw-history-card {
    background: var(--cw-card-bg);
    border: 1px solid var(--cw-border);
    border-radius: var(--cw-radius);
    padding: 20px;
    box-shadow: var(--cw-shadow);
    margin-top: 16px;
}

.cw-table-responsive {
    overflow-x: auto;
}

.cw-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}

.cw-table th {
    background: #F8FAFC;
    color: var(--cw-text-sub);
    font-weight: 700;
    padding: 10px 14px;
    border-bottom: 1px solid var(--cw-border);
}

.cw-table td {
    padding: 11px 14px;
    border-bottom: 1px solid #F1F5F9;
    color: var(--cw-navy);
    font-weight: 500;
}

/* ── Green Header Bar on Make a Call (Tab 2) ─────────────── */
.cw-card-green-header {
    background: linear-gradient(135deg, #0FB77A 0%, #0A9A66 100%);
    padding: 16px 20px;
    min-height: 56px;
    margin: -20px -20px 16px -20px;
    border-radius: 14px 14px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-sizing: border-box;
}

.cw-card-green-title {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #FFFFFF;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cw-translucent-pill {
    background: rgba(255, 255, 255, 0.2);
    color: #FFFFFF;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
}

/* ── Compact Calling Option Buttons (Tab 2) ─────────────── */
.cw-calling-buttons-row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 10px;
    flex-wrap: wrap;
    width: auto;
}

.cw-compact-call-btn {
    height: 38px;
    flex: 0 0 auto;
    width: auto;
    max-width: fit-content;
    padding: 9px 20px;
    border-radius: 8px;
    border: none;
    font-size: 13.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    box-sizing: border-box;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    text-decoration: none;
    white-space: nowrap;
}

.cw-compact-call-btn i {
    font-size: 16px;
    line-height: 1;
}

.cw-compact-call-btn.c2c-btn {
    background: linear-gradient(135deg, #0FB77A 0%, #0B9864 100%);
    color: #FFFFFF;
    box-shadow: 0 3px 10px rgba(15, 183, 122, 0.25);
}
.cw-compact-call-btn.c2c-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #10C684 0%, #0A8B5B 100%);
    box-shadow: 0 5px 14px rgba(15, 183, 122, 0.35);
    transform: translateY(-1px);
}

.cw-compact-call-btn.webrtc-btn {
    background: linear-gradient(135deg, #1F5FFF 0%, #0046E5 100%);
    color: #FFFFFF;
    box-shadow: 0 3px 10px rgba(31, 95, 255, 0.25);
}
.cw-compact-call-btn.webrtc-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #2D6BFF 0%, #003ECC 100%);
    box-shadow: 0 5px 14px rgba(31, 95, 255, 0.35);
    transform: translateY(-1px);
}

.cw-compact-call-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed !important;
    transform: none !important;
    box-shadow: none !important;
}

.cw-calling-option-hint {
    background: #F8FAFC;
    border: 1.5px dashed #CBD5E1;
    border-radius: 8px;
    padding: 10px 14px;
    color: #64748B;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    box-sizing: border-box;
}

.cw-btn-disabled {
    pointer-events: none !important;
    opacity: 0.65 !important;
    cursor: not-allowed !important;
}

/* ── Phone Meta Row & Detected Badge ─────────────────────── */
.cw-phone-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 6px;
    gap: 8px;
}

.cw-detected-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 14px;
    transition: all 0.2s ease;
}

.cw-detected-badge.badge-empty {
    background: #F1F5F9;
    color: #64748B;
    border: 1px solid #E2E8F0;
}

.cw-detected-badge.badge-c2c {
    background: #E6F8F2;
    color: #0FB77A;
    border: 1px solid rgba(15, 183, 122, 0.3);
}

.cw-detected-badge.badge-webrtc {
    background: #EFF6FF;
    color: var(--lg-accent);
    border: 1px solid rgba(37, 99, 235, 0.3);
}

.cw-detected-badge.badge-both {
    background: #EEF2FF;
    color: #4F46E5;
    border: 1px solid rgba(79, 70, 229, 0.3);
}


/* ── Phone Number Input (Matched to Call Outcome Inputs) ───── */
.cw-phone-input-wrap {
    position: relative;
    width: 100%;
}

.cw-phone-input-large {
    width: 100%;
    height: 42px;
    padding: 0 38px 0 12px;
    background: #FFFFFF;
    border: 1px solid var(--cw-border);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--cw-navy);
    font-family: inherit;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
}

.cw-phone-input-large:focus {
    border-color: var(--cw-blue);
    box-shadow: 0 0 0 3px rgba(31, 95, 255, 0.12);
}

.cw-phone-clear-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    background: #F1F5F9;
    color: #64748B;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 11px;
    padding: 0;
    transition: all 0.15s ease;
}

.cw-phone-clear-btn:hover {
    background: #E2E8F0;
    color: #0F172A;
}

/* ── Main Line Box & Compact Inline Checkbox Options ───────── */
.cw-mainline-box {
    background: #F8FAFC;
    border: 1px solid var(--cw-border);
    border-radius: 10px;
    padding: 10px 14px;
    box-sizing: border-box;
    transition: border-color 0.15s ease;
}

.cw-mainline-box:hover {
    border-color: #CBD5E1;
}

/* Click2Call VMN (left) and WebRTC VMN (right), equal width; stacked on small screens */
.cw-vmn-columns {
    display: flex;
    gap: 12px;
    align-items: stretch;
    width: 100%;
    min-width: 0;
}
.cw-vmn-col {
    flex: 1 1 0;
    min-width: 0;
    flex-direction: column;
    gap: 8px;
}
.cw-vmn-col .cw-label { margin-bottom: 0 !important; }
.cw-vmn-col .cw-mainline-box { flex: 1 1 auto; }
.cw-vmn-col .cw-mainline-top { flex-wrap: wrap; gap: 6px; }
.cw-vmn-col .cw-mainline-number { white-space: nowrap; }
.cw-vmn-col .cw-mainline-options-row { flex-wrap: wrap; }
.cw-vmn-avail-badge.is-unavailable {
    background: #FEF2F2;
    border-color: rgba(239, 68, 68, 0.3);
    color: #DC2626;
}
.cw-vmn-avail-badge.is-unavailable .cw-vmn-dot { background: #EF4444 !important; }
.cw-vmn-avail-badge.is-none {
    background: #F1F5F9;
    border-color: #E2E8F0;
    color: #64748B;
}
.cw-vmn-avail-badge.is-none .cw-vmn-dot { background: #94A3B8 !important; }
.cw-channel-off-note { display: none; }
.cw-vmn-col.is-channel-off .cw-channel-off-note { display: block; color: #B45309; }
.cw-vmn-col.is-channel-off .cw-select { opacity: .6; pointer-events: none; }
.cw-vmn-col.is-channel-off .cw-mainline-options-row,
.cw-vmn-col.is-channel-off #cw-webrtc-empty-note { display: none !important; }
.cw-vmn-empty-note {
    margin-top: 8px;
    font-size: 12px;
    color: #64748B;
    line-height: 1.4;
}
@media (max-width: 640px) {
    .cw-vmn-columns { flex-direction: column; }
}

.cw-mainline-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.cw-mainline-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cw-mainline-number {
    font-size: 14px;
    font-weight: 700;
    color: var(--cw-navy);
    letter-spacing: 0.2px;
}

.cw-mainline-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

/* Click2Call calling option (Smart31 / Enabled 1600): two compact square-style buttons under the number info */
.cw-mainline-options-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding-top: 10px;
    margin-top: 2px;
    border-top: 1px solid #E2E8F0;
}
.cw-call-opt { margin: 0; cursor: pointer; }
.cw-call-opt-input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
.cw-call-opt span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 32px;
    padding: 0 8px;            /* enable box + both options fit on one row in the narrow (1366px) column */
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    background: #F1F5F9;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    white-space: nowrap;
    transition: all 0.15s ease;
    user-select: none;
}
.cw-call-opt:hover span { border-color: var(--cw-blue); color: var(--cw-blue); }
.cw-call-opt-input:checked + span { background: var(--cw-blue); border-color: var(--cw-blue); color: #FFFFFF; }
.cw-call-opt-input:focus-visible + span { box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2); }
.cw-mainline-source {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
#cw-mainline-box .cw-mainline-left { flex-direction: column; align-items: flex-start; gap: 2px; }

/* ── Live Call Status Container (Inside Make a Call card) ──── */
.cw-live-status-container {
    border-radius: 8px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 10px;
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.cw-live-status-container.state-ringing {
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    color: #92400E;
}

.cw-live-status-container.state-connected {
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #065F46;
}

.cw-live-status-container.state-failed {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #991B1B;
}

.cw-live-status-container.state-ended {
    background: #F1F5F9;
    border: 1px solid #CBD5E1;
    color: #334155;
}

.cw-live-timer {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    font-weight: 800;
    padding: 2px 8px;
    background: rgba(0, 0, 0, 0.06);
    border-radius: 6px;
    letter-spacing: 0.5px;
}

.cw-btn-hangup {
    background: #EF4444;
    color: #FFFFFF;
    border: none;
    border-radius: 6px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.15s ease;
}

.cw-btn-hangup:hover {
    background: #DC2626;
}

/* ── Quick Action Modals ───────────────────────────────────── */
.cw-quick-modal {
    position: fixed;
    inset: 0;
    z-index: 9999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}

.cw-quick-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(11, 22, 48, 0.45);
    backdrop-filter: blur(2px);
}

.cw-quick-modal-box {
    position: relative;
    background: #FFFFFF;
    border-radius: 12px;
    box-shadow: 0 16px 36px rgba(11, 22, 48, 0.2);
    width: 100%;
    max-width: 450px;
    overflow: hidden;
    z-index: 1;
    animation: cwModalPop 0.2s ease-out;
}

@keyframes cwModalPop {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.cw-quick-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid #F1F5F9;
}

.cw-quick-modal-header h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--cw-navy);
    display: flex;
    align-items: center;
    gap: 8px;
}

.cw-quick-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #94A3B8;
    cursor: pointer;
    line-height: 1;
}

.cw-quick-modal-body {
    padding: 16px 18px;
}

.cw-quick-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 18px;
    border-top: 1px solid #F1F5F9;
    background: #F8FAFC;
}

.cw-btn-sec-sm {
    padding: 6px 14px;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.cw-btn-primary-sm {
    padding: 6px 16px;
    border-radius: 6px;
    border: none;
    background: var(--cw-blue);
    color: #FFFFFF;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.cw-btn-primary-sm:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.cw-tag-suggestion {
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 9px;
    background: #EFF6FF;
    color: var(--cw-blue);
    border: 1px solid rgba(31, 95, 255, 0.2);
    border-radius: 12px;
    cursor: pointer;
}

.cw-tag-suggestion:hover {
    background: #DBEAFE;
}

/* ── VMN number/DID Container ────────────────────────────── */
.cw-vmn-card-container {
    position: relative;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    min-height: 54px;
    box-sizing: border-box;
}

.cw-vmn-card-container:hover {
    border-color: #CBD5E1;
    background: #F1F5F9;
}

.cw-vmn-left {
    display: flex;
    flex-direction: column;
    gap: 2px;
    z-index: 1;
}

.cw-vmn-number-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cw-vmn-number {
    font-size: 15px;
    font-weight: 700;
    color: #0F172A;
    letter-spacing: 0.3px;
    line-height: 1.2;
}

.cw-vmn-chevron {
    font-size: 11px;
    color: #94A3B8;
    transition: transform 0.15s ease;
}

.cw-vmn-line-name {
    font-size: 12px;
    font-weight: 500;
    color: #64748B;
    line-height: 1.2;
}

.cw-vmn-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 3px;
    z-index: 1;
}

.cw-vmn-avail-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    background: #E6F8F2;
    border: 1px solid rgba(15, 183, 122, 0.25);
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    color: #0FB77A;
    line-height: 1.3;
}

.cw-vmn-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #0FB77A;
    display: inline-block;
}

.cw-vmn-balance {
    font-size: 11px;
    color: #64748B;
    font-weight: 500;
    line-height: 1.2;
}

.cw-vmn-native-select {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 5;
    background: transparent;
    border: none;
    outline: none;
}

.cw-make-call-card,
.cw-outcome-card {
    height: 100% !important;
    min-height: 520px;
    align-self: stretch !important;
    display: flex !important;
    flex-direction: column !important;
    box-sizing: border-box;
    overflow: visible !important;
}

.cw-make-call-card .cw-card-body,
.cw-outcome-card .cw-card-body {
    flex: 1 1 auto !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 12px;
    overflow: visible !important;
}

.cw-calling-option-section {
    margin-top: auto !important;
    padding-top: 10px;
}

/* ── Recent Numbers Section ──────────────────────────────── */
.cw-recent-numbers-section {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.cw-recent-chips-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.cw-recent-chip {
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 700;
    color: var(--cw-navy);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.cw-recent-chip i {
    font-size: 10px;
    color: #64748B;
    transition: color 0.15s ease;
}

.cw-recent-chip:hover {
    background: #EFF6FF;
    border-color: var(--lg-accent);
    color: var(--lg-accent);
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.12);
}

.cw-recent-chip:hover i {
    color: var(--lg-accent);
}

/* ── Live Call Status Toast Popup ────────────────────────── */
.cw-call-toast {
    position: fixed;
    top: 24px;
    right: 24px;
    width: 320px;
    background: #FFFFFF;
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: 0 10px 30px rgba(11, 22, 48, 0.15), 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid #E2E8F0;
    z-index: 999999;
    display: flex;
    align-items: center;
    gap: 12px;
    box-sizing: border-box;
    animation: cwSlideIn 0.25s ease;
    font-family: inherit;
}

@keyframes cwSlideIn {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}

.cw-toast-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

.cw-call-toast.state-dialing .cw-toast-icon-wrap {
    background: #EFF4FF;
    color: var(--cw-blue);
}
.cw-call-toast.state-ringing .cw-toast-icon-wrap {
    background: #FFFBEB;
    color: #F59E0B;
}
.cw-call-toast.state-connected .cw-toast-icon-wrap {
    background: #E6F8F2;
    color: var(--cw-green);
}
.cw-call-toast.state-error .cw-toast-icon-wrap {
    background: #FEF2F2;
    color: var(--cw-red);
}

.cw-toast-body {
    flex: 1;
    min-width: 0;
}

.cw-toast-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--cw-navy);
    line-height: 1.25;
}

.cw-toast-desc {
    font-size: 11.5px;
    color: #64748B;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cw-toast-close {
    background: transparent;
    border: none;
    font-size: 18px;
    line-height: 1;
    color: #94A3B8;
    cursor: pointer;
    padding: 2px;
    border-radius: 4px;
}
.cw-toast-close:hover {
    color: var(--cw-navy);
}

.cw-toast-via, .cw-toast-badge, .cw-toast-progress { display: none; }

/* Calling / Ringing / Call Blocked toasts: green, full wrapped text, below the header bar. Other toasts keep the look above.
   ponytail: #17b978→#0e9a63 gives white text only 2.6–3.6:1; this darker step of the same green keeps it ≥ 4.5:1 */
.cw-call-toast.is-call {
    top: 84px;
    width: 420px; min-width: 340px; max-width: 90vw;
    padding: 14px 16px;
    gap: 14px;
    align-items: flex-start;
    border-radius: 14px;
    overflow: hidden;
    background: linear-gradient(135deg, #0A8554 0%, #076B43 100%);
    border: 1px solid #065C39;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
    color: #FFFFFF;
    animation: cwToastIn 0.2s ease;
}
.cw-call-toast.is-call.is-leaving { animation: cwToastOut 0.2s ease forwards; }
@keyframes cwToastIn { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: none; } }
@keyframes cwToastOut { from { opacity: 1; transform: none; } to { opacity: 0; transform: translateX(24px); } }
.cw-call-toast.is-call .cw-toast-icon-wrap { width: 44px; height: 44px; border-radius: 12px; background: rgba(255, 255, 255, 0.22); color: #FFFFFF; font-size: 19px; }
.cw-call-toast.is-call .cw-toast-title { font-size: 16px; font-weight: 700; color: #FFFFFF; line-height: 1.4; }
.cw-call-toast.is-call .cw-toast-via { display: inline; font-size: 13px; font-weight: 500; opacity: 0.85; }
.cw-call-toast.is-call .cw-toast-via:empty { display: none; }
.cw-call-toast.is-call .cw-toast-desc {
    font-size: 14px; font-weight: 500; color: #FFFFFF; line-height: 1.4; margin-top: 2px;
    white-space: normal; overflow: visible; text-overflow: clip; overflow-wrap: anywhere;
}
.cw-call-toast.is-blocked .cw-toast-desc { font-weight: 700; }
.cw-call-toast.is-blocked .cw-toast-title { line-height: 1.6; }
.cw-call-toast.is-call:not(.is-blocked) .cw-toast-badge {
    display: inline-flex; align-items: center; gap: 5px; margin-top: 8px; padding: 3px 9px; border-radius: 999px;
    background: rgba(255, 255, 255, 0.18); color: #FFFFFF; font-size: 11px; font-weight: 600; letter-spacing: 0.3px;
}
.cw-call-toast.is-call:not(.is-blocked) .cw-toast-progress {
    display: block; position: absolute; left: 0; bottom: 0; height: 3px; width: 100%;
    background: rgba(255, 255, 255, 0.75); transform-origin: left; animation: cwToastProgress 30s linear forwards;
}
@keyframes cwToastProgress { from { transform: scaleX(0); } to { transform: scaleX(1); } }
.cw-call-toast.is-call .cw-toast-close { flex-shrink: 0; width: 26px; height: 26px; padding: 0; color: rgba(255, 255, 255, 0.85); border-radius: 6px; }
.cw-call-toast.is-call .cw-toast-close:hover { color: #FFFFFF; background: rgba(255, 255, 255, 0.16); }
.cw-toast-ring { width: 24px; height: 24px; box-sizing: border-box; border-radius: 50%; border: 3px solid rgba(255, 255, 255, 0.35); border-top-color: #FFFFFF; animation: cwToastSpin 1s linear infinite; }
.cw-toast-ring-static { display: none; }
@keyframes cwToastSpin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) {
    .cw-call-toast.is-call, .cw-call-toast.is-call.is-leaving { animation: none; }
    .cw-call-toast.is-call .cw-toast-progress { display: none !important; }
    .cw-toast-ring { display: none; }
    .cw-toast-ring-static { display: inline-block; }
}

.cw-card-green-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #FFFFFF !important;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.2;
}

.cw-translucent-pill {
    background: rgba(255, 255, 255, 0.22);
    color: #FFFFFF !important;
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 11.5px;
    font-weight: 700;
    line-height: 1;
}

/* ── 3 Equal Cards Row (Tab 2) ───────────────────────────── */
.cw-grid-3col {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-top: 16px;
}

@media (max-width: 1000px) {
    .cw-grid-3col {
        grid-template-columns: 1fr !important;
    }
}

.cw-perf-progress-bar {
    height: 8px;
    background: #E2E8F0;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 6px;
}

.cw-perf-progress-fill {
    height: 100%;
    background: var(--cw-blue);
    border-radius: 4px;
}

/* ── Slide-in Right Side Drawer ─────────────────────────── */
.cw-drawer-scrim {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(11, 22, 48, 0.4);
    backdrop-filter: blur(2px);
    z-index: 2000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}

.cw-drawer-scrim.open {
    opacity: 1;
    pointer-events: auto;
}

.cw-drawer {
    position: fixed;
    top: 0;
    right: 0;
    width: min(380px, 92vw);
    height: 100vh;
    background: #FFFFFF;
    box-shadow: -10px 0 30px rgba(11, 22, 48, 0.15);
    z-index: 2001;
    transform: translateX(100%);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
}

.cw-drawer.open {
    transform: translateX(0);
}

.cw-drawer-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--cw-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #F8FAFC;
}

.cw-drawer-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--cw-navy);
    margin: 0;
}

.cw-drawer-close {
    background: transparent;
    border: none;
    font-size: 20px;
    color: var(--cw-text-sub);
    cursor: pointer;
    line-height: 1;
}

.cw-drawer-body {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
}

.cw-drawer-search-box {
    margin-bottom: 14px;
}

.cw-drawer-lead-row {
    padding: 12px;
    border: 1px solid var(--cw-border);
    border-radius: 10px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: all 0.15s ease;
    background: #FFFFFF;
}

.cw-drawer-lead-row:hover {
    border-color: var(--cw-blue);
    background: var(--cw-blue-light);
}

.cw-drawer-lead-row.active {
    border-color: var(--cw-blue);
    background: var(--cw-blue-light);
    box-shadow: 0 2px 8px rgba(31, 95, 255, 0.1);
}

/* Campaign Call card while a lead loads: a light overlay on top (no size change) */
#cw-campaign-call-card { position: relative; }
#cw-campaign-call-card.is-loading-lead::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.6);
    border-radius: inherit;
    z-index: 5;
    cursor: progress;
}

/* "Switch lead without saving the call outcome?" */
.cw-switch-confirm { position: fixed; inset: 0; z-index: 2100; background: rgba(11, 22, 48, 0.35); display: flex; align-items: center; justify-content: center; padding: 16px; }
.cw-switch-confirm[hidden] { display: none; }
.cw-switch-confirm-card { background: #FFFFFF; border-radius: 12px; box-shadow: 0 16px 40px rgba(11, 22, 48, 0.25); padding: 20px; width: 100%; max-width: 340px; }
.cw-switch-confirm-card p { margin: 0 0 16px; font-size: 14.5px; font-weight: 700; color: var(--cw-navy); }
.cw-switch-confirm-btns { display: flex; justify-content: flex-end; gap: 10px; }

/* ── Make a Call card (Manual Dial): sized to its content, call button centered under the options ── */
.cw-main-grid > .cw-make-call-card { height: auto !important; min-height: 0; align-self: start !important; }
.cw-make-call-card .cw-card-body.cw-mc-body { gap: 14px !important; min-height: 0; }
.cw-mc-phone { flex-shrink: 0; }
.cw-mc-phone { --lg-control-h: 50px; --lg-fs-body: 22px; }   /* design-system sizes inputs from these variables */
.cw-mc-phone .cw-phone-input-large { padding-left: 14px; }
.cw-mc-scroll { display: flex; flex-direction: column; gap: 14px; }
.cw-mc-scroll > * { flex-shrink: 0; }
/* Click2Call / WebRTC VMN as two equal option tiles; the tile of the channel(s) the entered number will use is highlighted */
.cw-mc-scroll .cw-vmn-col {
    gap: 6px; padding: 10px 12px;
    border: 1px solid var(--cw-border); border-radius: 10px; background: #FFFFFF;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
}
.cw-mc-body:has(#cw-detected-type-badge.badge-c2c) #c2c-vmn-group:not(.is-channel-off),
.cw-mc-body:has(#cw-detected-type-badge.badge-both) #c2c-vmn-group:not(.is-channel-off),
.cw-mc-body:has(#cw-detected-type-badge.badge-webrtc) #webrtc-vmn-group:not(.is-channel-off),
.cw-mc-body:has(#cw-detected-type-badge.badge-both) #webrtc-vmn-group:not(.is-channel-off) {
    border-color: #17B978; background: #F0FDF7; box-shadow: 0 0 0 3px rgba(23, 185, 120, 0.12);
}
.cw-mc-scroll .cw-vmn-col.is-channel-off { background: #F8FAFC; }
.cw-camp-vmn-columns { margin-top: 14px; }
.cw-camp-vmn-columns .cw-vmn-col { gap: 6px; }
.cw-vmn-line {
    display: flex; align-items: center; gap: 6px; min-width: 0;
    font-size: 11.5px; font-weight: 600; color: #0E9A63; line-height: 1.3;
}
.cw-vmn-line .cw-vmn-status-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cw-vmn-line-dot { width: 7px; height: 7px; border-radius: 50%; background: #17B978; flex-shrink: 0; }
.cw-vmn-line.is-unavailable { color: #DC2626; }
.cw-vmn-line.is-unavailable .cw-vmn-line-dot { background: #EF4444; }
.cw-vmn-line.is-none { color: #64748B; }
.cw-vmn-line.is-none .cw-vmn-line-dot { background: #94A3B8; }
.cw-vmn-line[title] { cursor: help; }
.cw-mc-opts { display: flex; gap: 10px; }
.cw-mc-opt {
    flex: 1 1 0; min-width: 0; margin: 0; height: 40px; padding: 0 12px;
    display: flex !important; align-items: center; gap: 10px; font-weight: 400;
    border: 1px solid var(--cw-border); border-radius: 10px; background: #FFFFFF;
    cursor: pointer; user-select: none; transition: border-color .15s ease, background .15s ease;
}
.cw-mc-opt input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
.cw-mc-box {
    width: 18px; height: 18px; flex-shrink: 0; border: 1.5px solid #94A3B8; border-radius: 4px; background: #FFFFFF;
    display: inline-flex; align-items: center; justify-content: center; color: transparent; font-size: 11px;
    transition: all .15s ease;
}
.cw-mc-opt-text { font-size: 13px; font-weight: 700; color: var(--cw-navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cw-mc-opt:hover { border-color: var(--cw-blue); }
.cw-mc-opt:has(input:checked) { border-color: var(--cw-blue); background: var(--cw-blue-light, #EFF6FF); }
.cw-mc-opt input:checked + .cw-mc-box { background: var(--cw-blue); border-color: var(--cw-blue); color: #FFFFFF; }
.cw-mc-opt input:focus-visible + .cw-mc-box { box-shadow: 0 0 0 3px rgba(31, 95, 255, 0.2); }
.cw-mc-smart31 .cw-vmn-line { margin-top: 6px; }
.cw-mc-help { margin-top: 4px; font-size: 11.5px; color: #64748B; }
.cw-mc-scroll .cw-recent-numbers-section { gap: 6px; }
.cw-mc-scroll .cw-recent-chips-wrap { flex-wrap: nowrap; overflow: hidden; }
.cw-mc-scroll .cw-recent-chip { padding: 4px 10px; flex-shrink: 0; }
.cw-mc-foot { display: flex; flex-direction: column; gap: 8px; }
.cw-mc-foot .cw-live-status-container { margin-top: 0; }
.cw-mc-buttons { justify-content: center; flex-wrap: wrap; width: 100%; gap: 12px; }
.cw-mc-buttons .cw-compact-call-btn {
    /* Click2Call and WebRTC Call side by side at the same width */
    height: 44px; padding: 0 20px; width: 190px !important; max-width: 100%; flex: 0 0 190px !important;
    font-size: 14.5px; font-weight: 700; color: #FFFFFF; border-radius: 10px; gap: 8px;
}
.cw-mc-buttons .cw-compact-call-btn i { font-size: 14px; }
.cw-mc-buttons .cw-compact-call-btn.c2c-btn {
    background: linear-gradient(135deg, #17B978 0%, #0E9A63 100%);
    box-shadow: 0 4px 12px rgba(23, 185, 120, 0.28);
}
.cw-mc-buttons .cw-compact-call-btn:disabled {
    background: #CBD5E1 !important; color: #FFFFFF; opacity: 1;
}
</style>
@endpush

@section('content')
@php
    // Call Outcome: active statuses of Master Data -> Lead Status, in the Status page's order, with their substatuses
    $lgOutcomeStatuses = \App\Models\Status::where('status', 1)->with('substatuses')->orderBy('seq_order', 'asc')->orderBy('id', 'asc')->get();
@endphp
<div class="cw-container">

    <!-- Live Status Toast Popup (Calling / Ringing / Call Blocked). Shared by Manual Dial and the Campaign Queue:
         it sits outside both tabs, so it shows whichever tab is open (position: fixed, top right, above everything). -->
    <div id="cw-call-toast" class="cw-call-toast" style="display: none;">
        <div class="cw-toast-icon-wrap" id="cw-toast-icon">
            <i class="fa fa-spinner fa-spin"></i>
        </div>
        <div class="cw-toast-body">
            <div class="cw-toast-title"><span id="cw-toast-title">Calling...</span> <span class="cw-toast-via" id="cw-toast-via"></span></div>
            <div class="cw-toast-desc" id="cw-toast-desc">Dialing...</div>
            <span class="cw-toast-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 2 22 2 22 8"/><line x1="15" y1="9" x2="22" y2="2"/><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                OUTBOUND
            </span>
        </div>
        <button type="button" class="cw-toast-close" onclick="hideCallToast()" aria-label="Close">&times;</button>
        <div class="cw-toast-progress"></div>
    </div>

    <!-- PAGE HEADER WITH SEGMENTED SWITCH -->
    <div class="cw-header">
        <div class="cw-header-left">
            <div class="cw-header-icon">
                <i class="fa fa-headset"></i>
            </div>
            <div>
                <h1 class="cw-header-title">Calling Workspace</h1>
                <p class="cw-header-subtitle">Work your campaign queue or dial any number, all in one place.</p>
            </div>
        </div>

        <div class="cw-segmented-switch">
            <button type="button" class="cw-switch-btn {{ $activeTab === 'campaign' ? 'active' : '' }}" id="btn-tab-campaign" onclick="switchWorkspaceTab('campaign')">
                <i class="fa fa-list-ul"></i>
                <span>Campaign Queue</span>
                <span class="cw-switch-badge" id="cw-queue-count-badge">{{ $totalQueueCount ?? count($campaignLeads ?? []) }}</span>
            </button>
            <button type="button" class="cw-switch-btn {{ $activeTab === 'manual' ? 'active' : '' }}" id="btn-tab-manual" onclick="switchWorkspaceTab('manual')">
                <i class="fa fa-phone"></i>
                <span>Manual Dial</span>
            </button>
        </div>
    </div>

    <!-- ====================================================================
         TAB 1: CAMPAIGN QUEUE
         ==================================================================== -->
    <div id="tab-campaign-queue" style="display: {{ $activeTab === 'campaign' ? 'block' : 'none' }};">
        <!-- Queue Topbar -->
        <div class="cw-topbar">
            <div class="cw-topbar-left" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="cw-drawer-trigger-btn" id="btn-toggle-queue-drawer" onclick="openDrawer('queue')" style="height: 38px;">
                    <i class="fa fa-bars-staggered"></i>
                    <span>Next leads</span>
                    <span class="cw-badge-pill" id="drawer-queue-count">{{ (!empty($selectedCampaignId) && isset($campaignLeads)) ? ($totalQueueCount ?? count($campaignLeads)) : 0 }} in queue</span>
                    <i class="fa fa-chevron-down" style="font-size: 11px;"></i>
                </button>

                <!-- Select Campaign Dropdown (Campaign Queue tab ONLY) -->
                <div id="cw-campaign-select-wrap" style="position: relative; display: inline-flex; align-items: center;">
                    <select id="cw-workspace-campaign-select" class="cw-drawer-trigger-btn" onchange="onWorkspaceCampaignChange(this.value)">
                        <option value="">Select Campaign</option>
                        @foreach($allowedCampaigns ?? [] as $camp)
                            <option value="{{ $camp->id }}" {{ (isset($selectedCampaignId) && $selectedCampaignId == $camp->id) ? 'selected' : '' }}>
                                {{ $camp->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="fa fa-chevron-down cw-campaign-select-arrow"></i>
                </div>
            </div>

            <div class="cw-topbar-right">
                <div class="cw-status-indicator">
                    <span class="cw-live-dot" id="cw-status-dot" style="display: none;"></span>
                    <span class="cw-status-text" id="cw-status-text">Ready to call. Press Start call when ready</span>
                </div>
            </div>
        </div>

        <!-- Preserved Hidden Inputs for Selected Campaign & Lead -->
        <input type="hidden" id="current_lead_id" value="{{ !empty($selectedCampaignId) ? ($Lead->id ?? '') : '' }}">
        <input type="hidden" id="current_campaign_id" value="{{ $selectedCampaignId ?? ($Campaign->id ?? '') }}">

        <!-- Main Row of 2 Equal-Height Cards -->
        <div class="cw-main-grid">
            <!-- Left Card: Campaign call -->
            <div class="cw-card" id="cw-campaign-call-card">
                <div>
                    <div class="cw-card-header">
                        <h2 class="cw-card-title"><i class="fa fa-phone-volume" style="color: var(--cw-blue);"></i> Campaign Call</h2>
                        <span class="cw-status-badge-chip green" id="cw-lead-status-chip">{{ !empty($selectedCampaignId) ? ($Lead->status_name ?? 'New Lead') : '—' }}</span>
                    </div>

                    <div class="cw-lead-identity">
                        <div class="cw-lead-id-left">
                            <div class="cw-avatar-circle" id="camp-lead-initials">{{ !empty($selectedCampaignId) ? ($Lead->initials ?? 'LD') : '—' }}</div>
                            <div>
                                <h3 class="cw-lead-name-text" id="camp-lead-name">{{ !empty($selectedCampaignId) ? ($Lead->name ?? 'Lead') : 'Select a campaign' }}</h3>
                                <p class="cw-lead-phone-text" id="camp-lead-phone">{{ !empty($selectedCampaignId) ? ($Lead->phonenumber ?? '—') : '—' }}</p>
                            </div>
                        </div>
                        <div class="cw-call-timer-box" id="cw-campaign-timer">00:00:00</div>
                    </div>

                    <!-- 2-Column Info Grid -->
                    <div class="cw-info-grid-2col">
                        <div class="cw-info-item">
                            <span class="cw-info-label">City</span>
                            <span class="cw-info-val" id="camp-lead-city">{{ !empty($selectedCampaignId) ? ($Lead->city_name ?? 'Hyderabad') : '—' }}</span>
                        </div>
                        <div class="cw-info-item">
                            <span class="cw-info-label">Product</span>
                            <span class="cw-info-val" id="camp-lead-product">{{ !empty($selectedCampaignId) ? ($Lead->product_name ?? 'Home Loan') : '—' }}</span>
                        </div>
                        <div class="cw-info-item">
                            <span class="cw-info-label">Campaign</span>
                            <span class="cw-info-val" id="camp-lead-campaign">{{ !empty($selectedCampaignId) ? ($Campaign->name ?? 'September Campaign') : '—' }}</span>
                        </div>
                        <div class="cw-info-item">
                            <span class="cw-info-label">Lead ID</span>
                            <span class="cw-info-val" id="camp-lead-id">{!! (!empty($selectedCampaignId) && !empty($Lead->id)) ? Helper::leadIdLink($Lead->id) : '—' !!}</span>
                        </div>
                        <div class="cw-info-item">
                            <span class="cw-info-label">Source</span>
                            <span class="cw-info-val" id="camp-lead-source">{{ !empty($selectedCampaignId) ? ($Lead->source_name ?? 'Website') : '—' }}</span>
                        </div>
                        <div class="cw-info-item">
                            <span class="cw-info-label">Calls so far</span>
                            <span class="cw-info-val" id="camp-lead-calls">{{ !empty($selectedCampaignId) ? ($Lead->calls_count ?? 0) : 0 }}</span>
                        </div>
                        <div class="cw-info-item">
                            <span class="cw-info-label">Alternate phone</span>
                            <span class="cw-info-val" id="camp-lead-altphone">{{ !empty($selectedCampaignId) ? ($Lead->phonenumber_2 ?? '—') : '—' }}</span>
                        </div>
                        <div class="cw-info-item">
                            <span class="cw-info-label">Last contacted</span>
                            <span class="cw-info-val" id="camp-lead-lastcall">{{ !empty($selectedCampaignId) ? 'Just now' : '—' }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <!-- Campaign VMNs: Click2Call (left) and WebRTC (right); campaign calls use these numbers.
                         Same lists as Manual Dial (Configuration -> Add Voucher), but selected independently. -->
                    @php
                        $campC2cDefault = (string)(Auth::user()->c2c_default_number ?? '');
                        $campWDefault = (string)(Auth::user()->webrtc_default_number ?? '');
                        $campC2cSel = collect($vmnList ?? [])->first(fn ($r) => $r->virtual_number == $campC2cDefault) ?? collect($vmnList ?? [])->first();
                        $campWSel = collect($wvmnList ?? [])->first(fn ($r) => $r->virtual_number == $campWDefault) ?? collect($wvmnList ?? [])->first();
                    @endphp
                    <div class="cw-vmn-columns cw-camp-vmn-columns">
                        <div id="camp-c2c-vmn-group" class="cw-field-group cw-vmn-col {{ ($c2cEnabled ?? true) ? '' : 'is-channel-off' }}" style="margin-bottom: 0; display: flex;">
                            <label class="cw-label" for="camp_c2c_vmn_select" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Click2Call VMN</label>
                            <select id="camp_c2c_vmn_select" class="cw-select" style="width: 100%;" onchange="onCampVmnChange()" {{ count($vmnList ?? []) ? '' : 'disabled' }}>
                                @forelse($vmnList ?? [] as $row)
                                    <option value="{{ $row->virtual_number }}" data-available="{{ $row->available ? 1 : 0 }}" data-expiry="{{ $row->expiry_label }}" {{ $campC2cSel && $row->virtual_number == $campC2cSel->virtual_number ? 'selected' : '' }}>
                                        {{ $row->display }}{{ $row->available ? '' : ' (Expired)' }}
                                    </option>
                                @empty
                                    <option value="">No Click2Call numbers available</option>
                                @endforelse
                            </select>
                            <div class="cw-vmn-line" id="camp-c2c-status"><span class="cw-vmn-line-dot"></span><span class="cw-vmn-status-text">{{ $campC2cSel ? $campC2cSel->status_label : 'Not configured' }}</span></div>
                        </div>

                        <div id="camp-webrtc-vmn-group" class="cw-field-group cw-vmn-col {{ ($webrtcEnabled ?? true) ? '' : 'is-channel-off' }}" style="margin-bottom: 0; display: flex;">
                            <label class="cw-label" for="camp_webrtc_vmn_select" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">WebRTC VMN</label>
                            <select id="camp_webrtc_vmn_select" class="cw-select" style="width: 100%;" onchange="onCampVmnChange()" {{ count($wvmnList ?? []) ? '' : 'disabled' }}>
                                @forelse($wvmnList ?? [] as $row)
                                    <option value="{{ $row->virtual_number }}" data-available="{{ $row->available ? 1 : 0 }}" data-expiry="{{ $row->expiry_label }}" {{ $campWSel && $row->virtual_number == $campWSel->virtual_number ? 'selected' : '' }}>
                                        {{ $row->display }}{{ $row->available ? '' : ' (Expired)' }}
                                    </option>
                                @empty
                                    <option value="">No WebRTC numbers available</option>
                                @endforelse
                            </select>
                            <div class="cw-vmn-line" id="camp-webrtc-status"><span class="cw-vmn-line-dot"></span><span class="cw-vmn-status-text">{{ $campWSel ? $campWSel->status_label : 'Not configured' }}</span></div>
                        </div>
                    </div>

                    <!-- Row 1: Call Buttons & WhatsApp (Placed directly near lead info for immediate visibility) -->
                    <div class="cw-action-btns-row" id="camp-calling-buttons-container" style="margin-top: 14px; margin-bottom: 10px;">
                        @php
                            // Same rule as Manual Dial: the logged-in user's own channels decide the buttons
                            // (Click2Call and WebRTC independently); each button then checks its own VMN
                            $c2cActive = !empty($selectedCampaignId) && ($c2cEnabled ?? false);
                            $webrtcActive = !empty($selectedCampaignId) && ($webrtcEnabled ?? false);
                        @endphp

                        @if($c2cActive)
                            <button type="button" 
                                    class="cw-btn-call-start" 
                                    id="btn-camp-c2c" 
                                    onclick="placeCampaignCall('click2call')" 
                                    aria-label="Start Click2Call"
                                    style="background: linear-gradient(135deg, var(--cw-green) 0%, var(--cw-green-hover) 100%); padding: 10px 18px; font-size: 13.5px; height: 42px;">
                                <i class="fa fa-phone" id="btn-call-icon-c2c"></i>
                                <span>Click2Call</span>
                            </button>
                        @endif

                        @if($webrtcActive)
                            <button type="button" 
                                    class="cw-btn-call-start" 
                                    id="btn-camp-webrtc" 
                                    onclick="placeCampaignCall('webrtc')" 
                                    aria-label="Start WebRTC"
                                    style="background: linear-gradient(135deg, var(--cw-blue) 0%, var(--cw-blue-hover) 100%); padding: 10px 18px; font-size: 13.5px; height: 42px;">
                                <i class="fa fa-headset" id="btn-call-icon-webrtc"></i>
                                <span>WebRTC Call</span>
                            </button>
                        @endif

                        @if(!$c2cActive && !$webrtcActive)
                            <button type="button" 
                                    class="cw-btn-call-start disabled-type" 
                                    disabled="disabled" 
                                    aria-label="Calling not available"
                                    style="padding: 10px 18px; font-size: 13.5px; height: 42px;">
                                <i class="fa fa-phone-slash"></i>
                                <span>{{ empty($selectedCampaignId) ? 'Select a campaign' : 'Calling not available' }}</span>
                            </button>
                        @endif

                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $Lead->phonenumber ?? '') }}" target="_blank" class="cw-btn-icon-sec" id="btn-camp-whatsapp" style="height: 42px;">
                            <i class="fab fa-whatsapp" style="color: #25D366; font-size: 16px;"></i>
                            <span>WhatsApp</span>
                        </a>
                    </div>

                    <!-- Warning message when calling type or user channel is not configured -->
                    @php
                        $warningMsg = '';
                        $showWarning = false;
                        if (empty($selectedCampaignId)) {
                            $warningMsg = 'Please select a campaign from the header dropdown.';
                            $showWarning = true;
                        } elseif (!$c2cActive && !$webrtcActive) {
                            $warningMsg = 'Calling permission is not available for your account. Ask an admin to enable Click2Call or WebRTC for you.';
                            $showWarning = true;
                        }
                    @endphp
                    <div id="campaign-type-warning" style="font-size: 12px; color: #D97706; margin-top: -4px; font-weight: 600; margin-bottom: 12px; display: {{ $showWarning ? 'block' : 'none' }};">
                        <i class="fa fa-exclamation-triangle" style="margin-right: 4px;"></i>
                        <span id="campaign-type-warning-text">{{ $warningMsg }}</span>
                    </div>

                    <!-- Row 2: Call Options (Smart31 Only & Enabled 1600) -->
                    <div class="cw-call-options-row">
                        <label class="cw-call-option-label" id="camp-opt-smart31-label">
                            <input type="checkbox" id="camp_smart31_toggle" name="smart31_enabled" value="1" onchange="toggleCampSmart31(this.checked)">
                            <span>Smart31 Only</span>
                        </label>
                        <label class="cw-call-option-label" id="camp-opt-1600-label">
                            <input type="checkbox" id="camp_vmn1600_toggle" name="vmn1600_enabled" value="1" onchange="toggleCamp1600(this.checked)">
                            <span>Enabled 1600</span>
                        </label>
                    </div>

                    <!-- Campaign Smart31 VMN Box -->
                    <div id="camp-smart31-vmn-box" style="display:none; margin-bottom:12px;">
                        <div class="cw-field-group" style="margin-bottom:0;">
                            <label class="cw-label" style="color: var(--cw-blue);">
                                <i class="fa fa-shield"></i> <span id="camp-smart31-vmn-title">Smart31 VMN</span>
                            </label>
                            <select name="smart31_vmn" id="camp_smart31_vmn_select" class="cw-select" style="border-color: var(--cw-blue);"></select>
                            <div style="margin-top:5px; font-size:11.5px; display:flex; align-items:center; gap:6px;">
                                <span style="width:7px; height:7px; border-radius:50%; background:var(--cw-green); display:inline-block;"></span>
                                <span style="color:var(--cw-green); font-weight:700;">Smart31 Active</span>
                            </div>
                        </div>
                    </div>

                    <!-- Campaign 1600 VMN Box -->
                    <div id="camp-vmn1600-vmn-box" style="display:none; margin-bottom:12px;">
                        <div class="cw-field-group" style="margin-bottom:0;">
                            <label class="cw-label" style="color:#0284C7;">
                                <i class="fa fa-phone"></i> <span id="camp-vmn1600-vmn-title">1600 VMN</span>
                            </label>
                            <select name="vmn1600_vmn" id="camp_vmn1600_vmn_select" class="cw-select" style="border-color:#0284C7;"></select>
                            <div style="margin-top:5px; font-size:11.5px; display:flex; align-items:center; gap:6px;">
                                <span style="width:7px; height:7px; border-radius:50%; background:var(--cw-green); display:inline-block;"></span>
                                <span style="color:var(--cw-green); font-weight:700;">1600 Active</span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Quick-action Chips -->
                    <div class="cw-chips-row">
                        <button type="button" class="cw-chip-btn" onclick="quickAddNote()">+ Add note</button>
                        <button type="button" class="cw-chip-btn" onclick="quickCreateFollowup()">+ Create follow-up</button>
                        <button type="button" class="cw-chip-btn" onclick="quickAddTag()">+ Add tag</button>
                    </div>
                </div>
            </div>

            <!-- Right Card: Call Outcome -->
            <div class="cw-card">
                <div>
                    <div class="cw-card-header">
                        <h2 class="cw-card-title"><i class="fa fa-clipboard-check" style="color: var(--cw-blue);"></i> Call Outcome</h2>
                        <span class="cw-info-label">Required *</span>
                    </div>

                    <!-- Call Status Chips -->
                    <label class="cw-label">Call Status *</label>
                    <div class="cw-status-chips-wrap" data-target="#camp_status_id" role="group" aria-label="Call status">
{{-- Call Status = the active statuses of the Status page (same order); picks the Status below --}}
@forelse($lgOutcomeStatuses as $st)
<button type="button" class="cw-status-chip-opt" data-status-id="{{ $st->id }}" title="{{ $st->name }}">{{ $st->name }}</button>
@empty
<div class="cw-status-chips-empty">No statuses found</div>
@endforelse
                    </div>

                    <!-- Status Dropdown -->
                    <div class="cw-field-group">
                        <label class="cw-label" for="camp_status_id">Status *</label>
                        {{-- starts empty for every lead: the lead's current status is the badge on the Campaign Call card --}}
                        <select id="camp_status_id" class="cw-select">
                            <option value="">Select Status</option>
                            @foreach($lgOutcomeStatuses as $st)
                                <option value="{{ $st->id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Substatus Dropdown -->
                    <div class="cw-field-group">
                        <label class="cw-label" for="camp_substatus_id">Substatus *</label>
                        <select id="camp_substatus_id" class="cw-select" disabled>
                            <option value="">Select a status first</option>
                        </select>
                    </div>

                    <!-- Feedback Textarea -->
                    <div class="cw-field-group">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label class="cw-label" for="camp_comments">Feedback</label>
                            <span style="font-size: 11px; color: var(--cw-text-sub);" id="camp-comment-counter">0/500</span>
                        </div>
                        <textarea id="camp_comments" class="cw-textarea" rows="2" maxlength="500" placeholder="Add your feedback here..." oninput="updateCommentCount(this)"></textarea>
                    </div>

                    <!-- Follow-up Date & Time -->
                    <div class="cw-field-group">
                        <label class="cw-label" for="camp_followup_date">Follow-up Date & Time</label>
                        <input type="datetime-local" id="camp_followup_date" class="cw-input">
                    </div>
                </div>

                <!-- Action Buttons -->
                {{-- Save keeps the lead; Next lead opens the next one once the outcome is saved (tooltips on the wrappers:
                     a disabled button shows no tooltip) --}}
                <div class="cw-outcome-btns-row" id="cw-camp-outcome-btns">
                    <button type="button" class="cw-btn-skip" id="btn-skip-lead" onclick="skipCampaignLead()">Skip</button>
                    <span class="cw-btn-tip" id="btn-save-outcome-tip" title="Choose the Status first">
                        <button type="button" class="cw-btn-save-next" id="btn-save-outcome" onclick="saveCampaignOutcome()" disabled aria-keyshortcuts="Control+Enter">
                            <i class="fa fa-check" aria-hidden="true"></i>
                            <span>Save</span>
                        </button>
                    </span>
                    <span class="cw-btn-tip" id="btn-next-lead-tip" title="Save the outcome first">
                        <button type="button" class="cw-btn-next" id="btn-next-lead" onclick="goToNextLead()" disabled aria-keyshortcuts="Alt+ArrowRight">
                            <span>Next lead</span>
                            <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </span>
                </div>
            </div>
        </div>

        <!-- Full-Width Card: Campaign Call History -->
        <div class="cw-history-card">
            <div class="cw-card-header">
                <h2 class="cw-card-title">
                    <i class="fa fa-history" style="color: var(--cw-blue);"></i> Campaign Call History
                    <span class="cw-badge-pill">{{ count($callLogs ?? []) }} calls</span>
                </h2>
            </div>

            <div class="cw-table-responsive">
                <table class="cw-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Agent</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Substatus</th>
                            <th>Feedback</th>
                            <th>Recording</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($callLogs) && count($callLogs) > 0)
                            @foreach($callLogs as $log)
                                <tr>
                                    <td>{{ date('d-M-Y H:i', strtotime($log->created_at)) }}</td>
                                    <td>{{ $log->called_by_name ?? 'Agent' }}</td>
                                    <td>
                                        <span class="cw-status-badge-chip green">{{ $log->status_name ?? 'Connected' }}</span>
                                    </td>
                                    <td>{{ \App\Helpers\Helper::formatDuration($log->duration ?? 0) }}</td>
                                    <td>{{ $log->disposition ?? 'Interested' }}</td>
                                    <td>{{ $log->comments ?? 'Lead inquired about home loan rates.' }}</td>
                                    <td>
                                        @php
                                            $hasAudio = !empty($log->recording_url) || !empty($log->recording) || !empty($log->file_path);
                                            $isConnected = ($log->duration ?? 0) > 0 || str_contains(strtolower($log->status_name ?? ''), 'connect');
                                            $audioUrl = !empty($log->recording_url) ? $log->recording_url : route('downloadRecordings', [$log->id, 0]);
                                        @endphp
                                        @if($hasAudio)
                                            <div class="d-flex align-items-center" style="gap: 6px;">
                                                <button type="button" class="btn btn-sm btn-primary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;" onclick="playWorkspaceAudio('{{ $audioUrl }}', this)" title="Play Recording"><i class="fa fa-play" style="font-size: 11px;"></i></button>
                                                <a href="{{ $audioUrl }}" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;" title="Download Recording"><i class="fa fa-download" style="font-size: 11px;"></i></a>
                                            </div>
                                        @elseif($isConnected)
                                            <span class="badge" style="background-color: #FEF3C7; color: #92400E; font-size: 11px; padding: 4px 8px; border-radius: 4px;"><i class="fa fa-spinner fa-spin"></i> Processing...</span>
                                        @else
                                            <span style="color: var(--cw-text-sub); font-size: 12px;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--cw-text-sub); padding: 20px;">
                                    No previous call history found for this lead.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- ====================================================================
         TAB 2: MANUAL DIAL
         ==================================================================== -->
    <div id="tab-manual-dial" style="display: {{ $activeTab === 'manual' ? 'block' : 'none' }};">
        <!-- Manual Dial Topbar -->
        <div class="cw-topbar">
            <div class="cw-topbar-left">
                <button type="button" class="cw-drawer-trigger-btn" onclick="openDrawer('all_leads')">
                    <i class="fa fa-users"></i>
                    <span>Show leads</span>
                    <span class="cw-badge-pill">{{ count($allLeads ?? []) }} total</span>
                    <i class="fa fa-chevron-down" style="font-size: 11px;"></i>
                </button>
            </div>

            <div class="cw-topbar-right">
                <div class="cw-status-indicator">
                    <i class="fa fa-info-circle" style="color: var(--cw-blue);"></i>
                    <span>Pick a lead to fill the number, or type any number to dial.</span>
                </div>
            </div>
        </div>

        <!-- Preserved Hidden Inputs for Selected Lead -->
        <input type="hidden" id="manual_lead_id" value="{{ $Lead->id ?? '' }}">
        <input type="hidden" id="manual_lead_name" value="{{ $Lead->name ?? '' }}">
        <input type="hidden" id="manual_lead_campaign_id" value="{{ $campaignId ?? ($Campaign->id ?? '') }}">

        <!-- Main Row of 2 Equal-Height Cards -->
        <div class="cw-main-grid">
            <!-- Left Card: Make a call -->
            <div class="cw-card cw-make-call-card">
                <div class="cw-card-green-header">
                    <h2 class="cw-card-green-title"><i class="fa fa-phone" style="color: #FFFFFF;"></i> Make a Call</h2>
                    <span class="cw-translucent-pill">Quick Dial</span>
                </div>

                <div class="cw-card-body cw-mc-body">
                    <!-- 1. Phone Number Input -->
                    <div class="cw-field-group cw-mc-phone" style="margin-bottom: 0;">
                        <label class="cw-label" for="mobile" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Phone Number *</label>
                        <div class="cw-phone-input-wrap">
                            <input type="text"
                                   id="mobile"
                                   class="cw-phone-input-large"
                                   placeholder="Enter number..."
                                   value="{{ $Lead->phonenumber ?? '' }}"
                                   maxlength="20"
                                   autocomplete="off"
                                   inputmode="numeric">
                            <button type="button" class="cw-phone-clear-btn" onclick="$('#mobile').val('').trigger('input');" title="Clear" aria-label="Clear">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <!-- Meta row: Detected number type badge -->
                        <div class="cw-phone-meta-row" style="margin-top: 8px;">
                            <div id="cw-detected-type-badge" class="cw-detected-badge badge-empty">
                                <i class="fa fa-circle" style="font-size: 6px;"></i>
                                <span id="cw-detected-type-text">Enter a number</span>
                            </div>
                        </div>
                    </div>

                    <!-- Middle area: the only part that scrolls when the card is short; the card itself never grows -->
                    <div class="cw-mc-scroll">
                    <!-- 2. VMN columns: Click2Call (left) and WebRTC (right), one status line each.
                         Numbers come from Configuration -> Add Voucher (obd_did), filtered per channel and team. -->
                    @php
                        $c2cDefault = (string)(Auth::user()->c2c_default_number ?? '');
                        $wDefault = (string)(Auth::user()->webrtc_default_number ?? '');
                        $c2cSel = collect($vmnList ?? [])->first(fn ($r) => $r->virtual_number == $c2cDefault) ?? collect($vmnList ?? [])->first();
                        $wSel = collect($wvmnList ?? [])->first(fn ($r) => $r->virtual_number == $wDefault) ?? collect($wvmnList ?? [])->first();
                    @endphp
                    <div class="cw-vmn-columns">
                        <!-- Click2Call VMN -->
                        <div id="c2c-vmn-group" class="cw-field-group cw-vmn-col {{ ($c2cEnabled ?? true) ? '' : 'is-channel-off' }}" style="margin-bottom: 0; display: flex;">
                            <label class="cw-label" for="vmn_select" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Click2Call VMN</label>
                            <div class="cw-select-wrap" style="position: relative;">
                                <select id="vmn_select" name="vmn_select" class="cw-select" style="width: 100%;" onchange="onClick2CallVmnChange()" {{ count($vmnList ?? []) ? '' : 'disabled' }}>
                                    @forelse($vmnList ?? [] as $row)
                                        <option value="{{ $row->virtual_number }}" data-type="click2call" data-available="{{ $row->available ? 1 : 0 }}" data-expiry="{{ $row->expiry_label }}" {{ $c2cSel && $row->virtual_number == $c2cSel->virtual_number ? 'selected' : '' }}>
                                            {{ $row->display }}{{ $row->available ? '' : ' (Expired)' }}
                                        </option>
                                    @empty
                                        <option value="">No Click2Call numbers available</option>
                                    @endforelse
                                </select>
                                <input type="hidden" id="vmn" name="vmn" value="{{ $c2cSel->virtual_number ?? '' }}">
                            </div>
                            <div class="cw-vmn-line" id="cw-c2c-status"><span class="cw-vmn-line-dot"></span><span class="cw-vmn-status-text">{{ $c2cSel ? $c2cSel->status_label : 'Not configured' }}</span></div>
                        </div>

                        <!-- WebRTC VMN -->
                        <div id="webrtc-vmn-group" class="cw-field-group cw-vmn-col {{ ($webrtcEnabled ?? true) ? '' : 'is-channel-off' }}" style="margin-bottom: 0; display: flex;">
                            <label class="cw-label" for="webrtc_vmn_select" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">WebRTC VMN</label>
                            <div class="cw-select-wrap" style="position: relative;">
                                <select id="webrtc_vmn_select" name="webrtc_vmn_select" class="cw-select" style="width: 100%;" onchange="onWebRTCVmnChange()" {{ count($wvmnList ?? []) ? '' : 'disabled' }}>
                                    @forelse($wvmnList ?? [] as $row)
                                        <option value="{{ $row->virtual_number }}" data-type="webrtc" data-available="{{ $row->available ? 1 : 0 }}" data-expiry="{{ $row->expiry_label }}" {{ $wSel && $row->virtual_number == $wSel->virtual_number ? 'selected' : '' }}>
                                            {{ $row->display }}{{ $row->available ? '' : ' (Expired)' }}
                                        </option>
                                    @empty
                                        <option value="">No WebRTC numbers available</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="cw-vmn-line" id="cw-webrtc-status"><span class="cw-vmn-line-dot"></span><span class="cw-vmn-status-text">{{ $wSel ? $wSel->status_label : 'Not configured' }}</span></div>
                        </div>
                    </div>

                    <!-- 3. Click2Call options: Smart31 Only / Enabled 1600 (independent) -->
                    <div class="cw-mc-opts" role="group" aria-label="Click2Call calling options">
                        <label class="cw-mc-opt" for="smart31_toggle" id="tile-smart31">
                            <input type="checkbox" id="smart31_toggle" name="smart31_enabled" value="1" onchange="toggleSmart31(this.checked)">
                            <span class="cw-mc-box"><i class="fa fa-check"></i></span>
                            <span class="cw-mc-opt-text">Smart31 Only</span>
                        </label>
                        <label class="cw-mc-opt" for="vmn1600_toggle" id="tile-1600">
                            <input type="checkbox" id="vmn1600_toggle" name="vmn1600_enabled" value="1" onchange="toggle1600(this.checked)">
                            <span class="cw-mc-box"><i class="fa fa-check"></i></span>
                            <span class="cw-mc-opt-text">Enabled 1600</span>
                        </label>
                    </div>

                    <!-- 4. Smart31 VMN: shown only while "Smart31 Only" is checked; the call then uses this number -->
                    <div id="cw-smart31-block" class="cw-mc-smart31" style="display: none;">
                        <label class="cw-label" for="smart31_vmn_select" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;"><i class="fa fa-shield" style="color: var(--cw-blue);"></i> Smart31 VMN</label>
                        <select id="smart31_vmn_select" class="cw-select" style="width: 100%;" onchange="lgPaintSmart31()" disabled>
                            <option value="">Loading...</option>
                        </select>
                        <div class="cw-vmn-line is-none" id="cw-smart31-status"><span class="cw-vmn-line-dot"></span><span class="cw-vmn-status-text">Loading...</span></div>
                        <div class="cw-mc-help" id="cw-smart31-help" style="display: none;">Uncheck Smart31 Only to call from your Click2Call VMN.</div>
                    </div>

                    @php
                        $callingHoursStatus = \App\Services\CallingRestrictionService::getCallingHoursUiStatus();
                    @endphp

                    <!-- 5. Call footer directly under the options: notices, live call status, centered call button(s) -->
                    <div class="cw-mc-foot">
                        @if(!empty($callingHoursStatus['is_closed']))
                        <div id="cw-calling-hours-notice" class="alert alert-warning py-2 px-3 mb-2 d-flex align-items-center" style="border-radius: 8px; font-size: 13px; font-weight: 600; background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; width: 100%; margin-bottom: 0 !important;">
                            <i class="fa fa-clock me-2" style="color: #D97706; margin-right: 8px;"></i>
                            <span>{{ $callingHoursStatus['notice'] }}</span>
                        </div>
                        @endif

                        <div id="calling-option-hint" class="cw-calling-option-hint" style="width: 100%; display: none;"></div>

                        <!-- Live Call Status Container (Ringing -> Green Connected with timer -> Failed/Busy) -->
                        <div id="cw-live-status-container" class="cw-live-status-container" style="display: none;">
                            <div class="cw-live-status-left" style="display: flex; align-items: center; gap: 8px;">
                                <span id="cw-live-status-icon"><i class="fa fa-phone-volume"></i></span>
                                <span id="cw-live-status-text" style="font-size: 13px; font-weight: 700;">Ringing...</span>
                                <span id="cw-live-status-timer" class="cw-live-timer" style="display: none;">00:00</span>
                            </div>
                            <div class="cw-live-status-right">
                                <button type="button" id="btn-cw-hangup" class="cw-btn-hangup" onclick="hangupActiveCall()" style="display: none;">
                                    <i class="fa fa-phone-slash"></i> <span>End Call</span>
                                </button>
                            </div>
                        </div>

                        <!-- Call buttons: centered, only the channel(s) that apply to the number -->
                        <div class="cw-calling-buttons-row cw-mc-buttons" id="cw-calling-buttons-container">
                            <button type="button" id="btn-call-click2call" class="cw-compact-call-btn c2c-btn" onclick="startClick2Call()" disabled {{ !empty($callingHoursStatus['is_closed']) ? 'title="Calling is closed"' : '' }}>
                                <i class="fa fa-phone-alt"></i>
                                <span>Click2Call</span>
                            </button>
                            <button type="button" id="btn-call-webrtc" class="cw-compact-call-btn webrtc-btn" onclick="startWebRTCCall()" style="display: none;" disabled {{ !empty($callingHoursStatus['is_closed']) ? 'title="Calling is closed"' : '' }}>
                                <i class="fa fa-headset"></i>
                                <span>WebRTC Call</span>
                            </button>
                        </div>
                    </div>

                    <!-- 6. Recent Numbers Chips Row -->
                    @php
                        $recentList = $recentNumbers ?? [];
                        if (empty($recentList)) {
                            $recentList = \Illuminate\Support\Facades\DB::table('dialer_logs')
                                ->whereNotNull('phone_number')
                                ->where('phone_number', '!=', '')
                                ->where('created_by', Auth::id())
                                ->orderBy('id', 'desc')
                                ->pluck('phone_number')
                                ->unique()
                                ->take(3)
                                ->values();
                            if (count($recentList) === 0) {
                                $recentList = \Illuminate\Support\Facades\DB::table('dialer_logs')
                                    ->whereNotNull('phone_number')
                                    ->where('phone_number', '!=', '')
                                    ->orderBy('id', 'desc')
                                    ->pluck('phone_number')
                                    ->unique()
                                    ->take(3)
                                    ->values();
                            }
                        }
                    @endphp
                    @if(isset($recentList) && count($recentList) > 0)
                    <div class="cw-recent-numbers-section">
                        <label class="cw-label" style="font-size: 12px; font-weight: 700; margin-bottom: 0; color: var(--cw-text-sub); display: flex; align-items: center; gap: 6px;">
                            <i class="fa fa-history" style="font-size: 11px;"></i> Recent Numbers
                        </label>
                        <div class="cw-recent-chips-wrap">
                            @foreach(collect($recentList)->take(3) as $rNum)
                                <button type="button" class="cw-recent-chip" onclick="fillRecentNumber('{{ $rNum }}')" title="Dial {{ $rNum }}">
                                    <i class="fa fa-phone"></i>
                                    <span>{{ $rNum }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    </div>
                </div>
            </div>

            <!-- Remote Audio for WebRTC SIP Client -->
            <audio id="remoteAudio" autoplay style="display:none;"></audio>

            <!-- Right Card: Manual Call Outcome -->
            <div class="cw-card cw-outcome-card">
                <div class="cw-card-header">
                    <h2 class="cw-card-title"><i class="fa fa-comment-dots" style="color: var(--cw-blue);"></i> Call Outcome</h2>
                    <span class="cw-info-label">Required *</span>
                </div>

                <div class="cw-card-body">
                    <!-- Call Status Chips -->
                    <div class="cw-field-group" style="margin-bottom: 0;">
                        <label class="cw-label" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Call Status *</label>
                        <div class="cw-status-chips-wrap" data-target="#manual_status_id" role="group" aria-label="Call status">
{{-- Call Status = the active statuses of the Status page (same order); picks the Status below --}}
@forelse($lgOutcomeStatuses as $st)
<button type="button" class="cw-status-chip-opt" data-status-id="{{ $st->id }}" title="{{ $st->name }}">{{ $st->name }}</button>
@empty
<div class="cw-status-chips-empty">No statuses found</div>
@endforelse
                        </div>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="cw-field-group" style="margin-bottom: 0;">
                        <label class="cw-label" for="manual_status_id" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Status *</label>
                        <select id="manual_status_id" class="cw-select" onchange="lgFollowupRequired()" style="width: 100%; height: 42px; border-radius: 8px; font-size: 13.5px;">
                            <option value="">Select Status</option>
                            @foreach($lgOutcomeStatuses as $st)
                                {{-- data-followup: "Requires follow-up date" in Disposition Settings --}}
                                <option value="{{ $st->id }}" data-followup="{{ (int)$st->lead_pool === 1 ? 1 : 0 }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Substatus Dropdown -->
                    <div class="cw-field-group" style="margin-bottom: 0;">
                        <label class="cw-label" for="manual_substatus_id" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Substatus *</label>
                        <select id="manual_substatus_id" class="cw-select" style="width: 100%; height: 42px; border-radius: 8px; font-size: 13.5px;" disabled>
                            <option value="">Select a status first</option>
                        </select>
                    </div>

                    <!-- Follow-up Date & Time (the campaign comes from the call / lead itself, so there is no Campaign field) -->
                    <div class="cw-field-group" style="margin-bottom: 0;">
                        <label class="cw-label" for="manual_followup_date" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Follow-up Date & Time<span id="manual_followup_req" style="display:none;"> *</span></label>
                        <input type="datetime-local" id="manual_followup_date" class="cw-input" style="width: 100%; height: 42px; border-radius: 8px; font-size: 13.5px;">
                    </div>

                    <!-- Feedback Textarea -->
                    <div class="cw-field-group" style="margin-bottom: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="cw-label" for="manual_comments" style="font-size: 13px; font-weight: 700; margin-bottom: 0;">Feedback</label>
                            <span style="font-size: 11px; color: var(--cw-text-sub);" id="manual-comment-counter">0/500</span>
                        </div>
                        <textarea id="manual_comments" class="cw-textarea" rows="2" maxlength="500" placeholder="Add your feedback here..." oninput="updateCommentCount(this)" style="border-radius: 8px; font-size: 13.5px;"></textarea>
                    </div>
                </div>

                <!-- Action Button pinned to bottom -->
                <div class="cw-outcome-btns-row" style="margin-top: auto; padding-top: 14px; display: flex; justify-content: flex-end;">
                    <button type="button" id="btn-save-manual-outcome" class="cw-btn-save-next" onclick="saveManualOutcome()">
                        <span id="save-btn-text">Save</span>
                        <i id="save-btn-icon" class="fa fa-check"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Row of 3 Equal Cards Below Main Row -->
        <div class="cw-grid-3col">
            <!-- Card 1: Quick actions -->
            <div class="cw-card" style="min-height: auto;">
                <div class="cw-card-header">
                    <h3 class="cw-card-title"><i class="fa fa-bolt" style="color: var(--cw-blue);"></i> Quick Actions</h3>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <button type="button" id="qa-btn-note" class="cw-btn-icon-sec" onclick="openQuickNoteModal()" title="Please enter or select a phone number first" disabled><i class="fa fa-sticky-note"></i> Add Note</button>
                    <button type="button" id="qa-btn-followup" class="cw-btn-icon-sec" onclick="openQuickFollowupModal()" title="Please enter or select a phone number first" disabled><i class="fa fa-calendar-plus"></i> Follow-up</button>
                    <button type="button" id="qa-btn-tag" class="cw-btn-icon-sec" onclick="openQuickTagModal()" title="Please enter or select a phone number first" disabled><i class="fa fa-tag"></i> Add Tag</button>
                    <button type="button" id="qa-btn-history" class="cw-btn-icon-sec" onclick="openQuickHistoryModal()" title="Please enter or select a phone number first" disabled><i class="fa fa-history"></i> History</button>
                </div>
            </div>

            <!-- Card 2: Today's performance -->
            <div class="cw-card" style="min-height: auto;">
                <div class="cw-card-header">
                    <h3 class="cw-card-title"><i class="fa fa-chart-line" style="color: var(--cw-green);"></i> Today's Performance</h3>
                    <span class="cw-badge-pill" id="perf-target-badge" style="background: var(--cw-green-light); color: var(--cw-green);">Target: 50</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12.5px; margin-bottom: 8px;">
                    <div><span class="cw-info-label">Calls Made</span><strong id="perf-calls-made">0</strong></div>
                    <div><span class="cw-info-label">Connected</span><strong id="perf-connected">0</strong></div>
                    <div><span class="cw-info-label">Avg Talk Time</span><strong id="perf-avg-talk">00:00</strong></div>
                    <div><span class="cw-info-label">Follow-ups</span><strong id="perf-followups">0</strong></div>
                </div>
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: var(--cw-text-sub);">
                        <span>Daily Target</span>
                        <span id="perf-target-text">0% Completed</span>
                    </div>
                    <div class="cw-perf-progress-bar">
                        <div class="cw-perf-progress-fill" id="perf-progress-fill" style="width: 0%;"></div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Recent activity -->
            <div class="cw-card" style="min-height: auto;">
                <div class="cw-card-header">
                    <h3 class="cw-card-title"><i class="fa fa-clock" style="color: var(--cw-blue);"></i> Recent Activity</h3>
                    <a href="{{ route('callLogs') }}" style="font-size: 12px; font-weight: 700; color: var(--cw-blue); text-decoration: none;">View all →</a>
                </div>
                <div id="cw-recent-activity-list" style="font-size: 12.5px;">
                    <div class="text-center py-2 text-muted" style="font-size: 12px;">Loading recent activity...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================
     SLIDE-IN RIGHT SIDE DRAWER FOR LEADS
     ==================================================================== -->
<div class="cw-drawer-scrim" id="cw-drawer-scrim" onclick="closeDrawer()"></div>

<div class="cw-drawer" id="cw-side-drawer" role="dialog" aria-modal="true" aria-expanded="false">
    <div class="cw-drawer-header">
        <h3 class="cw-drawer-title" id="cw-drawer-title-text">Next leads in queue</h3>
        <button type="button" class="cw-drawer-close" onclick="closeDrawer()" aria-label="Close drawer">×</button>
    </div>

    <div class="cw-drawer-body">
        <div class="cw-drawer-search-box">
            <input type="search" id="cw-drawer-search" class="cw-input" placeholder="Search by name or phone..." oninput="filterDrawerLeads(this.value)" autocomplete="off">
        </div>

        <div class="cw-drawer-empty-state" id="cw-drawer-no-results" style="display: none; padding: 32px 16px; text-align: center; color: var(--cw-text-sub);">
            <i class="fa fa-search" style="font-size: 26px; margin-bottom: 10px; color: #94A3B8; display: block;"></i>
            <p style="margin: 0; font-weight: 700; font-size: 14px; color: var(--cw-navy);">No leads found</p>
            <p style="margin: 4px 0 0 0; font-size: 12px;">No lead in this list matches that name or number.</p>
        </div>

        {{-- Two lists, one per tab (lgSyncDrawerList shows the active tab's): the Campaign Queue's leads (rows load
             the lead into the Campaign Call card) and All Leads for Manual Dial (rows fill the number to dial) --}}
        <div class="cw-drawer-lead-list" id="cw-drawer-lead-list" data-campaign-id="{{ $activeTab === 'campaign' ? ($selectedCampaignId ?? '') : '' }}" style="display: {{ $activeTab === 'campaign' ? 'block' : 'none' }};">
            <!-- Queue items populated via Blade or JS -->
            @if($activeTab === 'campaign')
                @if(empty($selectedCampaignId))
                    <div class="cw-drawer-empty-state" id="cw-drawer-empty-state" style="padding: 40px 16px; text-align: center; color: var(--cw-text-sub);">
                        <i class="fa fa-layer-group" style="font-size: 32px; margin-bottom: 12px; color: #94A3B8; display: block;"></i>
                        <p style="margin: 0; font-weight: 700; font-size: 14px; color: var(--cw-navy);">Select a campaign to see its leads</p>
                        <p style="margin: 4px 0 0 0; font-size: 12px;">Choose a campaign from the header dropdown above to view its queue.</p>
                    </div>
                @elseif(isset($campaignLeads))
                    @foreach($campaignLeads as $cLead)
                        <div class="cw-drawer-lead-row {{ $cLead->id == ($Lead->id ?? 0) ? 'active' : '' }}" 
                             data-lead-id="{{ $cLead->id }}"
                             data-name="{{ strtolower($cLead->name) }}" 
                             data-phone="{{ $cLead->phonenumber }}"
                             onclick="selectQueueLead({{ $cLead->id }}, {{ $cLead->campaign_id }})">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="cw-avatar-circle" style="width: 36px; height: 36px; font-size: 13px; background: {{ $cLead->badge_color ?? 'var(--cw-blue)' }};">
                                    {{ $cLead->initials ?? 'LD' }}
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 13.5px; color: var(--cw-navy);">{{ $cLead->name }}</strong>
                                    <small style="color: var(--cw-text-sub); font-size: 12px;">{{ $cLead->phonenumber }}</small>
                                </div>
                            </div>
                            <span class="cw-status-badge-chip" style="background: #F1F5F9; color: {{ $cLead->badge_color ?? 'var(--cw-navy)' }};">
                                {{ $cLead->status_name ?? 'New' }}
                            </span>
                        </div>
                    @endforeach
                @endif
            @endif
        </div>
        <div class="cw-drawer-lead-list" id="cw-drawer-all-list" style="display: {{ $activeTab === 'campaign' ? 'none' : 'block' }};">
            @if(isset($allLeads))
                    @foreach($allLeads as $aLead)
                        <div class="cw-drawer-lead-row" 
                             data-name="{{ strtolower($aLead->name) }}" 
                             data-phone="{{ $aLead->phonenumber }}"
                             onclick="selectManualLead('{{ $aLead->name }}', '{{ $aLead->phonenumber }}', '{{ $aLead->city_name }}', '{{ $aLead->id }}', '{{ $aLead->email }}')">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="cw-avatar-circle" style="width: 36px; height: 36px; font-size: 13px; background: var(--cw-blue);">
                                    {{ $aLead->initials ?? 'LD' }}
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 13.5px; color: var(--cw-navy);">{{ $aLead->name }}</strong>
                                    <small style="color: var(--cw-text-sub); font-size: 12px;">{{ $aLead->phonenumber }}</small>
                                </div>
                            </div>
                            <span class="cw-status-badge-chip blue">{{ $aLead->status_name ?? 'New' }}</span>
                        </div>
                    @endforeach
            @endif
        </div>
    </div>
</div>

{{-- "Switch lead without saving the call outcome?" (Campaign Queue: a call was made, its outcome is not saved yet) --}}
<div class="cw-switch-confirm" id="cw-switch-confirm" role="alertdialog" aria-modal="true" aria-labelledby="cw-switch-confirm-text" hidden>
    <div class="cw-switch-confirm-card">
        <p id="cw-switch-confirm-text">Switch lead without saving the call outcome?</p>
        <div class="cw-switch-confirm-btns">
            <button type="button" class="cw-btn-skip" id="cw-switch-stay">Stay</button>
            <button type="button" class="cw-btn-save-next" id="cw-switch-go">Switch</button>
        </div>
    </div>
</div>

<!-- Preserved Hidden Inputs for Forms & State -->
<input type="hidden" id="call_type" value="webrtc">
<input type="hidden" id="uuid" value="">
<input type="hidden" id="id" value="">
<input type="hidden" id="is_campaign" value="0">

<!-- Quick Action Modals -->
<div id="qa-modal-note" class="cw-quick-modal" style="display: none;">
    <div class="cw-quick-modal-backdrop" onclick="closeQuickModal('note')"></div>
    <div class="cw-quick-modal-box">
        <div class="cw-quick-modal-header">
            <h4><i class="fa fa-sticky-note" style="color: var(--cw-blue);"></i> Add Note</h4>
            <button type="button" class="cw-quick-modal-close" onclick="closeQuickModal('note')">&times;</button>
        </div>
        <div class="cw-quick-modal-body">
            <label class="cw-label" for="qa-note-text" style="font-weight: 700; margin-bottom: 6px;">Note / Remarks</label>
            <textarea id="qa-note-text" class="cw-textarea" rows="3" placeholder="Enter note about this lead or call..."></textarea>
        </div>
        <div class="cw-quick-modal-footer">
            <button type="button" class="cw-btn-sec-sm" onclick="closeQuickModal('note')">Cancel</button>
            <button type="button" class="cw-btn-primary-sm" id="btn-submit-qa-note" onclick="submitQuickNote()">Save Note</button>
        </div>
    </div>
</div>

<div id="qa-modal-followup" class="cw-quick-modal" style="display: none;">
    <div class="cw-quick-modal-backdrop" onclick="closeQuickModal('followup')"></div>
    <div class="cw-quick-modal-box">
        <div class="cw-quick-modal-header">
            <h4><i class="fa fa-calendar-plus" style="color: var(--cw-blue);"></i> Schedule Follow-up</h4>
            <button type="button" class="cw-quick-modal-close" onclick="closeQuickModal('followup')">&times;</button>
        </div>
        <div class="cw-quick-modal-body">
            <div style="margin-bottom: 12px;">
                <label class="cw-label" for="qa-followup-datetime" style="font-weight: 700; margin-bottom: 6px;">Date & Time *</label>
                <input type="datetime-local" id="qa-followup-datetime" class="cw-input" style="width: 100%; height: 40px; border-radius: 8px;">
            </div>
            <div>
                <label class="cw-label" for="qa-followup-remarks" style="font-weight: 700; margin-bottom: 6px;">Remarks (optional)</label>
                <textarea id="qa-followup-remarks" class="cw-textarea" rows="2" placeholder="Reason for follow-up..."></textarea>
            </div>
        </div>
        <div class="cw-quick-modal-footer">
            <button type="button" class="cw-btn-sec-sm" onclick="closeQuickModal('followup')">Cancel</button>
            <button type="button" class="cw-btn-primary-sm" id="btn-submit-qa-followup" onclick="submitQuickFollowup()">Save Follow-up</button>
        </div>
    </div>
</div>

<div id="qa-modal-tag" class="cw-quick-modal" style="display: none;">
    <div class="cw-quick-modal-backdrop" onclick="closeQuickModal('tag')"></div>
    <div class="cw-quick-modal-box">
        <div class="cw-quick-modal-header">
            <h4><i class="fa fa-tag" style="color: var(--cw-blue);"></i> Add Tag</h4>
            <button type="button" class="cw-quick-modal-close" onclick="closeQuickModal('tag')">&times;</button>
        </div>
        <div class="cw-quick-modal-body">
            <label class="cw-label" for="qa-tag-input" style="font-weight: 700; margin-bottom: 6px;">Tag Name</label>
            <input type="text" id="qa-tag-input" class="cw-input" placeholder="e.g. Interested, High Priority, Callback..." style="width: 100%; height: 40px; border-radius: 8px; margin-bottom: 10px;">
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <span class="cw-tag-suggestion" onclick="$('#qa-tag-input').val('High Priority')">+ High Priority</span>
                <span class="cw-tag-suggestion" onclick="$('#qa-tag-input').val('Interested')">+ Interested</span>
                <span class="cw-tag-suggestion" onclick="$('#qa-tag-input').val('Follow-up')">+ Follow-up</span>
                <span class="cw-tag-suggestion" onclick="$('#qa-tag-input').val('Callback')">+ Callback</span>
            </div>
        </div>
        <div class="cw-quick-modal-footer">
            <button type="button" class="cw-btn-sec-sm" onclick="closeQuickModal('tag')">Cancel</button>
            <button type="button" class="cw-btn-primary-sm" id="btn-submit-qa-tag" onclick="submitQuickTag()">Add Tag</button>
        </div>
    </div>
</div>

<div id="qa-modal-history" class="cw-quick-modal" style="display: none;">
    <div class="cw-quick-modal-backdrop" onclick="closeQuickModal('history')"></div>
    <div class="cw-quick-modal-box" style="max-width: 600px;">
        <div class="cw-quick-modal-header">
            <h4><i class="fa fa-history" style="color: var(--cw-blue);"></i> Call History</h4>
            <button type="button" class="cw-quick-modal-close" onclick="closeQuickModal('history')">&times;</button>
        </div>
        <div class="cw-quick-modal-body" style="max-height: 400px; overflow-y: auto;">
            <div id="qa-history-table-container">
                <div class="text-center py-3 text-muted">Loading history...</div>
            </div>
        </div>
        <div class="cw-quick-modal-footer">
            <button type="button" class="cw-btn-sec-sm" onclick="closeQuickModal('history')">Close</button>
        </div>
    </div>
</div>

@push('after-scripts')
<script>
/* ==========================================================================
   Calling Workspace Client State & Logic
   ========================================================================== */
window.callingHoursClosed = {{ !empty($callingHoursStatus['is_closed']) ? 'true' : 'false' }};
window.callingHoursNotice = "{{ $callingHoursStatus['notice'] ?? '' }}";
let activeWorkspaceTab = "{{ $activeTab ?? 'campaign' }}";
let activeCallOption = 'webrtc';
let isOnCall = false;
let campaignTimerInterval = null;
let timerSeconds = 0;

$(document.body).on('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDrawer();
    }
});

/* ── Tab Switching ────────────────────────────────────────── */
function switchWorkspaceTab(tab) {
    if (isOnCall) {
        // Prevent interrupting call, just switch view
    }
    activeWorkspaceTab = tab;
    
    $('.cw-switch-btn').removeClass('active');
    $('#btn-tab-' + tab).addClass('active');

    if (tab === 'campaign') {
        $('#tab-manual-dial').hide();
        $('#tab-campaign-queue').show();
        $('#cw-drawer-title-text').text('Next leads in queue');
        // the lead shown in the Campaign Call card (not the one the page first opened with)
        window.history.pushState(null, '', (currentCampaignId && currentLeadId)
            ? '/admin/campaigns/' + currentCampaignId + '/lead/' + currentLeadId + '/calling'
            : "{{ route('leadCallingView', ['campaignId' => $campaignId ?? 1, 'leadId' => $leadId ?? ($Lead->id ?? 1)]) }}");
    } else {
        $('#tab-campaign-queue').hide();
        $('#tab-manual-dial').show();
        $('#cw-drawer-title-text').text('All Leads');
        window.history.pushState(null, '', "{{ route('dialer') }}");
    }
    lgSyncDrawerList();
    if (window.lgAutoDialer) window.lgAutoDialer.onTabChange(tab);
}

/**
 * The side panel shows the active tab's list: Campaign Queue -> the selected campaign's queue (rows load the lead
 * into the Campaign Call card); Manual Dial -> All Leads (rows fill the number to dial). The queue list is fetched
 * for the selected campaign when it was not built for it yet (e.g. the page opened on Manual Dial).
 */
function lgSyncDrawerList() {
    var campaignTab = activeWorkspaceTab === 'campaign';
    $('#cw-drawer-lead-list').toggle(campaignTab);
    $('#cw-drawer-all-list').toggle(!campaignTab);
    if (campaignTab && currentCampaignId && String($('#cw-drawer-lead-list').attr('data-campaign-id') || '') !== String(currentCampaignId)) {
        lgRefreshQueueList(currentCampaignId);
    }
    filterDrawerLeads($('#cw-drawer-search').val());
}

/** Rebuild the "Next leads in queue" rows of a campaign (does not change the lead in the Campaign Call card). */
function lgRefreshQueueList(campaignId) {
    $.ajax({ url: '/admin/campaigns/' + campaignId + '/queue-leads', type: 'GET', dataType: 'json' }).done(function (res) {
        if (!res || !res.success || String(campaignId) !== String(currentCampaignId)) return;
        campaignQueue = res.leads || [];
        var idx = campaignQueue.findIndex(function (l) { return l.id == currentLeadId; });
        currentQueueIndex = idx === -1 ? 0 : idx;
        lgRenderQueueRows(campaignQueue, campaignId);
        $('#drawer-queue-count').text((res.total_count || 0) + ' in queue');
        $('#cw-queue-count-badge').text(res.total_count || 0);
        filterDrawerLeads($('#cw-drawer-search').val());
    });
}

function lgEsc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/** The queue rows (same markup as the server-rendered ones); the lead in the Campaign Call card is highlighted. */
function lgRenderQueueRows(leads, campaignId) {
    var $list = $('#cw-drawer-lead-list').empty().attr('data-campaign-id', campaignId);
    if (!leads.length) {
        $list.html(`
            <div class="cw-drawer-empty-state" style="padding: 40px 16px; text-align: center; color: var(--cw-text-sub);">
                <i class="fa fa-inbox" style="font-size: 32px; margin-bottom: 12px; color: #94A3B8; display: block;"></i>
                <p style="margin: 0; font-weight: 700; font-size: 14px; color: var(--cw-navy);">No leads in queue</p>
                <p style="margin: 4px 0 0 0; font-size: 12px;">All leads in this campaign have been worked or no leads match your assignment.</p>
            </div>
        `);
        return;
    }
    leads.forEach(function (cLead) {
        var activeClass = cLead.id == currentLeadId ? 'active' : '';
        $list.append(`
            <div class="cw-drawer-lead-row ${activeClass}"
                 data-lead-id="${cLead.id}"
                 data-name="${lgEsc((cLead.name || '').toLowerCase())}"
                 data-phone="${lgEsc(cLead.phonenumber)}"
                 onclick="selectQueueLead(${parseInt(cLead.id, 10)}, ${parseInt(campaignId, 10)})">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="cw-avatar-circle" style="width: 36px; height: 36px; font-size: 13px; background: ${lgEsc(cLead.badge_color || 'var(--cw-blue)')};">
                        ${lgEsc(cLead.initials || 'LD')}
                    </div>
                    <div>
                        <strong style="display: block; font-size: 13.5px; color: var(--cw-navy);">${lgEsc(cLead.name)}</strong>
                        <small style="color: var(--cw-text-sub); font-size: 12px;">${lgEsc(cLead.phonenumber)}</small>
                    </div>
                </div>
                <span class="cw-status-badge-chip" style="background: #F1F5F9; color: ${lgEsc(cLead.badge_color || 'var(--cw-navy)')};">
                    ${lgEsc(cLead.status_name || 'New')}
                </span>
            </div>
        `);
    });
}

/* ── Slide-in Drawer Controls ────────────────────────────── */
function openDrawer(mode) {
    lgSyncDrawerList();
    $('#cw-drawer-scrim').addClass('open');
    $('#cw-side-drawer').addClass('open').attr('aria-expanded', 'true');
    $('#cw-drawer-search').focus();
    filterDrawerLeads($('#cw-drawer-search').val());
    if (mode === 'queue') {
        $('#cw-drawer-title-text').text('Next leads in queue');
    } else {
        $('#cw-drawer-title-text').text('All Leads');
    }
}

function closeDrawer() {
    $('#cw-drawer-scrim').removeClass('open');
    $('#cw-side-drawer').removeClass('open').attr('aria-expanded', 'false');
}

/**
 * Next leads search: partial name (any case) or partial phone number. Only matching leads stay in the list,
 * in their queue order, so they sit at the top; an empty search shows the whole queue again.
 * (Reads the raw attributes: jQuery .data() turns a phone like "9154936351" into a number, which has no indexOf.)
 */
function filterDrawerLeads(query) {
    var q = String(query || '').toLowerCase().trim();
    var qDigits = q.replace(/[^0-9]/g, '');
    var $rows = $(activeWorkspaceTab === 'campaign' ? '#cw-drawer-lead-list' : '#cw-drawer-all-list').find('.cw-drawer-lead-row');
    var shown = 0;
    $rows.each(function() {
        var name = String($(this).attr('data-name') || '').toLowerCase();
        var phone = String($(this).attr('data-phone') || '').replace(/[^0-9]/g, '');
        var match = q === '' || name.indexOf(q) !== -1 || (qDigits !== '' && phone.indexOf(qDigits) !== -1);
        $(this).toggle(match);
        if (match) shown++;
    });
    $('#cw-drawer-no-results').toggle(q !== '' && $rows.length > 0 && shown === 0);
}

function selectManualLead(name, phone, city, id, email) {
    $('#mobile').val(phone).trigger('input');
    $('#manual_lead_id').val(id);
    $('#manual_lead_name').val(name);
    $('#current_lead_id').val(id);

    closeDrawer();
    toastr.info('Lead selected: ' + name);
}

/* ── Outcome Chips Selection ──────────────────────────────── */

function updateCommentCount(textarea) {
    var len = $(textarea).val().length;
    var targetId = (textarea.id === 'camp_comments') ? '#camp-comment-counter' : '#manual-comment-counter';
    $(targetId).text(len + '/500');
}

/* ── In-Memory Queue State & Campaign Calling Logic ──────── */
window._currentCampaignCallingType = "{{ $campaignCallingType ?? '' }}";

let campaignQueue = [
    @if(!empty($selectedCampaignId) && isset($campaignLeads) && count($campaignLeads) > 0)
        @foreach($campaignLeads as $cLead)
            { id: {{ $cLead->id }}, name: "{!! addslashes($cLead->name) !!}", mobile: "{{ $cLead->phonenumber }}" },
        @endforeach
    @endif
];
let currentQueueIndex = 0;
let currentLeadId = {{ !empty($selectedCampaignId) ? ($Lead->id ?? 0) : 0 }};
let currentCampaignId = {{ !empty($selectedCampaignId) ? ($selectedCampaignId ?? ($Campaign->id ?? 0)) : 0 }};

if (campaignQueue.length > 0 && currentLeadId) {
    let idx = campaignQueue.findIndex(l => l.id == currentLeadId);
    if (idx !== -1) currentQueueIndex = idx;
}

function onWorkspaceCampaignChange(campaignId) {
    if (!campaignId) {
        currentCampaignId = 0;
        currentLeadId = 0;
        campaignQueue = [];
        currentQueueIndex = 0;
        window._currentCampaignCallingType = '';
        $('#current_campaign_id').val('');
        $('#current_lead_id').val('');

        $('#drawer-queue-count').text('0 in queue');
        $('#cw-queue-count-badge').text('0');

        $('#cw-drawer-lead-list').html(`
            <div class="cw-drawer-empty-state" id="cw-drawer-empty-state" style="padding: 40px 16px; text-align: center; color: var(--cw-text-sub);">
                <i class="fa fa-layer-group" style="font-size: 32px; margin-bottom: 12px; color: #94A3B8; display: block;"></i>
                <p style="margin: 0; font-weight: 700; font-size: 14px; color: var(--cw-navy);">Select a campaign to see its leads</p>
                <p style="margin: 4px 0 0 0; font-size: 12px;">Choose a campaign from the header dropdown above to view its queue.</p>
            </div>
        `);

        renderEmptyCampaignLeadCard();
        renderCampaignCallingButtons('');

        $.get('/admin/campaigns/0/queue-leads');
        return;
    }

    currentCampaignId = parseInt(campaignId);
    $('#current_campaign_id').val(campaignId);

    $.ajax({
        url: '/admin/campaigns/' + campaignId + '/queue-leads',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                campaignQueue = res.leads || [];
                currentQueueIndex = 0;
                window._currentCampaignCallingType = res.calling_type || '';

                var count = res.total_count || 0;
                $('#drawer-queue-count').text(count + ' in queue');
                $('#cw-queue-count-badge').text(count);

                lgRenderQueueRows(campaignQueue, campaignId);
                if (campaignQueue.length === 0) {
                    renderEmptyCampaignLeadCard();
                    renderCampaignCallingButtons('');
                } else if (res.first_lead_id) {
                    loadLead(res.first_lead_id, null, { campaignId: campaignId });
                }
            } else {
                toastr.error(res.message || 'Failed to load campaign leads.');
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error loading campaign leads';
            toastr.error(msg);
        }
    });
}

function renderEmptyCampaignLeadCard() {
    $('#camp-lead-name').text('Select a campaign');
    $('#camp-lead-phone').text('—');
    $('#camp-lead-initials').text('—');
    $('#cw-lead-status-chip').removeClass('blue red').addClass('green').text('—');
    $('#camp-lead-city, #camp-info-city').text('—');
    $('#camp-lead-product, #camp-info-product').text('—');
    $('#camp-lead-campaign, #camp-info-campaign').text('—');
    $('#camp-lead-id, #camp-info-lead-id').text('—');
    $('#camp-lead-source, #camp-info-source').text('—');
    $('#camp-lead-calls, #camp-info-calls').text('0');
    $('#camp-lead-altphone, #camp-info-alt-phone').text('—');
    $('#camp-lead-lastcall, #camp-info-last-contacted').text('—');
    $('#btn-camp-whatsapp').attr('href', 'https://wa.me/');
    renderCallHistoryTable([]);
}

function renderCampaignCallingButtons(callingType) {
    var cType = (callingType || window._currentCampaignCallingType || '').toLowerCase();
    window._currentCampaignCallingType = cType;

    // Same rule as Manual Dial: the user's own channels decide (Click2Call and WebRTC independently).
    // The campaign's stored calling type no longer hides a channel the user has.
    var userC2c = !!userChannelConfig.click2call_enabled;
    var userWebRTC = !!userChannelConfig.webrtc_enabled;
    var showC2c = userC2c;
    var showWebRTC = userWebRTC;

    var phone = ($('#camp-lead-phone').text() || '').replace(/[^0-9]/g, '');
    var hasLead = !!currentLeadId && phone.length >= 10;

    var html = '';
    if (hasLead && showC2c) {
        html += `
            <button type="button" 
                    class="cw-btn-call-start" 
                    id="btn-camp-c2c" 
                    onclick="placeCampaignCall('click2call')" 
                    aria-label="Start Click2Call"
                    style="background: linear-gradient(135deg, var(--cw-green) 0%, var(--cw-green-hover) 100%);">
                <i class="fa fa-phone" id="btn-call-icon-c2c"></i>
                <span>Click2Call</span>
            </button>
        `;
    }

    if (hasLead && showWebRTC) {
        html += `
            <button type="button" 
                    class="cw-btn-call-start" 
                    id="btn-camp-webrtc" 
                    onclick="placeCampaignCall('webrtc')" 
                    aria-label="Start WebRTC"
                    style="background: linear-gradient(135deg, var(--cw-blue) 0%, var(--cw-blue-hover) 100%);">
                <i class="fa fa-headset" id="btn-call-icon-webrtc"></i>
                <span>WebRTC Call</span>
            </button>
        `;
    }

    if (!hasLead || (!showC2c && !showWebRTC)) {
        var disabledLabel = !hasLead ? 'Select a lead to call' : 'Calling not available';
        html += `
            <button type="button" 
                    class="cw-btn-call-start disabled-type" 
                    disabled="disabled" 
                    aria-label="${disabledLabel}">
                <i class="fa fa-phone-slash"></i>
                <span>${disabledLabel}</span>
            </button>
        `;
    }

    html += `
        <a href="https://wa.me/${phone}" target="_blank" class="cw-btn-icon-sec" id="btn-camp-whatsapp">
            <i class="fab fa-whatsapp" style="color: #25D366; font-size: 16px;"></i>
            <span>WhatsApp</span>
        </a>
    `;

    $('#camp-calling-buttons-container').html(html);
    lgCampBtnsVmnState();

    if (!currentCampaignId) {
        $('#campaign-type-warning').show().find('#campaign-type-warning-text').text('Please select a campaign from the header dropdown.');
    } else if (!userC2c && !userWebRTC) {
        $('#campaign-type-warning').show().find('#campaign-type-warning-text').text('Calling permission is not available for your account. Ask an admin to enable Click2Call or WebRTC for you.');
    } else {
        $('#campaign-type-warning').hide();
    }
}

// Auto Dialer (partials/auto-dialer): told what happened to a campaign call; does nothing when no session runs
function lgAdNotify(evt, data) {
    if (evt === 'call_started') {
        window.lgOutcomePending = true;   // this call's outcome is now expected
        if (typeof lgCampOutcomeSaved !== 'undefined') lgCampOutcomeSaved = false;   // a new call: its outcome first
    }
    if (window.lgAutoDialer) window.lgAutoDialer.notify(evt, data || {});
    // Next lead follows the call (disabled while it is live)
    if (typeof lgUpdateOutcomeButtons === 'function') setTimeout(lgUpdateOutcomeButtons, 0);
}

function placeCampaignCall(type) {
    if (window.callingHoursClosed) {
        showCallToast('error', 'Calling Closed', window.callingHoursNotice || 'Calling is currently closed.');
        lgAdNotify('call_failed', { reason: 'outside_hours', message: window.callingHoursNotice });
        return;
    }

    var phone = ($('#camp-lead-phone').text() || '').replace(/[^0-9]/g, '');
    if (!phone || phone.length < 10) {
        toastr.error('Please select a valid lead to call.');
        lgAdNotify('call_failed', { reason: 'invalid_number', message: 'No valid phone number' });
        return;
    }

    var isSmart31 = $('#camp_smart31_toggle').is(':checked');
    var is1600 = $('#camp_vmn1600_toggle').is(':checked');
    var vmn = isSmart31 ? $('#camp_smart31_vmn_select').val() : (is1600 ? $('#camp_vmn1600_vmn_select').val() : '');
    if (!vmn) {
        // Each call type uses the Campaign Call card's own VMN for that channel (never the other channel's number)
        vmn = lgCampUsableVmn(type === 'webrtc' ? 'webrtc' : 'click2call');
        if (!vmn) {
            lgAdNotify('call_failed', { reason: 'vmn', message: 'No usable VMN' });
            return;
        }
    }

    // Duration timer starts only when the call is really connected (see showLiveCallStatus 'connected')
    stopCampaignTimer();
    timerSeconds = 0;
    $('#cw-campaign-timer').text('00:00:00');
    window._activeCallIsCampaign = true;

    $('#cw-status-dot').show();
    $('#cw-status-text').text('On call (' + (type === 'webrtc' ? 'WebRTC' : 'Click2Call') + ')').css('color', 'var(--cw-green)');
    $('#cw-lead-status-chip').removeClass('green red').addClass('blue').text('Call in Progress');

    if (type === 'click2call') {
        // Requirement: Do not show End Call for Click2Call calls
        var $btn = $('#btn-camp-c2c');
        $btn.prop('disabled', true).find('i').attr('class', 'fa fa-spinner fa-spin');

        showLiveCallStatus('ringing', 'Calling...', '', 'click2call');
        var masked = phone.length > 5 ? phone.substring(0, 5) + 'xxxxx' : phone;
        showCallToast('dialing', 'Calling ' + masked + '...', 'Initiating gateway call...');

        $.ajax({
            type: 'POST',
            url: "{{ route('dialerClick2call') }}",
            data: {
                _token: "{{ csrf_token() }}",
                mobile: phone,
                vmn: vmn,
                lead_id: currentLeadId,
                campaign_id: currentCampaignId,
                source: 'campaign',
                smart31_enabled: isSmart31 ? 1 : 0,
                vmn1600_enabled: is1600 ? 1 : 0
            },
            dataType: 'json',
            success: function(res) {
                if (res && res.success) {
                    $('#uuid').val(res.uuid || '');
                    $('#id').val(res.id || '');
                    $('#is_campaign').val(res && res.is_campaign ? '1' : '0');
                    sendCallStateUpdate(res.id, res.uuid, 'initiated');
                    pollCampaignCallStatus(res.id, res.uuid, $btn);
                    lgAdNotify('call_started', { call_log_id: res.call_log_id || res.id });
                } else {
                    $btn.prop('disabled', false).find('i').attr('class', 'fa fa-phone');
                    showLiveCallStatus('failed', 'Call Blocked', (res && res.message) || 'Gateway could not place call', 'click2call');
                    showCallToast('error', 'Call Blocked', (res && res.message) || 'Gateway could not place call');
                    clearInterval(campaignTimerInterval);
                    lgAdNotify('call_failed', { reason: (res && res.reason) || 'gateway', message: res && res.message });
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).find('i').attr('class', 'fa fa-phone');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to initiate Click2Call';
                showLiveCallStatus('failed', 'Call Error', msg, 'click2call');
                showCallToast('error', 'Call Blocked', msg);
                clearInterval(campaignTimerInterval);
                lgAdNotify('call_failed', { reason: (xhr.responseJSON && xhr.responseJSON.reason) || 'gateway', message: msg });
            }
        });
    } else if (type === 'webrtc') {
        // Requirement: Show End Call for WebRTC calls only!
        var $btn = $('#btn-camp-webrtc');
        $btn.addClass('on-call').attr('onclick', 'endCampaignWebRTCCall()').css('background', 'var(--cw-red) !important');
        $btn.html('<i class="fa fa-phone-slash"></i><span>End Call</span>');
        window._sipCallInProgress = true;

        showLiveCallStatus('ringing', 'Calling...', '', 'webrtc');
        var masked = phone.length > 5 ? phone.substring(0, 5) + 'xxxxx' : phone;
        showCallToast('dialing', 'Calling ' + masked + '...', 'Connecting SIP softphone...');

        $.ajax({
            type: 'POST',
            url: "{{ route('dialerwebrtc') }}",
            data: {
                _token: "{{ csrf_token() }}",
                mobile: phone,
                vmn: vmn,
                lead_id: currentLeadId,
                campaign_id: currentCampaignId,
                source: 'campaign',
                smart31_enabled: isSmart31 ? 1 : 0,
                vmn1600_enabled: is1600 ? 1 : 0
            },
            dataType: 'json',
            success: function(res) {
                if (res && res.success) {
                    $('#uuid').val(res.uuid || '');
                    $('#id').val(res.id || '');
                    $('#is_campaign').val(res && res.is_campaign ? '1' : '0');
                    sendCallStateUpdate(res.id, res.uuid, 'initiated');
                    lgAdNotify('call_started', { call_log_id: res.call_log_id || res.id });

                    if (typeof window._softphoneReady !== 'undefined') {
                        window._softphoneReady.then(function(sp) {
                            sp.initSoftphone({
                                username: res.sip_id || '',
                                password: res.sip_password || '',
                                domain: res.sip_ip || '',
                                websocket: res.sip_ws,
                            }).then(function() {
                                return sp.makeSoftphoneCall(phone, {
                                    callId: res.call_id,
                                    refId: res.ref_id,
                                    agent: res.sip_id,
                                    did: res.did,
                                    gateway: res.gateway,
                                    prefix: res.gateway_prefix,
                                    vmn: vmn,
                                });
                            }).catch(function(err) {
                                console.error('SIP call error:', err);
                                lgAdNotify('call_failed', { reason: 'sip', message: err && err.message });
                                endCampaignWebRTCCall();
                                showLiveCallStatus('failed', 'Call Failed', err.message || 'SIP connection error', 'webrtc');
                                sendCallStateUpdate(res.id, res.uuid, 'failed');
                            });
                        });
                    }
                } else {
                    lgAdNotify('call_failed', { reason: (res && res.reason) || 'gateway', message: res && res.message });
                    endCampaignWebRTCCall();
                    showLiveCallStatus('failed', 'Call Blocked', (res && res.message) || 'WebRTC initiation failed', 'webrtc');
                    showCallToast('error', 'Call Blocked', (res && res.message) || 'WebRTC initiation failed');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to initiate WebRTC call';
                lgAdNotify('call_failed', { reason: (xhr.responseJSON && xhr.responseJSON.reason) || 'gateway', message: msg });
                endCampaignWebRTCCall();
                showLiveCallStatus('failed', 'Call Error', msg, 'webrtc');
                showCallToast('error', 'Call Blocked', msg);
            }
        });
    }
}

function endCampaignWebRTCCall() {
    // Agent pressed End Call (or call setup failed). With a live SIP session the real hangup/cancel
    // event (onCallEnded) shows the result, saves the outcome and resets the card.
    if (typeof sipHasActiveCall === 'function' && sipHasActiveCall()) {
        sipHangup();
        return;
    }
    window._sipCallInProgress = false;
    stopCampaignTimer();
    hideCallToast();
    markCampaignCallEnded();
}

function markCampaignCallEnded() {
    stopCampaignTimer();
    window._activeCallIsCampaign = false;
    $('#cw-status-dot').hide();
    $('#cw-status-text').text('Call ended. Fill in the outcome, then pick the next lead.').css('color', 'var(--cw-amber)');
    $('#cw-lead-status-chip').removeClass('blue').addClass('green').text('Call Ended');
    if (typeof renderCampaignCallingButtons === 'function') {
        renderCampaignCallingButtons(window._currentCampaignCallingType);
    }
    lgAdNotify('call_ended');
}

function startCampaignTimer(initialSeconds) {
    if (!window._activeCallIsCampaign || campaignTimerInterval) return;
    timerSeconds = initialSeconds || 0;
    var paint = function () {
        var h = String(Math.floor(timerSeconds / 3600)).padStart(2, '0');
        var m = String(Math.floor((timerSeconds % 3600) / 60)).padStart(2, '0');
        var s = String(timerSeconds % 60).padStart(2, '0');
        $('#cw-campaign-timer').text(h + ':' + m + ':' + s);
    };
    paint();
    campaignTimerInterval = setInterval(function () { timerSeconds++; paint(); }, 1000);
}

function stopCampaignTimer() {
    clearInterval(campaignTimerInterval);
    campaignTimerInterval = null;
}

function pollCampaignCallStatus(callId, callUuid, $btn) {
    pollClick2CallStatus(callId, callUuid, true, $btn, 'fa fa-phone');
}

/* ── Opening a lead in the Campaign Call card ─────────────────────────────────────────
   Every way of changing the lead ends in loadLead(): a "Next leads" row, Skip, Next lead, the browser
   Back / Forward buttons and the Auto Dialer. lgRequestLeadSwitch() adds the agent-side checks for a row / Skip /
   Back: same lead, a live call, an unsaved call outcome. */

// A call placed from the Campaign Call card whose outcome is not saved yet (set on call start, cleared on save / lead change)
window.lgOutcomePending = false;

// A live campaign call: a WebRTC session, or a Click2Call call still being followed
function lgCampaignCallLive() {
    return !!(window._sipCallInProgress || (typeof _callPollInterval !== 'undefined' && _callPollInterval));
}

function selectQueueLead(leadId, campaignId) {
    lgRequestLeadSwitch(leadId, { campaignId: campaignId, fromPanel: true });
}

function lgRequestLeadSwitch(leadId, opts) {
    opts = opts || {};
    var campaignId = opts.campaignId || currentCampaignId;
    if (String(leadId) === String(currentLeadId) && String(campaignId) === String(currentCampaignId)) {
        if (opts.fromPanel) closeDrawer();   // already open: nothing to reload
        return false;
    }
    if (window.lgAutoDialer && window.lgAutoDialer.isRunning()) {
        toastr.info('Auto dialing is on: pause the auto dialer to open another lead.');
        return false;
    }
    if (lgCampaignCallLive()) {
        toastr.warning('Finish the current call before opening another lead');
        return false;   // the panel stays open
    }
    var go = function () {
        if (opts.fromPanel) closeDrawer();
        loadLead(leadId, opts.onLoaded, { campaignId: campaignId, push: opts.push });
    };
    if (window.lgOutcomePending) {
        lgConfirmSwitch(go, opts.onStay);
    } else {
        go();
    }
    return true;
}

// "Switch lead without saving the call outcome?" Stay / Switch (Esc = Stay)
function lgConfirmSwitch(onSwitch, onStay) {
    var $box = $('#cw-switch-confirm');
    var close = function () {
        $box.prop('hidden', true);
        $(document).off('keydown.lgSwitch');
        $('#cw-switch-go, #cw-switch-stay').off('click.lgSwitch');
    };
    $box.prop('hidden', false);
    $('#cw-switch-stay').focus();
    $('#cw-switch-go').on('click.lgSwitch', function () { close(); onSwitch(); });
    $('#cw-switch-stay').on('click.lgSwitch', function () { close(); if (onStay) onStay(); });
    $(document).on('keydown.lgSwitch', function (e) { if (e.key === 'Escape') { e.stopPropagation(); close(); if (onStay) onStay(); } });
}

// Browser Back / Forward between leads: open the lead in the address bar
window.addEventListener('popstate', function () {
    var m = window.location.pathname.match(/\/campaigns\/(\d+)\/lead\/(\d+)\/calling\/?$/);
    if (!m || activeWorkspaceTab !== 'campaign') return;
    var shownUrl = '/admin/campaigns/' + currentCampaignId + '/lead/' + currentLeadId + '/calling';
    var started = lgRequestLeadSwitch(parseInt(m[2], 10), {
        campaignId: parseInt(m[1], 10),
        push: false,
        onStay: function () { history.pushState(null, '', shownUrl); }
    });
    // not switched (live call / auto dialer): the address bar keeps showing the lead on screen
    if (started === false && (String(m[2]) !== String(currentLeadId))) history.pushState(null, '', shownUrl);
});

var lgLeadLoadSeq = 0;

/**
 * Load a lead into the Campaign Call card. The current lead stays (ids, card, URL) until the new one has loaded;
 * on failure an error toast is shown and nothing changes.
 * onLoaded(ok) (optional): called once the lead is shown (used by the Auto Dialer).
 * opts.campaignId: the lead's campaign (default: the current one); opts.push === false: do not add a history entry.
 */
function loadLead(leadId, onLoaded, opts) {
    if (!leadId) return;
    opts = opts || {};
    var done = typeof onLoaded === 'function' ? onLoaded : function () {};
    var campaignId = opts.campaignId || currentCampaignId;
    var seq = ++lgLeadLoadSeq;   // a newer click wins over an older, slower response
    lgClearCallToast();   // Skip / Next lead: no old "Calling..." toast on the next lead

    $('#btn-save-outcome, #btn-next-lead, #btn-skip-lead').prop('disabled', true);
    $('#cw-campaign-call-card').addClass('is-loading-lead').attr('aria-busy', 'true');
    var finish = function () {
        if (seq !== lgLeadLoadSeq) return false;
        $('#cw-campaign-call-card').removeClass('is-loading-lead').removeAttr('aria-busy');
        $('#btn-skip-lead').prop('disabled', false);
        lgUpdateOutcomeButtons();
        return true;
    };
    var failed = function () {
        if (!finish()) return;
        toastr.error('Could not load lead. Please try again');
        done(false);
    };

    $.ajax({
        url: '/admin/campaigns/' + campaignId + '/lead/' + leadId + '/calling-data',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (!res || !res.success || !res.lead) { failed(); return; }
            if (!finish()) return;
            {
                const lead = res.lead;
                // Now the new lead is the current one
                currentCampaignId = parseInt(campaignId, 10);
                currentLeadId = lead.id;
                var queueIdx = campaignQueue.findIndex(function (l) { return l.id == lead.id; });
                if (queueIdx !== -1) currentQueueIndex = queueIdx;
                window.lgOutcomePending = false;
                lgCampOutcomeSaved = false;      // a new lead: Next lead waits for its outcome
                lgCampNextQueueIdx = null;
                $('#current_campaign_id').val(currentCampaignId);
                $('#current_lead_id').val(lead.id);

                // Update Left Card Details
                $('#camp-lead-name').text(lead.name || 'Lead');
                $('#camp-lead-phone').text(lead.mobile || '—');
                $('#camp-lead-initials').text(lead.initials || 'LD');
                $('#cw-lead-status-chip').removeClass('blue red').addClass('green').text(lead.status_name || 'New Lead');

                $('#camp-lead-city, #camp-info-city').text(lead.city || '—');
                $('#camp-lead-product, #camp-info-product').text(lead.product_name || '—');
                $('#camp-lead-campaign, #camp-info-campaign').text(lead.campaign_name || '—');
                // Lead ID: digits, opens the shared Lead Details popup
                $('#camp-lead-id, #camp-info-lead-id').empty().append($('<a href="javascript:void(0)" class="lg-lead-id-link">').attr({ 'data-lead-id': lead.id, title: 'View lead ' + lead.id }).text(lead.id));
                $('#camp-lead-source, #camp-info-source').text(lead.source || '—');
                $('#camp-lead-calls, #camp-info-calls').text(lead.calls_count || 0);
                $('#camp-lead-altphone, #camp-info-alt-phone').text(lead.alt_phone || '—');
                $('#camp-lead-lastcall, #camp-info-last-contacted').text(lead.last_contacted || '—');

                var cleanPhone = (lead.mobile || '').replace(/[^0-9]/g, '');
                $('#btn-camp-whatsapp').attr('href', cleanPhone ? ('https://wa.me/' + cleanPhone) : 'https://wa.me/');

                // Update Calling Buttons dynamically
                window._currentCampaignCallingType = res.campaign_calling_type || lead.campaign_calling_type || '';
                renderCampaignCallingButtons(window._currentCampaignCallingType);

                // Update Browser URL (refresh / bookmark / Back open this lead); Back itself does not add an entry
                var leadUrl = '/admin/campaigns/' + currentCampaignId + '/lead/' + lead.id + '/calling';
                if (opts.push !== false && window.location.pathname !== leadUrl) {
                    window.history.pushState(null, '', leadUrl);
                }
                if (String($('#cw-workspace-campaign-select').val()) !== String(currentCampaignId)) {
                    $('#cw-workspace-campaign-select').val(String(currentCampaignId));
                }

                // Update Drawer Active Item
                $('#cw-drawer-lead-list .cw-drawer-lead-row').removeClass('active');
                $('#cw-drawer-lead-list .cw-drawer-lead-row[data-lead-id="' + lead.id + '"]').addClass('active');

                // Render Call History
                renderCallHistoryTable(res.callLogs || []);

                // Reset Form & Timer
                resetCallOutcomeForm();

                checkQueueBounds();
                done(true);
            }
        },
        error: failed
    });
}

function renderCallHistoryTable(callLogs) {
    var $tbody = $('.cw-history-card table tbody');
    $tbody.empty();
    $('.cw-history-card .cw-badge-pill').text((callLogs ? callLogs.length : 0) + ' calls');

    if (!callLogs || callLogs.length === 0) {
        $tbody.append(`
            <tr>
                <td colspan="7" style="text-align: center; color: var(--cw-text-sub); padding: 20px;">
                    No previous call history found for this lead.
                </td>
            </tr>
        `);
        return;
    }

    callLogs.forEach(function(log) {
        var isConnected = (log.duration && log.duration !== '00:00' && log.duration !== '0') || (log.status_name && log.status_name.toLowerCase().includes('connect'));
        var hasRec = log.recording_url || log.recording || log.file_path;
        var recHtml = '';
        if (hasRec) {
            var url = log.recording_url || ('/reports/download-recordings/' + log.id + '/0');
            recHtml = `<div class="d-flex align-items-center" style="gap: 6px;">
                <button type="button" class="btn btn-sm btn-primary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;" onclick="playWorkspaceAudio('${url}', this)" title="Play Recording"><i class="fa fa-play" style="font-size: 11px;"></i></button>
                <a href="${url}" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;" title="Download Recording"><i class="fa fa-download" style="font-size: 11px;"></i></a>
            </div>`;
        } else if (isConnected) {
            recHtml = `<span class="badge" style="background-color: #FEF3C7; color: #92400E; font-size: 11px; padding: 4px 8px; border-radius: 4px;"><i class="fa fa-spinner fa-spin"></i> Processing...</span>`;
        } else {
            recHtml = `<span style="color: var(--cw-text-sub); font-size: 12px;">—</span>`;
        }

        var rowHtml = `
            <tr>
                <td>${log.created_at}</td>
                <td>${log.called_by_name || 'Agent'}</td>
                <td><span class="cw-status-badge-chip green">${log.status_name || 'Connected'}</span></td>
                <td>${log.duration || '00:00'}</td>
                <td>${log.disposition || 'Interested'}</td>
                <td>${log.comments || ''}</td>
                <td>${recHtml}</td>
            </tr>
        `;
        $tbody.append(rowHtml);
    });
}

let currentWorkspaceAudio = null;
let currentWorkspaceBtn = null;
function playWorkspaceAudio(url, btn) {
    if (currentWorkspaceAudio && currentWorkspaceBtn === btn) {
        if (currentWorkspaceAudio.paused) {
            currentWorkspaceAudio.play();
            $(btn).find('i').removeClass('fa-play').addClass('fa-pause');
        } else {
            currentWorkspaceAudio.pause();
            $(btn).find('i').removeClass('fa-pause').addClass('fa-play');
        }
        return;
    }
    if (currentWorkspaceAudio) {
        currentWorkspaceAudio.pause();
        if (currentWorkspaceBtn) {
            $(currentWorkspaceBtn).find('i').removeClass('fa-pause').addClass('fa-play');
        }
    }
    currentWorkspaceBtn = btn;
    $(btn).find('i').removeClass('fa-play').addClass('fa-spinner fa-spin');
    currentWorkspaceAudio = new Audio(url);
    currentWorkspaceAudio.play().then(function() {
        $(btn).find('i').removeClass('fa-spinner fa-spin').addClass('fa-pause');
    }).catch(function() {
        $(btn).find('i').removeClass('fa-spinner fa-spin').addClass('fa-play');
        if (typeof toastr !== 'undefined') {
            toastr.error('Unable to play recording');
        }
    });
    currentWorkspaceAudio.onended = function() {
        $(btn).find('i').removeClass('fa-pause fa-spinner fa-spin').addClass('fa-play');
        currentWorkspaceAudio = null;
        currentWorkspaceBtn = null;
    };
}

/* ── Call Outcome: Save (stays on the lead) / Next lead ───────────────────────────── */
var lgCampSaving = false;            // a save request is running (no double save)
var lgCampOutcomeSaved = false;      // an outcome was saved for the lead on screen (Next lead allowed)
var lgCampNextQueueIdx = null;       // where the next lead is in the queue after the saved lead left it

function lgCampOutcomeComplete() {
    var statusId = $('#camp_status_id').val();
    return !!statusId && (!lgSubstatusRequired(statusId) || !!$('#camp_substatus_id').val());
}

/** The queue lead "Next lead" opens (null = none left). */
function lgCampNextLeadId() {
    if (!campaignQueue.length) return null;
    var idx = campaignQueue.findIndex(function (l) { return l.id == currentLeadId; });
    if (idx !== -1) {
        return campaignQueue.length > 1 ? campaignQueue[(idx + 1) % campaignQueue.length].id : null;
    }
    // the saved lead has left the queue: the lead that took its place
    var next = lgCampNextQueueIdx !== null && lgCampNextQueueIdx < campaignQueue.length ? lgCampNextQueueIdx : 0;
    return campaignQueue[next].id;
}

/** Enable / disable Save and Next lead (with the reason as the tooltip). */
function lgUpdateOutcomeButtons() {
    var $save = $('#btn-save-outcome'), $next = $('#btn-next-lead');
    if (!$save.length) return;
    var complete = lgCampOutcomeComplete();
    if (!$save.hasClass('is-saved')) {
        $save.prop('disabled', lgCampSaving || !complete || !currentLeadId);
    }
    $('#btn-save-outcome-tip').attr('title', lgCampSaving ? 'Saving...' : (complete ? 'Save the call outcome (Ctrl+Enter)'
        : ($('#camp_status_id').val() ? 'Choose the Substatus first' : 'Choose the Status first')));

    var reason = null;
    if (lgCampaignCallLive()) reason = 'Finish the call first';
    else if (!lgCampOutcomeSaved) reason = 'Save the outcome first';
    else if (!lgCampNextLeadId()) reason = 'No more leads in the queue';
    $next.prop('disabled', !!reason || lgCampSaving);
    $('#btn-next-lead-tip').attr('title', reason || 'Open the next lead (Alt+Right Arrow)');
}

/**
 * Save the Call Outcome of the current call; the lead stays on screen. Same validation and request as before.
 * Only after the server confirms: the form resets, the lead's badge / calls / last contacted are refreshed.
 */
function saveCampaignOutcome() {
    if (lgCampSaving) return;
    const statusId = $('#camp_status_id').val();
    const substatusId = $('#camp_substatus_id').val();
    const feedback = $('#camp_comments').val();
    const followupDate = $('#camp_followup_date').val();

    if (!statusId) {
        toastr.error('The status field is required.');
        $('#camp_status_id').focus();
        return;
    }
    if (!substatusId && lgSubstatusRequired(statusId)) {
        toastr.error('The substatus field is required.');
        $('#camp_substatus_id').focus();
        return;
    }

    const $btn = $('#btn-save-outcome');
    const originalBtnHtml = $btn.html();
    const savedLeadId = currentLeadId;
    const savedCampaignId = currentCampaignId;
    lgCampSaving = true;
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i><span>Saving...</span>');
    lgUpdateOutcomeButtons();

    $.ajax({
        url: '/admin/campaigns/' + savedCampaignId + '/lead/' + savedLeadId + '/save-disposition',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            main_disposition: statusId,   // the selected Status (the Call Status chip is not a lead status)
            sub_disposition: substatusId,
            remarks: feedback,
            follow_up_on: followupDate
        },
        success: function(res) {
            lgCampSaving = false;
            if (!res || res.success === false) {
                $btn.html(originalBtnHtml);
                toastr.error((res && res.message) || 'Failed to save outcome.');
                lgUpdateOutcomeButtons();
                return;
            }
            toastr.success('Outcome saved');
            window.lgOutcomePending = false;
            lgCampOutcomeSaved = true;

            // The saved lead leaves the queue (as before); remember where the next one is
            const savedIdx = campaignQueue.findIndex(l => l.id == savedLeadId);
            if (savedIdx !== -1) {
                campaignQueue.splice(savedIdx, 1);
                $('.cw-drawer-lead-row[data-lead-id="' + savedLeadId + '"]').remove();
                var newCount = campaignQueue.length;
                $('#drawer-queue-count').text(newCount + ' in queue');
                $('#cw-queue-count-badge').text(newCount);
                lgCampNextQueueIdx = savedIdx;
            }

            // Empty form for the next outcome; brief "Saved" tick on the button
            lgResetOutcomeFields();
            $btn.addClass('is-saved').prop('disabled', true).html('<i class="fa fa-check" aria-hidden="true"></i><span>Saved</span>');
            setTimeout(function () {
                $btn.removeClass('is-saved').html(originalBtnHtml);
                lgUpdateOutcomeButtons();
            }, 1000);
            lgUpdateOutcomeButtons();
            lgRefreshLeadDetails(savedCampaignId, savedLeadId);

            // A running Auto Dialer continues by itself (countdown to the next call)
            if (window.lgAutoDialer) window.lgAutoDialer.onOutcomeSaved(savedLeadId);
        },
        error: function(xhr) {
            lgCampSaving = false;
            $btn.html(originalBtnHtml);
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save outcome.';
            toastr.error(msg);   // the entered values stay
            lgUpdateOutcomeButtons();
        }
    });
}

/** Next lead: the next queue lead, through the same switch as Skip / the Next leads panel. */
function goToNextLead() {
    if ($('#btn-next-lead').prop('disabled')) return;
    // during the Auto Dialer's countdown: the next call starts now
    if (window.lgAutoDialer && window.lgAutoDialer.nextNow()) return;
    var nextId = lgCampNextLeadId();
    if (nextId) lgRequestLeadSwitch(nextId);
}

/** After a save: the lead's status badge, calls so far, last contacted and call history (the form is not touched). */
function lgRefreshLeadDetails(campaignId, leadId) {
    $.ajax({ url: '/admin/campaigns/' + campaignId + '/lead/' + leadId + '/calling-data', type: 'GET', dataType: 'json' }).done(function (res) {
        if (!res || !res.success || !res.lead || String(leadId) !== String(currentLeadId)) return;
        var lead = res.lead;
        if (lead.status_name) $('#cw-lead-status-chip').removeClass('blue red').addClass('green').text(lead.status_name);
        $('#camp-lead-calls, #camp-info-calls').text(lead.calls_count || 0);
        $('#camp-lead-lastcall, #camp-info-last-contacted').text(lead.last_contacted || '—');
        renderCallHistoryTable(res.callLogs || []);
        $('.cw-drawer-lead-row[data-lead-id="' + leadId + '"] .cw-status-badge-chip').text(lead.status_name || '');
    });
}

// Ctrl+Enter = Save, Alt+Right Arrow = Next lead (Campaign Queue tab)
$(document).on('keydown', function (e) {
    if (!$('#tab-campaign-queue').is(':visible') || !$('#cw-switch-confirm').prop('hidden')) return;
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        if (!$('#btn-save-outcome').prop('disabled')) saveCampaignOutcome();
    } else if (e.key === 'ArrowRight' && e.altKey) {
        e.preventDefault();
        goToNextLead();
    }
});
$(document).on('change', '#camp_status_id, #camp_substatus_id', function () { setTimeout(lgUpdateOutcomeButtons, 0); });

function skipCampaignLead() {
    // While the Auto Dialer runs it decides the next lead; the call outcome is required first
    if (window.lgAutoDialer && window.lgAutoDialer.isRunning()) {
        toastr.info('Auto dialing is on: save the call outcome to continue, or pause the auto dialer.');
        return;
    }
    if (campaignQueue.length <= 1) {
        showEndOfQueueState();
        return;
    }
    // same checks as picking a lead in Next leads (live call, unsaved outcome); currentQueueIndex moves on load
    var nextLeadId = campaignQueue[(currentQueueIndex + 1) % campaignQueue.length].id;
    lgRequestLeadSwitch(nextLeadId);
}

/* Call Outcome (both tabs): status -> its substatuses, as stored on the Status page */
// Active substatuses of each status (Master Data -> Lead Status), in the Status page's order
window.lgStatusSubs = @json($lgOutcomeStatuses->mapWithKeys(fn ($st) => [$st->id => $st->substatuses->filter(fn ($x) => $x->status === null || (int)$x->status === 1)->map(fn ($x) => ['id' => $x->id, 'name' => $x->name])->values()]));

/**
 * Fill the Substatus select for a status: only that status's substatuses; disabled (not required) when it has none.
 * A status with exactly one substatus gets it selected straight away (shown in the field and saved with the outcome);
 * with several, the given preselect (e.g. the lead's saved substatus) is kept, otherwise the agent chooses.
 * The list comes from the page itself (no request), so a later status change can never be overwritten by an older one.
 */
function lgFillSubstatus($sub, statusId, preselect) {
    var list = statusId ? (window.lgStatusSubs[statusId] || []) : null;
    $sub.empty();
    if (!statusId) {
        $sub.append('<option value="">Select a status first</option>').prop('disabled', true);
    } else if (!list.length) {
        $sub.append('<option value="">No substatus for this status</option>').prop('disabled', true);
    } else {
        var keep = preselect && list.some(function (x) { return String(x.id) === String(preselect); });
        if (list.length > 1) $sub.append('<option value="">Select Substatus</option>');
        list.forEach(function (x) { $sub.append($('<option>').val(x.id).text(x.name)); });
        $sub.prop('disabled', false).val(keep ? String(preselect) : (list.length === 1 ? String(list[0].id) : ''));
    }
    if ($.fn.select2 && $sub.hasClass('select2-hidden-accessible')) $sub.trigger('change.select2');
}

/** True when the status has substatuses, i.e. a substatus must be chosen. */
function lgSubstatusRequired(statusId) {
    return !!(statusId && (window.lgStatusSubs[statusId] || []).length);
}

/** Highlight the Call Status button that matches the Status dropdown (none when there is no match). */
function lgSyncChips($select) {
    var $wrap = $('.cw-status-chips-wrap[data-target="#' + $select.attr('id') + '"]');
    $wrap.find('.cw-status-chip-opt').removeClass('selected').attr('aria-pressed', 'false');
    var v = $select.val();
    var $b = v ? $wrap.find('.cw-status-chip-opt[data-status-id="' + v + '"]') : $();
    $b.addClass('selected').attr('aria-pressed', 'true');
    if ($b.length) $wrap.scrollTop($b[0].offsetTop - $wrap[0].offsetTop - 1);   // chosen in the dropdown: bring its button into view
}

function updateCampSubstatusOptions(statusId, preselectSubstatusId) {
    lgFillSubstatus($('#camp_substatus_id'), statusId, preselectSubstatusId);
}

// Button -> Status dropdown (which then refreshes the Substatus list)
$(document).on('click', '.cw-status-chips-wrap .cw-status-chip-opt[data-status-id]', function () {
    var $select = $($(this).closest('.cw-status-chips-wrap').data('target'));
    $select.val(String($(this).data('status-id'))).trigger('change');
});
// Status dropdown (by hand or from a button) -> highlight + Substatus for that status, previous choice cleared
$(document).on('change', '#camp_status_id, #manual_status_id', function () {
    lgSyncChips($(this));
    lgFillSubstatus($(this).attr('id') === 'camp_status_id' ? $('#camp_substatus_id') : $('#manual_substatus_id'), $(this).val(), null);
});
$(function () {
    ['#camp_status_id', '#manual_status_id'].forEach(function (id) {
        var $s = $(id);
        if ($s.length) { lgSyncChips($s); lgFillSubstatus(id === '#camp_status_id' ? $('#camp_substatus_id') : $('#manual_substatus_id'), $s.val(), null); }
    });
    lgUpdateOutcomeButtons();
});

/** The Call Outcome fields back to empty: no Call Status highlighted, "Select Status", Substatus disabled. */
function lgResetOutcomeFields() {
    $('#camp_status_id').val('');
    if ($.fn.select2 && $('#camp_status_id').hasClass('select2-hidden-accessible')) $('#camp_status_id').trigger('change.select2');
    lgSyncChips($('#camp_status_id'));
    updateCampSubstatusOptions('', null);
    $('#camp_comments').val('');
    $('#camp-comment-counter').text('0/500');
    $('#camp_followup_date').val('');
    // validation marks, if any
    $('#camp_status_id, #camp_substatus_id, #camp_comments, #camp_followup_date').removeClass('is-invalid has-error').removeAttr('aria-invalid');
    lgUpdateOutcomeButtons();
}

/** A new lead: empty Call Outcome form and a ready call state. */
function resetCallOutcomeForm() {
    lgResetOutcomeFields();

    timerSeconds = 0;
    stopCampaignTimer();
    window._activeCallIsCampaign = false;
    $('#cw-campaign-timer').text('00:00:00');
    $('#cw-status-dot').hide();
    $('#cw-status-text').text('Ready to call. Press Start call when ready').css('color', '');
}

function checkQueueBounds() {
    if (campaignQueue.length === 0) {
        showEndOfQueueState();
    }
}

function showEndOfQueueState() {
    lgClearCallToast();
    toastr.info("You've reached the end of the queue.");
    $('#cw-status-text').text("You've reached the end of the queue").css('color', '#EF4444');
    $('#btn-skip-lead').prop('disabled', true);
    lgUpdateOutcomeButtons();   // Next lead: "No more leads in the queue"; Save still saves this lead's outcome
}

/* ── Manual Outcome Saving ───────────────────────────────── */
var isSavingOutcome = false;

/* A status with "Requires follow-up date" makes the follow-up date mandatory; no past dates in either picker */
function lgFollowupRequired() {
    var req = $('#manual_status_id option:selected').data('followup') == 1;
    $('#manual_followup_req').toggle(req);
    return req;
}
function lgFollowupMinNow() {
    var d = new Date(); d.setSeconds(0, 0);
    var local = new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    $('#manual_followup_date, #qa-followup-datetime').attr('min', local);
}
$(function () { lgFollowupMinNow(); setInterval(lgFollowupMinNow, 60000); });

function saveManualOutcome() {
    if (isSavingOutcome) {
        return;
    }

    var rawMobile = $('#mobile').val() || '';
    var mobile = rawMobile.replace(/[^0-9]/g, '');
    var statusId = $('#manual_status_id').val();
    var substatusId = $('#manual_substatus_id').val();
    var comments = $('#manual_comments').val();
    var followupDate = $('#manual_followup_date').val();

    if (!mobile || mobile.length < 10) {
        toastr.error('Please enter a valid 10-digit mobile number.');
        $('#mobile').focus();
        return;
    }

    if (!statusId) {
        toastr.error('Please select Status *');
        $('#manual_status_id').focus();
        return;
    }

    if (!substatusId && lgSubstatusRequired(statusId)) {
        toastr.error('The substatus field is required.');
        $('#manual_substatus_id').focus();
        return;
    }

    if (lgFollowupRequired() && !followupDate) {
        toastr.error($('#manual_status_id option:selected').text() + ' requires a follow-up date and time.');
        $('#manual_followup_date').focus();
        return;
    }

    // No campaign is chosen here: campaign calls keep their lead's campaign; a new manual-dial lead goes to Manual Dial

    // Form is valid: immediately disable button for ~3 seconds
    isSavingOutcome = true;
    var $btn = $('#btn-save-manual-outcome');
    var $btnText = $('#save-btn-text');
    var $btnIcon = $('#save-btn-icon');

    $btn.prop('disabled', true).addClass('cw-btn-disabled');
    $btnText.text('Saving...');
    $btnIcon.removeClass('fa-check').addClass('fa-spinner fa-spin');

    var restoreButton = function() {
        $btn.prop('disabled', false).removeClass('cw-btn-disabled');
        $btnText.text('Save');
        $btnIcon.removeClass('fa-spinner fa-spin').addClass('fa-check');
        isSavingOutcome = false;
    };

    $.ajax({
        url: "{{ route('dialerSaveLog') }}",
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            mobile: mobile,
            status_id: statusId,
            substatus_id: substatusId,
            lead_id: $('#manual_lead_id').val() || '',   // used only when it has this number
            follow_up_on: followupDate,
            comments: comments,
            feedback: comments,
            call_type: activeCallOption || 'click2call',
            id: $('#id').val() || '',
            is_campaign: $('#is_campaign').val() || '0'
        },
        dataType: 'json',
        success: function(res) {
            // Shown only after the server committed the outcome (and the follow-up, when a date was given)
            if (res && res.warning) { toastr.warning(res.warning); } else { toastr.success((res && res.message) || 'Call outcome saved successfully.'); }
            refreshTodayPerformance();
            refreshRecentActivity();
            setTimeout(function() {
                restoreButton();
            }, 3000);
        },
        error: function(xhr) {
            var msg = 'Failed to save outcome';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            toastr.error(msg);
            restoreButton();
        }
    });
}

$(document).on('keydown', function(e) {
    if (e.key === 'Enter') {
        var activeEl = document.activeElement;
        if (activeEl && activeEl.tagName === 'TEXTAREA') {
            return;
        }
        if ($('#tab-manual-dial').is(':visible') && $(activeEl).closest('#tab-manual-dial').length > 0) {
            e.preventDefault();
            if (!isSavingOutcome) {
                saveManualOutcome();
            }
        }
    }
});

/* ── Quick Actions Handlers (Add Note, Follow-up, Add Tag, History) ── */
function updateQuickActionButtonsState() {
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    var leadId = $('#manual_lead_id').val() || '';
    var hasTarget = (mobile.length >= 10 || leadId !== '');

    var $btns = $('#qa-btn-note, #qa-btn-followup, #qa-btn-tag, #qa-btn-history');
    if (hasTarget) {
        $btns.prop('disabled', false).removeAttr('title');
    } else {
        $btns.prop('disabled', true).attr('title', 'Please enter or select a phone number first');
    }
}

function openQuickNoteModal() {
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    var leadId = $('#manual_lead_id').val() || '';
    if (!mobile && !leadId) {
        toastr.warning('Please enter or select a phone number first');
        return;
    }
    $('#qa-note-text').val('');
    $('#qa-modal-note').fadeIn(150);
    $('#qa-note-text').focus();
}

function openQuickFollowupModal() {
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    var leadId = $('#manual_lead_id').val() || '';
    if (!mobile && !leadId) {
        toastr.warning('Please enter or select a phone number first');
        return;
    }
    $('#qa-followup-datetime').val('');
    $('#qa-followup-remarks').val('');
    $('#qa-modal-followup').fadeIn(150);
}

function openQuickTagModal() {
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    var leadId = $('#manual_lead_id').val() || '';
    if (!mobile && !leadId) {
        toastr.warning('Please enter or select a phone number first');
        return;
    }
    $('#qa-tag-input').val('');
    $('#qa-modal-tag').fadeIn(150);
}

function openQuickHistoryModal() {
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    var leadId = $('#manual_lead_id').val() || '';
    if (!mobile && !leadId) {
        toastr.warning('Please enter or select a phone number first');
        return;
    }
    $('#qa-history-table-container').html('<div class="text-center py-3 text-muted"><i class="fa fa-spinner fa-spin"></i> Loading history...</div>');
    $('#qa-modal-history').fadeIn(150);

    $.ajax({
        url: "{{ route('dialerPhoneHistory') }}",
        type: 'GET',
        data: { mobile: mobile, lead_id: leadId },
        dataType: 'json',
        success: function(res) {
            if (res && res.status === 'success' && res.data && res.data.length > 0) {
                var html = `
                    <table class="table table-sm table-striped" style="font-size: 12px; margin-bottom: 0;">
                        <thead>
                            <tr style="color: var(--cw-text-sub);">
                                <th>Date & Time</th>
                                <th>Agent</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Disposition</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                $.each(res.data, function(i, row) {
                    html += `
                        <tr>
                            <td>${row.created_at || '—'}</td>
                            <td>${row.agent_name || 'Agent'}</td>
                            <td><span class="cw-badge-pill" style="font-size: 10.5px;">${row.status || 'Connected'}</span></td>
                            <td>${row.duration || '00:00'}</td>
                            <td>${row.disposition || '—'}</td>
                        </tr>
                    `;
                });
                html += `</tbody></table>`;
                $('#qa-history-table-container').html(html);
            } else {
                $('#qa-history-table-container').html('<div class="text-center py-3 text-muted" style="font-size: 12px;">No previous call records found for this number.</div>');
            }
        },
        error: function() {
            $('#qa-history-table-container').html('<div class="text-center py-3 text-danger" style="font-size: 12px;">Failed to load call history.</div>');
        }
    });
}

function closeQuickModal(type) {
    $('#qa-modal-' + type).fadeOut(150);
}

function submitQuickNote() {
    var note = $('#qa-note-text').val().trim();
    if (!note) {
        toastr.error('Please enter a note');
        return;
    }
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    var leadId = $('#manual_lead_id').val() || '';
    var callId = $('#id').val() || '';

    var $btn = $('#btn-submit-qa-note').prop('disabled', true).text('Saving...');
    $.ajax({
        url: "{{ route('dialerQuickNote') }}",
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            note: note,
            mobile: mobile,
            lead_id: leadId,
            call_id: callId
        },
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).text('Save Note');
            closeQuickModal('note');
            toastr.success(res.message || 'Note saved successfully');
        },
        error: function(xhr) {
            $btn.prop('disabled', false).text('Save Note');
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save note';
            toastr.error(msg);
        }
    });
}

function submitQuickFollowup() {
    var datetime = $('#qa-followup-datetime').val();
    if (!datetime) {
        toastr.error('Please select follow-up date and time');
        return;
    }
    var remarks = $('#qa-followup-remarks').val().trim();
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    var leadId = $('#manual_lead_id').val() || '';

    var $btn = $('#btn-submit-qa-followup').prop('disabled', true).text('Saving...');
    $.ajax({
        url: "{{ route('dialerQuickFollowup') }}",
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            followup_datetime: datetime,
            remarks: remarks,
            mobile: mobile,
            lead_id: leadId
        },
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).text('Save Follow-up');
            closeQuickModal('followup');
            toastr.success(res.message || 'Follow-up scheduled successfully');
            refreshTodayPerformance();
        },
        error: function(xhr) {
            $btn.prop('disabled', false).text('Save Follow-up');
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save follow-up';
            toastr.error(msg);
        }
    });
}

function submitQuickTag() {
    var tag = $('#qa-tag-input').val().trim();
    if (!tag) {
        toastr.error('Please enter a tag');
        return;
    }
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    var leadId = $('#manual_lead_id').val() || '';

    var $btn = $('#btn-submit-qa-tag').prop('disabled', true).text('Adding...');
    $.ajax({
        url: "{{ route('dialerQuickTag') }}",
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            tag: tag,
            mobile: mobile,
            lead_id: leadId
        },
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).text('Add Tag');
            closeQuickModal('tag');
            toastr.success(res.message || 'Tag added successfully');
        },
        error: function(xhr) {
            $btn.prop('disabled', false).text('Add Tag');
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to add tag';
            toastr.error(msg);
        }
    });
}

function quickAddNote() { openQuickNoteModal(); }
function quickCreateFollowup() { openQuickFollowupModal(); }
function quickAddTag() { openQuickTagModal(); }

function copyCampaignPhone() {
    var phone = $('#camp-lead-phone').text();
    navigator.clipboard.writeText(phone);
    toastr.success('Phone number copied!');
}

/* ── Campaign Queue: Smart31 & 1600 toggle + VMN loading ─── */
function toggleCampSmart31(enabled) {
    if (enabled) {
        $('#camp_vmn1600_toggle').prop('checked', false);
        $('#camp-vmn1600-vmn-box').hide();
        $('#camp-smart31-vmn-box').show();
        loadCampSmart31Vmns();
    } else {
        $('#camp-smart31-vmn-box').hide();
        if ($('#camp_vmn1600_toggle').is(':checked')) {
            $('#camp-vmn1600-vmn-box').show();
            loadCamp1600Vmns();
        }
    }
}

function toggleCamp1600(enabled) {
    if (enabled) {
        $('#camp_smart31_toggle').prop('checked', false);
        $('#camp-smart31-vmn-box').hide();
        $('#camp-vmn1600-vmn-box').show();
        loadCamp1600Vmns();
    } else {
        $('#camp-vmn1600-vmn-box').hide();
        if ($('#camp_smart31_toggle').is(':checked')) {
            $('#camp-smart31-vmn-box').show();
            loadCampSmart31Vmns();
        }
    }
}

function loadCampSmart31Vmns() {
    if (!$('#camp_smart31_toggle').is(':checked')) return;
    var $btn   = $('#btn-campaign-call-toggle');
    var cType  = ($btn.attr('data-call-type') || 'webrtc').toLowerCase();
    $.ajax({
        url: "{{ route('dialerSmart31Vmns') }}",
        type: 'GET',
        data: { type: cType },
        dataType: 'json',
        success: function(res) {
            var $select = $('#camp_smart31_vmn_select').empty();
            if (res.success && res.vmns && res.vmns.length > 0) {
                $.each(res.vmns, function(i, item) {
                    $select.append($('<option>', { value: item.number, text: item.display }));
                });
                $('#camp-smart31-vmn-title').text(cType === 'click2call' ? 'Click2Call VMN' : 'WebRTC VMN');
            } else {
                $select.append('<option value="">No VMN available</option>');
            }
        },
        error: function() {
            $('#camp_smart31_vmn_select').empty().append('<option value="">Error loading VMNs</option>');
        }
    });
}

function loadCamp1600Vmns() {
    if (!$('#camp_vmn1600_toggle').is(':checked')) return;
    var $btn   = $('#btn-campaign-call-toggle');
    var cType  = ($btn.attr('data-call-type') || 'webrtc').toLowerCase();
    $.ajax({
        url: "{{ route('dialer1600Vmns') }}",
        type: 'GET',
        data: { type: cType },
        dataType: 'json',
        success: function(res) {
            var $select = $('#camp_vmn1600_vmn_select').empty();
            if (res.success && res.vmns && res.vmns.length > 0) {
                $.each(res.vmns, function(i, item) {
                    $select.append($('<option>', { value: item.number, text: item.display }));
                });
                $('#camp-vmn1600-vmn-title').text(cType === 'click2call' ? 'Click2Call 1600 VMN' : 'WebRTC 1600 VMN');
            } else {
                $select.append('<option value="">No 1600 VMN available</option>');
            }
        },
        error: function() {
            $('#camp_vmn1600_vmn_select').empty().append('<option value="">Error loading VMNs</option>');
        }
    });
}

/* ── Original VMN Options & Channel Configuration State ─── */
let originalVmnOptionsHtml = '';
let userChannelConfig = { webrtc_enabled: {{ ($webrtcEnabled ?? true) ? 'true' : 'false' }}, click2call_enabled: {{ ($c2cEnabled ?? true) ? 'true' : 'false' }} };

function updateChannelDropdownsVisibility() {
    var webrtc = !!userChannelConfig.webrtc_enabled;
    var c2c = !!userChannelConfig.click2call_enabled;

    // Both columns always sit side by side; a channel the user may not use is shown as "Not enabled"
    $('#c2c-vmn-group, #camp-c2c-vmn-group').toggleClass('is-channel-off', !c2c);
    $('#webrtc-vmn-group, #camp-webrtc-vmn-group').toggleClass('is-channel-off', !webrtc);
    syncVmnDetailsBox();
    onCampVmnChange();
}

function fetchChannelConfig() {
    $.ajax({
        url: "{{ route('dialerChannelConfig') }}",
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res && res.status === 'success') {
                userChannelConfig.webrtc_enabled = !!res.webrtc_enabled;
                userChannelConfig.click2call_enabled = !!res.click2call_enabled;
                updateChannelDropdownsVisibility();
                updateCallingButtonsVisibility();
                syncVmnDetailsBox();
                // Campaign Call card: show the call buttons for the user's saved channels
                renderCampaignCallingButtons(window._currentCampaignCallingType);
            }
        },
        error: function() {
            updateChannelDropdownsVisibility();
            updateCallingButtonsVisibility();
            syncVmnDetailsBox();
        }
    });
}

function applyDetectedChannel(type) {
    lgCallBtnsState();
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');

    var $c2cBtn = $('#btn-call-click2call');
    var $webrtcBtn = $('#btn-call-webrtc');
    var $hint = $('#calling-option-hint');
    var $badge = $('#cw-detected-type-badge');

    if (!mobile || mobile.length === 0) {
        // Each enabled channel's button is shown (greyed until a number is entered); none -> a clear message
        var noChannel = !userChannelConfig.click2call_enabled && !userChannelConfig.webrtc_enabled;
        $c2cBtn.css('display', userChannelConfig.click2call_enabled ? 'inline-flex' : 'none');
        $webrtcBtn.css('display', userChannelConfig.webrtc_enabled ? 'inline-flex' : 'none');
        if (noChannel) {
            $hint.html('<i class="fa fa-exclamation-circle" style="color: #EF4444;"></i> <span style="color: #EF4444;">Calling is not configured for your account. Ask an admin to enable Click2Call or WebRTC for you.</span>').show();
        } else {
            $hint.hide();
        }
        lgCallBtnsState();
        $badge.removeClass('badge-c2c badge-webrtc badge-both')
              .addClass('badge-empty')
              .html('<i class="fa fa-circle" style="font-size: 6px;"></i> <span id="cw-detected-type-text">Enter a number</span>');
        activeCallOption = '';
        $('#call_type').val('');
        return;
    }

    // Number is present: hide placeholder hint
    $hint.hide();

    var userC2c = !!userChannelConfig.click2call_enabled;
    var userWebRTC = !!userChannelConfig.webrtc_enabled;

    var effectiveType = type;
    if (!effectiveType) {
        effectiveType = (userC2c && userWebRTC) ? 'both' : (userWebRTC ? 'webrtc' : 'click2call');
    }

    if (effectiveType === 'both') {
        if (userC2c && userWebRTC) {
            $c2cBtn.css({'display': 'inline-flex', 'flex': '0 0 auto', 'width': 'auto'}).show();
            $webrtcBtn.css({'display': 'inline-flex', 'flex': '0 0 auto', 'width': 'auto'}).show();
            $badge.removeClass('badge-empty badge-c2c badge-webrtc')
                  .addClass('badge-both')
                  .html('<i class="fa fa-phone-volume" style="font-size: 10px;"></i> <span id="cw-detected-type-text">Click2Call & WebRTC</span>');
            activeCallOption = 'both';
            $('#call_type').val('both');
            syncVmnDetailsBox('click2call');
            return;
        } else if (userC2c) {
            effectiveType = 'click2call';
        } else if (userWebRTC) {
            effectiveType = 'webrtc';
        }
    }

    // Never offer a channel the user's account does not have (default or number-detected type)
    if (effectiveType === 'click2call' && !userC2c) effectiveType = userWebRTC ? 'webrtc' : 'none';
    if (effectiveType === 'webrtc' && !userWebRTC) effectiveType = userC2c ? 'click2call' : 'none';
    if (effectiveType === 'both') effectiveType = 'none';

    if (effectiveType === 'click2call') {
        $c2cBtn.css({'display': 'inline-flex', 'flex': '0 0 auto', 'width': 'auto'}).show();
        $webrtcBtn.hide();
        $badge.removeClass('badge-empty badge-webrtc badge-both')
              .addClass('badge-c2c')
              .html('<i class="fa fa-phone-alt" style="font-size: 10px;"></i> <span id="cw-detected-type-text">Click2Call</span>');
        activeCallOption = 'click2call';
        $('#call_type').val('click2call');
        syncVmnDetailsBox('click2call');
    } else if (effectiveType === 'webrtc') {
        $webrtcBtn.css({'display': 'inline-flex', 'flex': '0 0 auto', 'width': 'auto'}).show();
        $c2cBtn.hide();
        $badge.removeClass('badge-empty badge-c2c badge-both')
              .addClass('badge-webrtc')
              .html('<i class="fa fa-headset" style="font-size: 10px;"></i> <span id="cw-detected-type-text">WebRTC</span>');
        activeCallOption = 'webrtc';
        $('#call_type').val('webrtc');
        syncVmnDetailsBox('webrtc');
    } else {
        $c2cBtn.hide();
        $webrtcBtn.hide();
        $hint.html('<i class="fa fa-exclamation-circle" style="color: #EF4444;"></i> <span style="color: #EF4444;">Calling is not configured for your account. Ask an admin to enable Click2Call or WebRTC for you.</span>').show();
    }
    lgCallBtnsState();
}

function updateCallingButtonsVisibility() {
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    // Re-applied after the channel config is (re)loaded, so a newly assigned channel shows without retyping
    applyDetectedChannel(mobile ? lgUserCallType() : null);
}

/* ── Smart31 Only / Enabled 1600 (independent chips) ──── */
// Smart31 Only: shows its own VMN dropdown; the call uses that number while it is checked.
function toggleSmart31(enabled) {
    var $b = $('#cw-smart31-block').stop(true, true);
    if (enabled) {
        loadSmart31Vmns();
        $b.slideDown(180);
    } else {
        $b.slideUp(180);
    }
    lgCallBtnsState();   // Click2Call now uses (or stops using) the Smart31 VMN
}

// Enabled 1600: swaps the Click2Call VMN list to the 1600 numbers (as before).
function toggle1600(enabled) {
    if (enabled) {
        load1600Vmns();
    } else {
        if (originalVmnOptionsHtml) {
            $('#vmn_select').html(originalVmnOptionsHtml);
        }
        onVmnDropdownChange();
    }
}

function lgPoolOptions(res) {
    var opts = [];
    if (res && res.success && res.vmns) {
        $.each(res.vmns, function(i, item) {
            var num = (item.number || '').replace(/[^0-9]/g, '');
            if (num.length === 12 && num.startsWith('91')) num = num.substring(2);
            opts.push($('<option>', { value: item.number, text: '+91' + num, 'data-available': item.available === false ? 0 : 1, 'data-expiry': item.expiry || '' }));
        });
    }
    return opts;
}

function loadSmart31Vmns() {
    var $select = $('#smart31_vmn_select');
    $select.html('<option value="">Loading...</option>').prop('disabled', true);
    lgPaintSmart31(true);
    $.ajax({
        url: "{{ route('dialerSmart31Vmns') }}",
        type: 'GET',
        data: { type: 'click2call' },
        dataType: 'json',
        success: function(res) {
            var opts = lgPoolOptions(res);
            $select.empty().append(opts.length ? opts : '<option value="">No Smart31 number configured</option>');
            // configured number first: the first available one
            var $first = $select.find('option[data-available="1"]').first();
            if ($first.length) $select.val($first.val());
            $select.prop('disabled', !opts.length).trigger('change.select2');
            lgPaintSmart31();
        },
        error: function() {
            $select.html('<option value="">No Smart31 number configured</option>').prop('disabled', true).trigger('change.select2');
            lgPaintSmart31();
        }
    });
}

function lgPaintSmart31(loading) {
    var $line = $('#cw-smart31-status').removeClass('is-unavailable is-none');
    var info = lgVmnInfo($('#smart31_vmn_select'));
    var text;
    if (loading) { $line.addClass('is-none'); text = 'Loading...'; }
    else if (info.none) { $line.addClass('is-none'); text = 'Smart31 not configured'; }
    else if (!info.available) { $line.addClass('is-unavailable'); text = 'Expired' + (info.expiry ? ' · Expired on ' + info.expiry : ''); }
    else { text = 'Smart31 Active' + (info.expiry ? ' · Valid till ' + info.expiry : ''); }
    $line.find('.cw-vmn-status-text').text(text);
    $('#cw-smart31-help').toggle(!loading && info.none);
    lgCallBtnsState();
}

function load1600Vmns() {
    $.ajax({
        url: "{{ route('dialer1600Vmns') }}",
        type: 'GET',
        data: { type: 'click2call' },   // fills the Click2Call VMN dropdown
        dataType: 'json',
        success: function(res) {
            var opts = lgPoolOptions(res);
            $('#vmn_select').empty().append(opts.length ? opts : '<option value="">No Enabled 1600 number configured</option>');
            onVmnDropdownChange();
        }
    });
}

/* ── VMN Dropdown Synchronizers ─────────────────────────── */
function onClick2CallVmnChange() {
    var val = $('#vmn_select').val();
    if (val) {
        localStorage.setItem('leadger_c2c_vmn', val);
    }
    syncVmnDetailsBox('click2call');
}

function onWebRTCVmnChange() {
    var val = $('#webrtc_vmn_select').val();
    if (val) {
        localStorage.setItem('leadger_webrtc_vmn', val);
    }
    syncVmnDetailsBox('webrtc');
}

/**
 * Each VMN dropdown has one status line: Click2Call <- #vmn_select, WebRTC <- #webrtc_vmn_select.
 * Options carry data-available / data-expiry from Add Voucher (Smart31 / 1600 lists have no expiry = available).
 */
function lgVmnInfo($select) {
    var $opt = $select.find('option:selected');
    var val = $select.val() || '';
    if (!val) return { value: '', display: 'No number', available: false, none: true, expiry: '' };
    var num = val.replace(/[^0-9]/g, '');
    if (num.length === 12 && num.startsWith('91')) num = num.substring(2);
    var avail = String($opt.data('available')) !== '0';
    return { value: val, display: '+91' + num, available: avail, none: false, expiry: $opt.data('expiry') || '' };
}

// One line: dot + "Available · Valid till X" / "Expired · Expired on X" / "Not configured" / "Not enabled"; notes go in the tooltip.
function lgPaintVmnLine(info, lineSel, channelOn, noneNote, offNote) {
    var $line = $(lineSel).removeClass('is-unavailable is-none').removeAttr('title');
    var text;
    if (!channelOn) {
        $line.addClass('is-none').attr('title', offNote);
        text = 'Not enabled';
    } else if (info.none) {
        $line.addClass('is-none').attr('title', noneNote);
        text = 'Not configured';
    } else if (!info.available) {
        $line.addClass('is-unavailable');
        text = 'Expired' + (info.expiry ? ' · Expired on ' + info.expiry : '');
    } else {
        text = 'Available' + (info.expiry ? ' · Valid till ' + info.expiry : '');
    }
    if (!channelOn && info.expiry) text += ' · Valid till ' + info.expiry;
    $line.find('.cw-vmn-status-text').text(text);
}

function lgChannelOn(channel) {
    return !$(channel === 'webrtc' ? '#webrtc-vmn-group' : '#c2c-vmn-group').hasClass('is-channel-off');
}

function syncVmnDetailsBox() {
    var c2c = lgVmnInfo($('#vmn_select'));
    var web = lgVmnInfo($('#webrtc_vmn_select'));
    $('#vmn').val(c2c.value);
    var opt = lgC2cOption();
    lgPaintVmnLine(c2c, '#cw-c2c-status', lgChannelOn('click2call'),
        opt ? 'No ' + opt + ' number is configured for Click2Call. Uncheck ' + opt + ' to call from your Click2Call VMN.'
            : 'No Click2Call numbers available. Add one in Configuration → Add Voucher.',
        'Click2Call calling is not enabled for your account. An admin can enable it in Call Channels.');
    lgPaintVmnLine(web, '#cw-webrtc-status', lgChannelOn('webrtc'),
        'No WebRTC numbers available. Add one in Configuration → Add Voucher.',
        'WebRTC calling is not enabled for your account. An admin can enable it in Call Channels.');
    lgCallBtnsState();
}

/** Option that changed the Click2Call VMN list: 'Enabled 1600' or '' (plain Click2Call VMN). Smart31 has its own dropdown. */
function lgC2cOption() {
    return $('#vmn1600_toggle').is(':checked') ? 'Enabled 1600' : '';
}

/**
 * Returns the usable VMN for a channel ('click2call', 'webrtc' or 'smart31'), or null after telling the user why it cannot be used.
 * $sel / channelOn default to the Manual Dial dropdowns; the Campaign Call card passes its own.
 */
function lgUsableVmn(channel, $sel, channelOn) {
    $sel = $sel || (channel === 'webrtc' ? $('#webrtc_vmn_select') : (channel === 'smart31' ? $('#smart31_vmn_select') : $('#vmn_select')));
    if (channelOn === undefined) channelOn = lgChannelOn(channel === 'webrtc' ? 'webrtc' : 'click2call');
    var info = lgVmnInfo($sel);
    var label = channel === 'webrtc' ? 'WebRTC' : (channel === 'smart31' ? 'Smart31' : 'Click2Call');
    if (!channelOn) {
        toastr.error((channel === 'webrtc' ? 'WebRTC' : 'Click2Call') + ' calling is not enabled for your account.');
        return null;
    }
    if (info.none) {
        var opt = channel === 'smart31' ? 'Smart31 Only' : (channel === 'webrtc' ? '' : lgC2cOption());
        toastr.error(opt ? 'No ' + opt.replace(' Only', '') + ' number is configured for Click2Call. Uncheck ' + opt + ' to call from your Click2Call VMN.'
                         : 'No ' + label + ' VMN selected. Add or enable a ' + label + ' number in Configuration > Add Voucher.');
        return null;
    }
    if (!info.available) {
        toastr.error(label + ' VMN ' + info.display + ' has expired' + (info.expiry ? ' (' + info.expiry + ')' : '') + '. Choose another number.');
        return null;
    }
    var num = info.value.replace(/[^0-9]/g, '');
    if (num.length === 12 && num.startsWith('91')) num = num.substring(2);
    return num;
}

/* ── Campaign Call card VMNs (#camp_c2c_vmn_select / #camp_webrtc_vmn_select) ── */
function lgCampChannelOn(channel) {
    return !$(channel === 'webrtc' ? '#camp-webrtc-vmn-group' : '#camp-c2c-vmn-group').hasClass('is-channel-off');
}

function onCampVmnChange() {
    lgPaintVmnLine(lgVmnInfo($('#camp_c2c_vmn_select')), '#camp-c2c-status', lgCampChannelOn('click2call'),
        'No Click2Call numbers available. Add one in Configuration → Add Voucher.',
        'Click2Call calling is not enabled for your account. An admin can enable it in Call Channels.');
    lgPaintVmnLine(lgVmnInfo($('#camp_webrtc_vmn_select')), '#camp-webrtc-status', lgCampChannelOn('webrtc'),
        'No WebRTC numbers available. Add one in Configuration → Add Voucher.',
        'WebRTC calling is not enabled for your account. An admin can enable it in Call Channels.');
    lgCampBtnsVmnState();
}

/**
 * Campaign call buttons: each is enabled only while its own VMN on this card is usable (selected, not expired).
 * Smart31 Only / Enabled 1600 calls use their own numbers (handled in placeCampaignCall), so they are not gated here.
 * Buttons in a call (spinner / End Call) are left alone.
 */
function lgCampBtnsVmnState() {
    if ($('#camp_smart31_toggle').is(':checked') || $('#camp_vmn1600_toggle').is(':checked')) {
        $('#btn-camp-c2c, #btn-camp-webrtc').filter('[data-vmn-blocked]').prop('disabled', false).removeAttr('data-vmn-blocked title');
        return;
    }
    [['#btn-camp-c2c', '#camp_c2c_vmn_select', 'Click2Call'], ['#btn-camp-webrtc', '#camp_webrtc_vmn_select', 'WebRTC']].forEach(function (c) {
        var $btn = $(c[0]);
        if (!$btn.length || $btn.hasClass('on-call') || $btn.find('.fa-spinner').length) return;
        var ok = lgVmnUsable($(c[1]));
        $btn.prop('disabled', !ok).attr('title', ok ? '' : 'No available ' + c[2] + ' VMN: choose a valid, unexpired number');
        if (ok) $btn.removeAttr('data-vmn-blocked'); else $btn.attr('data-vmn-blocked', '1');
    });
}
$(document).on('change', '#camp_smart31_toggle, #camp_vmn1600_toggle', lgCampBtnsVmnState);

/** VMN for a campaign call from the Campaign Call card, or null (with a message) when it is missing, expired or the channel is off. */
function lgCampUsableVmn(channel) {
    var $sel = $(channel === 'webrtc' ? '#camp_webrtc_vmn_select' : '#camp_c2c_vmn_select');
    var on = lgCampChannelOn(channel);
    if (on && lgVmnInfo($sel).none) {
        var label = channel === 'webrtc' ? 'WebRTC' : 'Click2Call';
        toastr.error('No ' + label + ' VMN selected. Add or enable a ' + label + ' number in Configuration > Add Voucher.');
        return null;
    }
    return lgUsableVmn(channel, $sel, on);
}

$(function() { onCampVmnChange(); });

/** True when the dropdown has a selected VMN that is available (not expired). */
function lgVmnUsable($select) {
    var info = lgVmnInfo($select);
    return !info.none && info.available;
}

/**
 * Call buttons stay visible but grey until a valid (10+ digit) number is entered, while calling is closed, and
 * while that channel has no usable VMN (Click2Call: the Smart31 VMN when "Smart31 Only" is ticked, else the
 * Click2Call VMN; WebRTC: the WebRTC VMN). Each button is checked on its own.
 */
function lgCallBtnsState() {
    var numberOk = ($('#mobile').val() || '').replace(/[^0-9]/g, '').length >= 10;
    var closed = !!window.callingHoursClosed;
    var c2cVmnOk = lgVmnUsable($($('#smart31_toggle').is(':checked') ? '#smart31_vmn_select' : '#vmn_select'));
    var webVmnOk = lgVmnUsable($('#webrtc_vmn_select'));
    var reason = function (vmnOk, label) {
        return closed ? 'Calling is closed' : (!vmnOk ? 'No available ' + label + ' VMN: choose a valid, unexpired number' : '');
    };
    $('#btn-call-click2call').prop('disabled', !numberOk || closed || !c2cVmnOk).attr('title', reason(c2cVmnOk, 'Click2Call'));
    $('#btn-call-webrtc').prop('disabled', !numberOk || closed || !webVmnOk).attr('title', reason(webVmnOk, 'WebRTC'));
}

// Backward-compatibility aliases
function onVmnDropdownChange() {
    syncVmnDetailsBox();
}
function syncVmnCardDisplay() {
    syncVmnDetailsBox();
}

/* ── Recent Numbers Helper ───────────────────────────────── */
function fillRecentNumber(num) {
    $('#mobile').val(num).trigger('input');
    evaluatePhoneNumber();
}

/* ── Dynamic Calling Option Detection (Debounced ~300ms) ─── */
let _detectTimeout = null;

function evaluatePhoneNumber() {
    clearTimeout(_detectTimeout);
    _detectTimeout = setTimeout(function() {
        doDetectPhoneNumber();
        updateQuickActionButtonsState();
    }, 250);
}

function doDetectPhoneNumber() {
    var raw = $('#mobile').val() || '';
    var mobile = raw.replace(/[^0-9]/g, '');

    if (!mobile || mobile.length === 0) {
        applyDetectedChannel(null);
        return;
    }

    // Immediately hide placeholder when number has digits
    $('#calling-option-hint').hide();

    // The call buttons follow the logged-in user's own channels (Click2Call and/or WebRTC), never the dialed
    // number's history, its lead's campaign or another user's settings: a user with both always gets both.
    applyDetectedChannel(lgUserCallType());
}

/** 'both' | 'click2call' | 'webrtc' | 'none' from the user's saved channels. */
function lgUserCallType() {
    var c2c = !!userChannelConfig.click2call_enabled;
    var web = !!userChannelConfig.webrtc_enabled;
    return c2c && web ? 'both' : (c2c ? 'click2call' : (web ? 'webrtc' : 'none'));
}

/* ── Live Call Status Container (Inside Card) & Floating Toast ─── */
/* ── Live Call Status Container (Inside Card) & Floating Toast ─── */
var _liveTimerInterval = null;
var _liveTimerSeconds = 0;
window._currentCallChannel = '';

function stopLiveTimer() {
    if (_liveTimerInterval) {
        clearInterval(_liveTimerInterval);
        _liveTimerInterval = null;
    }
    _liveTimerSeconds = 0;
}

function sendCallStateUpdate(id, uuid, state, duration) {
    if (!id && !uuid) return;
    var leadId = $('#manual_lead_id').val() || $('#current_lead_id').val() || '';
    var campaignId = $('#manual_campaign_id').val() || $('#current_campaign_id').val() || '';

    $.ajax({
        url: "{{ route('dialerUpdateCallState') }}",
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            id: id,
            uuid: uuid,
            state: state,
            duration: duration || 0,
            lead_id: leadId,
            campaign_id: campaignId
        },
        dataType: 'json',
        success: function(res) {
            // Updated call log state live
        }
    });
}

function lgFmtCallSecs(sec) {
    sec = Math.max(0, parseInt(sec, 10) || 0);
    var h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
    var pad = function (n) { return String(n).padStart(2, '0'); };
    return h > 0 ? h + ':' + pad(m) + ':' + pad(s) : pad(m) + ':' + pad(s);
}

function showLiveCallStatus(state, title, desc, channel, seconds) {
    if (channel) {
        window._currentCallChannel = channel;
    }
    var $box = $('#cw-live-status-container');
    var $icon = $('#cw-live-status-icon');
    var $text = $('#cw-live-status-text');
    var $timer = $('#cw-live-status-timer');
    var $hangup = $('#btn-cw-hangup');

    clearTimeout(window._liveStatusHideTimer);
    $box.removeClass('state-ringing state-connected state-failed state-ended');

    // Click2Call calls: no End Call button. WebRTC calls: End Call button while ringing or connected.
    var isWebRtcCall = (window._currentCallChannel === 'webrtc');

    if (state === 'ringing') {
        stopLiveTimer();
        $box.addClass('state-ringing').fadeIn(150);
        $icon.html('<i class="fa fa-spinner fa-spin" style="color: #D97706;"></i>');
        $text.text(title || 'Calling...');
        $timer.hide();
        if (isWebRtcCall) {
            $hangup.show();
        } else {
            $hangup.hide();
        }
    } else if (state === 'connected') {
        // Only reached on a confirmed connection (SIP Established / provider customer-connected time)
        $box.addClass('state-connected').fadeIn(150);
        $icon.html('<i class="fa fa-check-circle" style="color: #0FB77A;"></i>');
        $text.text(title || 'Call Successful / Connected');
        if (isWebRtcCall) {
            $hangup.show();
        } else {
            $hangup.hide();
        }
        if (!_liveTimerInterval) {   // repeated status polls keep the running timer
            _liveTimerSeconds = parseInt(seconds, 10) || 0;
            $timer.show().text(lgFmtCallSecs(_liveTimerSeconds));
            _liveTimerInterval = setInterval(function() {
                _liveTimerSeconds++;
                $timer.text(lgFmtCallSecs(_liveTimerSeconds));
            }, 1000);
            if (typeof startCampaignTimer === 'function') startCampaignTimer(parseInt(seconds, 10) || 0);
        }
    } else if (state === 'ended') {
        // The call was connected and has now ended: stop timers, keep the final talk time visible
        var finalSecs = (seconds !== undefined && seconds !== null) ? seconds : _liveTimerSeconds;
        stopLiveTimer();
        if (typeof stopCampaignTimer === 'function') stopCampaignTimer();
        $hangup.hide();
        window._currentCallChannel = '';
        hideCallToast();
        $box.addClass('state-ended').fadeIn(150);
        $icon.html('<i class="fa fa-phone-slash" style="color: #64748B;"></i>');
        $text.text(title || 'Call Ended');
        $timer.show().text(lgFmtCallSecs(finalSecs));
        window._liveStatusHideTimer = setTimeout(function() {
            $box.fadeOut(300);
        }, 10000);
    } else if (state === 'failed' || state === 'completed') {
        stopLiveTimer();
        if (typeof stopCampaignTimer === 'function') stopCampaignTimer();
        $hangup.hide();
        window._currentCallChannel = '';
        $box.addClass('state-failed').fadeIn(150);
        $icon.html('<i class="fa fa-times-circle" style="color: #EF4444;"></i>');
        $text.text(title || 'Call Failed');
        $timer.hide();
        window._liveStatusHideTimer = setTimeout(function() {
            $box.fadeOut(300);
        }, 6000);
    }
}

let _toastDismissTimer = null;

function showCallToast(state, title, desc) {
    clearTimeout(_toastDismissTimer);
    var $toast = $('#cw-call-toast');
    var $icon = $('#cw-toast-icon');

    var wasRunning = $toast.is(':visible') && $toast.is('.is-call:not(.is-blocked)');
    $toast.removeClass('state-dialing state-ringing state-connected state-error is-leaving');
    $toast.addClass('state-' + state);

    // Calling / Ringing / Call Blocked use the green calling style; other toasts keep theirs
    var blocked = title === 'Call Blocked';
    var live = state === 'dialing' || state === 'ringing';
    $toast.toggleClass('is-blocked', blocked).toggleClass('is-call', blocked || live);
    if (blocked) $toast.attr({role: 'alert', 'aria-live': 'assertive'});
    else if (live) $toast.attr({role: 'status', 'aria-live': 'polite'});
    else $toast.removeAttr('role aria-live');

    // Number as already masked by the caller ("91549xxxxx"), shown as "91549 xxxxx"
    var m = /^Calling\s+(\S+?)\.*$/.exec(title || '');
    if (state === 'dialing' && m) window._toastNumber = m[1].replace(/^(\d+)(x+)$/i, '$1 $2');
    if (live) {
        title = (state === 'dialing' ? 'Calling ' : 'Ringing ') + (window._toastNumber || '');
        if (state === 'ringing') desc = 'Waiting for the customer to answer...';
    }
    var ch = window._currentCallChannel;
    $('#cw-toast-via').text(live && ch ? 'via ' + (ch === 'webrtc' ? 'WebRTC' : 'Click2Call') : '');

    if (state === 'dialing') {
        $icon.html('<span class="cw-toast-ring" aria-hidden="true"></span><i class="fa fa-phone cw-toast-ring-static" aria-hidden="true"></i>');
    } else if (state === 'ringing') {
        $icon.html('<i class="fa fa-phone-volume" aria-hidden="true"></i>');
    } else if (state === 'connected') {
        $icon.html('<i class="fa fa-check-circle"></i>');
        _toastDismissTimer = setTimeout(function() {
            hideCallToast();
        }, 3000);
    } else if (state === 'error') {
        $icon.html('<i class="fa fa-exclamation-circle"></i>');
        _toastDismissTimer = setTimeout(function() {
            hideCallToast();
        }, blocked ? 6000 : 4000);
    }

    $('#cw-toast-title').text(title);
    $('#cw-toast-desc').text(desc || '');
    if (live && wasRunning) return;   // Calling -> Ringing updates in place; the progress bar keeps going
    if (live) { var $bar = $toast.find('.cw-toast-progress'); $bar.replaceWith($bar.clone()); }   // restart the bar
    if ($toast.hasClass('is-call')) $toast.stop(true, true).css('opacity', '').show();   // CSS slide-in
    else $toast.fadeIn(200);
}

// "Call Blocked": hovering pauses the auto-dismiss, leaving restarts it
$(document).on('mouseenter', '#cw-call-toast.is-blocked', function () { clearTimeout(_toastDismissTimer); })
           .on('mouseleave', '#cw-call-toast.is-blocked', function () {
               clearTimeout(_toastDismissTimer);
               _toastDismissTimer = setTimeout(hideCallToast, 6000);
           });

// Remove the calling toast at once (moving to another lead / leaving the page) and stop its dismiss timer.
// Like hideCallToast, it never touches the call itself.
function lgClearCallToast() {
    try { clearTimeout(_toastDismissTimer); } catch (e) { /* toast timer not set up yet */ }
    window._toastNumber = '';
    $('#cw-call-toast').stop(true, true).removeClass('is-leaving').hide();
}
window.addEventListener('pagehide', lgClearCallToast);

// Only hides the toast; an active call is never cancelled from here
function hideCallToast() {
    clearTimeout(_toastDismissTimer);
    var $toast = $('#cw-call-toast');
    if ($toast.hasClass('is-call') && $toast.is(':visible')) {
        $toast.addClass('is-leaving');
        setTimeout(function () { if ($toast.hasClass('is-leaving')) $toast.hide().removeClass('is-leaving'); }, 200);
    } else {
        $toast.fadeOut(200);
    }
}

function hangupActiveCall() {
    stopCallPolling();
    if (typeof sipHasActiveCall === 'function' && sipHasActiveCall()) {
        sipHangup();   // onCallEnded shows "Call Ended" / "Cancelled" and saves the real outcome
        return;
    }
    window._sipCallInProgress = false;
    $('#btn-call-click2call').prop('disabled', false).find('i').attr('class', 'fa fa-phone-alt');
    $('#btn-call-webrtc').prop('disabled', false).find('i').attr('class', 'fa fa-headset');
    showLiveCallStatus('ended', 'Call Ended', '', 'webrtc');
}

/* ── Status Polling for Click2Call ───────────────────────── */
var _callPollInterval = null;
var _callPollTimeout = null;

function stopCallPolling() {
    if (_callPollInterval) {
        clearInterval(_callPollInterval);
        _callPollInterval = null;
    }
    if (_callPollTimeout) {
        clearTimeout(_callPollTimeout);
        _callPollTimeout = null;
    }
}

function pollCallStatus(callId, callUuid, $btn) {
    pollClick2CallStatus(callId, callUuid, false, $btn, 'fa fa-phone-alt');
}

/*
 * Click2Call status from the provider's real call record (via /dialer/call-status).
 * The server saves the final result; the page only displays it. No timeout guesses: if the provider
 * has not reported the call after 15 minutes, the page says so instead of inventing an outcome.
 */
function pollClick2CallStatus(callId, callUuid, isCampaign, $btn, iconClass) {
    stopCallPolling();
    window._activeCallIsCampaign = !!isCampaign;
    var startTime = Date.now();
    var inFlight = false;
    var resetBtn = function () { $btn.prop('disabled', false).find('i').attr('class', iconClass); };
    var finish = function () {
        stopCallPolling();
        resetBtn();
        if (isCampaign) markCampaignCallEnded();
        refreshTodayPerformance();
        refreshRecentActivity();
    };
    var failLabels = { busy: 'Busy', no_answer: 'No Answer', failed: 'Failed' };

    _callPollInterval = setInterval(function() {
        if (Date.now() - startTime >= 15 * 60 * 1000) {
            stopCallPolling();
            resetBtn();
            showLiveCallStatus('failed', 'Call result not available', 'The provider has not reported this call yet', 'click2call');
            return;
        }
        if (inFlight) return;
        inFlight = true;

        $.ajax({
            url: "{{ route('dialerCallStatus') }}",
            type: 'GET',
            data: { id: callId, uuid: callUuid, is_campaign: isCampaign ? 1 : 0 },
            dataType: 'json',
            complete: function () { inFlight = false; },
            success: function(res) {
                if (!res || !_callPollInterval) return;
                var st = res.status;
                if (st === 'calling') {
                    showLiveCallStatus('ringing', 'Calling...', '', 'click2call');
                } else if (st === 'ringing') {
                    showLiveCallStatus('ringing', 'Ringing...', '', 'click2call');
                    showCallToast('ringing', 'Ringing...', res.message || 'Customer phone is ringing');
                } else if (st === 'connected') {
                    if (!_liveTimerInterval) showCallToast('connected', 'Call Successful / Connected', 'Both parties connected');
                    showLiveCallStatus('connected', 'Call Successful / Connected', '', 'click2call', res.connected_for || 0);
                } else if (st === 'ended') {
                    showLiveCallStatus('ended', 'Call Ended', '', 'click2call', res.duration || 0);
                    finish();
                } else if (failLabels[st]) {
                    showLiveCallStatus('failed', failLabels[st], res.message || '', 'click2call');
                    showCallToast('error', failLabels[st], res.message || '');
                    finish();
                }
            }
        });
    }, 2000);
}

/* ── Click2Call Initiation ───────────────────────────────── */
function startClick2Call() {
    if (window.callingHoursClosed) {
        showCallToast('error', 'Calling Closed', window.callingHoursNotice || 'Calling is currently closed.');
        return;
    }

    var raw = $('#mobile').val() || $('#camp-lead-phone').text() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    if (!mobile || mobile.length < 10) {
        toastr.error('Please enter a valid 10-digit phone number');
        return;
    }

    window._currentCallChannel = 'click2call';
    syncVmnDetailsBox('click2call');

    var isSmart31 = $('#smart31_toggle').is(':checked');
    var is1600 = $('#vmn1600_toggle').is(':checked');
    var vmn = lgUsableVmn(isSmart31 ? 'smart31' : 'click2call');   // Smart31 VMN or Click2Call VMN; blocked when expired / not configured
    if (!vmn) return;

    var $btn = $('#btn-call-click2call');
    $btn.prop('disabled', true).find('i').attr('class', 'fa fa-spinner fa-spin');

    showLiveCallStatus('ringing', 'Calling...', '', 'click2call');
    var maskedPhone = mobile.length > 5 ? mobile.substring(0, 5) + 'xxxxx' : mobile;
    showCallToast('dialing', 'Calling ' + maskedPhone + '...', 'Initiating gateway call...');

    var leadId = $('#manual_lead_id').val() || $('#current_lead_id').val() || '';
    var campaignId = $('#manual_campaign_id').val() || $('#current_campaign_id').val() || '';

    $.ajax({
        type: 'POST',
        url: "{{ route('dialerClick2call') }}",
        data: {
            '_token': "{{ csrf_token() }}",
            mobile: mobile,
            vmn: vmn,
            lead_id: leadId,
            campaign_id: campaignId,
            source: 'manual',
            smart31_enabled: isSmart31 ? 1 : 0,
            vmn1600_enabled: is1600 ? 1 : 0
        },
        dataType: 'json',
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) {}
            }
            if (response && response.success) {
                $('#uuid').val(response.uuid || '');
                $('#id').val(response.id || '');
                $('#is_campaign').val(response && response.is_campaign ? '1' : '0');
                $('#call_type').val('click2call');

                sendCallStateUpdate(response.id, response.uuid, 'initiated');
                pollCallStatus(response.id, response.uuid, $btn);
            } else {
                $btn.prop('disabled', false).find('i').attr('class', 'fa fa-phone-alt');
                showLiveCallStatus('failed', 'Call Blocked', (response && response.message) || 'Gateway could not place call', 'click2call');
                showCallToast('error', 'Call Blocked', (response && response.message) || 'Gateway could not place call');
            }
        },
        error: function(xhr) {
            $btn.prop('disabled', false).find('i').attr('class', 'fa fa-phone-alt');
            var msg = 'Failed to initiate Click2Call';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showLiveCallStatus('failed', 'Call Error', msg, 'click2call');
            showCallToast('error', 'Call Blocked', msg);
        }
    });
}

/* ── WebRTC Initiation ───────────────────────────────────── */
function startWebRTCCall() {
    if (window.callingHoursClosed) {
        showCallToast('error', 'Calling Closed', window.callingHoursNotice || 'Calling is currently closed.');
        return;
    }

    var raw = $('#mobile').val() || $('#camp-lead-phone').text() || '';
    var mobile = raw.replace(/[^0-9]/g, '');
    if (!mobile || mobile.length < 10) {
        toastr.error('Please enter a valid 10-digit phone number');
        return;
    }

    if (window._sipCallInProgress) {
        toastr.warning('A call is already in progress');
        return;
    }

    window._currentCallChannel = 'webrtc';
    window._activeCallIsCampaign = false;
    syncVmnDetailsBox('webrtc');

    var isSmart31 = $('#smart31_toggle').is(':checked');
    var is1600 = $('#vmn1600_toggle').is(':checked');
    var vmn = lgUsableVmn('webrtc');   // WebRTC VMN only (never the Click2Call number); blocked when expired / not configured
    if (!vmn) return;

    var $btn = $('#btn-call-webrtc');
    $btn.prop('disabled', true).find('i').attr('class', 'fa fa-spinner fa-spin');
    window._sipCallInProgress = true;

    showLiveCallStatus('ringing', 'Calling...', '', 'webrtc');
    var maskedPhone = mobile.length > 5 ? mobile.substring(0, 5) + 'xxxxx' : mobile;
    showCallToast('dialing', 'Calling ' + maskedPhone + '...', 'Connecting SIP softphone...');

    var leadId = $('#manual_lead_id').val() || $('#current_lead_id').val() || '';
    var campaignId = $('#manual_campaign_id').val() || $('#current_campaign_id').val() || '';

    $.ajax({
        type: 'POST',
        url: "{{ route('dialerwebrtc') }}",
        data: {
            '_token': "{{ csrf_token() }}",
            mobile: mobile,
            vmn: vmn,
            lead_id: leadId,
            campaign_id: campaignId,
            source: 'manual',
            smart31_enabled: isSmart31 ? 1 : 0,
            vmn1600_enabled: is1600 ? 1 : 0
        },
        dataType: 'json',
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) {}
            }
            if (response && response.success) {
                $('#uuid').val(response.uuid || '');
                $('#id').val(response.id || '');
                $('#is_campaign').val(response && response.is_campaign ? '1' : '0');
                $('#call_type').val('webrtc');

                sendCallStateUpdate(response.id, response.uuid, 'initiated');

                if (typeof window._softphoneReady === 'undefined') {
                    showLiveCallStatus('failed', 'SIP Error', 'Softphone module not ready', 'webrtc');
                    showCallToast('error', 'SIP Error', 'SIP softphone module not ready');
                    window._sipCallInProgress = false;
                    $btn.prop('disabled', false).find('i').attr('class', 'fa fa-headset');
                    return;
                }

                window._softphoneReady.then(function(sp) {
                    sp.initSoftphone({
                        username:  response.sip_id       || '',
                        password:  response.sip_password || '',
                        domain:    response.sip_ip       || '',
                        websocket: response.sip_ws,
                    })
                    .then(function() {
                        return sp.makeSoftphoneCall(mobile, {
                            callId:  response.call_id,
                            refId:   response.ref_id,
                            agent:   response.sip_id,
                            did:     response.did,
                            gateway: response.gateway,
                            prefix:  response.gateway_prefix,
                            vmn:     vmn,
                        });
                    })
                    .catch(function(err) {
                        console.error('SIP call error:', err);
                        showLiveCallStatus('failed', 'Call Failed', err.message || 'SIP connection error', 'webrtc');
                        showCallToast('error', 'Call Failed', err.message || 'SIP call could not connect');
                        window._sipCallInProgress = false;
                        $btn.prop('disabled', false).find('i').attr('class', 'fa fa-headset');
                        sendCallStateUpdate(response.id, response.uuid, 'failed');
                    });
                });
            } else {
                window._sipCallInProgress = false;
                $btn.prop('disabled', false).find('i').attr('class', 'fa fa-headset');
                showLiveCallStatus('failed', 'Call Blocked', (response && response.message) || 'WebRTC initiation failed', 'webrtc');
                showCallToast('error', 'Call Blocked', (response && response.message) || 'WebRTC initiation failed');
            }
        },
        error: function(xhr) {
            window._sipCallInProgress = false;
            $btn.prop('disabled', false).find('i').attr('class', 'fa fa-headset');
            var msg = 'Failed to initiate WebRTC call';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showLiveCallStatus('failed', 'Call Error', msg, 'webrtc');
            showCallToast('error', 'Call Blocked', msg);
        }
    });
}

// Aliases for compatibility
function click2call() { startClick2Call(); }
function webrtc() { startWebRTCCall(); }

/* ── Today's Performance & Recent Activity Loaders ───────── */
function refreshTodayPerformance() {
    $.ajax({
        url: "{{ route('dialerTodayPerformance') }}",
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res && res.status === 'success' && res.data) {
                var d = res.data;
                $('#perf-calls-made').text(d.calls_made || 0);
                $('#perf-connected').text(d.connected || 0);
                $('#perf-avg-talk').text(d.avg_talk_time || '00:00');
                $('#perf-followups').text(d.followups || 0);
                $('#perf-target-badge').text('Target: ' + (d.target || 50));
                $('#perf-target-text').text((d.percent_completed || 0) + '% Completed');
                $('#perf-progress-fill').css('width', (d.percent_completed || 0) + '%');
            }
        }
    });
}

function refreshRecentActivity() {
    $.ajax({
        url: "{{ route('dialerRecentActivity') }}",
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            var $list = $('#cw-recent-activity-list').empty();
            if (res && res.status === 'success' && res.data && res.data.length > 0) {
                $.each(res.data, function(i, item) {
                    var badgeStyle = '';
                    if (item.badge_class === 'green') {
                        badgeStyle = 'background: var(--cw-green-light); color: var(--cw-green);';
                    } else if (item.badge_class === 'amber') {
                        badgeStyle = 'background: var(--cw-amber-light); color: var(--cw-amber);';
                    } else if (item.badge_class === 'red') {
                        badgeStyle = 'background: var(--cw-red-light); color: var(--cw-red);';
                    } else {
                        badgeStyle = 'background: var(--cw-blue-light); color: var(--cw-blue);';
                    }

                    var borderBottom = (i < res.data.length - 1) ? 'border-bottom: 1px solid #F1F5F9;' : '';
                    var phoneToDial = item.phone_number || '';

                    var rowHtml = `
                        <div class="cw-recent-activity-row" style="padding: 7px 0; ${borderBottom} display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="fillRecentNumber('${phoneToDial}')" title="Click to dial ${phoneToDial}">
                            <div style="display: flex; flex-direction: column;">
                                <span>Call to <strong>${item.lead_name || phoneToDial || 'Unknown'}</strong></span>
                                <span style="font-size: 11px; color: var(--cw-text-sub);">${item.time_ago || ''}</span>
                            </div>
                            <span class="cw-badge-pill" style="${badgeStyle}">${item.status || 'Connected'}</span>
                        </div>
                    `;
                    $list.append(rowHtml);
                });
            } else {
                $list.html('<div class="text-center py-3 text-muted" style="font-size: 12px;"><i class="fa fa-phone-slash" style="margin-right: 4px;"></i> No calls made today yet</div>');
            }
        },
        error: function() {
            $('#cw-recent-activity-list').html('<div class="text-center py-2 text-danger" style="font-size: 12px;">Failed to load activity</div>');
        }
    });
}

// Wire phone input events & initialize page
$(function() {
    originalVmnOptionsHtml = $('#vmn_select').html();

    var savedC2c = localStorage.getItem('leadger_c2c_vmn');
    if (savedC2c && $('#vmn_select option[value="' + savedC2c + '"]').length > 0) {
        $('#vmn_select').val(savedC2c);
    }
    var savedWebrtc = localStorage.getItem('leadger_webrtc_vmn');
    if (savedWebrtc && $('#webrtc_vmn_select option[value="' + savedWebrtc + '"]').length > 0) {
        $('#webrtc_vmn_select').val(savedWebrtc);
    }

    $('#mobile').on('input keyup paste', function() {
        evaluatePhoneNumber();
        updateQuickActionButtonsState();
    });

    syncVmnDetailsBox();
    fetchChannelConfig();
    doDetectPhoneNumber();
    refreshTodayPerformance();
    refreshRecentActivity();

    var initialSelectedCamp = $('#cw-workspace-campaign-select').val();
    if (initialSelectedCamp && currentLeadId) {
        renderCampaignCallingButtons(window._currentCampaignCallingType);
    } else {
        renderEmptyCampaignLeadCard();
        renderCampaignCallingButtons('');
    }
});
</script>

<script type="module">
(async () => {
    let sp;
    try {
        sp = await import("{{ asset('js/webrtc-softphone.js') }}?v={{ time() }}");
        window._softphoneReady = Promise.resolve(sp);
    } catch (e) {
        console.error('Softphone module failed to load', e);
        return;
    }
    const callIds = () => [document.getElementById('id')?.value, document.getElementById('uuid')?.value];

    // Real SIP events only: 180/183 -> Ringing, 200 OK (Established) -> Connected, hangup -> result
    sp.onCallRinging(() => {
        showLiveCallStatus('ringing', 'Ringing...', '', 'webrtc');
        showCallToast('ringing', 'Ringing...', 'Customer phone is ringing');
        sendCallStateUpdate(...callIds(), 'ringing');
    });

    sp.onCallEstablished(() => {
        showCallToast('connected', 'Call Successful / Connected', 'Both parties connected');
        showLiveCallStatus('connected', 'Call Successful / Connected', '', 'webrtc', 0);
        const btn = document.getElementById('btn-call-webrtc');
        if (btn) btn.disabled = false;
        sendCallStateUpdate(...callIds(), 'answered');
    });

    sp.onCallEnded((info) => {
        window._sipCallInProgress = false;
        const outcome = info?.outcome || 'completed';
        const duration = info?.duration || 0;
        sendCallStateUpdate(...callIds(), outcome, duration);

        if (outcome === 'completed') {
            showLiveCallStatus('ended', 'Call Ended', '', 'webrtc', duration);
        } else {
            const labels = {
                busy: ['Busy', 'Customer line is busy'],
                'no-answer': ['No Answer', 'Customer did not answer'],
                cancelled: ['Cancelled', 'Call cancelled before it was answered'],
                failed: ['Failed', 'The call could not be connected'],
            };
            const [title, desc] = labels[outcome] || labels.failed;
            showLiveCallStatus('failed', title, desc, 'webrtc');
            showCallToast('error', title, desc);
        }

        $('#btn-call-webrtc').prop('disabled', false).find('i').attr('class', 'fa fa-headset');
        if (window._activeCallIsCampaign && typeof markCampaignCallEnded === 'function') markCampaignCallEnded();
        if (typeof refreshTodayPerformance === 'function') refreshTodayPerformance();
        if (typeof refreshRecentActivity === 'function') refreshRecentActivity();
    });

    globalThis.sipHangup = e => { e?.preventDefault?.(); sp.hangupCall(); };
    globalThis.sipMute   = e => { e?.preventDefault?.(); sp.muteCall(); };
    globalThis.sipHold   = e => { e?.preventDefault?.(); sp.holdCall(); };
    globalThis.sipHasActiveCall = () => sp.hasActiveCall();
})();
</script>
{{-- Opened from a link (Inbound Calls customer mobile): Manual Dial with the number filled in, never dialled --}}
@php $prefillNumber = ($activeTab ?? '') === 'manual' ? \App\Http\Controllers\Dashboard\DialerController::prefillNumber() : null; @endphp
<script>
$(function () {
    var prefill = @json($prefillNumber);
    if (prefill) {
        // as if the agent typed it: channel detection, quick actions and Recent Numbers react to the input event
        $('#mobile').val(prefill).trigger('input').trigger('focus');
    }
    if (new URLSearchParams(window.location.search).has('number')) {
        var u = new URL(window.location.href);
        u.searchParams.delete('number');
        history.replaceState(history.state, '', u.pathname + u.search + u.hash);   // a refresh does not fill it again
    }
    @if(session('inboundNotice'))
    if (typeof toastr !== 'undefined') toastr.info(@json(session('inboundNotice')));
    @endif
});
</script>
{{-- Auto Dialer: floating button + popup, Campaign Queue tab only --}}
@include('dashboard.partials.auto-dialer')
@endpush
@endsection
