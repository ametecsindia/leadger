@extends('dashboard.layouts.master')
@section('title', 'Sub status')

@push('after-styles')
<style>
/* ============================================================
   LeadGER Corporate Sub status Page – Design System Match
   Mirrors the Status list page styling exactly.
   ============================================================ */

.lg-substatus-dashboard {
    padding: 24px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    font-family: var(--lg-font-sans);
    color: #0F172A;
    box-sizing: border-box;
}

/* ── Page Header ─────────────────────────────────────────── */
.lg-subs-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-subs-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lg-subs-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lg-accent);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    flex-shrink: 0;
}

.lg-subs-title-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lg-subs-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #64748B;
}

.lg-subs-breadcrumb a {
    color: #64748B;
    text-decoration: none;
    transition: color 0.15s ease;
}

.lg-subs-breadcrumb a:hover {
    color: var(--lg-accent);
}

.lg-subs-title {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}

.lg-subs-subtitle {
    font-size: 13.5px;
    color: #64748B;
    font-weight: 400;
    margin: 3px 0 0 0;
}

/* ── Primary Add Button ──────────────────────────────────── */
.lg-btn-add-subs {
    height: 42px;
    padding: 0 20px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border: none;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none !important;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    white-space: nowrap;
}

.lg-btn-add-subs:hover {
    background: #0D52CC;
    color: #FFFFFF !important;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.35);
    transform: translateY(-1px);
}

/* ── Main Card ───────────────────────────────────────────── */
.lg-subs-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

/* ── Toolbar ─────────────────────────────────────────────── */
.lg-subs-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #E2E8F0;
    background: #FFFFFF;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-subs-entries-box {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    color: #64748B;
    font-weight: 500;
}

.lg-subs-entries-select {
    height: 38px;
    padding: 0 32px 0 12px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
    outline: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
}

.lg-subs-search-box {
    position: relative;
    width: 260px;
}

.lg-subs-search-box svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
}

.lg-subs-search-input {
    width: 100%;
    height: 38px;
    padding: 0 14px 0 40px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.lg-subs-search-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

/* ── Table ───────────────────────────────────────────────── */
.lg-subs-table-wrap {
    overflow-x: auto;
    width: 100%;
}

.lg-subs-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    background: #FFFFFF;
}

.lg-subs-table th {
    background: #F8FAFC;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 14px 24px;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}

.lg-subs-table td {
    padding: 15px 24px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    color: #0F172A;
    font-size: 14px;
}

.lg-subs-table tbody tr {
    transition: background-color 0.12s ease;
}

.lg-subs-table tbody tr:hover {
    background-color: #F8FAFC;
}

.lg-subs-table tbody tr:last-child td {
    border-bottom: none;
}

/* Substatus name pill */
.lg-subs-name-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-subs-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--lg-accent);
    flex-shrink: 0;
}

.lg-subs-name-text {
    font-weight: 600;
    color: #0F172A;
    font-size: 14px;
}

/* Status badge */
.lg-status-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    background: #EFF6FF;
    color: var(--lg-accent);
    border: 1px solid #BFDBFE;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 600;
    white-space: nowrap;
}

/* Order badge */
.lg-order-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 30px;
    height: 28px;
    background: #F1F5F9;
    color: #334155;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    padding: 0 8px;
}

/* Description */
.lg-desc-text {
    color: #64748B;
    font-size: 13.5px;
    max-width: 340px;
    display: block;
}

/* Edit button */
.lg-btn-edit-subs {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 14px;
    background: #EFF6FF;
    color: var(--lg-accent) !important;
    border: 1px solid #BFDBFE;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.lg-btn-edit-subs:hover {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
}

/* ── Empty State ─────────────────────────────────────────── */
.lg-subs-empty {
    text-align: center;
    padding: 64px 24px;
}

.lg-subs-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px auto;
    color: #94A3B8;
}

.lg-subs-empty-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 6px 0;
}

.lg-subs-empty-msg {
    font-size: 14px;
    color: #64748B;
    margin: 0 0 24px 0;
}

/* ── Table Footer (pagination + info) ───────────────────── */
.lg-subs-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-top: 1px solid #E2E8F0;
    background: #FFFFFF;
    flex-wrap: wrap;
    gap: 12px;
}

.lg-subs-footer-info {
    font-size: 13.5px;
    color: #64748B;
    font-weight: 500;
}

.lg-subs-footer .pagination {
    margin: 0;
}

/* ── Flash Alert ─────────────────────────────────────────── */
.lg-subs-alert {
    border-radius: 10px;
    margin-bottom: 20px;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 500;
    border: 1px solid transparent;
}

.lg-subs-alert.success {
    background: #F0FDF4;
    color: #16A34A;
    border-color: #BBF7D0;
}

.lg-subs-alert.error {
    background: #FEF2F2;
    color: #DC2626;
    border-color: #FECACA;
}

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 900px) {
    .lg-substatus-dashboard { padding: 16px 16px; }
    .lg-subs-search-box { width: 100%; }
    .lg-subs-toolbar { flex-direction: column; align-items: flex-start; }
    .lg-subs-footer { flex-direction: column; align-items: flex-start; }
}

/* ── EDIT SUBSTATUS MODAL & POPUP STYLING ───────────────── */
.lg-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}

.lg-modal-overlay.active {
    display: flex;
}

.lg-modal-container {
    background: #FFFFFF;
    border-radius: 16px;
    width: 580px;
    max-width: 92vw;
    box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.22), 0 2px 6px rgba(15, 23, 42, 0.08);
    overflow: hidden;
    box-sizing: border-box;
    animation: lgModalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes lgModalPop {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-8px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.lg-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #E2E8F0;
    background: #FFFFFF;
}

.lg-modal-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lg-modal-header-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #EFF6FF;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.lg-modal-title {
    font-size: 17px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-modal-subtitle {
    font-size: 12.5px;
    color: #64748B;
    margin: 2px 0 0 0;
    font-weight: 400;
}

.lg-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #64748B;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    padding: 0;
}

.lg-modal-close:hover {
    background: #F1F5F9;
    color: #0F172A;
}

.lg-modal-body {
    padding: 24px;
    max-height: calc(85vh - 140px);
    overflow-y: auto;
}

.lg-modal-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

@media (max-width: 600px) {
    .lg-modal-form-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}

.lg-modal-form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-bottom: 20px;
}

.lg-modal-form-grid .lg-modal-form-group {
    margin-bottom: 0;
}

.lg-modal-form-group:last-child {
    margin-bottom: 0;
}

.lg-modal-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    margin: 0;
}

.lg-modal-required {
    color: #EF4444;
    font-weight: 700;
}

.lg-modal-input {
    width: 100%;
    height: 44px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.lg-modal-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-modal-input.error {
    border-color: #EF4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.lg-modal-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 40px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 14px center;
    cursor: pointer;
}

.lg-modal-textarea {
    height: 100px;
    padding: 12px 16px;
    resize: vertical;
    line-height: 1.5;
}

.lg-modal-error-message {
    font-size: 12.5px;
    color: #EF4444;
    font-weight: 500;
    margin-top: 2px;
}

.lg-modal-alert-error {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #DC2626;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 16px;
}

.lg-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #E2E8F0;
    background: #F8FAFC;
}

.lg-modal-btn-cancel {
    height: 40px;
    padding: 0 18px;
    background: #FFFFFF;
    color: #475569;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: inherit;
}

.lg-modal-btn-cancel:hover {
    background: #F1F5F9;
    border-color: #94A3B8;
    color: #0F172A;
}

.lg-modal-btn-submit {
    height: 40px;
    padding: 0 22px;
    background: var(--lg-accent);
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.25);
    font-family: inherit;
}

.lg-modal-btn-submit:hover {
    background: #0D52CC;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.35);
}

.lg-modal-btn-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    box-shadow: none;
}

.lg-spin {
    animation: lgSpinAnim 0.8s linear infinite;
}
@keyframes lgSpinAnim {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Toast Notifications */
.lg-status-toast {
    position: fixed;
    top: 24px;
    right: 28px;
    z-index: 100001;
    background: #FFFFFF;
    border-left: 4px solid #10B981;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 4px 6px -2px rgba(15, 23, 42, 0.04);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-top: 1px solid #E2E8F0;
    border-right: 1px solid #E2E8F0;
    border-bottom: 1px solid #E2E8F0;
    animation: lgToastSlideIn 0.25s ease-out;
}

@keyframes lgToastSlideIn {
    from { transform: translateX(40px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>
@endpush

@section('content')
<div class="lg-substatus-dashboard">

    {{-- ── PAGE HEADER ──────────────────────────────────────── --}}
    <div class="lg-subs-header-row">
        <div class="lg-subs-header-left">
            <div class="lg-subs-icon-box">
                {{-- hierarchy / substatus icon --}}
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/>
                    <path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>
                    <line x1="6" y1="9" x2="6" y2="12"/><line x1="6" y1="12" x2="9" y2="12"/>
                </svg>
            </div>
            <div class="lg-subs-title-group">
                <h1 class="lg-subs-title">Sub status</h1>
                <p class="lg-subs-subtitle">Manage and organize lead sub status values and their associated statuses.</p>
            </div>
        </div>

        @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
            <a href="javascript:void(0)" onclick="lgOpenAddSubstatusModal()" class="lg-btn-add-subs">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add a new Substatus</span>
            </a>
        @endif
    </div>

    {{-- ── FLASH MESSAGES ──────────────────────────────────── --}}
    @if(session('doneMessage'))
        <div class="lg-subs-alert success" role="alert">
            {{ session('doneMessage') }}
        </div>
    @endif
    @if(session('errorMessage'))
        <div class="lg-subs-alert error" role="alert">
            {{ session('errorMessage') }}
        </div>
    @endif

    {{-- ── MAIN CARD ────────────────────────────────────────── --}}
    <div class="lg-subs-card">

        @if($Substatuses->total() == 0)
            {{-- Empty state --}}
            <div class="lg-subs-empty">
                <div class="lg-subs-empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/>
                        <path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>
                    </svg>
                </div>
                <p class="lg-subs-empty-title">No sub statuses yet</p>
                <p class="lg-subs-empty-msg">Create your first sub status to help categorise lead outcomes more granularly.</p>
                @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                    <a href="javascript:void(0)" onclick="lgOpenAddSubstatusModal()" class="lg-btn-add-subs">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Add a new Substatus</span>
                    </a>
                @endif
            </div>
        @else
            {{-- ── TOOLBAR ──────────────────────────────────────── --}}
            <div class="lg-subs-toolbar">
                <div class="lg-subs-entries-box">
                    <span>Show</span>
                    <select id="lgSubsEntriesSelect" class="lg-subs-entries-select" onchange="lgSubsChangeEntries(this.value)">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="999">All</option>
                    </select>
                    <span>entries</span>
                </div>

                <div class="lg-subs-search-box">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="lgSubsSearch" class="lg-subs-search-input" placeholder="Search sub statuses..." onkeyup="lgSubsFilter()">
                </div>
            </div>

            {{-- ── TABLE ────────────────────────────────────────── --}}
            <div class="lg-subs-table-wrap">
                <table class="lg-subs-table" id="lgSubsTable">
                    <thead>
                        <tr>
                            <th style="width: 55%;">Substatus</th>
                            <th style="width: 30%;">Status</th>
                            @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                                <th style="width: 15%; text-align: right; padding-right: 24px;">Options</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($Substatuses as $Substatus)
                            <tr class="lg-subs-row" id="lgSubsRow_{{ $Substatus->id }}">
                                {{-- Substatus Name --}}
                                <td>
                                    <div class="lg-subs-name-cell">
                                        <span class="lg-subs-dot"></span>
                                        <span class="lg-subs-name-text" id="lgSubsName_{{ $Substatus->id }}">{!! $Substatus->name !!}</span>
                                    </div>
                                </td>

                                {{-- Parent Status --}}
                                <td>
                                    <span class="lg-status-pill" id="lgSubsStatusPill_{{ $Substatus->id }}">{!! $Substatus->status_name !!}</span>
                                </td>

                                {{-- Options --}}
                                @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                                    <td style="text-align: right; padding-right: 24px;">
                                        <button type="button"
                                                class="lg-btn-edit-subs"
                                                title="Edit Substatus"
                                                data-id="{{ $Substatus->id }}"
                                                data-name="{{ $Substatus->name }}"
                                                data-status-id="{{ $Substatus->status_id }}"
                                                data-status-name="{{ $Substatus->status_name }}"
                                                data-order="{{ $Substatus->seq_order }}"
                                                data-desc="{{ $Substatus->description ?? '' }}"
                                                onclick="lgOpenEditSubstatusModal(this)">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            <span>Edit</span>
                                        </button>
                                    </td>
                                @endif
                            </tr>

                            {{-- Preserved confirmation modal (unchanged) --}}
                            <div id="m-{{ $Substatus->id }}" class="modal fade" data-backdrop="true">
                                <div class="modal-dialog" id="animate">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('backend.confirmation') }}</h5>
                                        </div>
                                        <div class="modal-body text-center p-lg">
                                            <p>
                                                {{ __('backend.confirmationDeleteMsg') }}<br>
                                                <strong>[ {{ $Substatus->name }} ]</strong>
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn dark-white p-x-md" data-dismiss="modal">{{ __('backend.no') }}</button>
                                            <a href="{{ route('usersDestroy', ['id' => $Substatus->id]) }}" class="btn danger p-x-md">{{ __('backend.yes') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── TABLE FOOTER (info + pagination) ──────────── --}}
            <div class="lg-subs-footer">
                <div class="lg-subs-footer-info" id="lgSubsFooterInfo">
                    Showing
                    <strong>{{ $Substatuses->firstItem() }}</strong>
                    to
                    <strong>{{ $Substatuses->lastItem() }}</strong>
                    of
                    <strong>{{ $Substatuses->total() }}</strong>
                    entries
                </div>
                <div>
                    {!! $Substatuses->links() !!}
                </div>
            </div>
        @endif

    </div>{{-- /.lg-subs-card --}}

    <!-- SUBSTATUS POPUP MODAL (Shared Add & Edit) -->
    <div id="lgSubstatusModal" class="lg-modal-overlay" onclick="lgHandleSubsBackdropClick(event)">
        <div class="lg-modal-container" onclick="event.stopPropagation()">
            
            <!-- Modal Header -->
            <div class="lg-modal-header">
                <div class="lg-modal-header-left">
                    <div class="lg-modal-header-icon" id="lgModalSubsIcon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="lg-modal-title" id="lgModalSubsTitle">Add New Substatus</h3>
                        <p class="lg-modal-subtitle" id="lgModalSubsSubtitle">Create a new substatus and associate with a parent status</p>
                    </div>
                </div>
                <button type="button" class="lg-modal-close" onclick="lgCloseSubstatusModal()" title="Close (Esc)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="lgSubstatusForm" method="POST" action="" onsubmit="lgSubmitSubstatus(event)">
                @csrf
                <input type="hidden" id="lgModalSubsId" name="id" value="">
                <input type="hidden" id="lgModalSubsMode" name="_mode" value="add">

                <div class="lg-modal-body">
                    <!-- General Error Alert -->
                    <div id="lgModalSubsAlert" class="lg-modal-alert-error" style="display: none;"></div>

                    <!-- Row 1: Name and Parent Status -->
                    <div class="lg-modal-form-grid">
                        <!-- Substatus Name -->
                        <div class="lg-modal-form-group">
                            <label class="lg-modal-label" for="lgModalSubsName">
                                Name <span class="lg-modal-required">*</span>
                            </label>
                            <input type="text" 
                                   id="lgModalSubsName" 
                                   name="name" 
                                   class="lg-modal-input" 
                                   placeholder="Substatus name..." 
                                   required 
                                   autocomplete="off">
                            <div id="lgModalSubsNameError" class="lg-modal-error-message" style="display: none;"></div>
                        </div>

                        <!-- Parent Status Dropdown -->
                        <div class="lg-modal-form-group">
                            <label class="lg-modal-label" for="lgModalSubsStatus">
                                Status <span class="lg-modal-required">*</span>
                            </label>
                            <select id="lgModalSubsStatus" 
                                    name="status_id" 
                                    class="lg-modal-input lg-modal-select" 
                                    required>
                                <option value="" disabled selected>- - Select Status - -</option>
                                @php
                                    $allParentStatuses = $Statuses ?? \App\Models\Status::orderby('name', 'asc')->get();
                                @endphp
                                @foreach($allParentStatuses as $parentStat)
                                    <option value="{{ $parentStat->id }}">{{ $parentStat->name }}</option>
                                @endforeach
                            </select>
                            <div id="lgModalSubsStatusError" class="lg-modal-error-message" style="display: none;"></div>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="lg-modal-footer">
                    <button type="button" class="lg-modal-btn-cancel" onclick="lgCloseSubstatusModal()">
                        Cancel
                    </button>
                    <button type="submit" id="lgModalSubsSubmitBtn" class="lg-modal-btn-submit">
                        <span class="lg-modal-btn-text" id="lgModalSubsBtnText">Add Substatus</span>
                        <span class="lg-modal-btn-spinner" id="lgModalSubsBtnSpinner" style="display: none;">
                            <svg class="lg-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
                            </svg>
                            <span id="lgModalSubsBtnSpinnerText">Saving...</span>
                        </span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div id="lgSubsToast" class="lg-status-toast" style="display: none;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span id="lgSubsToastText" style="font-size: 13.5px; font-weight: 600; color: #0F172A;">Substatus saved successfully.</span>
        <button type="button" onclick="lgHideSubsToast()" style="background: none; border: none; cursor: pointer; color: #94A3B8; padding: 0 0 0 8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

</div>{{-- /.lg-substatus-dashboard --}}
@endsection

@push('after-scripts')
<script>
/* ── Client-side search filter ──────────────────────────── */
function lgSubsFilter() {
    var input  = document.getElementById('lgSubsSearch');
    var filter = input ? input.value.toLowerCase() : '';
    var table  = document.getElementById('lgSubsTable');
    if (!table) return;

    var rows = table.getElementsByClassName('lg-subs-row');
    var visible = 0;

    for (var i = 0; i < rows.length; i++) {
        var text = rows[i].textContent || rows[i].innerText;
        var show = text.toLowerCase().indexOf(filter) > -1;
        rows[i].style.display = show ? '' : 'none';
        if (show) visible++;
    }
}

/* ── Client-side show-entries limiter ───────────────────── */
function lgSubsChangeEntries(val) {
    var table = document.getElementById('lgSubsTable');
    if (!table) return;
    var rows  = table.getElementsByClassName('lg-subs-row');
    var limit = parseInt(val);

    for (var i = 0; i < rows.length; i++) {
        rows[i].style.display = (i < limit) ? '' : 'none';
    }
}

/* ── Preserved original script (checkbox actions) ─────── */
$(document).ready(function() {
    $('#checkAll').click(function () {
        $('input:checkbox').not(this).prop('checked', this.checked);
    });

    $('#action').change(function () {
        if (this.value === 'delete') {
            $('#submit_all').css('display', 'none');
            $('#submit_show_msg').css('display', 'inline-block');
        } else {
            $('#submit_all').css('display', 'inline-block');
            $('#submit_show_msg').css('display', 'none');
        }
    });
});

/* ── SUBSTATUS MODAL LOGIC (Shared Add & Edit) ─────────── */
let currentEditSubsId = null;
let currentSubsMode = 'add';
let subsToastTimeout = null;
let subsIsSubmitting = false;

function lgGetSubsModalElements() {
    return {
        modal: document.getElementById('lgSubstatusModal') || document.getElementById('lgEditSubstatusModal'),
        form: document.getElementById('lgSubstatusForm') || document.getElementById('lgEditSubstatusForm'),
        title: document.getElementById('lgModalSubsTitle'),
        subtitle: document.getElementById('lgModalSubsSubtitle'),
        icon: document.getElementById('lgModalSubsIcon'),
        btnText: document.getElementById('lgModalSubsBtnText'),
        btnSpinner: document.getElementById('lgModalSubsBtnSpinner'),
        modeInput: document.getElementById('lgModalSubsMode'),
        idInput: document.getElementById('lgModalSubsId') || document.getElementById('lgEditSubstatusId'),
        nameInput: document.getElementById('lgModalSubsName'),
        statusSelect: document.getElementById('lgModalSubsStatus'),
        orderInput: document.getElementById('lgModalSubsOrder'),
        descInput: document.getElementById('lgModalSubsDesc'),
        nameError: document.getElementById('lgModalSubsNameError'),
        statusError: document.getElementById('lgModalSubsStatusError'),
        orderError: document.getElementById('lgModalSubsOrderError'),
        descError: document.getElementById('lgModalSubsDescError'),
        alertBox: document.getElementById('lgModalSubsAlert')
    };
}

function lgResetSubsModalErrors() {
    const el = lgGetSubsModalElements();
    if (el.alertBox) { el.alertBox.style.display = 'none'; el.alertBox.textContent = ''; }
    if (el.nameError) { el.nameError.style.display = 'none'; el.nameError.textContent = ''; }
    if (el.statusError) { el.statusError.style.display = 'none'; el.statusError.textContent = ''; }
    if (el.orderError) { el.orderError.style.display = 'none'; el.orderError.textContent = ''; }
    if (el.descError) { el.descError.style.display = 'none'; el.descError.textContent = ''; }
    if (el.nameInput) el.nameInput.classList.remove('error');
    if (el.statusSelect) el.statusSelect.classList.remove('error');
    if (el.orderInput) el.orderInput.classList.remove('error');
    if (el.descInput) el.descInput.classList.remove('error');
}

function lgOpenAddSubstatusModal() {
    if (subsIsSubmitting) return;
    const el = lgGetSubsModalElements();
    if (!el.modal || !el.form) return;

    currentSubsMode = 'add';
    currentEditSubsId = null;

    lgResetSubsModalErrors();
    lgSetSubsSubmitLoading(false);

    // Dynamic Texts for Add
    if (el.title) el.title.textContent = 'Add New Substatus';
    if (el.subtitle) el.subtitle.textContent = 'Create a new substatus and associate with a parent status';
    if (el.btnText) el.btnText.textContent = 'Add Substatus';
    if (el.icon) {
        el.icon.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>`;
    }

    // Set form mode and action
    if (el.modeInput) el.modeInput.value = 'add';
    if (el.idInput) el.idInput.value = '';
    el.form.action = "{{ route('substatusesStore') }}";

    // Clear form inputs
    if (el.nameInput) el.nameInput.value = '';
    if (el.statusSelect) el.statusSelect.value = '';
    if (el.orderInput) el.orderInput.value = '';
    if (el.descInput) el.descInput.value = '';

    // Show modal
    el.modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    setTimeout(function() {
        if (el.nameInput) {
            el.nameInput.focus();
        }
    }, 120);
}

function lgOpenEditSubstatusModal(elOrId, name, statusId, order, desc) {
    if (subsIsSubmitting) return;
    const el = lgGetSubsModalElements();
    if (!el.modal || !el.form) return;

    currentSubsMode = 'edit';

    let id = elOrId;
    let subsName = name;
    let subsStatusId = statusId;
    let subsOrder = order;
    let subsDesc = desc;

    if (typeof elOrId === 'object' && elOrId !== null) {
        id = elOrId.getAttribute('data-id');
        subsName = elOrId.getAttribute('data-name');
        subsStatusId = elOrId.getAttribute('data-status-id');
        subsOrder = elOrId.getAttribute('data-order');
        subsDesc = elOrId.getAttribute('data-desc');
    }

    currentEditSubsId = id;

    lgResetSubsModalErrors();
    lgSetSubsSubmitLoading(false);

    // Dynamic Texts for Edit
    if (el.title) el.title.textContent = 'Edit Substatus';
    if (el.subtitle) el.subtitle.textContent = 'Update substatus details and configuration';
    if (el.btnText) el.btnText.textContent = 'Save Changes';
    if (el.icon) {
        el.icon.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>`;
    }

    // Set form mode and action
    if (el.modeInput) el.modeInput.value = 'edit';
    if (el.idInput) el.idInput.value = id;
    el.form.action = "{{ url('admin/substatuses') }}/" + id + "/update";

    // Populate data
    if (el.nameInput) el.nameInput.value = subsName || '';
    if (el.statusSelect) el.statusSelect.value = subsStatusId || '';
    if (el.orderInput) el.orderInput.value = subsOrder || '';
    if (el.descInput) el.descInput.value = subsDesc || '';

    // Show modal
    el.modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    setTimeout(function() {
        if (el.nameInput) {
            el.nameInput.focus();
            el.nameInput.select();
        }
    }, 120);
}

function lgCloseSubstatusModal() {
    if (subsIsSubmitting) return;
    const el = lgGetSubsModalElements();
    if (el.modal) {
        el.modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Clean URL query params if opened directly
    if (window.location.search.indexOf('create=') > -1 || window.location.search.indexOf('edit=') > -1) {
        window.history.replaceState({}, document.title, "{{ route('substatuses') }}");
    }
}

function lgCloseEditSubstatusModal() {
    lgCloseSubstatusModal();
}

function lgHandleSubsBackdropClick(event) {
    if (subsIsSubmitting) return;
    const el = lgGetSubsModalElements();
    if (event.target === el.modal) {
        lgCloseSubstatusModal();
    }
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !subsIsSubmitting) {
        const el = lgGetSubsModalElements();
        if (el.modal && el.modal.classList.contains('active')) {
            lgCloseSubstatusModal();
        }
    }
});

function lgSetSubsSubmitLoading(loading) {
    subsIsSubmitting = !!loading;
    const btn = document.getElementById('lgModalSubsSubmitBtn');
    if (!btn) return;
    const textSpan = document.getElementById('lgModalSubsBtnText');
    const spinnerSpan = document.getElementById('lgModalSubsBtnSpinner');

    if (loading) {
        btn.disabled = true;
        if (textSpan) textSpan.style.display = 'none';
        if (spinnerSpan) spinnerSpan.style.display = 'inline-flex';
    } else {
        btn.disabled = false;
        if (textSpan) textSpan.style.display = 'inline';
        if (spinnerSpan) spinnerSpan.style.display = 'none';
    }
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

function lgSubmitSubstatus(event) {
    event.preventDefault();
    if (subsIsSubmitting) return;

    const el = lgGetSubsModalElements();
    if (!el.form) return;

    lgResetSubsModalErrors();

    const nameVal = el.nameInput ? el.nameInput.value.trim() : '';
    if (!nameVal) {
        if (el.nameError) {
            el.nameError.textContent = 'Substatus name is required.';
            el.nameError.style.display = 'block';
        }
        if (el.nameInput) {
            el.nameInput.classList.add('error');
            el.nameInput.focus();
        }
        return;
    }

    if (el.statusSelect && !el.statusSelect.value) {
        if (el.statusError) {
            el.statusError.textContent = 'Please select a parent status.';
            el.statusError.style.display = 'block';
        }
        el.statusSelect.classList.add('error');
        el.statusSelect.focus();
        return;
    }

    lgSetSubsSubmitLoading(true);

    const formData = new FormData(el.form);

    fetch(el.form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async function(response) {
        const isJson = response.headers.get('content-type')?.includes('application/json');
        const data = isJson ? await response.json() : null;

        if (response.status === 422 && data && data.errors) {
            // Validation errors
            lgSetSubsSubmitLoading(false);
            if (data.errors.name && el.nameError) {
                el.nameError.textContent = data.errors.name[0];
                el.nameError.style.display = 'block';
                if (el.nameInput) {
                    el.nameInput.classList.add('error');
                    el.nameInput.focus();
                }
            }
            if (data.errors.status_id && el.statusError) {
                el.statusError.textContent = data.errors.status_id[0];
                el.statusError.style.display = 'block';
                if (el.statusSelect) el.statusSelect.classList.add('error');
            }
            if (data.errors.seq_order && el.orderError) {
                el.orderError.textContent = data.errors.seq_order[0];
                el.orderError.style.display = 'block';
                if (el.orderInput) el.orderInput.classList.add('error');
            }
            if (data.errors.description && el.descError) {
                el.descError.textContent = data.errors.description[0];
                el.descError.style.display = 'block';
                if (el.descInput) el.descInput.classList.add('error');
            }
            return;
        }

        if (!response.ok) {
            // Server error
            lgSetSubsSubmitLoading(false);
            if (el.alertBox) {
                el.alertBox.textContent = (data && data.message) ? data.message : 'An error occurred while saving. Please try again.';
                el.alertBox.style.display = 'block';
            } else {
                el.form.submit();
            }
            return;
        }

        // Success!
        lgSetSubsSubmitLoading(false);
        lgCloseSubstatusModal();

        const substatus = data?.substatus || {};
        const savedId = substatus.id || currentEditSubsId;
        const savedName = substatus.name || nameVal;
        const savedStatusId = substatus.status_id || (el.statusSelect ? el.statusSelect.value : '');
        const savedStatusName = substatus.status_name || (el.statusSelect?.options[el.statusSelect?.selectedIndex]?.text || '');
        const savedOrder = substatus.seq_order || (el.orderInput ? el.orderInput.value : '');
        const savedDesc = substatus.description || (el.descInput ? el.descInput.value : '');

        if (currentSubsMode === 'add') {
            // Add mode: dynamically add to table or reload if empty
            const table = document.getElementById('lgSubsTable');
            const tbody = table ? table.querySelector('tbody') : null;
            const emptyState = document.querySelector('.lg-subs-empty');

            if (emptyState && !table) {
                window.location.reload();
                return;
            }

            if (tbody) {
                const tr = document.createElement('tr');
                tr.className = 'lg-subs-row';
                tr.id = 'lgSubsRow_' + savedId;
                tr.style.backgroundColor = '#F0FDF4';
                tr.style.transition = 'background-color 1.5s ease';

                let optionsHtml = '';
                @if(in_array(@Auth::user()->permissionsGroup->name, ['Admin','Manager']))
                optionsHtml = `
                    <td style="text-align: right; padding-right: 24px;">
                        <button type="button"
                                class="lg-btn-edit-subs"
                                title="Edit Substatus"
                                data-id="${savedId}"
                                data-name="${escapeHtml(savedName)}"
                                data-status-id="${savedStatusId}"
                                data-status-name="${escapeHtml(savedStatusName)}"
                                data-order="${savedOrder || ''}"
                                data-desc="${escapeHtml(savedDesc || '')}"
                                onclick="lgOpenEditSubstatusModal(this)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span>Edit</span>
                        </button>
                    </td>
                `;
                @endif

                tr.innerHTML = `
                    <td>
                        <div class="lg-subs-name-cell">
                            <span class="lg-subs-dot"></span>
                            <span class="lg-subs-name-text" id="lgSubsName_${savedId}">${escapeHtml(savedName)}</span>
                        </div>
                    </td>
                    <td>
                        <span class="lg-status-pill" id="lgSubsStatusPill_${savedId}">${escapeHtml(savedStatusName)}</span>
                    </td>
                    ${optionsHtml}
                `;

                tbody.insertBefore(tr, tbody.firstChild);

                setTimeout(() => {
                    tr.style.backgroundColor = '';
                }, 1500);
            }

            const successMsg = data?.message || 'Substatus created successfully.';
            lgShowSubsToast(successMsg);
        } else {
            // Edit mode: update DOM
            const nameEl = document.getElementById('lgSubsName_' + savedId);
            if (nameEl) nameEl.textContent = savedName;

            const statusPill = document.getElementById('lgSubsStatusPill_' + savedId);
            if (statusPill) statusPill.textContent = savedStatusName;

            const editBtn = document.querySelector('.lg-btn-edit-subs[data-id="' + savedId + '"]');
            if (editBtn) {
                editBtn.setAttribute('data-name', savedName);
                editBtn.setAttribute('data-status-id', savedStatusId);
                editBtn.setAttribute('data-status-name', savedStatusName);
                editBtn.setAttribute('data-order', savedOrder);
                editBtn.setAttribute('data-desc', savedDesc);
            }

            const successMsg = data?.message || 'Substatus updated successfully.';
            lgShowSubsToast(successMsg);
        }
    })
    .catch(function(err) {
        console.error('Substatus submit error:', err);
        lgSetSubsSubmitLoading(false);
        el.form.submit();
    });
}

function lgSubmitEditSubstatus(event) {
    lgSubmitSubstatus(event);
}

function lgShowSubsToast(msg) {
    const toast = document.getElementById('lgSubsToast');
    const textEl = document.getElementById('lgSubsToastText');
    if (!toast || !textEl) return;

    textEl.textContent = msg;
    toast.style.display = 'flex';

    if (subsToastTimeout) clearTimeout(subsToastTimeout);
    subsToastTimeout = setTimeout(function() {
        lgHideSubsToast();
    }, 4000);
}

function lgHideSubsToast() {
    const toast = document.getElementById('lgSubsToast');
    if (toast) toast.style.display = 'none';
}

@if(isset($createModal) || request('create'))
document.addEventListener('DOMContentLoaded', function() {
    lgOpenAddSubstatusModal();
});
@elseif(isset($editSubstatus) && !empty($editSubstatus))
document.addEventListener('DOMContentLoaded', function() {
    lgOpenEditSubstatusModal('{{ $editSubstatus->id }}', {!! json_encode($editSubstatus->name) !!}, '{{ $editSubstatus->status_id }}', '{{ $editSubstatus->seq_order }}', {!! json_encode($editSubstatus->description ?? '') !!});
});
@elseif(request('edit'))
document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.querySelector('.lg-btn-edit-subs[data-id="{{ request('edit') }}"]');
    if (editBtn) {
        lgOpenEditSubstatusModal(editBtn);
    }
});
@endif
</script>
@endpush
