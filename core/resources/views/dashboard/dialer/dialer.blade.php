@extends('dashboard.layouts.master')
@section('title', 'Dialer – LeadGER')

@push('after-styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ============================================================
   LeadGER Dialer – Reference-Matched Design
   ============================================================ */
:root {
    --dblue:   #1E3A5F;
    --dpurple: #4F46E5;
    --dgreen:  #10B981;
    --dorange: #F97316;
    --dred:    #EF4444;
    --dbg:     #F4F7FB;
    --dcard:   #FFFFFF;
    --dborder: #E2E8F0;
    --dtext:   #1E293B;
    --dmuted:  #64748B;
}

.lg-dialer-canvas {
    background: var(--dbg);
    min-height: calc(100vh - 60px);
    padding: 16px 20px 32px;
    font-family: var(--lg-font-sans);
    color: var(--dtext);
}

/* ── Two-column layout ───────────────────── */
.lg-dialer-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 16px;
    align-items: start;
}

@media (max-width: 1100px) {
    .lg-dialer-layout { grid-template-columns: 1fr; }
}

/* ── Base card ───────────────────────────── */
.lg-dc {
    background: var(--dcard);
    border: 1px solid var(--dborder);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15,23,42,0.06);
    margin-bottom: 14px;
    overflow: hidden;
}

/* ── Make-a-Call header (blue gradient) ── */
.lg-mac-header {
    background: linear-gradient(135deg, #1E3A5F 0%, #2D4E7A 100%);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.lg-mac-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lg-mac-icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--dgreen);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 3px 10px rgba(16,185,129,0.35);
}

.lg-mac-icon-circle i { color: #fff; font-size: 16px; }

.lg-mac-title { color: #fff; font-size: 15px; font-weight: 700; margin: 0; line-height: 1.2; }
.lg-mac-sub   { color: rgba(255,255,255,0.72); font-size: 11.5px; margin: 2px 0 0; }

.lg-btn-quickdial {
    background: rgba(99,102,241,0.85);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .15s;
    white-space: nowrap;
}
.lg-btn-quickdial:hover { background: rgba(99,102,241,1); }

/* ── Mac body 3-col ─────────────────────── */
.lg-mac-body {
    padding: 22px 20px 22px;
}

.lg-mac-cols {
    display: grid;
    grid-template-columns: 1.1fr 1.2fr 1.3fr;
    gap: 16px;
    margin-bottom: 22px;
}

.lg-mac-cols .lg-field {
    gap: 7px;
}

.lg-mac-cols .lg-finput,
.lg-mac-cols .lg-fselect {
    height: 42px;
}

.lg-mac-cols .lg-copt {
    padding: 10px 12px;
    min-height: 42px;
    box-sizing: border-box;
}

.lg-mac-cols .lg-did-sub {
    margin-top: 7px;
}

@media (max-width: 900px) {
    .lg-mac-cols { grid-template-columns: 1fr; }
}

/* ── Field ──────────────────────────────── */
.lg-field { display: flex; flex-direction: column; gap: 5px; }

.lg-flabel {
    font-size: 11px;
    font-weight: 600;
    color: var(--dmuted);
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.lg-flabel i { color: var(--dpurple); }

.lg-finput {
    height: 38px;
    border: 1.5px solid var(--dborder);
    border-radius: 8px;
    padding: 0 32px 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--dtext);
    background: #fff;
    width: 100%;
    box-sizing: border-box;
    transition: border-color .15s, box-shadow .15s;
}
.lg-finput:focus { outline: none; border-color: var(--dpurple); box-shadow: 0 0 0 3px rgba(79,70,229,0.10); }

.lg-input-wrap { position: relative; }
.lg-input-wrap .lg-input-clear {
    position: absolute;
    right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #94A3B8; font-size: 12px; padding: 0;
    transition: color .15s;
}
.lg-input-wrap .lg-input-clear:hover { color: var(--dred); }

/* lead mini card below phone */
.lg-lead-mini {
    margin-top: 6px;
    background: #F8FAFC;
    border: 1px solid var(--dborder);
    border-radius: 8px;
    padding: 7px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-lead-avatar-sm {
    width: 28px; height: 28px; border-radius: 50%;
    background: linear-gradient(135deg, #6366F1, #8B5CF6);
    color: #fff; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.lg-lead-mini-info { flex: 1; min-width: 0; }
.lg-lead-mini-name { font-size: 11.5px; font-weight: 700; color: var(--dtext); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lg-lead-mini-phone { font-size: 10.5px; color: var(--dmuted); }
.lg-lead-mini-copy { color: #94A3B8; cursor: pointer; font-size: 12px; }

/* DID subtext */
.lg-did-sub {
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
}
.lg-avail-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--dgreen); flex-shrink: 0;
}
.lg-avail-text { color: var(--dgreen); font-weight: 600; }
.lg-balance-text { color: var(--dmuted); }

/* Calling option cards */
.lg-call-opts { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

.lg-copt {
    border: 1.5px solid var(--dborder);
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    background: #FAFCFF;
    transition: all .15s;
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    user-select: none;
}
.lg-copt:hover { border-color: #93C5FD; }
.lg-copt.selected {
    border-color: var(--dgreen);
    background: rgba(16,185,129,0.06);
    box-shadow: 0 0 0 2px rgba(16,185,129,0.15);
}

.lg-copt-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: #F1F5F9; color: #64748B;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
    transition: all .15s;
}
.lg-copt.selected .lg-copt-icon { background: var(--dgreen); color: #fff; }

.lg-copt-text .copt-title { font-size: 12px; font-weight: 700; color: var(--dtext); }
.lg-copt-text .copt-sub   { font-size: 10px; color: var(--dmuted); margin-top: 1px; }

.lg-copt-check {
    position: absolute; top: 5px; right: 5px;
    width: 14px; height: 14px; border-radius: 50%;
    background: var(--dgreen); color: #fff;
    display: none; align-items: center; justify-content: center;
    font-size: 8px; font-weight: 700;
}
.lg-copt.selected .lg-copt-check { display: flex; }

/* ── Call Now row ───────────────────────── */
.lg-call-action-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.lg-btn-callnow {
    flex: 1;
    height: 44px;
    background: var(--dgreen);
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 3px 12px rgba(16,185,129,0.30);
    transition: all .15s;
}
.lg-btn-callnow:hover { background: #0DA770; box-shadow: 0 5px 18px rgba(16,185,129,0.42); transform: translateY(-1px); }

.lg-btn-clearall {
    height: 44px;
    padding: 0 22px;
    background: #fff;
    color: var(--dmuted);
    border: 1.5px solid var(--dborder);
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
    white-space: nowrap;
}
.lg-btn-clearall:hover { background: #F1F5F9; color: var(--dtext); border-color: #94A3B8; }

/* ── Live Call card ─────────────────────── */
.lg-livecall-header {
    background: linear-gradient(135deg, #1B3D6F 0%, #2A5298 100%);
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-lc-icon {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--dgreen);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.lg-lc-icon i { color: #fff; font-size: 14px; }

.lg-lc-title { color: #fff; font-size: 14px; font-weight: 700; flex: 1; }

.lg-badge-connected {
    background: rgba(16,185,129,0.9);
    color: #fff;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}
.lg-badge-connected::before {
    content: '';
    width: 6px; height: 6px; border-radius: 50%; background: #fff;
}

.lg-lc-timer {
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    letter-spacing: 1px;
    min-width: 68px;
    text-align: center;
}

.lg-btn-mute, .lg-btn-speaker {
    width: 30px; height: 30px; border-radius: 50%;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
    transition: background .15s;
}
.lg-btn-mute:hover, .lg-btn-speaker:hover { background: rgba(255,255,255,0.28); }

.lg-btn-endcall {
    background: var(--dred);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(239,68,68,0.3);
    transition: all .15s;
    white-space: nowrap;
}
.lg-btn-endcall:hover { background: #DC2626; box-shadow: 0 4px 12px rgba(239,68,68,0.4); }

/* Live call info bar */
.lg-lc-info-bar {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1.3fr 1.3fr;
    gap: 0;
    padding: 0 18px;
    border-bottom: 1px solid #F1F5F9;
}

@media (max-width: 900px) {
    .lg-lc-info-bar { grid-template-columns: 1fr 1fr; }
}

.lg-lc-info-cell {
    padding: 14px 12px 14px 0;
    border-right: 1px solid #F1F5F9;
}
.lg-lc-info-cell:last-child { border-right: none; padding-left: 12px; }
.lg-lc-info-cell:first-child { padding-left: 0; }

.lg-lc-info-label {
    font-size: 10.5px;
    color: var(--dmuted);
    font-weight: 600;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.lg-lc-info-label i { color: #94A3B8; }

.lg-lc-contact-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-lc-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, #6366F1, #8B5CF6);
    color: #fff; font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.lg-lc-contact-name { font-size: 13px; font-weight: 700; color: var(--dtext); }
.lg-lc-contact-phone { font-size: 11px; color: var(--dmuted); }
.lg-badge-conn-sm {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(16,185,129,0.1);
    color: var(--dgreen);
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 10px; font-weight: 600;
    margin-top: 3px;
}
.lg-badge-conn-sm::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: var(--dgreen); }

.lg-lc-info-value {
    font-size: 13px;
    font-weight: 700;
    color: var(--dtext);
}
.lg-lc-info-sub {
    font-size: 10.5px;
    color: var(--dmuted);
    margin-top: 1px;
}

/* Connected notice */
.lg-conn-notice {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-radius: 10px;
    margin: 14px 18px;
    padding: 10px 14px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.lg-conn-notice-icon {
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--dgreen);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 13px;
}
.lg-conn-notice-title { font-size: 12.5px; font-weight: 700; color: var(--lg-accent-hover); margin: 0 0 2px; }
.lg-conn-notice-desc  { font-size: 11.5px; color: #3B82F6; margin: 0; }

/* ── Call Outcome card header ───────────── */
.lg-co-header {
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #F1F5F9;
}
.lg-co-icon {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--dorange);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 15px; flex-shrink: 0;
    box-shadow: 0 3px 10px rgba(249,115,22,0.28);
}
.lg-co-title { font-size: 14px; font-weight: 700; color: var(--dtext); margin: 0; }
.lg-co-sub   { font-size: 11px; color: var(--dmuted); margin: 1px 0 0; }

/* Call outcome body */
.lg-co-body { padding: 14px 18px; }

.lg-co-row1 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    margin-bottom: 14px;
}

@media (max-width: 900px) {
    .lg-co-row1 { grid-template-columns: 1fr; }
}

/* Call status 4-button grid */
.lg-cs-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 7px;
}

.lg-cs-btn {
    border: 1.5px solid var(--dborder);
    border-radius: 8px;
    background: #fff;
    padding: 8px 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    transition: all .15s;
    user-select: none;
}
.lg-cs-btn:hover { border-color: #93C5FD; background: #F8FAFF; }

.lg-cs-btn.cs-connected {
    background: var(--dgreen);
    border-color: var(--dgreen);
    color: #fff;
    box-shadow: 0 2px 8px rgba(16,185,129,0.28);
}
.lg-cs-btn.cs-busy    { border-color: #F59E0B; color: #F59E0B; }
.lg-cs-btn.cs-no-answer { border-color: #94A3B8; color: #64748B; }
.lg-cs-btn.cs-failed  { border-color: var(--dred); color: var(--dred); }

/* Select field */
.lg-fselect {
    height: 38px;
    border: 1.5px solid var(--dborder);
    border-radius: 8px;
    padding: 0 32px 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--dtext);
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat calc(100% - 10px) center;
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    box-sizing: border-box;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
}
.lg-fselect:focus { outline: none; border-color: var(--dpurple); box-shadow: 0 0 0 3px rgba(79,70,229,0.10); }

/* Comments textarea */
.lg-ftextarea {
    width: 100%;
    border: 1.5px solid var(--dborder);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--dtext);
    background: #fff;
    box-sizing: border-box;
    resize: vertical;
    font-family: inherit;
    min-height: 80px;
    transition: border-color .15s, box-shadow .15s;
}
.lg-ftextarea:focus { outline: none; border-color: var(--dpurple); box-shadow: 0 0 0 3px rgba(79,70,229,0.10); }

.lg-char-count { font-size: 10.5px; color: var(--dmuted); text-align: right; margin-top: 3px; }

/* Follow-up date/time row */
.lg-followup-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

/* Save/Next & Skip row */
.lg-co-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 18px;
}

.lg-btn-save-next {
    flex: 1;
    height: 42px;
    background: linear-gradient(135deg, var(--lg-accent) 0%, #6366F1 100%);
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 3px 12px rgba(20,100,244,0.30);
    transition: all .15s;
}
.lg-btn-save-next:hover { box-shadow: 0 5px 18px rgba(20,100,244,0.42); transform: translateY(-1px); }

.lg-btn-skip {
    height: 42px;
    padding: 0 22px;
    background: #fff;
    color: #475569;
    border: 1.5px solid var(--dborder);
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all .15s;
}
.lg-btn-skip:hover { background: #F1F5F9; color: var(--dtext); }

/* ── RIGHT SIDEBAR ───────────────────────── */
.lg-dialer-sidebar {}

/* Lead details */
.lg-ld-header {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #F1F5F9;
}
.lg-ld-title { font-size: 13px; font-weight: 700; color: var(--dtext); display: flex; align-items: center; gap: 6px; }
.lg-ld-title i { color: var(--dpurple); }

.lg-btn-edit-sm {
    background: #fff;
    border: 1.5px solid var(--dborder);
    border-radius: 7px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all .15s;
}
.lg-btn-edit-sm:hover { background: #F1F5F9; color: var(--dpurple); }

.lg-ld-body { padding: 14px 16px; }

.lg-ld-profile-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 12px;
}

.lg-ld-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #6366F1, #8B5CF6);
    color: #fff; font-size: 14px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.lg-ld-name { font-size: 14px; font-weight: 700; color: var(--dtext); margin: 0 0 3px; }
.lg-badge-new-lead {
    display: inline-flex; align-items: center;
    background: rgba(16,185,129,0.12);
    color: var(--dgreen);
    border-radius: 20px;
    padding: 2px 9px;
    font-size: 10px; font-weight: 700;
    margin-left: 6px;
}

.lg-ld-contact-info {
    margin-bottom: 12px;
}
.lg-ld-info-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    color: var(--dmuted);
    margin-bottom: 5px;
}
.lg-ld-info-row i { color: #94A3B8; width: 14px; text-align: center; }

/* Lead mini info tiles */
.lg-ld-tiles {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6px;
    margin-bottom: 0;
}
.lg-ld-tile {
    background: #F8FAFC;
    border: 1px solid var(--dborder);
    border-radius: 8px;
    padding: 8px 8px 7px;
    text-align: center;
}
.lg-ld-tile-icon {
    width: 22px; height: 22px; border-radius: 50%;
    margin: 0 auto 4px;
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; color: #fff;
}
.lg-tile-blue   { background: #3B82F6; }
.lg-tile-purple { background: #8B5CF6; }
.lg-tile-green  { background: var(--dgreen); }

.lg-ld-tile-label { font-size: 9.5px; color: var(--dmuted); font-weight: 600; margin-bottom: 2px; }
.lg-ld-tile-val   { font-size: 10.5px; font-weight: 700; color: var(--dtext); }

/* Quick Actions */
.lg-qa-body { padding: 12px 16px; }
.lg-qa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

.lg-qa-btn {
    background: #F8FAFC;
    border: 1px solid var(--dborder);
    border-radius: 9px;
    padding: 10px 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 11.5px;
    font-weight: 600;
    color: var(--dtext);
    transition: all .15s;
}
.lg-qa-btn:hover { background: #fff; border-color: #93C5FD; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }

.lg-qa-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
}
.qa-orange { background: rgba(249,115,22,0.12); color: var(--dorange); }
.qa-blue   { background: rgba(59,130,246,0.12); color: #3B82F6; }
.qa-purple { background: rgba(139,92,246,0.12); color: #8B5CF6; }
.qa-clock  { background: rgba(249,115,22,0.12); color: var(--dorange); }

/* Recent Activity */
.lg-ra-header {
    padding: 12px 16px 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #F1F5F9;
}
.lg-ra-title { font-size: 13px; font-weight: 700; color: var(--dtext); display: flex; align-items: center; gap: 6px; }
.lg-ra-title i { color: var(--dorange); }
.lg-ra-viewall { font-size: 11px; font-weight: 600; color: var(--dpurple); text-decoration: none; }
.lg-ra-viewall:hover { text-decoration: underline; }

.lg-ra-body { padding: 10px 16px 14px; }

.lg-ra-timeline { position: relative; }

.lg-ra-item {
    display: grid;
    grid-template-columns: 30px 1fr;
    gap: 8px;
    margin-bottom: 14px;
    position: relative;
}
.lg-ra-item:last-child { margin-bottom: 0; }
.lg-ra-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 14px; top: 26px;
    width: 1px; height: calc(100% + 2px);
    background: #E2E8F0;
}

.lg-ra-dot {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; flex-shrink: 0; position: relative; z-index: 1;
}
.ra-green  { background: rgba(16,185,129,0.12); color: var(--dgreen); }
.ra-blue   { background: rgba(59,130,246,0.12);  color: #3B82F6; }
.ra-orange { background: rgba(249,115,22,0.12);  color: var(--dorange); }
.ra-purple { background: rgba(139,92,246,0.12);  color: #8B5CF6; }

.lg-ra-content {}
.lg-ra-event-title { font-size: 11.5px; font-weight: 700; color: var(--dtext); margin: 0 0 1px; }
.lg-ra-event-time  { font-size: 10px; color: var(--dmuted); }

.lg-ra-right { text-align: right; }
.lg-ra-duration { font-size: 10.5px; color: var(--dmuted); }
.lg-ra-badge {
    display: inline-block;
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 10px;
    font-weight: 600;
}
.ra-badge-green   { background: rgba(16,185,129,0.12); color: var(--dgreen); }
.ra-badge-blue    { background: rgba(59,130,246,0.12);  color: #3B82F6; }
.ra-badge-orange  { background: rgba(249,115,22,0.12);  color: var(--dorange); }
.ra-badge-purple  { background: rgba(139,92,246,0.12);  color: #8B5CF6; }

/* Today's Performance */
.lg-perf-body {
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.lg-perf-metrics { flex: 1; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
.lg-perf-metric { text-align: center; }
.lg-perf-metric-val { font-size: 20px; font-weight: 800; color: var(--dtext); line-height: 1; }
.lg-perf-metric-label { font-size: 9.5px; color: var(--dmuted); font-weight: 600; margin-top: 3px; }

/* SVG circle progress */
.lg-perf-circle { flex-shrink: 0; text-align: center; }
.lg-perf-circle-svg { transform: rotate(-90deg); }
.lg-perf-circle-bg  { fill: none; stroke: #F1F5F9; stroke-width: 6; }
.lg-perf-circle-prog { fill: none; stroke: var(--dgreen); stroke-width: 6; stroke-linecap: round; transition: stroke-dashoffset .5s ease; }
.lg-perf-pct { font-size: 13px; font-weight: 800; color: var(--dtext); margin-top: 4px; }
.lg-perf-pct-sub { font-size: 9px; color: var(--dmuted); font-weight: 600; }

/* Card section header shared */
.lg-sec-header {
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #F1F5F9;
}
.lg-sec-title { font-size: 13px; font-weight: 700; color: var(--dtext); display: flex; align-items: center; gap: 6px; }

/* Live call hidden by default */
#lg-livecall-card { display: none; }

</style>
@endpush

@section('content')
<div class="lg-dialer-canvas">

    {{-- MAIN FORM --}}
    {{ Form::open(['route' => ['dialerSaveLog'], 'method' => 'POST', 'id' => 'add-edit-form', 'files' => true]) }}

    {{-- Hidden Fields --}}
    <input type="hidden" id="uuid"      name="uuid"      value="">
    <input type="hidden" id="id"        name="id"        value="">
    <input type="hidden" id="is_campaign" name="is_campaign" value="0">
    <input type="hidden" id="call_type" name="call_type" value="webrtc">
    <input type="hidden" id="time_taken" name="time_taken" value="0">

    <div class="lg-dialer-layout">

        {{-- ══════════════ LEFT / MAIN COLUMN ══════════════ --}}
        <div>

            {{-- ── 1. MAKE A CALL ─────────────────────────── --}}
            <div class="lg-dc">
                {{-- Header --}}
                <div class="lg-mac-header">
                    <div class="lg-mac-header-left">
                        <div class="lg-mac-icon-circle">
                            <i class="fa fa-phone"></i>
                        </div>
                        <div>
                            <div class="lg-mac-title">Make a Call</div>
                            <div class="lg-mac-sub">Enter number and choose the calling option</div>
                        </div>
                    </div>
                    <button type="button" class="lg-btn-quickdial" onclick="phone()">
                        <i class="fa fa-bolt"></i> Quick Dial
                    </button>
                </div>

                {{-- Body --}}
                <div class="lg-mac-body">
                    <div class="lg-mac-cols">

                        {{-- Col 1: Phone Number --}}
                        <div class="lg-field">
                            <label class="lg-flabel"><i class="fa fa-phone"></i> Phone Number</label>
                            <div class="lg-input-wrap">
                                {!! Form::text('mobile', preg_replace('/[^0-9]/', '', request()->get('mobile', '')), [
                                    'placeholder'  => 'Enter number',
                                    'class'        => 'lg-finput',
                                    'id'           => 'mobile',
                                    'required'     => '',
                                    'maxlength'    => 20,
                                    'autocomplete' => 'off',
                                    'inputmode'    => 'numeric',
                                    'pattern'      => '[0-9]*',
                                    'oninput'      => "this.value = this.value.replace(/[^0-9]/g, ''); onMobileKey();",
                                    'onkeypress'   => "return isNumberKey(event);",
                                    'onpaste'      => "handleMobilePaste(event);",
                                    'style'        => 'padding-right: 32px;'
                                ]) !!}
                                <button type="button" class="lg-input-clear" onclick="clearMobile()" title="Clear">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                            {{-- Lead mini preview --}}
                            <div class="lg-lead-mini" id="lead-mini-preview" style="display:none;">
                                <div class="lg-lead-avatar-sm" id="lead-mini-initials">RK</div>
                                <div class="lg-lead-mini-info">
                                    <div class="lg-lead-mini-name" id="lead-mini-name">—</div>
                                    <div class="lg-lead-mini-phone" id="lead-mini-phone">—</div>
                                </div>
                                <i class="fa fa-copy lg-lead-mini-copy" title="Copy" onclick="copyPhone()"></i>
                            </div>

                            {{-- Smart31 Only & 1600 Enabled Checkboxes --}}
                            <div class="lg-smart31-toggle-wrap" style="margin-top: 8px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                <label style="display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; user-select: none; margin: 0;">
                                    <input type="checkbox" id="smart31_toggle" name="smart31_enabled" value="1" onchange="toggleSmart31(this.checked)" style="width: 15px; height: 15px; accent-color: #4F46E5; cursor: pointer; margin: 0;">
                                    <span>Smart31 Only</span>
                                </label>

                                <label style="display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; user-select: none; margin: 0;">
                                    <input type="checkbox" id="vmn1600_toggle" name="vmn1600_enabled" value="1" onchange="toggle1600(this.checked)" style="width: 15px; height: 15px; accent-color: #0284C7; cursor: pointer; margin: 0;">
                                    <span>Enabled 1600</span>
                                </label>
                            </div>
                        </div>

                        {{-- Col 2: Caller ID / DID, Smart31 VMN & 1600 VMN --}}
                        <div class="lg-field">
                            {{-- Standard Caller ID / DID Box (Shown when Smart31 & 1600 are OFF) --}}
                            <div id="standard-callerid-box">
                                <label class="lg-flabel"><i class="fa fa-phone-square"></i> Select Caller ID / DID</label>

                                @php
                                    $hasC2c = ($User->c2c_channel == 1) || (isset($vmnList) && count($vmnList) > 0);
                                    $hasWebrtc = ($User->webrtc_channel == 1) || (isset($wvmnList) && count($wvmnList) > 0);
                                @endphp
                                @if($hasC2c && $hasWebrtc)
                                    <select name="vmn" id="vmn" class="lg-fselect">
                                        <option value="">Select Caller ID</option>
                                        @foreach($vmnList as $row)
                                            <option value="{{ $row->virtual_number }}" {{ $row->virtual_number == Auth::user()->c2c_default_number ? 'selected' : '' }}>
                                                +91 {{ $row->virtual_number }} (Main Line)
                                            </option>
                                        @endforeach
                                        @foreach($wvmnList as $row)
                                            <option value="{{ $row->virtual_number }}" {{ $row->virtual_number == Auth::user()->webrtc_default_number ? 'selected' : '' }}>
                                                +91 {{ $row->virtual_number }} (WebRTC Line)
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif($hasC2c)
                                    <select name="vmn" id="vmn" class="lg-fselect">
                                        @foreach($vmnList as $row)
                                            <option value="{{ $row->virtual_number }}" {{ $row->virtual_number == Auth::user()->c2c_default_number ? 'selected' : '' }}>
                                                +91 {{ $row->virtual_number }} (Main Line)
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif($hasWebrtc)
                                    <select name="vmn" id="vmn" class="lg-fselect">
                                        @foreach($wvmnList as $row)
                                            <option value="{{ $row->virtual_number }}" {{ $row->virtual_number == Auth::user()->webrtc_default_number ? 'selected' : '' }}>
                                                +91 {{ $row->virtual_number }} (WebRTC Line)
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    @php
                                        $fallbackDid = \App\Models\ObdDID::first();
                                        $fallbackNum = $fallbackDid ? $fallbackDid->virtual_number : '7714581128';
                                    @endphp
                                    <select name="vmn" id="vmn" class="lg-fselect">
                                        <option value="{{ $fallbackNum }}" selected>+91 {{ $fallbackNum }} (Main Line)</option>
                                    </select>
                                @endif

                                <div class="lg-did-sub">
                                    <div class="lg-avail-dot"></div>
                                    <span class="lg-avail-text">Available</span>
                                    <span class="lg-balance-text">· Balance: 2454 mins</span>
                                </div>
                            </div>

                            {{-- Smart31 VMN Box (Shown when Smart31 is ON) --}}
                            <div id="smart31-vmn-box" style="display: none;">
                                <label class="lg-flabel" style="color: #4F46E5;"><i class="fa fa-shield"></i> <span id="smart31-vmn-title">WebRTC VMN</span></label>
                                <select name="smart31_vmn" id="smart31_vmn_select" class="lg-fselect" style="border-color: #4F46E5;">
                                    <!-- Options populated via AJAX -->
                                </select>
                                <div class="lg-did-sub" id="smart31-status-sub" style="margin-top: 5px;">
                                    <div class="lg-avail-dot" id="smart31-status-dot" style="background: #10B981;"></div>
                                    <span class="lg-avail-text" id="smart31-status-text" style="color: #10B981; font-weight: 600;">Smart31 Active</span>
                                </div>
                            </div>

                            {{-- 1600 VMN Box (Shown when 1600 Enabled is ON) --}}
                            <div id="vmn1600-vmn-box" style="display: none;">
                                <label class="lg-flabel" style="color: #0284C7;"><i class="fa fa-phone"></i> <span id="vmn1600-vmn-title">1600 VMN</span></label>
                                <select name="vmn1600_vmn" id="vmn1600_vmn_select" class="lg-fselect" style="border-color: #0284C7;">
                                    <!-- Options populated via AJAX -->
                                </select>
                                <div class="lg-did-sub" id="vmn1600-status-sub" style="margin-top: 5px;">
                                    <div class="lg-avail-dot" id="vmn1600-status-dot" style="background: #10B981;"></div>
                                    <span class="lg-avail-text" id="vmn1600-status-text" style="color: #10B981; font-weight: 600;">1600 Active</span>
                                </div>
                            </div>
                        </div>

                        {{-- Col 3: Calling Option --}}
                        <div class="lg-field">
                            <label class="lg-flabel"><i class="fa fa-headphones"></i> Calling Option</label>
                            <div class="lg-call-opts">

                                <div class="lg-copt {{ ($User->c2c_channel == 1 && $User->webrtc_channel != 1) ? '' : 'selected' }}" id="opt-webrtc" onclick="selectCallOption('webrtc')">
                                    <div class="lg-copt-icon"><i class="fa fa-headphones"></i></div>
                                    <div class="lg-copt-text">
                                        <div class="copt-title">WebRTC</div>
                                        <div class="copt-sub">Internet Required</div>
                                    </div>
                                    <div class="lg-copt-check">✓</div>
                                </div>

                                <div class="lg-copt {{ ($User->c2c_channel == 1 && $User->webrtc_channel != 1) ? 'selected' : '' }}" id="opt-c2c" onclick="selectCallOption('click2call')">
                                    <div class="lg-copt-icon"><i class="fa fa-phone"></i></div>
                                    <div class="lg-copt-text">
                                        <div class="copt-title">Click2Call</div>
                                        <div class="copt-sub">Gateway</div>
                                    </div>
                                    <div class="lg-copt-check">✓</div>
                                </div>

                            </div>
                        </div>

                    </div>

                    @php
                        $callingHoursStatus = \App\Services\CallingRestrictionService::getCallingHoursUiStatus();
                    @endphp

                    @if(!empty($callingHoursStatus['is_closed']))
                        <div id="dialer-calling-hours-notice" class="alert alert-warning py-2 px-3 mb-2 d-flex align-items-center" style="border-radius: 8px; font-size: 13px; font-weight: 600; background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; width: 100%;">
                            <i class="fa fa-clock-o me-2" style="color: #D97706; margin-right: 8px;"></i>
                            <span>{{ $callingHoursStatus['notice'] }}</span>
                        </div>
                    @endif

                    {{-- Call Now row --}}
                    <div class="lg-call-action-row">
                        <button type="button" class="lg-btn-callnow" id="btn-call-trigger" {{ !empty($callingHoursStatus['is_closed']) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : 'onclick=executeSelectedCall()' }}>
                            <i class="fa fa-phone"></i>
                            <span id="call-btn-label">Call Now →</span>
                        </button>
                        <button type="button" class="lg-btn-clearall" onclick="clearCallInputs()">
                            <i class="fa fa-trash-o"></i> Clear All
                        </button>
                    </div>

                    {{-- Hidden --}}
                    <div style="display:none;">
                        {!! Form::checkbox('default_dialer_campaign', ($User->default_dialer_campaign != '0' ? '1' : '0'), false, ['id' => 'default_dialer_campaign']) !!}
                        {!! Form::checkbox('default_vmn',  ($User->default_vmn  != '0' ? '1' : '0'), false, ['id' => 'default_vmn'])  !!}
                        {!! Form::checkbox('default_wvmn', ($User->default_wvmn != '0' ? '1' : '0'), false, ['id' => 'default_wvmn']) !!}
                        <input type="checkbox" id="smart31_checkbox" class="editor-active">
                    </div>
                </div>
            </div>

            {{-- ── 2. LIVE CALL ────────────────────────────── --}}
            <div class="lg-dc" id="lg-livecall-card">
                {{-- Header --}}
                <div class="lg-livecall-header">
                    <div class="lg-lc-icon"><i class="fa fa-phone"></i></div>
                    <div class="lg-lc-title">Live Call</div>
                    <div class="lg-badge-connected" id="lc-status-badge">Connected</div>
                    <div class="lg-lc-timer" id="lc-timer">00:00:00</div>
                    <button type="button" class="lg-btn-speaker" title="Speaker" onclick="toggleSpeaker(this)">
                        <i class="fa fa-volume-up"></i>
                    </button>
                    <button type="button" class="lg-btn-mute" title="Mute" id="btn-mute" onclick="toggleMute(this)">
                        <i class="fa fa-microphone"></i>
                    </button>
                    <button type="button" class="lg-btn-endcall" onclick="endCall()">
                        <i class="fa fa-phone" style="transform:rotate(135deg); display:inline-block;"></i> End Call
                    </button>
                </div>

                {{-- Info Bar --}}
                <div class="lg-lc-info-bar">
                    {{-- Contact --}}
                    <div class="lg-lc-info-cell">
                        <div class="lg-lc-info-label"><i class="fa fa-user"></i> Contact</div>
                        <div class="lg-lc-contact-row">
                            <div class="lg-lc-avatar" id="lc-avatar">RK</div>
                            <div>
                                <div class="lg-lc-contact-name" id="lc-name">—</div>
                                <div class="lg-lc-contact-phone" id="lc-phone">—</div>
                                <div class="lg-badge-conn-sm">Connected</div>
                            </div>
                        </div>
                    </div>

                    {{-- Caller ID --}}
                    <div class="lg-lc-info-cell">
                        <div class="lg-lc-info-label"><i class="fa fa-phone-square"></i> Caller ID</div>
                        <div class="lg-lc-info-value" id="lc-callerid">—</div>
                        <div class="lg-lc-info-sub">(Main Line)</div>
                    </div>

                    {{-- Duration --}}
                    <div class="lg-lc-info-cell">
                        <div class="lg-lc-info-label"><i class="fa fa-clock-o"></i> Call Duration</div>
                        <div class="lg-lc-info-value" id="lc-duration">00:00:00</div>
                    </div>

                    {{-- Mode --}}
                    <div class="lg-lc-info-cell">
                        <div class="lg-lc-info-label"><i class="fa fa-cog"></i> Call Mode</div>
                        <div class="lg-lc-info-value" id="lc-mode">WebRTC</div>
                    </div>
                </div>

                {{-- Connected notice --}}
                <div class="lg-conn-notice">
                    <div class="lg-conn-notice-icon"><i class="fa fa-check"></i></div>
                    <div>
                        <div class="lg-conn-notice-title">Call Connected</div>
                        <p class="lg-conn-notice-desc">You can now update the status, feedback and other details after the call ends.</p>
                    </div>
                </div>
            </div>

            {{-- ── 3. CALL OUTCOME ──────────────────────────── --}}
            <div class="lg-dc">
                {{-- Header --}}
                <div class="lg-co-header">
                    <div class="lg-co-icon"><i class="fa fa-phone"></i></div>
                    <div>
                        <div class="lg-co-title">Call Outcome</div>
                        <div class="lg-co-sub">Select call status, lead status and provide feedback</div>
                    </div>
                </div>

                <div class="lg-co-body">

                    {{-- Row 1: Call Status | Lead Status | Comments --}}
                    <div class="lg-co-row1">

                        {{-- Call Status --}}
                        <div class="lg-field">
                            <label class="lg-flabel"><i class="fa fa-check-circle" style="color:var(--dgreen);"></i> Call Status *</label>
                            <input type="hidden" id="call_status_val" name="call_status" value="connected">
                            <div class="lg-cs-grid">
                                <button type="button" class="lg-cs-btn cs-connected" id="cs-connected" onclick="selectCallStatus('connected', this)">
                                    <i class="fa fa-check"></i> Connected
                                </button>
                                <button type="button" class="lg-cs-btn" id="cs-busy" onclick="selectCallStatus('busy', this)">
                                    <i class="fa fa-clock-o"></i> Busy
                                </button>
                                <button type="button" class="lg-cs-btn" id="cs-no-answer" onclick="selectCallStatus('no_answer', this)">
                                    <i class="fa fa-phone" style="transform:rotate(135deg);display:inline-block;"></i> No Answer
                                </button>
                                <button type="button" class="lg-cs-btn" id="cs-failed" onclick="selectCallStatus('failed', this)">
                                    <i class="fa fa-times"></i> Failed
                                </button>
                            </div>

                            {{-- Lead Status + Campaign (below call status) --}}
                            <div style="margin-top:12px;">
                                <label class="lg-flabel" style="margin-bottom:5px;"><i class="fa fa-flag" style="color:#3B82F6;"></i> Campaign *</label>
                                <select name="campaign_id" id="campaign_id" class="lg-fselect">
                                    <option value="">— Select Campaign —</option>
                                    @foreach($Campaigns as $Campaign)
                                        <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Lead Status + Feedback --}}
                        <div class="lg-field" style="gap:12px;">
                            <div class="lg-field">
                                <label class="lg-flabel"><i class="fa fa-user" style="color:#3B82F6;"></i> Lead Status *</label>
                                <select name="status_id" id="status_id" required class="lg-fselect">
                                    <option value="">— Select Status —</option>
                                    @foreach($Statuses as $Status)
                                        <option value="{{ $Status->id }}">{{ $Status->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lg-field">
                                <label class="lg-flabel"><i class="fa fa-thumbs-up" style="color:var(--dgreen);"></i> Feedback / Disposition *</label>
                                <select name="feedback" id="feedback" class="lg-fselect">
                                    <option value="Interested">Interested</option>
                                    <option value="Callback">Callback</option>
                                    <option value="Not Interested">Not Interested</option>
                                    <option value="Wrong Number">Wrong Number</option>
                                    <option value="Busy">Busy</option>
                                    <option value="No Answer">No Answer</option>
                                </select>
                            </div>
                        </div>

                        {{-- Comments + Follow-up --}}
                        <div class="lg-field" style="gap:10px;">
                            <div class="lg-field">
                                <label class="lg-flabel"><i class="fa fa-comment" style="color:var(--dmuted);"></i> Comments</label>
                                {!! Form::textarea('comments', '', [
                                    'placeholder' => 'Add your comments here...',
                                    'class'       => 'lg-ftextarea',
                                    'id'          => 'comments',
                                    'rows'        => 3,
                                    'maxlength'   => 500,
                                    'onkeyup'     => 'updateCharCount()'
                                ]) !!}
                                <div class="lg-char-count"><span id="char-count">0</span>/500</div>
                            </div>

                            <div class="lg-field">
                                <label class="lg-flabel"><i class="fa fa-calendar" style="color:var(--dpurple);"></i> Follow-up Date & Time</label>
                                <div class="lg-followup-row">
                                    <div class="lg-input-wrap">
                                        <input type="date" name="followup_date" id="followup_date" class="lg-finput"
                                               value="{{ date('Y-m-d') }}" style="padding-left:12px; padding-right:8px;">
                                    </div>
                                    <div class="lg-input-wrap">
                                        <input type="time" name="followup_time" id="followup_time" class="lg-finput"
                                               value="11:00" style="padding-left:12px; padding-right:8px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Save & Skip --}}
                    <div class="lg-co-actions">
                        <button type="button" onclick="savelog()" class="lg-btn-save-next">
                            <i class="fa fa-save"></i>
                            <span>Save &amp; Next Lead →</span>
                        </button>
                        <button type="button" class="lg-btn-skip" onclick="skipLead()">
                            <i class="fa fa-step-forward"></i> Skip
                        </button>
                    </div>

                </div>
            </div>

        </div>
        {{-- end main column --}}


        {{-- ══════════════ RIGHT SIDEBAR ══════════════ --}}
        <div class="lg-dialer-sidebar">

            {{-- Lead Details --}}
            <div class="lg-dc">
                <div class="lg-ld-header">
                    <div class="lg-ld-title"><i class="fa fa-user-circle"></i> Lead Details</div>
                    <button type="button" class="lg-btn-edit-sm"><i class="fa fa-pencil"></i> Edit</button>
                </div>
                <div class="lg-ld-body">

                    <div class="lg-ld-profile-row">
                        <div class="lg-ld-avatar" id="sidebar-avatar">RK</div>
                        <div>
                            <div class="lg-ld-name">
                                <span id="sidebar-lead-name">Ravi Kumar</span>
                                <span class="lg-badge-new-lead">New Lead</span>
                            </div>
                        </div>
                    </div>

                    <div class="lg-ld-contact-info">
                        <div class="lg-ld-info-row"><i class="fa fa-phone"></i> <span id="sidebar-phone">+91 9876543210</span></div>
                        <div class="lg-ld-info-row"><i class="fa fa-envelope"></i> <span id="sidebar-email">ravi.kumar@gmail.com</span></div>
                        <div class="lg-ld-info-row"><i class="fa fa-map-marker"></i> <span id="sidebar-location">Pune, Maharashtra</span></div>
                    </div>

                    <div class="lg-ld-tiles">
                        <div class="lg-ld-tile">
                            <div class="lg-ld-tile-icon lg-tile-blue"><i class="fa fa-id-badge"></i></div>
                            <div class="lg-ld-tile-label">Lead ID</div>
                            <div class="lg-ld-tile-val">LG-000123</div>
                        </div>
                        <div class="lg-ld-tile">
                            <div class="lg-ld-tile-icon lg-tile-purple"><i class="fa fa-bullhorn"></i></div>
                            <div class="lg-ld-tile-label">Campaign</div>
                            <div class="lg-ld-tile-val" id="sidebar-campaign">Campaign A</div>
                        </div>
                        <div class="lg-ld-tile">
                            <div class="lg-ld-tile-icon lg-tile-green"><i class="fa fa-user"></i></div>
                            <div class="lg-ld-tile-label">Assigned To</div>
                            <div class="lg-ld-tile-val">{{ Auth::user()->name ?? 'Agent' }}</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="lg-dc">
                <div class="lg-sec-header">
                    <div class="lg-sec-title"><i class="fa fa-bolt" style="color:var(--dorange);"></i> Quick Actions</div>
                </div>
                <div class="lg-qa-body">
                    <div class="lg-qa-grid">
                        <button type="button" class="lg-qa-btn">
                            <div class="lg-qa-icon qa-orange"><i class="fa fa-sticky-note-o"></i></div> Add Note
                        </button>
                        <button type="button" class="lg-qa-btn">
                            <div class="lg-qa-icon qa-blue"><i class="fa fa-calendar-plus-o"></i></div> Create Follow-up
                        </button>
                        <button type="button" class="lg-qa-btn">
                            <div class="lg-qa-icon qa-purple"><i class="fa fa-tag"></i></div> Add Tag
                        </button>
                        <button type="button" class="lg-qa-btn">
                            <div class="lg-qa-icon qa-clock"><i class="fa fa-history"></i></div> View History
                        </button>
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="lg-dc">
                <div class="lg-ra-header">
                    <div class="lg-ra-title"><i class="fa fa-history"></i> Recent Activity</div>
                    <a href="#" class="lg-ra-viewall">View All</a>
                </div>
                <div class="lg-ra-body">
                    <div class="lg-ra-timeline">

                        <div class="lg-ra-item">
                            <div class="lg-ra-dot ra-green"><i class="fa fa-phone"></i></div>
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:4px;">
                                    <div>
                                        <div class="lg-ra-event-title">Call Connected</div>
                                        <div class="lg-ra-event-time">26 Sep 2025 10:24 AM</div>
                                    </div>
                                    <div class="lg-ra-duration" style="white-space:nowrap;">Duration: 00:02:34</div>
                                </div>
                            </div>
                        </div>

                        <div class="lg-ra-item">
                            <div class="lg-ra-dot ra-blue"><i class="fa fa-comment"></i></div>
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:4px;">
                                    <div>
                                        <div class="lg-ra-event-title">Feedback Added</div>
                                        <div class="lg-ra-event-time">26 Sep 2025 10:27 AM</div>
                                    </div>
                                    <div><span class="lg-ra-badge ra-badge-green">Interested</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="lg-ra-item">
                            <div class="lg-ra-dot ra-orange"><i class="fa fa-calendar"></i></div>
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:4px;">
                                    <div>
                                        <div class="lg-ra-event-title">Follow-up Set</div>
                                        <div class="lg-ra-event-time">26 Sep 2025 10:28 AM</div>
                                    </div>
                                    <div><span class="lg-ra-badge ra-badge-orange">26 Sep 11:00 AM</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="lg-ra-item">
                            <div class="lg-ra-dot ra-purple"><i class="fa fa-refresh"></i></div>
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:4px;">
                                    <div>
                                        <div class="lg-ra-event-title">Lead Status Updated</div>
                                        <div class="lg-ra-event-time">26 Sep 2025 10:28 AM</div>
                                    </div>
                                    <div><span class="lg-ra-badge ra-badge-purple">In Progress</span></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Today's Performance --}}
            <div class="lg-dc">
                <div class="lg-sec-header">
                    <div class="lg-sec-title"><i class="fa fa-bullseye" style="color:var(--dpurple);"></i> Today's Performance</div>
                </div>
                <div class="lg-perf-body">
                    <div class="lg-perf-metrics">
                        <div class="lg-perf-metric">
                            <div class="lg-perf-metric-val" id="perf-total">28</div>
                            <div class="lg-perf-metric-label">Total Calls</div>
                        </div>
                        <div class="lg-perf-metric">
                            <div class="lg-perf-metric-val" id="perf-connected" style="color:var(--dgreen);">22</div>
                            <div class="lg-perf-metric-label">Connected</div>
                        </div>
                        <div class="lg-perf-metric">
                            <div class="lg-perf-metric-val" id="perf-conversion" style="color:var(--dpurple);">5</div>
                            <div class="lg-perf-metric-label">Conversion</div>
                        </div>
                    </div>

                    <div class="lg-perf-circle">
                        <svg class="lg-perf-circle-svg" width="68" height="68" viewBox="0 0 68 68">
                            <circle class="lg-perf-circle-bg"   cx="34" cy="34" r="28"/>
                            <circle class="lg-perf-circle-prog" cx="34" cy="34" r="28"
                                    id="perf-prog-circle"
                                    stroke-dasharray="175.9"
                                    stroke-dashoffset="38.7"/>
                        </svg>
                        <div class="lg-perf-pct" id="perf-pct">78%</div>
                        <div class="lg-perf-pct-sub">Success Rate</div>
                    </div>
                </div>
            </div>

        </div>
        {{-- end sidebar --}}

    </div>

    {{ Form::close() }}

</div>

{{-- Preserved telephony containers --}}
<div id="sso-hangup-container" style="display:none;">
    <h3>LeadGER Dialer</h3>
    <div id="sso-drag-handle" title="Drag"></div>
    <iframe id="sso-hangup" src="https://smartcloud.smartping.io"
            allow="microphone; camera"
            sandbox="allow-scripts allow-same-origin"
            style="position:absolute;width:100%;height:100%;border:none;background:transparent;z-index:1;"></iframe>
</div>

<div id="sip-dialer" style="display:none;">
    <div class="sip-header" style="text-align:center;">SIP Softphone</div>
    <div class="sip-body">
        <div class="sip-number"><input type="text" id="sip_target"/></div>
        <div style="font-weight:bold;margin-bottom:10px;"><span id="sip-timer">00:00</span></div>
        <audio id="remoteAudio" autoplay></audio>
        <div class="sip-buttons">
            <button type="button" onclick="sipMute()"   class="sip-btn mute">Mute</button>
            <button type="button" onclick="sipHold()"   class="sip-btn hold">Hold</button>
            <button type="button" onclick="sipHangup()" class="sip-btn hangup">Hangup</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
/* ── State ─────────────────────────────────────── */
window.callingHoursClosed = {{ !empty($callingHoursStatus['is_closed']) ? 'true' : 'false' }};
window.callingHoursNotice = @json($callingHoursStatus['notice'] ?? '');
let activeCallOption = "{{ ($User->c2c_channel == 1 && $User->webrtc_channel != 1) ? 'click2call' : 'webrtc' }}";
let callTimerInterval = null;
let callSeconds = 0;
let isMuted = false;
$(function() {
    $('#call_type').val(activeCallOption);
});

/* ── Calling option select ──────────────────────── */
function selectCallOption(opt) {
    activeCallOption = opt;
    $('#call_type').val(opt);
    $('.lg-copt').removeClass('selected');
    $('#opt-' + (opt === 'click2call' ? 'c2c' : 'webrtc')).addClass('selected');
    $('#lc-mode').text(opt === 'click2call' ? 'Click2Call' : 'WebRTC');

    if ($('#smart31_toggle').is(':checked')) {
        loadSmart31Vmns();
    } else if ($('#vmn1600_toggle').is(':checked')) {
        load1600Vmns();
    }
}

/* ── Smart31 Toggle & Dynamic VMN Load ────────── */
function toggleSmart31(enabled) {
    if (enabled) {
        $('#vmn1600_toggle').prop('checked', false);
        $('#vmn1600-vmn-box').hide();
        $('#standard-callerid-box').hide();
        $('#smart31-vmn-box').show();
        loadSmart31Vmns();
    } else {
        $('#smart31-vmn-box').hide();
        if ($('#vmn1600_toggle').is(':checked')) {
            $('#vmn1600-vmn-box').show();
            load1600Vmns();
        } else {
            $('#standard-callerid-box').show();
        }
    }
}

/* ── 1600 Enabled Toggle & Dynamic VMN Load ─────── */
function toggle1600(enabled) {
    if (enabled) {
        $('#smart31_toggle').prop('checked', false);
        $('#smart31-vmn-box').hide();
        $('#standard-callerid-box').hide();
        $('#vmn1600-vmn-box').show();
        load1600Vmns();
    } else {
        $('#vmn1600-vmn-box').hide();
        if ($('#smart31_toggle').is(':checked')) {
            $('#smart31-vmn-box').show();
            loadSmart31Vmns();
        } else {
            $('#standard-callerid-box').show();
        }
    }
}

function loadSmart31Vmns() {
    if (!$('#smart31_toggle').is(':checked')) return;

    var callingType = activeCallOption; // 'webrtc' or 'click2call'
    var typeLabel = (callingType === 'click2call') ? 'Click2Call VMN' : 'WebRTC VMN';
    $('#smart31-vmn-title').text(typeLabel);

    $('#smart31_vmn_select').html('<option value="">Loading VMNs...</option>');
    $('#smart31-status-text').text('Loading...').css('color', '#64748B');
    $('#smart31-status-dot').css('background', '#94A3B8');

    $.ajax({
        url: "{{ route('dialerSmart31Vmns') }}",
        type: 'GET',
        data: { type: callingType },
        dataType: 'json',
        success: function(res) {
            var $select = $('#smart31_vmn_select');
            $select.empty();

            if (res.success && res.vmns && res.vmns.length > 0) {
                $.each(res.vmns, function(idx, item) {
                    $select.append($('<option>', {
                        value: item.number,
                        text: item.display
                    }));
                });
                $('#smart31-status-text').text('Smart31 Active').css('color', '#10B981');
                $('#smart31-status-dot').css('background', '#10B981');
            } else {
                $select.append($('<option>', {
                    value: '',
                    text: 'No VMN available for this calling option.'
                }));
                $('#smart31-status-text').text('No VMN available for this calling option.').css('color', '#EF4444');
                $('#smart31-status-dot').css('background', '#EF4444');
            }
        },
        error: function() {
            var $select = $('#smart31_vmn_select');
            $select.html('<option value="">No VMN available for this calling option.</option>');
            $('#smart31-status-text').text('No VMN available for this calling option.').css('color', '#EF4444');
            $('#smart31-status-dot').css('background', '#EF4444');
        }
    });
}

function load1600Vmns() {
    if (!$('#vmn1600_toggle').is(':checked')) return;

    var callingType = activeCallOption; // 'webrtc' or 'click2call'
    var typeLabel = (callingType === 'click2call') ? 'Click2Call 1600 VMN' : 'WebRTC 1600 VMN';
    $('#vmn1600-vmn-title').text(typeLabel);

    $('#vmn1600_vmn_select').html('<option value="">Loading 1600 VMNs...</option>');
    $('#vmn1600-status-text').text('Loading...').css('color', '#64748B');
    $('#vmn1600-status-dot').css('background', '#94A3B8');

    $.ajax({
        url: "{{ route('dialer1600Vmns') }}",
        type: 'GET',
        data: { type: callingType },
        dataType: 'json',
        success: function(res) {
            var $select = $('#vmn1600_vmn_select');
            $select.empty();

            if (res.success && res.vmns && res.vmns.length > 0) {
                $.each(res.vmns, function(idx, item) {
                    $select.append($('<option>', {
                        value: item.number,
                        text: item.display
                    }));
                });
                $('#vmn1600-status-text').text('1600 Active').css('color', '#10B981');
                $('#vmn1600-status-dot').css('background', '#10B981');
            } else {
                $select.append($('<option>', {
                    value: '',
                    text: 'No 1600 VMN available for this calling option.'
                }));
                $('#vmn1600-status-text').text('No 1600 VMN available for this calling option.').css('color', '#EF4444');
                $('#vmn1600-status-dot').css('background', '#EF4444');
            }
        },
        error: function() {
            var $select = $('#vmn1600_vmn_select');
            $select.html('<option value="">No 1600 VMN available for this calling option.</option>');
            $('#vmn1600-status-text').text('No 1600 VMN available for this calling option.').css('color', '#EF4444');
            $('#vmn1600-status-dot').css('background', '#EF4444');
        }
    });
}

/* ── Number-only filter helpers ─────────────────── */
function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    // Allow digits 0-9 (ASCII 48 to 57). Block alphabets, spaces, and special characters.
    if (evt.which !== 0 && evt.charCode !== 0 && (charCode < 48 || charCode > 57)) {
        evt.preventDefault();
        return false;
    }
    return true;
}

function handleMobilePaste(evt) {
    var clipboardData = (evt.originalEvent || evt).clipboardData;
    if (clipboardData) {
        var text = clipboardData.getData('text');
        if (text !== undefined && text !== null) {
            evt.preventDefault();
            var clean = text.replace(/[^0-9]/g, '');
            var input = evt.target || document.getElementById('mobile');
            var start = input.selectionStart || 0;
            var end = input.selectionEnd || 0;
            var val = input.value || '';
            var next = (val.substring(0, start) + clean + val.substring(end)).replace(/[^0-9]/g, '');
            if (input.maxLength > 0 && next.length > input.maxLength) {
                next = next.substring(0, input.maxLength);
            }
            input.value = next;
            var newCursor = Math.min(start + clean.length, next.length);
            input.setSelectionRange(newCursor, newCursor);
            onMobileKey();
        }
    }
}

/* ── Mobile input ───────────────────────────────── */
function onMobileKey() {
    var el = document.getElementById('mobile');
    if (el) {
        var clean = el.value.replace(/[^0-9]/g, '');
        if (el.value !== clean) {
            el.value = clean;
        }
    }
    var v = ($('#mobile').val() || '').trim();
    if (v.length >= 10) {
        $('#lead-mini-preview').show();
        $('#lead-mini-phone').text('+91 ' + v);
    } else {
        $('#lead-mini-preview').hide();
    }
    updateLiveCallInfo();
}

function clearMobile() {
    $('#mobile').val('');
    $('#lead-mini-preview').hide();
    updateLiveCallInfo();
}

function copyPhone() {
    var phone = ($('#mobile').val() || '').replace(/[^0-9]/g, '');
    if (phone) {
        navigator.clipboard.writeText(phone).then(function() {
            toastr.info('Phone number copied!');
        });
    }
}

function updateLiveCallInfo() {
    var phone = ($('#mobile').val() || '').replace(/[^0-9]/g, '');
    $('#lc-phone').text(phone ? ('+91 ' + phone) : '+91 —');
    $('#lc-avatar').text(getInitials($('#lead-mini-name').text() || phone));
}

function getInitials(name) {
    var parts = name.trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
}

/* ── Call Status select ─────────────────────────── */
function selectCallStatus(status, btn) {
    $('.lg-cs-btn').removeClass('cs-connected cs-busy cs-no-answer cs-failed');
    var map = { connected: 'cs-connected', busy: 'cs-busy', no_answer: 'cs-no-answer', failed: 'cs-failed' };
    $(btn).addClass(map[status] || '');
    $('#call_status_val').val(status);

    // Auto-select lead status
    $('#status_id option').each(function() {
        if ($(this).text().toLowerCase().indexOf(status.replace('_', ' ')) !== -1) {
            $(this).prop('selected', true);
        }
    });
    toastr.info('Call status: ' + status.replace(/_/g, ' ').toUpperCase());
}

/* ── Character counter ──────────────────────────── */
function updateCharCount() {
    var len = $('#comments').val().length;
    $('#char-count').text(len);
}

/* ── Live call timer ────────────────────────────── */
function startCallTimer() {
    callSeconds = 0;
    if (callTimerInterval) clearInterval(callTimerInterval);
    callTimerInterval = setInterval(function() {
        callSeconds++;
        var h = Math.floor(callSeconds / 3600);
        var m = Math.floor((callSeconds % 3600) / 60);
        var s = callSeconds % 60;
        var str = pad(h) + ':' + pad(m) + ':' + pad(s);
        $('#lc-timer').text(str);
        $('#lc-duration').text(str);
    }, 1000);
}

function stopCallTimer() {
    if (callTimerInterval) { clearInterval(callTimerInterval); callTimerInterval = null; }
    $('#time_taken').val(callSeconds);
}

function pad(n) { return n < 10 ? '0' + n : '' + n; }

/* ── Show / hide live call card ─────────────────── */
function showLiveCall() {
    var phone = $('#mobile').val();
    var vmn   = $('#vmn').val() || $('#wvmn').val() || '+91 6744123502';
    $('#lc-phone').text('+91 ' + phone);
    $('#lc-callerid').text('+91 ' + vmn);
    $('#lc-mode').text(activeCallOption === 'click2call' ? 'Click2Call' : 'WebRTC');
    $('#lg-livecall-card').slideDown(200);
    startCallTimer();
}

function endCall() {
    stopCallTimer();
    $('#lg-livecall-card').slideUp(200);
    window._sipCallInProgress = false;
    toastr.warning('Call ended. Please fill out the Call Outcome.');
}

/* ── Mute / Speaker ─────────────────────────────── */
function toggleMute(btn) {
    isMuted = !isMuted;
    if (isMuted) {
        $(btn).find('i').removeClass('fa-microphone').addClass('fa-microphone-slash');
        $(btn).css('background', 'rgba(239,68,68,0.3)');
        toastr.info('Microphone muted');
    } else {
        $(btn).find('i').removeClass('fa-microphone-slash').addClass('fa-microphone');
        $(btn).css('background', 'rgba(255,255,255,0.15)');
        toastr.info('Microphone unmuted');
    }
}

function toggleSpeaker(btn) {
    var $i = $(btn).find('i');
    if ($i.hasClass('fa-volume-up')) {
        $i.removeClass('fa-volume-up').addClass('fa-volume-off');
        toastr.info('Speaker off');
    } else {
        $i.removeClass('fa-volume-off').addClass('fa-volume-up');
        toastr.info('Speaker on');
    }
}

/* ── Execute call ───────────────────────────────── */
function executeSelectedCall() {
    if (window.callingHoursClosed) {
        toastr.warning(window.callingHoursNotice || 'Calling is currently closed.');
        return false;
    }
    var mobile = ($('#mobile').val() || '').replace(/[^0-9]/g, '');
    $('#mobile').val(mobile);
    if (!mobile || mobile.length < 10) {
        toastr.error('Please enter minimum 10 digits in mobile number');
        return false;
    }
    if (activeCallOption === 'webrtc') {
        webrtc();
    } else if (activeCallOption === 'click2call') {
        click2call();
    } else {
        phone();
    }
}

/* ── Clear All ──────────────────────────────────── */
function clearCallInputs() {
    $('#mobile').val('');
    $('#comments').val('');
    $('#char-count').text('0');
    $('#lead-mini-preview').hide();
    updateLiveCallInfo();
    toastr.info('Inputs cleared');
}

/* ── Save log ───────────────────────────────────── */
function savelog() {
    var mobile = ($('#mobile').val() || '').replace(/[^0-9]/g, '');
    $('#mobile').val(mobile);
    if (!mobile || mobile.length < 10) {
        toastr.error('Please enter minimum 10 digits in mobile number');
        return false;
    }
    if ($('#status_id').val() == '') {
        toastr.error('Please select a lead status');
        return false;
    }

    jQuery.ajax({
        type: 'POST',
        url: "{{route('dialerSaveLog')}}",
        data: $('#add-edit-form').serialize(),
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) {}
            }
            if (response && response.success === false) {
                toastr.error(response.message || 'Error saving call log');
                return;
            }
            if (typeof Android != 'undefined') {
                Android.showToast($('#dp_code').val() || '', '{{$User->id}}', $('#mobile').val());
            }
            toastr.success('Call log saved successfully!');
            setTimeout(function() { location.reload(); }, 1200);
        },
        error: function(xhr) {
            var msg = 'Error saving call log';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            toastr.error(msg);
        }
    });
}

/* ── Skip ───────────────────────────────────────── */
function skipLead() {
    toastr.info('Skipping to next lead...');
    setTimeout(function() { location.reload(); }, 800);
}

/* ── Preserved click2call/webrtc/phone/sip functions ── */
function phone() {
    var mobile = $('#mobile').val();
    if (!mobile || mobile.length < 10) {
        toastr.error('Please enter minimum 10 digits in mobile number');
        return false;
    }
    let phone_url = 'tel:' + mobile;
    $('#uuid').val(Math.floor(100000 + Math.random() * 900000));
    window.open(phone_url, '_blank');
}

function sipcall() {
    var mobile = $('#mobile').val();
    if (!mobile || mobile.length < 10) {
        toastr.error('Please enter minimum 10 digits in mobile number');
        return false;
    }
    $('#call_type').val('sip');
    let dcm_url = 'dcm:' + mobile;
    window.open(dcm_url, '_blank');
}

function click2call() {
    var mobile = ($('#mobile').val() || '').replace(/[^0-9]/g, '');
    $('#mobile').val(mobile);
    let isSmart31 = $('#smart31_toggle').is(':checked');
    let is1600 = $('#vmn1600_toggle').is(':checked');
    let vmn;
    if (isSmart31) {
        vmn = $('#smart31_vmn_select').val();
        if (!vmn || vmn === '') {
            toastr.error('No VMN available for this calling option.');
            return false;
        }
    } else if (is1600) {
        vmn = $('#vmn1600_vmn_select').val();
        if (!vmn || vmn === '') {
            toastr.error('No 1600 VMN available for this calling option.');
            return false;
        }
    } else {
        vmn = $('#vmn').val() || $('#wvmn').val() || $('[name="vmn"]').val() || '';
        if (vmn) {
            vmn = vmn.replace(/[^0-9]/g, '');
            if (vmn.length === 12 && vmn.startsWith('91')) {
                vmn = vmn.substring(2);
            }
        }
    }
    let default_vmn = $('#default_vmn').is(':checked');
    if (!vmn) { toastr.error('Please select VMN'); return false; }
    if (mobile.length < 10) { toastr.error('Please enter 10 digits mobile number'); return false; }

    var token = "{{ csrf_token() }}";
    jQuery.ajax({
        type: 'POST',
        url: "{{route('dialerClick2call')}}",
        data: { '_token': token, mobile: mobile, vmn: vmn, default_vmn: default_vmn, smart31_enabled: isSmart31 ? 1 : 0, vmn1600_enabled: is1600 ? 1 : 0 },
        dataType: 'json',
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) {}
            }
            if (response.success == true) {
                var $btn = $('#btn-call-trigger');
                $btn.prop('disabled', true).find('#call-btn-label').text('Connecting...');
                setTimeout(function() {
                    $btn.prop('disabled', false).find('#call-btn-label').text('Call Now →');
                    showLiveCall();
                }, 2000);
                $('#uuid').val(response.uuid);
                $('#id').val(response.id);
                $('#is_campaign').val(response && response.is_campaign ? '1' : '0');
                $('#call_type').val('click2call');
                toastr.success('Click2Call initiated!');
            } else {
                toastr.warning(response.message || 'Call failed');
            }
        },
        error: function(xhr) {
            var msg = 'Failed to initiate Click2Call';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            if (xhr && (xhr.status === 403 || xhr.status === 422)) {
                toastr.warning(msg);
            } else {
                toastr.error(msg);
            }
        }
    });
}

function webrtc() {
    if (window._sipCallInProgress) { alert('Already on a call'); return; }
    window._sipCallInProgress = true;
    var mobile = ($('#mobile').val() || '').replace(/[^0-9]/g, '');
    $('#mobile').val(mobile);
    let isSmart31 = $('#smart31_toggle').is(':checked');
    let is1600 = $('#vmn1600_toggle').is(':checked');
    let vmn;
    if (isSmart31) {
        vmn = $('#smart31_vmn_select').val();
        if (!vmn || vmn === '') {
            toastr.error('No VMN available for this calling option.');
            window._sipCallInProgress = false;
            return false;
        }
    } else if (is1600) {
        vmn = $('#vmn1600_vmn_select').val();
        if (!vmn || vmn === '') {
            toastr.error('No 1600 VMN available for this calling option.');
            window._sipCallInProgress = false;
            return false;
        }
    } else {
        vmn = $('#wvmn').val() || $('#vmn').val() || $('[name="vmn"]').val() || '';
        if (vmn) {
            vmn = vmn.replace(/[^0-9]/g, '');
            if (vmn.length === 12 && vmn.startsWith('91')) {
                vmn = vmn.substring(2);
            }
        }
    }
    let default_wvmn = $('#default_wvmn').is(':checked');
    if (!vmn) { toastr.error('Please select VMN'); window._sipCallInProgress = false; return false; }
    if (mobile.length < 10) { toastr.error('Please enter 10 digits mobile number'); window._sipCallInProgress = false; return false; }

    var token = "{{ csrf_token() }}";
    jQuery.ajax({
        type: 'POST',
        url: "{{route('dialerwebrtc')}}",
        data: { '_token': token, mobile: mobile, vmn: vmn, default_wvmn: default_wvmn, smart31_enabled: isSmart31 ? 1 : 0, vmn1600_enabled: is1600 ? 1 : 0 },
        dataType: 'json',
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) {}
            }
            if (response.success == true) {
                var $btn = $('#btn-call-trigger');
                $btn.prop('disabled', true).find('#call-btn-label').text('Connecting...');
                setTimeout(function() {
                    $btn.prop('disabled', false).find('#call-btn-label').text('Call Now →');
                }, 5000);

                if (typeof window._softphoneReady === 'undefined') {
                    alert('SIP module not loaded yet. Please wait a moment and try again.');
                    window._sipCallInProgress = false;
                    return;
                }

                window._softphoneReady.then(function(sp) {
                    sp.initSoftphone({
                        username:  response.sip_id       || (window.__SIP_CONFIG__ || {}).username  || '',
                        password:  response.sip_password || (window.__SIP_CONFIG__ || {}).password  || '',
                        domain:    response.sip_ip       || (window.__SIP_CONFIG__ || {}).domain    || '',
                        websocket: response.sip_ws       || (window.__SIP_CONFIG__ || {}).websocket || '',
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
                        alert('SIP call failed: ' + err.message);
                        window._sipCallInProgress = false;
                    });
                });

                $('#uuid').val(response.uuid);
                $('#id').val(response.id);
                $('#is_campaign').val(response && response.is_campaign ? '1' : '0');
                $('#call_type').val('webrtc');
                showLiveCall();
                toastr.success('WebRTC call initiated!');
            } else {
                toastr.warning(response.message || 'Call failed');
                window._sipCallInProgress = false;
            }
        },
        error: function(xhr) {
            window._sipCallInProgress = false;
            var msg = 'Failed to initiate WebRTC call';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            if (xhr && (xhr.status === 403 || xhr.status === 422)) {
                toastr.warning(msg);
            } else {
                toastr.error(msg);
            }
        }
    });
}

/* ── Performance circle & Phone input bindings ─── */
$(function() {
    // Strict numeric-only enforcement for Phone Number input
    $('#mobile').on('keypress', function(e) {
        return isNumberKey(e);
    });

    $('#mobile').on('input', function() {
        var clean = this.value.replace(/[^0-9]/g, '');
        if (this.value !== clean) {
            this.value = clean;
        }
        onMobileKey();
    });

    $('#mobile').on('paste', function(e) {
        handleMobilePaste(e);
    });

    var total = parseInt($('#perf-total').text())     || 28;
    var connected = parseInt($('#perf-connected').text()) || 22;
    var pct = total > 0 ? Math.round((connected / total) * 100) : 0;
    var circumference = 2 * Math.PI * 28; // r=28
    var offset = circumference - (pct / 100) * circumference;
    $('#perf-prog-circle').attr('stroke-dasharray', circumference.toFixed(1));
    $('#perf-prog-circle').attr('stroke-dashoffset', offset.toFixed(1));
    $('#perf-pct').text(pct + '%');
});
</script>

<script type="module">
(async () => {
    try {
        const sp = await import("{{ asset('js/webrtc-softphone.js') }}?v={{ time() }}");
        window._softphoneReady = Promise.resolve(sp);
        if (window.__SIP_AUTO_REGISTER__) {
            const cfg = window.__SIP_CONFIG__ || {};
            if (cfg.username) sp.initSoftphone(cfg).catch(err => console.warn('Pre-register failed:', err));
        }
    } catch (e) {
        console.error('Softphone module failed to load', e);
    }
})();

import {
    initSip   as _initSip,
    makeCall,
    hangupCall,
    muteCall,
    holdCall,
    closeSipDialerUI,
    onCallEnded as _onCallEnded,
} from "{{ asset('js/sip-client.js') }}?v={{ time() }}";

_onCallEnded(() => {
    const el = document.getElementById('sip-timer');
    if (el) el.textContent = '00:00';
});

globalThis.sipHangup      = e => { e?.preventDefault(); hangupCall(); };
globalThis.sipMute        = e => { e?.preventDefault(); muteCall();   };
globalThis.sipHold        = e => { e?.preventDefault(); holdCall();   };
globalThis.closeSipDialer = () => { hangupCall(); closeSipDialerUI(); };
</script>

@endsection
