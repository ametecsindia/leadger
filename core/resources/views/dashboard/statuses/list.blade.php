@extends('dashboard.layouts.master')
@section('title', 'Status')

@push("after-styles")
<style>
/* LeadGER Corporate Status Master Table System Styles */
.lg-status-dashboard {
    padding: 24px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    font-family: var(--lg-font-sans);
    color: #0F172A;
    box-sizing: border-box;
}

/* Page Header */
.lg-page-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lg-header-icon-box {
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

.lg-header-text-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lg-breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #64748B;
}

.lg-breadcrumb-nav a {
    color: #64748B;
    text-decoration: none;
    transition: color 0.15s ease;
}

.lg-breadcrumb-nav a:hover {
    color: var(--lg-accent);
}

.lg-title-main {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}

/* Add Status Button */
.lg-btn-add-status {
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
}

.lg-btn-add-status:hover {
    background: #0D52CC;
    color: #FFFFFF !important;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.35);
}

/* Main Card Container */
.lg-status-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

/* Toolbar Controls */
.lg-table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #E2E8F0;
    background: #FFFFFF;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-entries-box {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    color: #64748B;
    font-weight: 500;
}

.lg-entries-select {
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
}

.lg-search-box {
    position: relative;
    width: 260px;
}

.lg-search-box svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
}

.lg-search-input {
    width: 100%;
    height: 38px;
    padding: 0 14px 0 38px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-search-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

/* Table Styling */
.lg-table-responsive {
    overflow-x: auto;
    width: 100%;
}

.lg-status-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    background: #FFFFFF;
}

.lg-status-table th {
    background: #F8FAFC;
    color: #475569;
    font-size: 12.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 14px 24px;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}

.lg-status-table td {
    padding: 16px 24px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    color: #0F172A;
    font-size: 14px;
}

.lg-status-table tbody tr {
    transition: background-color 0.15s ease;
}

.lg-status-table tbody tr:hover {
    background-color: #F8FAFC;
}

.lg-status-table tbody tr:last-child td {
    border-bottom: none;
}

.lg-status-name-flex {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-status-dot-sm {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.lg-status-name {
    font-weight: 600;
    color: #0F172A;
    font-size: 14px;
}

.lg-order-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    background: #F1F5F9;
    color: #334155;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
}

.lg-status-desc {
    color: #64748B;
    font-size: 13.5px;
}

/* Edit Button */
.lg-btn-edit-action {
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
    cursor: pointer;
    font-family: inherit;
}

.lg-btn-edit-action:hover {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
}

.lg-table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-top: 1px solid #E2E8F0;
    background: #FFFFFF;
}

.lg-footer-info {
    font-size: 13.5px;
    color: #64748B;
    font-weight: 500;
}

/* ==========================================================================
   LeadGER Edit Status Popup Modal & Toast Notification Styling
   (Strictly identical to Products / Sources / Segments)
   ========================================================================== */
/* One popup for Add Status and Edit Status: same design as the Products popup */
.lg-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}
.lg-modal-overlay.active { display: flex !important; }

.lg-modal-wrapper {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    max-height: calc(100vh - 32px);
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    overflow: hidden;
    animation: lgModalPopup 0.2s ease-out;
    margin: auto;
    display: flex;
    flex-direction: column;
}
@keyframes lgModalPopup {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
}

.lg-modal-header-bar {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.lg-modal-header-title {
    font-size: 18px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}
.lg-modal-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
}
.lg-modal-close-btn:hover { background: #E2E8F0; color: #0F172A; border-color: #CBD5E1; }

.lg-modal-body {
    padding: 24px 28px;
    background: #FFFFFF;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 18px;
    overflow-y: auto;
}
.lg-modal-form-group { display: flex; flex-direction: column; gap: 8px; }
.lg-modal-label { font-size: 13.5px; font-weight: 600; color: #334155; margin: 0; }
.lg-modal-required { color: #EF4444; }
.lg-modal-hint { font-size: 12px; color: #64748B; margin: 0; }

.lg-modal-control {
    width: 100%;
    height: 44px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}
.lg-modal-control::placeholder { color: #94A3B8; }
.lg-modal-control:focus { border-color: #1769E8; box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.12); }
.lg-modal-control.error, .lg-subs-field.error { border-color: #EF4444; }
textarea.lg-modal-control { height: auto; min-height: 96px; padding: 12px 16px; resize: vertical; line-height: 1.5; }
.lg-modal-error { color: #EF4444; font-size: 12.5px; font-weight: 500; display: none; }

/* Substatus: tag input inside the popup */
.lg-subs-field {
    min-height: 44px;
    padding: 6px 8px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    cursor: text;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.lg-subs-field:focus-within { border-color: #1769E8; box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.12); }
.lg-subs-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 28px;
    padding: 0 4px 0 10px;
    background: #EFF6FF;
    color: #1E40AF;
    border: 1px solid #BFDBFE;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
}
.lg-subs-chip.is-new { background: #ECFDF5; color: #047857; border-color: #A7F3D0; }
.lg-subs-chip-remove {
    border: none;
    background: transparent;
    color: #64748B;
    cursor: pointer;
    width: 20px;
    height: 20px;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    font-size: 15px;
    line-height: 1;
}
.lg-subs-chip-remove:hover { background: rgba(15, 23, 42, 0.08); color: #0F172A; }
.lg-subs-input {
    flex: 1;
    min-width: 150px;
    height: 30px;
    border: none;
    outline: none;
    background: transparent;
    padding: 0 6px;
    font-size: 14px;
    font-weight: 500;
    color: #0F172A;
    font-family: inherit;
}
.lg-subs-input::placeholder { color: #94A3B8; }
/* the site-wide input style adds its own border/shadow; inside the tag field the input must be borderless */
.lg-subs-field .lg-subs-input,
.lg-subs-field .lg-subs-input:focus {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    height: 30px !important;
    min-height: 0 !important;
    padding: 0 6px !important;
    outline: none !important;
}

.lg-modal-footer-bar {
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    padding: 16px 28px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-shrink: 0;
}
.lg-btn-modal-cancel {
    height: 40px;
    padding: 0 20px;
    background: #FFFFFF;
    color: #172B4D;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.lg-btn-modal-cancel:hover { background: #F8FAFC; border-color: #94A3B8; color: #0F172A; }
.lg-btn-modal-submit {
    height: 40px;
    min-width: 96px;
    padding: 0 22px;
    background: #1769E8;
    color: #FFFFFF;
    border: none;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 2px 6px rgba(23, 105, 232, 0.2);
}
.lg-btn-modal-submit:hover { background: #1056C7; color: #FFFFFF; }
.lg-btn-modal-submit:disabled { opacity: 0.7; cursor: not-allowed; }

.lg-spin { animation: lgSpinAnim 0.8s linear infinite; }
@keyframes lgSpinAnim { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Substatus column: one small badge per substatus, "—" when there are none */
.lg-status-subs { display: flex; flex-wrap: wrap; gap: 6px; }
.lg-status-subs-none { color: #94A3B8; font-weight: 500; }
.lg-status-sub-pill {
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    padding: 2px 8px;
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

.lg-status-toast-content {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0F172A;
    font-size: 13.5px;
    font-weight: 500;
}

.lg-status-toast-content svg {
    color: #10B981;
    flex-shrink: 0;
}

.lg-status-toast-close {
    background: transparent;
    border: none;
    color: #94A3B8;
    cursor: pointer;
    padding: 2px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 8px;
}

.lg-status-toast-close:hover {
    color: #0F172A;
    background: #F1F5F9;
}
</style>
@endpush

@section('content')
<div class="lg-status-dashboard">

    <!-- 1. PAGE HEADER -->
    <div class="lg-page-header-row">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/>
                    <path d="M7 7h.01"/>
                </svg>
            </div>
            <div class="lg-header-text-group">
                <div class="lg-breadcrumb-nav">
                    <a href="{{ route('adminHome') }}">Home</a>
                    <span>/</span>
                    <span>Lead Status</span>
                    <span>/</span>
                    <span>Status</span>
                </div>
                <h1 class="lg-title-main">Status</h1>
            </div>
        </div>

        @if(\App\Support\RoleAccess::allows(Auth::user(), 'master_data.statuses.create'))
            <div>
                <a href="{{ route('statusesCreate') }}" class="lg-btn-add-status" onclick="lgOpenStatusModal(); return false;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Add a new Status</span>
                </a>
            </div>
        @endif
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('doneMessage'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px; margin-bottom: 20px;">
            {{ session('doneMessage') }}
        </div>
    @endif

    <!-- Floating Toast for AJAX updates -->
    <div id="lgStatusToast" class="lg-status-toast" style="display: none;">
        <div class="lg-status-toast-content">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span id="lgStatusToastText">Status updated successfully.</span>
        </div>
        <button type="button" class="lg-status-toast-close" onclick="lgHideStatusToast()" title="Dismiss">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- 2. MAIN TABLE CARD CONTAINER -->
    <div class="lg-status-card">

        <!-- TOOLBAR: SHOW ENTRIES & SEARCH -->
        <div class="lg-table-toolbar">
            <div class="lg-entries-box">
                <span>Show</span>
                <select id="lgEntriesSelect" class="lg-entries-select" onchange="lgChangeEntries(this.value)">
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
                <span>entries</span>
            </div>

            <div class="lg-search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="lgStatusSearch" class="lg-search-input" placeholder="Search statuses..." onkeyup="lgFilterStatuses()">
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="lg-table-responsive">
            <table class="lg-status-table" id="lgStatusTable">
                <thead>
                    <tr>
                        <th style="width: 30%;">Status</th>
                        <th style="width: 50%;">Substatus</th>
                        @if(\App\Support\RoleAccess::allows(Auth::user(), 'master_data.statuses.edit') || \App\Support\RoleAccess::allows(Auth::user(), 'master_data.statuses.delete'))
                            <th class="text-right" style="width: 20%; text-align: right; padding-right: 24px;">Options</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($Statuses as $Status)
                        @php
                            $sName = strtolower(trim($Status->name));
                            $dotColor = '#64748B'; // default grey
                            if (strpos($sName, 'connect') !== false || strpos($sName, 'deal') !== false || strpos($sName, 'interest') !== false || strpos($sName, 'lead') !== false) {
                                $dotColor = '#10B981'; // Green
                            } elseif (strpos($sName, 'follow') !== false || strpos($sName, 'cbk') !== false || strpos($sName, 'call back') !== false || strpos($sName, 'demo') !== false) {
                                $dotColor = '#F59E0B'; // Orange
                            } elseif (strpos($sName, 'not') !== false || strpos($sName, 'dnd') !== false || strpos($sName, 'reject') !== false || strpos($sName, 'rnr') !== false || strpos($sName, 'nc') !== false) {
                                $dotColor = '#64748B'; // Muted grey
                            }
                        @endphp
                        <tr class="lg-status-row" id="lgStatusRow_{{ $Status->id }}">
                            <td>
                                <div class="lg-status-name-flex">
                                    <span class="lg-status-dot-sm" id="lgStatusDot_{{ $Status->id }}" style="background-color: {{ $dotColor }};"></span>
                                    <span class="lg-status-name" id="lgStatusName_{{ $Status->id }}">{!! $Status->name !!}</span>
                                </div>
                            </td>
                            {{-- Substatuses of this status (Status::substatuses, eager-loaded in StatusesController::index) --}}
                            <td>
                                @if($Status->substatuses->count())
                                    <div class="lg-status-subs">
                                        @foreach($Status->substatuses as $Sub)
                                            <span class="lg-status-sub-pill">{{ $Sub->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="lg-status-subs-none">—</span>
                                @endif
                            </td>
                            @if(\App\Support\RoleAccess::allows(Auth::user(), 'master_data.statuses.edit') || \App\Support\RoleAccess::allows(Auth::user(), 'master_data.statuses.delete'))
                                <td class="text-right" style="text-align: right; padding-right: 24px;">
                                    <button type="button" 
                                            class="lg-btn-edit-action" 
                                            title="Edit Status"
                                            data-id="{{ $Status->id }}"
                                            data-name="{{ $Status->name }}"
                                            data-desc="{{ $Status->description ?? '' }}"
                                            data-subs="{{ $Status->substatuses->map(fn ($x) => ['id' => $x->id, 'name' => $x->name])->values()->toJson() }}"
                                            onclick="lgOpenStatusModal(this)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                        <span>Edit</span>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- FOOTER INFO -->
        <div class="lg-table-footer">
            <div class="lg-footer-info">
                Showing <strong>1</strong> to <strong>{{ count($Statuses) }}</strong> of <strong>{{ count($Statuses) }}</strong> entries
            </div>
        </div>

    </div>

    <!-- STATUS POPUP: one form for Add Status and Edit Status -->
    <div id="lgStatusModal" class="lg-modal-overlay" onclick="if (event.target === this) lgCloseStatusModal()">
        <div class="lg-modal-wrapper" role="dialog" aria-modal="true" aria-labelledby="lgStatusModalTitle">

            <div class="lg-modal-header-bar">
                <h3 class="lg-modal-header-title" id="lgStatusModalTitle">Add Status</h3>
                <button type="button" class="lg-modal-close-btn" onclick="lgCloseStatusModal()" title="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <form id="lgStatusForm" onsubmit="lgSubmitStatus(event)" novalidate style="display: flex; flex-direction: column; min-height: 0;">
                <div class="lg-modal-body">
                    <!-- 1. Status -->
                    <div class="lg-modal-form-group">
                        <label class="lg-modal-label" for="lgStatusName">Status <span class="lg-modal-required">*</span></label>
                        <input type="text" id="lgStatusName" class="lg-modal-control" placeholder="Enter status name..." maxlength="255" autocomplete="off">
                        <div id="lgStatusNameError" class="lg-modal-error"></div>
                    </div>

                    <!-- 2. Substatus -->
                    <div class="lg-modal-form-group">
                        <label class="lg-modal-label" for="lgSubsInput">Substatus</label>
                        <div class="lg-subs-field" id="lgSubsField" onclick="document.getElementById('lgSubsInput').focus()">
                            <input type="text" id="lgSubsInput" class="lg-subs-input" placeholder="Type a substatus and press Enter..." maxlength="255" autocomplete="off">
                        </div>
                        <p class="lg-modal-hint">Press Enter or comma to add. Changes are saved when you click Save.</p>
                        <div id="lgSubsError" class="lg-modal-error"></div>
                    </div>

                    <!-- 3. Description -->
                    <div class="lg-modal-form-group">
                        <label class="lg-modal-label" for="lgStatusDesc">Description</label>
                        <textarea id="lgStatusDesc" class="lg-modal-control" placeholder="Enter description..." maxlength="2000"></textarea>
                        <div id="lgStatusDescError" class="lg-modal-error"></div>
                    </div>
                </div>

                <div class="lg-modal-footer-bar">
                    <button type="button" class="lg-btn-modal-cancel" onclick="lgCloseStatusModal()">Cancel</button>
                    <button type="submit" class="lg-btn-modal-submit" id="lgStatusSubmit">
                        <svg class="lg-spin" id="lgStatusSpinner" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display: none;">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
                        </svg>
                        <span id="lgStatusSubmitText">Save</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection

@push("after-scripts")
<script type="text/javascript">
let toastTimeout = null;
let lgStatusState = { id: null, subs: [], removeIds: [], saving: false };

function lgEscape(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

/* Open the shared popup: no argument = Add Status, an Edit button = Edit Status with its saved values */
function lgOpenStatusModal(btn) {
    if (lgStatusState.saving) return;
    const isEdit = !!(btn && btn.getAttribute);
    let subs = [];
    if (isEdit) {
        try { subs = JSON.parse(btn.getAttribute('data-subs') || '[]'); } catch (e) { subs = []; }
    }
    lgStatusState = {
        id: isEdit ? btn.getAttribute('data-id') : null,
        subs: subs.map(function (x) { return { id: x.id, name: x.name }; }),
        removeIds: [],
        saving: false
    };

    document.getElementById('lgStatusModalTitle').textContent = isEdit ? 'Edit Status' : 'Add Status';
    document.getElementById('lgStatusSubmitText').textContent = 'Save';
    document.getElementById('lgStatusName').value = isEdit ? (btn.getAttribute('data-name') || '') : '';
    document.getElementById('lgStatusDesc').value = isEdit ? (btn.getAttribute('data-desc') || '') : '';
    document.getElementById('lgSubsInput').value = '';
    lgClearStatusErrors();
    lgRenderSubs();
    lgSetStatusSaving(false);

    document.getElementById('lgStatusModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(function () { const n = document.getElementById('lgStatusName'); n.focus(); n.select(); }, 120);
}

function lgCloseStatusModal() {
    if (lgStatusState.saving) return;
    document.getElementById('lgStatusModal').classList.remove('active');
    document.body.style.overflow = '';
    if (window.location.search.indexOf('edit=') > -1 || window.location.search.indexOf('add=') > -1) {
        window.history.replaceState({}, document.title, "{{ route('statuses') }}");
    }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('lgStatusModal').classList.contains('active')) lgCloseStatusModal();
});

function lgRenderSubs() {
    const field = document.getElementById('lgSubsField');
    const input = document.getElementById('lgSubsInput');
    field.querySelectorAll('.lg-subs-chip').forEach(function (c) { c.remove(); });
    lgStatusState.subs.forEach(function (sub, i) {
        const chip = document.createElement('span');
        chip.className = 'lg-subs-chip' + (sub.id ? '' : ' is-new');
        chip.innerHTML = lgEscape(sub.name) +
            '<button type="button" class="lg-subs-chip-remove" title="Remove" aria-label="Remove ' + lgEscape(sub.name) + '">&times;</button>';
        chip.querySelector('button').addEventListener('click', function (ev) { ev.stopPropagation(); lgRemoveSub(i); });
        field.insertBefore(chip, input);
    });
}

function lgAddSubsFromInput() {
    const input = document.getElementById('lgSubsInput');
    const err = document.getElementById('lgSubsError');
    err.style.display = 'none';
    input.value.split(',').map(function (v) { return v.trim(); }).filter(Boolean).forEach(function (name) {
        const dup = lgStatusState.subs.some(function (x) { return x.name.toLowerCase() === name.toLowerCase(); });
        if (dup) { err.textContent = '"' + name + '" is already in the list.'; err.style.display = 'block'; return; }
        lgStatusState.subs.push({ id: null, name: name });
    });
    input.value = '';
    lgRenderSubs();
}

function lgRemoveSub(i) {
    const sub = lgStatusState.subs[i];
    if (sub && sub.id) lgStatusState.removeIds.push(sub.id);
    lgStatusState.subs.splice(i, 1);
    lgRenderSubs();
    document.getElementById('lgSubsInput').focus();
}

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('lgSubsInput');
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); lgAddSubsFromInput(); }
        else if (e.key === 'Backspace' && !input.value && lgStatusState.subs.length) { lgRemoveSub(lgStatusState.subs.length - 1); }
    });
    input.addEventListener('blur', function () { if (input.value.trim()) lgAddSubsFromInput(); });
});

function lgClearStatusErrors() {
    ['lgStatusNameError', 'lgSubsError', 'lgStatusDescError'].forEach(function (id) {
        const el = document.getElementById(id); el.style.display = 'none'; el.textContent = '';
    });
    document.getElementById('lgStatusName').classList.remove('error');
    document.getElementById('lgSubsField').classList.remove('error');
    document.getElementById('lgStatusDesc').classList.remove('error');
}

function lgShowFieldError(id, inputId, msg) {
    const el = document.getElementById(id);
    el.textContent = msg; el.style.display = 'block';
    if (inputId) document.getElementById(inputId).classList.add('error');
}

function lgSetStatusSaving(saving) {
    lgStatusState.saving = !!saving;
    document.getElementById('lgStatusSubmit').disabled = !!saving;
    document.getElementById('lgStatusSpinner').style.display = saving ? 'inline-block' : 'none';
    document.getElementById('lgStatusSubmitText').textContent = saving ? 'Saving...' : 'Save';
}

function lgSubmitStatus(event) {
    event.preventDefault();
    if (lgStatusState.saving) return;
    if (document.getElementById('lgSubsInput').value.trim()) lgAddSubsFromInput();
    lgClearStatusErrors();

    const name = document.getElementById('lgStatusName').value.trim();
    if (!name) {
        lgShowFieldError('lgStatusNameError', 'lgStatusName', 'Status name is required.');
        document.getElementById('lgStatusName').focus();
        return;
    }

    const fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('name', name);
    fd.append('description', document.getElementById('lgStatusDesc').value);
    lgStatusState.subs.filter(function (x) { return !x.id; }).forEach(function (x) { fd.append('substatuses[]', x.name); });
    lgStatusState.removeIds.forEach(function (id) { fd.append('remove_substatus_ids[]', id); });

    const isEdit = !!lgStatusState.id;
    const url = isEdit ? "{{ url('admin/statuses') }}/" + lgStatusState.id + "/update" : "{{ route('statusesStore') }}";

    lgSetStatusSaving(true);
    fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd })
        .then(async function (res) {
            const data = (res.headers.get('content-type') || '').includes('application/json') ? await res.json() : null;
            if (res.status === 422 && data && data.errors) {
                lgSetStatusSaving(false);
                if (data.errors.name) lgShowFieldError('lgStatusNameError', 'lgStatusName', data.errors.name[0]);
                if (data.errors.description) lgShowFieldError('lgStatusDescError', 'lgStatusDesc', data.errors.description[0]);
                const subErr = Object.keys(data.errors).find(function (k) { return k.indexOf('substatus') === 0; });
                if (subErr) lgShowFieldError('lgSubsError', 'lgSubsField', data.errors[subErr][0]);
                return;
            }
            if (!res.ok || !data || !data.success) {
                lgSetStatusSaving(false);
                lgShowStatusToast((data && data.message) || 'Could not save the status. Please try again.', true);
                return;
            }
            // Saved: reload so the list shows the stored values; the message is shown after reload
            try { sessionStorage.setItem('lgStatusToast', data.message || 'Status saved successfully.'); } catch (e) {}
            lgStatusState.saving = false;
            window.location.href = "{{ route('statuses') }}";
        })
        .catch(function () {
            lgSetStatusSaving(false);
            lgShowStatusToast('Network error - the status was not saved. Please try again.', true);
        });
}

function lgUpdateStatusDot(el, name) {
    const sName = (name || '').toLowerCase().trim();
    let dotColor = '#64748B'; // default grey
    if (sName.includes('connect') || sName.includes('deal') || sName.includes('interest') || sName.includes('lead')) {
        dotColor = '#10B981'; // Green
    } else if (sName.includes('follow') || sName.includes('cbk') || sName.includes('call back') || sName.includes('demo')) {
        dotColor = '#F59E0B'; // Orange
    } else if (sName.includes('not') || sName.includes('dnd') || sName.includes('reject') || sName.includes('rnr') || sName.includes('nc')) {
        dotColor = '#64748B'; // Muted grey
    }
    el.style.backgroundColor = dotColor;
}

function lgShowStatusToast(msg, isError) {
    const toast = document.getElementById('lgStatusToast');
    const textEl = document.getElementById('lgStatusToastText');
    if (!toast || !textEl) return;

    textEl.textContent = msg;
    toast.style.background = isError ? '#B91C1C' : '';
    toast.style.display = 'flex';

    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(function() {
        lgHideStatusToast();
    }, 4000);
}

function lgHideStatusToast() {
    const toast = document.getElementById('lgStatusToast');
    if (toast) toast.style.display = 'none';
}

function lgFilterStatuses() {
    const input = document.getElementById('lgStatusSearch');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('lgStatusTable');
    if (!table) return;
    
    const trs = table.getElementsByClassName('lg-status-row');

    for (let i = 0; i < trs.length; i++) {
        const rowText = trs[i].textContent || trs[i].innerText;
        if (rowText.toLowerCase().indexOf(filter) > -1) {
            trs[i].style.display = '';
        } else {
            trs[i].style.display = 'none';
        }
    }
}

function lgChangeEntries(val) {
    const table = document.getElementById('lgStatusTable');
    if (!table) return;
    const trs = table.getElementsByClassName('lg-status-row');
    const limit = parseInt(val);

    for (let i = 0; i < trs.length; i++) {
        if (i < limit) {
            trs[i].style.display = '';
        } else {
            trs[i].style.display = 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    try {
        const msg = sessionStorage.getItem('lgStatusToast');
        if (msg) { sessionStorage.removeItem('lgStatusToast'); lgShowStatusToast(msg); }
    } catch (e) {}
    @if(request('edit'))
    const editBtn = document.querySelector('.lg-btn-edit-action[data-id="{{ (int) request('edit') }}"]');
    if (editBtn) lgOpenStatusModal(editBtn);
    @elseif(request('add'))
    lgOpenStatusModal();
    @endif
});
</script>
@endpush
