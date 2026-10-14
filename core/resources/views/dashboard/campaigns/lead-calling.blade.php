@extends('dashboard.layouts.master')
@section('title', 'LeadGER - Sales Calling - ' . ($Lead->name ?? 'Lead ' . $Lead->id))

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ==========================================================================
   LeadGER Sales Calling Workspace - Prototype Exact Redesign
   ========================================================================== */
:root {
    --lg-primary: #1464F4;
    --lg-primary-dark: #0F52CC;
    --lg-primary-light: #EFF6FF;
    --lg-navy: #0F172A;
    --lg-text-secondary: #64748B;
    --lg-border: #E2E8F0;
    --lg-card-bg: #FFFFFF;
    --lg-page-bg: #F4F7FB;
    --lg-green: #10B981;
    --lg-green-hover: #0DA770;
    --lg-green-light: #ECFDF5;
    --lg-amber: #F59E0B;
    --lg-amber-light: #FFFBEB;
    --lg-blue: #3B82F6;
    --lg-blue-light: #EFF6FF;
    --lg-purple: #8B5CF6;
    --lg-purple-light: #F5F3FF;
    --lg-red: #EF4444;
    --lg-red-light: #FEF2F2;
    --lg-orange: #F97316;
}

.lg-calling-canvas {
    padding: 16px 20px 36px 20px;
    background-color: var(--lg-page-bg);
    min-height: calc(100vh - 60px);
    font-family: var(--lg-font-sans);
    color: var(--lg-navy);
}

/* ── Workspace Header Card ──────────────────────────────── */
.lg-calling-header {
    background: var(--lg-card-bg);
    border: 1px solid var(--lg-border);
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--lg-accent) 0%, #0F52CC 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    box-shadow: 0 4px 12px rgba(23, 105, 255, 0.22);
    flex-shrink: 0;
}

.lg-header-breadcrumb {
    font-size: 12px;
    font-weight: 500;
    color: var(--lg-text-secondary);
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.lg-header-breadcrumb a {
    color: var(--lg-primary);
    text-decoration: none;
}
.lg-header-breadcrumb a:hover {
    text-decoration: underline;
}

.lg-header-title {
    margin: 0;
    font-weight: 700;
    color: var(--lg-navy);
    font-size: 20px;
    line-height: 1.25;
}

.lg-header-sub {
    font-size: 12px;
    color: var(--lg-text-secondary);
    margin-top: 3px;
    font-weight: 500;
}

.lg-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* ── Buttons ────────────────────────────────────────────── */
.lg-btn {
    height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    border: none;
    box-sizing: border-box;
}

.lg-btn-primary {
    background: var(--lg-primary);
    color: #FFFFFF;
    border: 1px solid var(--lg-primary);
    box-shadow: 0 2px 6px rgba(23, 105, 255, 0.2);
}
.lg-btn-primary:hover {
    background: var(--lg-primary-dark);
    border-color: var(--lg-primary-dark);
    color: #FFFFFF;
}

.lg-btn-secondary {
    background: #FFFFFF;
    color: #334155;
    border: 1px solid var(--lg-border);
}
.lg-btn-secondary:hover {
    background: #F1F5F9;
    color: var(--lg-navy);
    border-color: #94A3B8;
}

/* ── Two-Column Layout (Queue on LEFT, Workspace on RIGHT) ── */
.lg-calling-grid {
    display: grid;
    grid-template-columns: 320px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
}

@media (max-width: 1024px) {
    .lg-calling-grid {
        grid-template-columns: 1fr;
    }
}

/* ── Reusable Card ──────────────────────────────────────── */
.lg-card {
    background: var(--lg-card-bg);
    border: 1px solid var(--lg-border);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    margin-bottom: 18px;
    overflow: hidden;
}

.lg-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--lg-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #FAFCFF;
}

.lg-card-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--lg-navy);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.lg-card-title i {
    color: var(--lg-primary);
}

.lg-card-body {
    padding: 20px;
}

/* ── Status Pills (Prototype Match) ─────────────────────── */
.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    white-space: nowrap;
}

.lg-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}

.pill-green {
    background: var(--lg-green-light);
    color: #059669;
    border: 1px solid #A7F3D0;
}
.pill-green .lg-dot { background: #059669; }

.pill-amber {
    background: var(--lg-amber-light);
    color: #D97706;
    border: 1px solid #FDE68A;
}
.pill-amber .lg-dot { background: #D97706; }

.pill-blue {
    background: var(--lg-blue-light);
    color: var(--lg-accent);
    border: 1px solid #BFDBFE;
}
.pill-blue .lg-dot { background: var(--lg-accent); }

.pill-slate {
    background: #F1F5F9;
    color: #475569;
    border: 1px solid #CBD5E1;
}
.pill-slate .lg-dot { background: #64748B; }

/* ── LEFT QUEUE SIDEBAR (Prototype Exact Match) ─────────── */
.lg-queue-sidebar {
    background: var(--lg-card-bg);
    border: 1px solid var(--lg-border);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    position: sticky;
    top: 20px;
}

.lg-queue-header {
    padding: 14px 18px;
    background: #FCFDFE;
    border-bottom: 1px solid var(--lg-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lg-queue-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.lg-queue-accent-bar {
    width: 3px;
    height: 16px;
    background: var(--lg-orange);
    border-radius: 2px;
    display: inline-block;
}

.lg-queue-title {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--lg-navy);
    margin: 0;
}

.lg-queue-count {
    background: var(--lg-primary-light);
    color: var(--lg-primary);
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 12px;
}

.lg-queue-search {
    padding: 10px 14px;
    border-bottom: 1px solid var(--lg-border);
    background: #FFFFFF;
}

.lg-queue-search-input {
    width: 100%;
    border: 1px solid var(--lg-border);
    border-radius: 6px;
    padding: 7px 10px 7px 30px;
    font-size: 12px;
    box-sizing: border-box;
    background: #F8FAFC url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") no-repeat 10px center;
    transition: border-color 0.15s ease;
}
.lg-queue-search-input:focus {
    outline: none;
    border-color: var(--lg-primary);
    background-color: #FFFFFF;
}

/* Independent Vertical Scroll for Queue */
.lg-queue-list {
    max-height: calc(100vh - 220px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #CBD5E1 #F8FAFC;
}

.lg-queue-list::-webkit-scrollbar {
    width: 5px;
}
.lg-queue-list::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 4px;
}

.lg-queue-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #F1F5F9;
    text-decoration: none !important;
    transition: background-color 0.15s ease;
    cursor: pointer;
}

.lg-queue-item:hover {
    background-color: #F8FAFC;
}

.lg-queue-item.active {
    background-color: #EFF6FF;
    border-left: 3px solid var(--lg-primary);
}

.lg-queue-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--lg-primary);
    color: #FFFFFF;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.lg-queue-info {
    flex: 1;
    min-width: 0;
}

.lg-queue-name {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--lg-navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 0 1px 0;
}

.lg-queue-phone {
    font-size: 11.5px;
    color: var(--lg-text-secondary);
    display: block;
}

.lg-queue-subtext {
    font-size: 11px;
    color: #94A3B8;
    margin-top: 1px;
}

.lg-queue-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.lg-queue-badge {
    font-size: 10.5px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 12px;
    white-space: nowrap;
}

.lg-queue-active-tag {
    font-size: 9.5px;
    font-weight: 800;
    color: var(--lg-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ── RIGHT MAIN WORKSPACE (Prototype Exact Match) ────────── */
.lg-proto-status-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #F1F5F9;
}

.lg-proto-status-left {
    display: flex;
    flex-direction: column;
}

.lg-proto-status-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--lg-navy);
}

.lg-proto-status-sub {
    font-size: 11.5px;
    color: var(--lg-text-secondary);
    margin-top: 1px;
}

.lg-proto-timer {
    font-family: 'Courier New', Courier, monospace;
    font-size: 26px;
    font-weight: 800;
    color: var(--lg-navy);
    letter-spacing: 1.5px;
}

.lg-proto-lead-profile {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.lg-proto-lead-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lg-proto-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--lg-accent) 0%, #0F52CC 100%);
    color: #FFFFFF;
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(23, 105, 255, 0.25);
}

.lg-proto-lead-meta {
    display: flex;
    flex-direction: column;
}

.lg-proto-lead-name {
    font-size: 22px;
    font-weight: 800;
    color: var(--lg-navy);
    line-height: 1.2;
    margin: 0 0 2px 0;
}

.lg-proto-lead-phone {
    font-size: 15px;
    font-weight: 600;
    color: var(--lg-text-secondary);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Prototype Info Grid */
.lg-proto-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 24px;
    background: #FAFCFF;
    border: 1px solid #EDF2F7;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 22px;
}

@media (max-width: 600px) {
    .lg-proto-info-grid {
        grid-template-columns: 1fr;
    }
}

.lg-proto-info-item {
    display: flex;
    flex-direction: column;
}

.lg-proto-info-label {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--lg-text-secondary);
    margin-bottom: 2px;
    text-transform: capitalize;
}

.lg-proto-info-val {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--lg-navy);
    word-break: break-word;
}

/* Call Actions Row */
.lg-proto-action-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-btn-startcall-lg {
    flex: 1;
    height: 46px;
    background: var(--lg-green);
    color: #FFFFFF !important;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    text-decoration: none !important;
    box-shadow: 0 3px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.15s ease;
}

.lg-btn-startcall-lg:hover {
    background: var(--lg-green-hover);
    box-shadow: 0 5px 18px rgba(16, 185, 129, 0.42);
    transform: translateY(-1px);
}

.lg-btn-wa-lg {
    height: 46px;
    padding: 0 18px;
    background: #25D366;
    color: #FFFFFF !important;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none !important;
    transition: all 0.15s ease;
}
.lg-btn-wa-lg:hover { background: #1EBE5D; }

.lg-btn-copy-lg {
    height: 46px;
    padding: 0 16px;
    background: #FFFFFF;
    color: var(--lg-navy);
    border: 1.5px solid var(--lg-border);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}
.lg-btn-copy-lg:hover { background: #F1F5F9; border-color: #94A3B8; }

/* ── Live Call Session Active Controls Bar ───────────────── */
.lg-call-session-bar {
    background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
    color: #FFFFFF;
    border-radius: 12px;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
}

.lg-session-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-session-pulse {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.2);
    border: 2px solid var(--lg-green);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lg-green);
    font-size: 15px;
}

.lg-session-title {
    font-size: 14px;
    font-weight: 700;
    margin: 0;
    color: #FFFFFF;
}

.lg-session-sub {
    font-size: 12px;
    color: #94A3B8;
    margin-top: 2px;
}

.lg-session-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.lg-session-btn {
    height: 36px;
    padding: 0 16px;
    border-radius: 7px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}

.lg-session-btn-start {
    background: var(--lg-green);
    color: #FFFFFF;
}
.lg-session-btn-start:hover { background: #059669; }

.lg-session-btn-stop {
    background: var(--lg-red);
    color: #FFFFFF;
}
.lg-session-btn-stop:hover { background: #DC2626; }

/* ── Disposition Form ───────────────────────────────────── */
.lg-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

@media (max-width: 768px) {
    .lg-form-row {
        grid-template-columns: 1fr;
    }
}

.lg-field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.lg-label {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--lg-navy);
    display: flex;
    align-items: center;
    gap: 6px;
}

.lg-label i {
    color: var(--lg-primary);
    font-size: 12px;
}

.lg-label .req {
    color: var(--lg-red);
}

.lg-select, .lg-input, .lg-textarea {
    width: 100%;
    border: 1.5px solid var(--lg-border);
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 13px;
    color: var(--lg-navy);
    background: #FFFFFF;
    box-sizing: border-box;
    font-family: inherit;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lg-select:focus, .lg-input:focus, .lg-textarea:focus {
    outline: none;
    border-color: var(--lg-primary);
    box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12);
}

.lg-textarea {
    resize: vertical;
    min-height: 85px;
}

.lg-disposition-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid var(--lg-border);
    flex-wrap: wrap;
}

/* ── Call History Timeline Table ────────────────────────── */
.lg-history-table {
    width: 100%;
    border-collapse: collapse;
}

.lg-history-table th {
    text-align: left;
    font-size: 11.5px;
    font-weight: 600;
    color: var(--lg-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 14px;
    border-bottom: 1px solid var(--lg-border);
    background: #F8FAFC;
}

.lg-history-table td {
    padding: 12px 14px;
    font-size: 13px;
    color: var(--lg-navy);
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
}

.lg-history-table tr:hover td {
    background-color: #F8FAFC;
}

.lg-empty-history {
    text-align: center;
    padding: 30px 20px;
    color: var(--lg-text-secondary);
}
.lg-empty-history i {
    font-size: 32px;
    color: #CBD5E1;
    margin-bottom: 10px;
    display: block;
}
</style>
@endpush

@section('content')
<div class="lg-calling-canvas">

    {{-- ══════════════ 1. WORKSPACE HEADER CARD ══════════════ --}}
    <div class="lg-calling-header">
        <div class="lg-header-left">
            <div class="lg-header-icon">
                <i class="fa fa-phone-volume"></i>
            </div>
            <div>
                <h1 class="lg-header-title">Sales Calling Workspace</h1>
                <div class="lg-header-sub">
                    Agent: <strong>{{ Auth::user()->name ?? 'Sales Admin' }}</strong> &bull; Campaign: {{ $Campaign->name }} &bull; Queue: {{ $totalQueueCount }} Leads
                </div>
            </div>
        </div>
        <div class="lg-header-right">
            <a href="{{ route('campaignsView', $campaignId) }}" class="lg-btn lg-btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Campaign
            </a>
            @if(!empty($nextLeadId))
                <a href="{{ route('leadCallingView', ['campaignId' => $campaignId, 'leadId' => $nextLeadId]) }}" class="lg-btn lg-btn-primary" title="Go to next lead in queue">
                    <span>Next Lead</span> <i class="fa fa-arrow-right"></i>
                </a>
            @endif
        </div>
    </div>

    {{-- ══════════════ 2. TWO-COLUMN LAYOUT (Next Leads LEFT | Workspace RIGHT) ══════════════ --}}
    <div class="lg-calling-grid">

        {{-- ── LEFT COLUMN: NEXT LEADS / CAMPAIGN QUEUE ── --}}
        <div>
            <div class="lg-queue-sidebar">
                <div class="lg-queue-header">
                    <div class="lg-queue-header-left">
                        <span class="lg-queue-accent-bar"></span>
                        <h4 class="lg-queue-title">Next leads</h4>
                    </div>
                    <span class="lg-queue-count">{{ $totalQueueCount }} in queue</span>
                </div>

                {{-- Fast Search --}}
                <div class="lg-queue-search">
                    <svg class="lg-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="queueSearchInput" class="lg-queue-search-input" placeholder="Search queue by name or phone..." onkeyup="filterQueueList()">
                </div>

                {{-- Queue List with Independent Vertical Scrolling --}}
                <div class="lg-queue-list" id="queueListContainer">
                    @forelse($campaignLeads as $cLead)
                        @php
                            $isActive = ($cLead->id == $Lead->id);
                            $badgeCol = !empty($cLead->badge_color) ? $cLead->badge_color : '#3B82F6';
                        @endphp
                        <a href="{{ route('leadCallingView', ['campaignId' => $campaignId, 'leadId' => $cLead->id]) }}" 
                           class="lg-queue-item {{ $isActive ? 'active' : '' }}" 
                           data-search="{{ strtolower(($cLead->name ?? '') . ' ' . ($cLead->phonenumber ?? '') . ' LD' . $cLead->id) }}">
                            
                            <div class="lg-queue-avatar">
                                {{ $cLead->initials ?: 'LD' }}
                            </div>

                            <div class="lg-queue-info">
                                <div class="lg-queue-name" title="{{ $cLead->name ?: 'Lead ' . $cLead->id }}">
                                    {{ $cLead->name ?: 'Lead ' . $cLead->id }}
                                </div>
                                <span class="lg-queue-phone">{{ $cLead->phonenumber ?: '—' }}</span>
                                <div class="lg-queue-subtext">&bull; {{ $cLead->product_name ?: 'Home Loan' }}</div>
                            </div>

                            <div class="lg-queue-meta">
                                <span class="lg-queue-badge" style="background: {{ $badgeCol }}15; color: {{ $badgeCol }}; border: 1px solid {{ $badgeCol }}30;">
                                    &bull; {{ $cLead->status_name ?: 'New' }}
                                </span>
                                @if($isActive)
                                    <span class="lg-queue-active-tag">Active</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div style="padding: 24px 16px; text-align: center; color: #94A3B8; font-size: 13px;">
                            No other leads in queue.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── RIGHT COLUMN: SELECTED LEAD & MAIN WORKSPACE ── --}}
        <div>

            {{-- 1. MAIN LEAD DETAILS & CALL BANNER CARD (Prototype Style) --}}
            <div class="lg-card">
                <div class="lg-card-body">
                    
                    {{-- Status / Timer Top Row --}}
                    <div class="lg-proto-status-bar">
                        <div class="lg-proto-status-left">
                            <div class="lg-proto-status-title" id="callSessionStatusTitle">Ready to call</div>
                            <div class="lg-proto-status-sub" id="callSessionStatusSub">Press Start Call when ready</div>
                        </div>
                        <div class="lg-proto-timer" id="callDurationTimer">
                            00:00:00
                        </div>
                    </div>

                    {{-- Lead Profile Row --}}
                    <div class="lg-proto-lead-profile">
                        <div class="lg-proto-lead-left">
                            <div class="lg-proto-avatar">
                                {{ $Lead->initials ?: 'LD' }}
                            </div>
                            <div class="lg-proto-lead-meta">
                                <h2 class="lg-proto-lead-name">{{ $Lead->name ?: 'Unnamed Lead' }}</h2>
                                <div class="lg-proto-lead-phone">
                                    <span>{{ $Lead->phonenumber ?: '—' }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            @php
                                $sLower = strtolower($Lead->status_name ?? 'new');
                                $pillClass = 'pill-blue';
                                if (strpos($sLower, 'connect') !== false || strpos($sLower, 'interest') !== false || strpos($sLower, 'deal') !== false || strpos($sLower, 'won') !== false) {
                                    $pillClass = 'pill-green';
                                } elseif (strpos($sLower, 'follow') !== false || strpos($sLower, 'pending') !== false || strpos($sLower, 'back') !== false) {
                                    $pillClass = 'pill-amber';
                                } elseif (strpos($sLower, 'new') !== false || strpos($sLower, 'unactioned') !== false) {
                                    $pillClass = 'pill-blue';
                                } else {
                                    $pillClass = 'pill-slate';
                                }
                            @endphp
                            <span class="lg-status-pill {{ $pillClass }}" id="leadCurrentStatusBadge">
                                <span class="lg-dot"></span>
                                <span id="leadCurrentStatusText">{{ $Lead->status_name ?: 'New' }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- Prototype Clean Grid of Details --}}
                    <div class="lg-proto-info-grid">
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">City</div>
                            <div class="lg-proto-info-val">{{ $Lead->city_name ?: 'Hyderabad' }}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Product</div>
                            <div class="lg-proto-info-val">{{ $Lead->product_name ?: 'Home Loan' }}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Campaign</div>
                            <div class="lg-proto-info-val">{{ $Campaign->name ?: 'September Home Loan' }}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Lead ID</div>
                            <div class="lg-proto-info-val">{!! Helper::leadIdLink($Lead->id) !!}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Source</div>
                            <div class="lg-proto-info-val">{{ $Lead->source_name ?: 'Website' }}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Calls so far</div>
                            <div class="lg-proto-info-val">{{ $Lead->calls_count ?: count($callLogs) }}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Mobile Number</div>
                            <div class="lg-proto-info-val">{{ $Lead->phonenumber ?: '—' }}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Alternate Phone</div>
                            <div class="lg-proto-info-val">{{ $Lead->phonenumber_2 ?: '—' }}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Email Address</div>
                            <div class="lg-proto-info-val">{{ $Lead->email ?: '—' }}</div>
                        </div>
                        <div class="lg-proto-info-item">
                            <div class="lg-proto-info-label">Last Contacted</div>
                            <div class="lg-proto-info-val">{{ $Lead->last_actioned_date ?: 'Not contacted yet' }}</div>
                        </div>
                    </div>

                    {{-- Full-Width Call Actions --}}
                    <div class="lg-proto-action-row">
                        <a href="tel:{{ $Lead->phonenumber }}" class="lg-btn-startcall-lg" id="btnTelCall" onclick="startCallTimer()">
                            <i class="fa fa-phone"></i> Start Call
                        </a>
                        @if(!empty($Lead->phonenumber))
                            @php
                                $cleanPhone = preg_replace('/[^0-9]/', '', $Lead->phonenumber);
                                if(strlen($cleanPhone) == 10) { $cleanPhone = '91' . $cleanPhone; }
                            @endphp
                            <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="lg-btn-wa-lg" title="Open WhatsApp">
                                <i class="fa-brands fa-whatsapp"></i> WhatsApp
                            </a>
                        @endif
                        <button type="button" class="lg-btn-copy-lg" onclick="copyLeadPhone('{{ $Lead->phonenumber }}')" title="Copy Phone Number">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>

                </div>
            </div>

            {{-- 2. LIVE CALL SESSION ACTIVE CONTROLS --}}
            <div class="lg-call-session-bar">
                <div class="lg-session-left">
                    <div class="lg-session-pulse">
                        <i class="fa fa-headset"></i>
                    </div>
                    <div>
                        <div class="lg-session-title" id="callSessionStatus">Call Session Controls</div>
                        <div class="lg-session-sub">Line: WebRTC / Gateway Line &bull; Target: {{ $Lead->phonenumber }}</div>
                    </div>
                </div>
                <div class="lg-session-actions">
                    <button type="button" class="lg-session-btn lg-session-btn-start" id="btnStartCallTimer" onclick="startCallTimer()">
                        <i class="fa fa-play"></i> Start Timer
                    </button>
                    <button type="button" class="lg-session-btn lg-session-btn-stop" id="btnStopCallTimer" onclick="stopCallTimer()">
                        <i class="fa fa-stop"></i> End Call
                    </button>
                </div>
            </div>

            {{-- 3. CALL DISPOSITION & OUTCOME CARD --}}
            <div class="lg-card">
                <div class="lg-card-header">
                    <h3 class="lg-card-title">
                        <i class="fa fa-clipboard-check"></i> Call Disposition & Outcome
                    </h3>
                    <span class="text-muted" style="font-size: 12px;">Log disposition to update lead status and records</span>
                </div>
                <div class="lg-card-body">
                    <form id="leadDispositionForm">
                        @csrf
                        <input type="hidden" name="duration" id="inputCallDuration" value="0">

                        <div class="lg-form-row">
                            {{-- Main Disposition / Status --}}
                            <div class="lg-field-group">
                                <label class="lg-label" for="main_disposition">
                                    <i class="fa fa-tag"></i> Main Disposition / Status <span class="req">*</span>
                                </label>
                                <select name="main_disposition" id="main_disposition" class="lg-select" required onchange="onStatusChange(this.value)">
                                    <option value="">— Select Call Outcome / Status —</option>
                                    @foreach($Statuses as $status)
                                        <option value="{{ $status->id }}" {{ $Lead->status == $status->id ? 'selected' : '' }}>
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sub-Disposition --}}
                            <div class="lg-field-group">
                                <label class="lg-label" for="sub_disposition">
                                    <i class="fa fa-tags"></i> Sub-Disposition
                                </label>
                                <select name="sub_disposition" id="sub_disposition" class="lg-select">
                                    <option value="">— Select Sub-Disposition (Optional) —</option>
                                    @foreach($SubStatuses as $substatus)
                                        <option value="{{ $substatus->id }}" data-status-id="{{ $substatus->status_id ?? '' }}" {{ $Lead->substatus == $substatus->id ? 'selected' : '' }}>
                                            {{ $substatus->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Follow Up Date/Time (Conditional) --}}
                        <div class="lg-form-row" id="followUpGroup" style="display: none;">
                            <div class="lg-field-group">
                                <label class="lg-label" for="follow_up_on">
                                    <i class="fa fa-clock"></i> Follow-up Date & Time
                                </label>
                                <input type="datetime-local" name="follow_up_on" id="follow_up_on" class="lg-input" value="{{ !empty($Lead->follow_up_on) ? date('Y-m-d\TH:i', strtotime($Lead->follow_up_on)) : '' }}">
                            </div>
                            <div class="lg-field-group" style="justify-content: flex-end;">
                                <small class="text-muted" style="padding-bottom: 8px;">
                                    Set scheduled follow-up reminder for this lead.
                                </small>
                            </div>
                        </div>

                        {{-- Call Notes / Remarks --}}
                        <div class="lg-field-group">
                            <label class="lg-label" for="remarks">
                                <i class="fa fa-comment-dots"></i> Call Remarks & Notes
                            </label>
                            <textarea name="remarks" id="remarks" class="lg-textarea" placeholder="Enter key points discussed during the call, client feedback, or required next steps...">{{ $Lead->remarks }}</textarea>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="lg-disposition-actions">
                            @if(!empty($nextLeadId))
                                <a href="{{ route('leadCallingView', ['campaignId' => $campaignId, 'leadId' => $nextLeadId]) }}" class="lg-btn lg-btn-secondary" style="margin-right: auto;">
                                    <i class="fa fa-forward"></i> Skip to Next Lead
                                </a>
                            @endif

                            <button type="button" class="lg-btn lg-btn-secondary" id="btnSaveDisposition" onclick="saveDisposition(false)">
                                <i class="fa fa-save"></i> Save Disposition
                            </button>

                            @if(!empty($nextLeadId))
                                <button type="button" class="lg-btn lg-btn-primary" id="btnSaveAndNext" onclick="saveDisposition(true)">
                                    <i class="fa fa-check-circle"></i> Save & Next Lead &rarr;
                                </button>
                            @else
                                <button type="button" class="lg-btn lg-btn-primary" id="btnSaveAndFinish" onclick="saveDisposition(false)">
                                    <i class="fa fa-check-circle"></i> Save & Complete
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- 4. CALL HISTORY LOGS TABLE --}}
            <div class="lg-card">
                <div class="lg-card-header">
                    <h3 class="lg-card-title">
                        <i class="fa fa-history"></i> Call History & Interaction Logs
                        <span class="badge" style="background:#E2E8F0; color:#475569; font-size:11px; margin-left:6px;" id="callHistoryCount">{{ count($callLogs) }}</span>
                    </h3>
                </div>
                <div class="lg-card-body" style="padding: 0;">
                    @if(count($callLogs) > 0)
                        <div class="table-responsive">
                            <table class="lg-history-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Agent</th>
                                        <th>Status Reached</th>
                                        <th>Duration</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="callLogsTbody">
                                    @foreach($callLogs as $log)
                                        <tr>
                                            <td style="white-space: nowrap; font-size: 12px;">
                                                <i class="fa fa-clock text-muted" style="margin-right: 4px;"></i>
                                                {{ $log->created_at ? date('d M Y, h:i A', strtotime($log->created_at)) : '—' }}
                                            </td>
                                            <td>
                                                <strong>{{ $log->called_by_name ?: 'System' }}</strong>
                                            </td>
                                            <td>
                                                <span class="lg-status-pill pill-blue">
                                                    <span class="lg-dot"></span>
                                                    {{ $log->status_name ?: 'Logged' }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $durSeconds = (int)($log->duration ?? 0);
                                                    $durFormat = sprintf('%02d:%02d', floor($durSeconds / 60), $durSeconds % 60);
                                                @endphp
                                                <span class="text-muted"><i class="fa fa-stopwatch"></i> {{ $durFormat }}</span>
                                            </td>
                                            <td>
                                                {{ $log->remarks ?: '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="lg-empty-history" id="emptyHistoryBox">
                            <i class="fa fa-phone-slash"></i>
                            <p style="margin: 0; font-size: 13px;">No prior call logs recorded for this lead yet.</p>
                            <small class="text-muted">Start a call and save a disposition above to record the first log.</small>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('after-scripts')
<script>
// ── Call Timer Logic ────────────────────────────────────────
var timerInterval = null;
var timerSeconds = 0;
var isTimerRunning = false;

function formatSeconds(secs) {
    var hours = Math.floor(secs / 3600);
    var minutes = Math.floor((secs % 3600) / 60);
    var seconds = secs % 60;
    return (hours < 10 ? "0" + hours : hours) + ":" +
           (minutes < 10 ? "0" + minutes : minutes) + ":" +
           (seconds < 10 ? "0" + seconds : seconds);
}

function startCallTimer() {
    if (isTimerRunning) return;
    isTimerRunning = true;
    $('#callSessionStatusTitle').text('Call In Progress...');
    $('#callSessionStatusSub').text('Timer running');
    $('#btnStartCallTimer').hide();
    $('#btnStopCallTimer').show();

    timerInterval = setInterval(function() {
        timerSeconds++;
        $('#callDurationTimer').text(formatSeconds(timerSeconds));
        $('#inputCallDuration').val(timerSeconds);
    }, 1000);
}

function stopCallTimer() {
    if (!isTimerRunning) return;
    clearInterval(timerInterval);
    isTimerRunning = false;
    $('#callSessionStatusTitle').text('Call Ended (' + formatSeconds(timerSeconds) + ')');
    $('#callSessionStatusSub').text('Select disposition below');
    $('#btnStartCallTimer').show().html('<i class="fa fa-redo"></i> Restart Timer');
    $('#btnStopCallTimer').hide();
    toastr.info("Call timer stopped. Duration: " + formatSeconds(timerSeconds));
}

// ── Copy Phone Function ─────────────────────────────────────
function copyLeadPhone(phone) {
    if (!phone) {
        toastr.warning("No phone number available to copy.");
        return;
    }
    navigator.clipboard.writeText(phone).then(function() {
        toastr.success("Phone number " + phone + " copied to clipboard!");
    }).catch(function() {
        // Fallback
        var dummy = document.createElement("input");
        document.body.appendChild(dummy);
        dummy.value = phone;
        dummy.select();
        document.execCommand("copy");
        document.body.removeChild(dummy);
        toastr.success("Phone number " + phone + " copied!");
    });
}

// ── Status Change & Follow-up Toggle ────────────────────────
function onStatusChange(statusId) {
    var selectedText = $("#main_disposition option:selected").text().toLowerCase();
    
    // Toggle follow-up date input if status implies a follow-up or callback
    if (selectedText.indexOf('follow') !== -1 || selectedText.indexOf('callback') !== -1 || selectedText.indexOf('call back') !== -1 || selectedText.indexOf('pending') !== -1) {
        $('#followUpGroup').slideDown();
    } else {
        $('#followUpGroup').slideUp();
    }

    // Filter substatus dropdown to show related ones or all
    var hasMatchingSub = false;
    $('#sub_disposition option').each(function() {
        var optStatusId = $(this).attr('data-status-id');
        if (!optStatusId || optStatusId === statusId) {
            $(this).show();
            if (optStatusId === statusId) hasMatchingSub = true;
        } else {
            $(this).hide();
        }
    });

    if (!hasMatchingSub) {
        // Show all if none matched specifically
        $('#sub_disposition option').show();
    }
}

// ── Queue Fast Filter ───────────────────────────────────────
function filterQueueList() {
    var query = $('#queueSearchInput').val().toLowerCase().trim();
    $('#queueListContainer .lg-queue-item').each(function() {
        var searchStr = $(this).attr('data-search') || '';
        if (!query || searchStr.indexOf(query) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// ── Save Disposition via AJAX ───────────────────────────────
function saveDisposition(navigateNext) {
    var mainDisp = $('#main_disposition').val();
    if (!mainDisp) {
        toastr.error("Please select a Main Disposition / Status first.");
        $('#main_disposition').focus();
        return;
    }

    var saveBtn = navigateNext ? $('#btnSaveAndNext') : $('#btnSaveDisposition');
    var originalHtml = saveBtn.html();
    saveBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

    var formData = {
        _token: '{{ csrf_token() }}',
        main_disposition: mainDisp,
        sub_disposition: $('#sub_disposition').val(),
        remarks: $('#remarks').val(),
        follow_up_on: $('#follow_up_on').val(),
        duration: timerSeconds
    };

    $.ajax({
        url: "{{ route('saveLeadCallingDisposition', ['campaignId' => $campaignId, 'leadId' => $leadId]) }}",
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            toastr.success(response.message || "Disposition saved successfully!");
            
            // Update active status badge in UI
            var statusName = $("#main_disposition option:selected").text().trim();
            $('#leadCurrentStatusText').text(statusName);

            // Update call history count
            var countEl = $('#callHistoryCount');
            var currCount = parseInt(countEl.text()) || 0;
            countEl.text(currCount + 1);

            // Stop timer if running
            if (isTimerRunning) {
                stopCallTimer();
            }

            if (navigateNext) {
                @if(!empty($nextLeadId))
                    setTimeout(function() {
                        window.location.href = "{{ route('leadCallingView', ['campaignId' => $campaignId, 'leadId' => $nextLeadId]) }}";
                    }, 600);
                @else
                    toastr.info("All leads in this campaign have been actioned!");
                    saveBtn.prop('disabled', false).html(originalHtml);
                @endif
            } else {
                saveBtn.prop('disabled', false).html(originalHtml);
            }
        },
        error: function(xhr) {
            saveBtn.prop('disabled', false).html(originalHtml);
            var errorMsg = "Failed to save disposition. Please try again.";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastr.error(errorMsg);
        }
    });
}

// ── Initialize on Document Ready ────────────────────────────
$(document).ready(function() {
    onStatusChange($('#main_disposition').val());
});
</script>
@endpush
