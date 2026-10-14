@extends('dashboard.layouts.master')
@section('title', 'LeadGER AI Call Analysis')

@push("after-styles")
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Page Canvas: Light cool blue-gray background */
.lg-ai-dashboard {
    padding: 20px 24px 40px 24px;
    background-color: #F8FAFD;
    min-height: calc(100vh - 60px);
    font-family: var(--lg-font-sans);
}

/* 1. TOP HEADER CARD */
.lg-header-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
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
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.lg-header-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 20px;
    line-height: 1.25;
}
.lg-header-subtitle {
    color: #64748B;
    font-size: 13px;
    margin-top: 2px;
}
.lg-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lg-btn-refresh {
    background: #FFFFFF;
    color: #334155;
    border: 1px solid #CBD5E1;
    font-weight: 600;
    border-radius: 8px;
    height: 38px;
    padding: 0 16px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.15s ease;
    text-decoration: none;
}
.lg-btn-refresh:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
}
.lg-btn-export {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    font-weight: 600;
    border-radius: 8px;
    height: 38px;
    padding: 0 18px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.15s ease;
    text-decoration: none;
}
.lg-btn-export:hover {
    background: #0F52CC;
    border-color: #0F52CC;
    color: #FFFFFF;
}

/* 2. KPI SUMMARY CARDS (4 Cards in 1 Row) */
.lg-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 991px) {
    .lg-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 575px) {
    .lg-kpi-grid { grid-template-columns: 1fr; }
}

.lg-kpi-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 104px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.lg-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}
.lg-kpi-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}
.lg-kpi-title {
    color: #64748B;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.lg-kpi-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.icon-blue { background: #EFF6FF; color: var(--lg-accent); }
.icon-green { background: #ECFDF5; color: #059669; }
.icon-amber { background: #FFFBEB; color: #D97706; }
.icon-purple { background: #F5F3FF; color: #7C3AED; }

.lg-kpi-val {
    font-size: 24px;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.25;
    margin-bottom: 2px;
}
.lg-kpi-subtext {
    font-size: 12px;
    color: #64748B;
    font-weight: 500;
}

/* 3. CALL INTELLIGENCE ANALYSIS SECTION & CARD */
.lg-table-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: visible;
}
.lg-table-top {
    padding: 16px 24px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.lg-table-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lg-table-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-table-count-badge {
    font-size: 12px;
    font-weight: 600;
    color: var(--lg-accent);
    background: #EFF6FF;
    padding: 3px 11px;
    border-radius: 20px;
    border: 1px solid #DBEAFE;
}

/* Table Toolbar: User Selector on Left, Show entries & Search on Right */
.lg-table-toolbar {
    padding: 14px 24px;
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}
.lg-toolbar-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lg-toolbar-right {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

/* Multi-select User Dropdown Button */
.lg-user-select-wrap {
    position: relative;
}
.lg-btn-user-select {
    height: 38px;
    min-width: 160px;
    padding: 0 14px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    color: #334155;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.lg-btn-user-select:hover {
    border-color: #94A3B8;
    background: #F8FAFC;
}
.lg-btn-user-select-text {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 150px;
}
.lg-chevron {
    font-size: 10px;
    color: #94A3B8;
}

/* User Popover Panel */
.lg-user-popover {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 270px;
    z-index: 1050;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    padding: 12px;
}
.lg-user-popover-search {
    position: relative;
    margin-bottom: 8px;
}
.lg-user-search-field {
    width: 100%;
    height: 34px;
    padding: 0 10px 0 30px;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    font-size: 12px;
    color: #334155;
    outline: none;
}
.lg-user-search-field:focus {
    border-color: var(--lg-accent);
}
.lg-search-icon-inside {
    position: absolute;
    left: 9px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #94A3B8;
}
.lg-user-popover-all {
    padding: 6px 4px;
    border-bottom: 1px solid #F1F5F9;
    margin-bottom: 4px;
}
.lg-user-popover-list {
    max-height: 180px;
    overflow-y: auto;
    padding-right: 4px;
}
.lg-user-checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 4px;
    font-size: 13px;
    color: #1E293B;
    cursor: pointer;
    user-select: none;
    margin: 0;
    border-radius: 4px;
}
.lg-user-checkbox-item:hover {
    background: #F8FAFC;
}
.lg-user-checkbox-item input[type="checkbox"] {
    width: 15px;
    height: 15px;
    accent-color: var(--lg-accent);
    cursor: pointer;
}
.lg-user-popover-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10px;
    margin-top: 8px;
    border-top: 1px solid #F1F5F9;
}
.lg-user-selected-count {
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
}
.lg-btn-apply-user {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    height: 30px;
    padding: 0 14px;
    cursor: pointer;
}

/* Show Entries Control */
.lg-entries-control {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #64748B;
}
.lg-entries-select {
    height: 38px;
    padding: 0 10px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    background: #FFFFFF;
    color: #334155;
    font-size: 13px;
    font-weight: 500;
    outline: none;
    cursor: pointer;
}
.lg-entries-select:focus {
    border-color: var(--lg-accent);
}

/* Search Field Control */
.lg-search-control {
    position: relative;
    width: 220px;
}
.lg-search-field {
    width: 100%;
    height: 38px;
    padding: 0 12px 0 34px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    background: #FFFFFF;
    color: #334155;
    font-size: 13px;
    outline: none;
    transition: all 0.15s ease;
}
.lg-search-field:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 2px rgba(23,105,255,0.15);
}
.lg-search-icon-field {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    color: #94A3B8;
    pointer-events: none;
}

/* 4. TABLE STYLING */
.lg-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
}
.lg-table thead th {
    background: #F8FAFD;
    color: #0F172A;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 14px 20px;
    border-top: none;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}
.lg-table tbody td {
    padding: 15px 20px;
    font-size: 13px;
    color: #1E293B;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    background: #FFFFFF;
}
.lg-table tbody tr:hover td {
    background-color: #F8FAFC;
}

/* Cell typography & links */
.lg-call-link {
    color: var(--lg-accent);
    font-weight: 700;
    text-decoration: none;
}
.lg-call-link:hover {
    color: #0F52CC;
    text-decoration: underline;
}
.lg-user-text {
    font-weight: 600;
    color: #0F172A;
}
.lg-customer-text {
    font-weight: 500;
    color: #1E293B;
}
.lg-duration-text {
    color: #64748B;
    font-weight: 500;
    white-space: nowrap;
}
.lg-intent-text {
    color: #475569;
    font-size: 13px;
    white-space: nowrap;
}
.lg-date-text {
    color: #64748B;
    white-space: nowrap;
}

/* 5. SENTIMENT BADGES (Pill + Soft Background + Icon) */
.lg-sentiment-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    white-space: nowrap;
}
.sentiment-positive {
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #A7F3D0;
}
.sentiment-neutral {
    background: #F1F5F9;
    color: #475569;
    border: 1px solid #E2E8F0;
}
.sentiment-negative {
    background: #FEF2F2;
    color: #DC2626;
    border: 1px solid #FECACA;
}

/* INTEREST BADGES (Pill + Soft Background) */
.lg-interest-pill {
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    white-space: nowrap;
}
.interest-high {
    background: #EFF6FF;
    color: var(--lg-accent-hover);
    border: 1px solid #DBEAFE;
}
.interest-medium {
    background: #F0F7FF;
    color: #3B82F6;
    border: 1px solid #E0EDFE;
}
.interest-low {
    background: #FFF7ED;
    color: #EA580C;
    border: 1px solid #FFEDD5;
}

/* STATUS BADGES (Pill + Soft Background + Icon) */
.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    white-space: nowrap;
}
.status-completed {
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #A7F3D0;
}
.status-processing {
    background: #EFF6FF;
    color: var(--lg-accent);
    border: 1px solid #BFDBFE;
}
.status-pending {
    background: #FFFBEB;
    color: #D97706;
    border: 1px solid #FDE68A;
}
.status-failed {
    background: #FEF2F2;
    color: #DC2626;
    border: 1px solid #FECACA;
}

/* 6. ACTION BUTTON: Bright Blue [ View Analysis ] */
.lg-btn-view-analysis {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    font-size: 12px;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.lg-btn-view-analysis:hover {
    background: #0F52CC;
    border-color: #0F52CC;
    color: #FFFFFF;
    text-decoration: none;
}

/* 7. TABLE FOOTER / PAGINATION */
.lg-table-footer {
    padding: 16px 24px;
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.lg-table-info {
    font-size: 13px;
    color: #64748B;
}
.lg-pagination {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
}
.lg-page-btn {
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}
.lg-page-btn:hover:not(:disabled) {
    background: #F1F5F9;
    border-color: #94A3B8;
    color: #0F172A;
}
.lg-page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #F8FAFC;
    color: #94A3B8;
}
.lg-page-btn.active {
    background: var(--lg-accent);
    border-color: var(--lg-accent);
    color: #FFFFFF;
}

/* Empty State Box */
.lg-empty-box {
    text-align: center;
    padding: 48px 20px;
}
.lg-empty-icon {
    font-size: 40px;
    color: #CBD5E1;
    margin-bottom: 12px;
}
.lg-empty-title {
    font-weight: 700;
    color: #1E293B;
    font-size: 15px;
    margin: 0 0 4px 0;
}
.lg-empty-desc {
    color: #64748B;
    font-size: 13px;
    margin: 0;
}
</style>
@endpush

@section('content')
<div class="lg-ai-dashboard">

    <!-- 1. TOP HEADER — LeadGER AI Analysis -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon">
                <i class="fas fa-wand-magic-sparkles"></i>
            </div>
            <div>
                <h4 class="lg-header-title">LeadGER AI Call Analysis</h4>
                <div class="lg-header-subtitle">AI-powered call transcripts and conversation insights</div>
            </div>
        </div>
        <div class="lg-header-actions">
            <button type="button" class="lg-btn-refresh" id="btnRefreshAiTable" onclick="handleRefresh()">
                <i class="fas fa-arrows-rotate"></i> Refresh
            </button>
            <button type="button" class="lg-btn-export" id="btnExportAiTable" onclick="handleExport()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    @if(session('doneMessage'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
            <i class="fas fa-circle-check me-2"></i>{{ session('doneMessage') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 2. KPI SUMMARY CARDS (4 Cards in 1 Row) -->
    <div class="lg-kpi-grid">
        <!-- Card 1: TOTAL CALLS -->
        <div class="lg-kpi-card">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">TOTAL CALLS</span>
                <div class="lg-kpi-icon icon-blue">
                    <i class="fas fa-phone-volume"></i>
                </div>
            </div>
            <div class="lg-kpi-val">{{ number_format($totalCalls ?? 28942) }}</div>
            <div class="lg-kpi-subtext">All logged calls</div>
        </div>

        <!-- Card 2: ANALYZED CALLS -->
        <div class="lg-kpi-card">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">ANALYZED CALLS</span>
                <div class="lg-kpi-icon icon-green">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <div class="lg-kpi-val">{{ number_format($analyzedCalls ?? 0) }}</div>
            <div class="lg-kpi-subtext">Completed analysis</div>
        </div>

        <!-- Card 3: PENDING -->
        <div class="lg-kpi-card">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">PENDING</span>
                <div class="lg-kpi-icon icon-amber">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="lg-kpi-val">{{ number_format($pendingCalls ?? 0) }}</div>
            <div class="lg-kpi-subtext">In queue or processing</div>
        </div>

        <!-- Card 4: AVG CALL -->
        <div class="lg-kpi-card">
            <div class="lg-kpi-card-header">
                <span class="lg-kpi-title">AVG CALL</span>
                <div class="lg-kpi-icon icon-purple">
                    <i class="fas fa-stopwatch"></i>
                </div>
            </div>
            <div class="lg-kpi-val">{{ $avgDurationMins ?? '3.5' }} mins</div>
            <div class="lg-kpi-subtext">Average duration</div>
        </div>
    </div>

    <!-- 3. CALL INTELLIGENCE ANALYSIS SECTION & TABLE CARD -->
    <div class="lg-table-card">
        
        <!-- Header -->
        <div class="lg-table-top">
            <div class="lg-table-title-wrap">
                <h5 class="lg-table-title">
                    <i class="fas fa-table-list" style="color: var(--lg-accent);"></i> Call Intelligence Analysis
                </h5>
                <span class="lg-table-count-badge" id="tableRecordCount">{{ count($records) }} records</span>
            </div>
        </div>

        <!-- Table Toolbar: [All Users ▼] on Left, Show Entries & Search on Right -->
        <div class="lg-table-toolbar">
            
            <!-- LEFT: User Select Dropdown -->
            <div class="lg-toolbar-left">
                <div class="lg-user-select-wrap" id="userMultiselectWrapper">
                    <button type="button" class="lg-btn-user-select" id="btnUserMultiselect" onclick="toggleUserDropdown(event)">
                        <span class="lg-btn-user-select-text">
                            <i class="fas fa-users text-muted"></i>
                            <span id="userSelectLabel">All Users</span>
                        </span>
                        <i class="fas fa-chevron-down lg-chevron"></i>
                    </button>

                    <!-- Popover Panel -->
                    <div class="lg-user-popover" id="userMultiselectMenu" onclick="event.stopPropagation()">
                        <div class="lg-user-popover-search">
                            <i class="fas fa-search lg-search-icon-inside"></i>
                            <input type="text" id="userSearchInput" class="lg-user-search-field" placeholder="Search users..." onkeyup="filterUserList()">
                        </div>

                        <div class="lg-user-popover-all">
                            <label class="lg-user-checkbox-item" style="font-weight: 600;">
                                <input type="checkbox" id="user_check_all" checked onchange="toggleSelectAllUsers(this)">
                                <span>Select All</span>
                            </label>
                        </div>

                        <div class="lg-user-popover-list" id="userCheckboxList">
                            @foreach($Telecallers as $agent)
                                <label class="lg-user-checkbox-item user-item-row" data-name="{{ strtolower($agent->name) }}">
                                    <input type="checkbox" class="user-checkbox" value="{{ $agent->id }}" data-name="{{ $agent->name }}" checked onchange="handleUserCheckboxChange()">
                                    <span>{{ $agent->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="lg-user-popover-footer">
                            <span class="lg-user-selected-count" id="userSelectedCount">All Users</span>
                            <button type="button" class="lg-btn-apply-user" onclick="applyUserSelection(event)">Apply</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Show entries & Search -->
            <div class="lg-toolbar-right">
                <div class="lg-entries-control">
                    <span>Show</span>
                    <select id="showEntriesSelect" class="lg-entries-select" onchange="changePageSize(this.value)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries</span>
                </div>

                <div class="lg-search-control">
                    <i class="fas fa-search lg-search-icon-field"></i>
                    <input type="text" id="tableSearchInput" class="lg-search-field" placeholder="Search records..." onkeyup="handleSearch(this.value)">
                </div>
            </div>

        </div>

        <!-- 4. TABLE -->
        <div class="table-responsive">
            <table class="table lg-table mb-0" id="aiAnalysisTable">
                <thead>
                    <tr>
                        <th>CALL ID</th>
                        <th>USER</th>
                        <th>CUSTOMER</th>
                        <th>DURATION</th>
                        <th>INTEREST</th>
                        <th>SENTIMENT</th>
                        <th>INTENT</th>
                        <th>STATUS</th>
                        <th>DATE</th>
                        <th style="text-align: right;">ACTION</th>
                    </tr>
                </thead>
                <tbody id="aiAnalysisTableBody">
                    @forelse($records as $item)
                        @php
                            $durationMins = round(($item->audio_duration_seconds ?: $item->duration ?: 0) / 60, 1);
                            
                            // Interest Badge Mapping
                            $interest = strtoupper($item->interest_level ?: 'HIGH');
                            $interestClass = match($interest) {
                                'HIGH' => 'interest-high',
                                'MEDIUM' => 'interest-medium',
                                'LOW' => 'interest-low',
                                default => 'interest-high'
                            };

                            // Sentiment Badge Mapping (Pill + Soft Bg + Icon)
                            $sentiment = strtoupper($item->sentiment ?: 'POSITIVE');
                            $sentimentClass = match($sentiment) {
                                'POSITIVE' => 'sentiment-positive',
                                'NEUTRAL' => 'sentiment-neutral',
                                'NEGATIVE' => 'sentiment-negative',
                                default => 'sentiment-positive'
                            };
                            $sentimentIcon = match($sentiment) {
                                'POSITIVE' => 'fa-face-smile',
                                'NEUTRAL' => 'fa-face-meh',
                                'NEGATIVE' => 'fa-face-frown',
                                default => 'fa-face-smile'
                            };

                            // Status Badge Mapping (Pill + Soft Bg + Icon)
                            $status = strtolower($item->status ?: 'completed');
                            $statusClass = match($status) {
                                'completed' => 'status-completed',
                                'processing' => 'status-processing',
                                'pending' => 'status-pending',
                                'failed' => 'status-failed',
                                default => 'status-completed'
                            };
                            $statusIcon = match($status) {
                                'completed' => 'fa-check',
                                'processing' => 'fa-spinner fa-spin',
                                'pending' => 'fa-clock',
                                'failed' => 'fa-times',
                                default => 'fa-check'
                            };
                        @endphp
                        <tr class="ai-table-row" data-user-id="{{ $item->created_by }}" data-user-name="{{ strtolower($item->agent_name) }}">
                            <!-- CALL ID -->
                            <td>
                                <a onclick="leadDetail('{{ $item->lead_id }}')" class="lg-call-link" title="View Lead Details">#{{ $item->call_id }}</a>
                            </td>

                            <!-- USER -->
                            <td>
                                <span class="lg-user-text">{{ $item->agent_name ?: 'System User' }}</span>
                            </td>

                            <!-- CUSTOMER -->
                            <td>
                                <span class="lg-customer-text">{{ $item->customer_name ?: ($item->phonenumber ?: 'N/A') }}</span>
                            </td>

                            <!-- DURATION -->
                            <td>
                                <span class="lg-duration-text">{{ $durationMins }} mins</span>
                            </td>

                            <!-- INTEREST -->
                            <td>
                                <span class="lg-interest-pill {{ $interestClass }}">
                                    {{ ucfirst(strtolower($item->interest_level ?: 'High')) }}
                                </span>
                            </td>

                            <!-- SENTIMENT -->
                            <td>
                                <span class="lg-sentiment-pill {{ $sentimentClass }}">
                                    <i class="far {{ $sentimentIcon }}"></i>
                                    <span>{{ ucfirst(strtolower($item->sentiment ?: 'Positive')) }}</span>
                                </span>
                            </td>

                            <!-- INTENT -->
                            <td>
                                <span class="lg-intent-text">{{ $item->intent ?: 'Product Inquiry' }}</span>
                            </td>

                            <!-- STATUS -->
                            <td>
                                <span class="lg-status-pill {{ $statusClass }}">
                                    <i class="fas {{ $statusIcon }}"></i>
                                    <span>{{ ucfirst($status) }}</span>
                                </span>
                            </td>

                            <!-- DATE -->
                            <td>
                                <span class="lg-date-text">{{ date('d M Y', strtotime($item->created_at)) }}</span>
                            </td>

                            <!-- ACTION -->
                            <td style="text-align: right;">
                                <a href="{{ route('aiAnalysisDetail', $item->id) }}" class="lg-btn-view-analysis">
                                    <i class="fas fa-chart-pie"></i> View Analysis
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr id="initialEmptyRow">
                            <td colspan="10">
                                <div class="lg-empty-box">
                                    <i class="fas fa-folder-open lg-empty-icon"></i>
                                    <h6 class="lg-empty-title">No AI analysis records found.</h6>
                                    <p class="lg-empty-desc">Analyzed call recordings will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 7. TABLE FOOTER / PAGINATION -->
        <div class="lg-table-footer">
            <div class="lg-table-info" id="tableInfoText">
                Showing 1 to {{ min(10, count($records)) }} of {{ count($records) }} entries
            </div>
            <div class="lg-pagination" id="tablePagination">
                <button type="button" class="lg-page-btn" id="btnPrevPage" onclick="goToPrevPage()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div id="pageNumbersContainer" style="display: inline-flex; gap: 4px;"></div>
                <button type="button" class="lg-page-btn" id="btnNextPage" onclick="goToNextPage()">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

    </div>

</div>
@endsection

@push("after-scripts")
<script>
(function() {
    var currentPage = 1;
    var pageSize = 10;
    var searchQuery = '';
    var selectedUserNames = [];
    var selectedUserIds = [];
    var isAllUsersSelected = true;

    var allRows = Array.from(document.querySelectorAll('#aiAnalysisTableBody tr.ai-table-row'));

    window.toggleUserDropdown = function(e) {
        if (e) e.stopPropagation();
        var menu = document.getElementById('userMultiselectMenu');
        if (menu) {
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        }
    };

    window.closeUserDropdown = function(e) {
        if (e) e.stopPropagation();
        var menu = document.getElementById('userMultiselectMenu');
        if (menu) menu.style.display = 'none';
    };

    window.filterUserList = function() {
        var q = (document.getElementById('userSearchInput').value || '').toLowerCase().trim();
        var rows = document.querySelectorAll('#userCheckboxList .user-item-row');
        rows.forEach(function(row) {
            var name = row.getAttribute('data-name') || '';
            row.style.display = (name.indexOf(q) !== -1) ? 'flex' : 'none';
        });
    };

    window.toggleSelectAllUsers = function(master) {
        var isChecked = master.checked;
        var checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(function(cb) { cb.checked = isChecked; });
        updateUserLabels();
    };

    window.handleUserCheckboxChange = function() {
        var total = document.querySelectorAll('.user-checkbox').length;
        var checked = document.querySelectorAll('.user-checkbox:checked').length;
        var master = document.getElementById('user_check_all');
        if (master) master.checked = (total === checked);
        updateUserLabels();
    };

    function updateUserLabels() {
        var total = document.querySelectorAll('.user-checkbox').length;
        var checked = document.querySelectorAll('.user-checkbox:checked');
        var label = document.getElementById('userSelectLabel');
        var countText = document.getElementById('userSelectedCount');

        var text = 'All Users';
        if (checked.length === 0) {
            text = 'No Users Selected';
        } else if (checked.length === total) {
            text = 'All Users';
        } else if (checked.length === 1) {
            text = checked[0].getAttribute('data-name') || '1 User Selected';
        } else {
            text = checked.length + ' Users Selected';
        }

        if (label) label.textContent = text;
        if (countText) countText.textContent = text;
    }

    window.applyUserSelection = function(e) {
        window.closeUserDropdown(e);

        var total = document.querySelectorAll('.user-checkbox').length;
        var checked = document.querySelectorAll('.user-checkbox:checked');
        
        isAllUsersSelected = (checked.length === total || total === 0);
        selectedUserNames = [];
        selectedUserIds = [];

        checked.forEach(function(cb) {
            var name = (cb.getAttribute('data-name') || '').toLowerCase().trim();
            if (name) selectedUserNames.push(name);
            if (cb.value) selectedUserIds.push(cb.value);
        });

        currentPage = 1;
        renderTable();
    };

    window.handleSearch = function(val) {
        searchQuery = (val || '').toLowerCase().trim();
        currentPage = 1;
        renderTable();
    };

    window.changePageSize = function(val) {
        pageSize = parseInt(val, 10) || 10;
        currentPage = 1;
        renderTable();
    };

    window.goToPrevPage = function() {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    };

    window.goToNextPage = function() {
        var filtered = getFilteredRows();
        var maxPage = Math.ceil(filtered.length / pageSize) || 1;
        if (currentPage < maxPage) {
            currentPage++;
            renderTable();
        }
    };

    window.goToPage = function(p) {
        currentPage = p;
        renderTable();
    };

    function getFilteredRows() {
        return allRows.filter(function(row) {
            if (!isAllUsersSelected) {
                var rowUserName = (row.getAttribute('data-user-name') || '').toLowerCase().trim();
                var rowUserId = (row.getAttribute('data-user-id') || '').trim();
                var matched = (selectedUserNames.indexOf(rowUserName) !== -1) || (selectedUserIds.indexOf(rowUserId) !== -1);
                if (!matched) return false;
            }

            if (searchQuery) {
                var rowText = (row.textContent || '').toLowerCase();
                if (rowText.indexOf(searchQuery) === -1) {
                    return false;
                }
            }

            return true;
        });
    }

    function renderTable() {
        var filteredRows = getFilteredRows();
        var totalFiltered = filteredRows.length;
        var maxPage = Math.ceil(totalFiltered / pageSize) || 1;

        if (currentPage > maxPage) currentPage = maxPage;
        if (currentPage < 1) currentPage = 1;

        var startIdx = (currentPage - 1) * pageSize;
        var endIdx = startIdx + pageSize;

        allRows.forEach(function(row) { row.style.display = 'none'; });

        var visibleSlice = filteredRows.slice(startIdx, endIdx);
        visibleSlice.forEach(function(row) { row.style.display = ''; });

        var tbody = document.getElementById('aiAnalysisTableBody');
        var emptyRow = document.getElementById('dynamicEmptyRow');

        if (totalFiltered === 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'dynamicEmptyRow';
                emptyRow.innerHTML = '<td colspan="10">' +
                    '<div class="lg-empty-box">' +
                    '<i class="fas fa-folder-open lg-empty-icon"></i>' +
                    '<h6 class="lg-empty-title">No AI analysis records found.</h6>' +
                    '<p class="lg-empty-desc">Try adjusting your search query or selected users.</p>' +
                    '</div>' +
                    '</td>';
                tbody.appendChild(emptyRow);
            }
            emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }

        var countBadge = document.getElementById('tableRecordCount');
        if (countBadge) {
            countBadge.textContent = totalFiltered + (totalFiltered === 1 ? ' record' : ' records');
        }

        var infoText = document.getElementById('tableInfoText');
        if (infoText) {
            if (totalFiltered === 0) {
                infoText.textContent = 'Showing 0 to 0 of 0 entries';
            } else {
                var actualEnd = Math.min(endIdx, totalFiltered);
                infoText.textContent = 'Showing ' + (startIdx + 1) + ' to ' + actualEnd + ' of ' + totalFiltered + ' entries';
            }
        }

        var btnPrev = document.getElementById('btnPrevPage');
        var btnNext = document.getElementById('btnNextPage');
        if (btnPrev) btnPrev.disabled = (currentPage <= 1);
        if (btnNext) btnNext.disabled = (currentPage >= maxPage || totalFiltered === 0);

        var numContainer = document.getElementById('pageNumbersContainer');
        if (numContainer) {
            numContainer.innerHTML = '';
            for (var p = 1; p <= maxPage; p++) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'lg-page-btn' + (p === currentPage ? ' active' : '');
                btn.textContent = p;
                (function(pageIndex) {
                    btn.onclick = function() { goToPage(pageIndex); };
                })(p);
                numContainer.appendChild(btn);
            }
        }
    }

    window.handleRefresh = function() {
        var refreshIcon = document.querySelector('#btnRefreshAiTable i');
        if (refreshIcon) refreshIcon.classList.add('fa-spin');
        
        var searchInput = document.getElementById('tableSearchInput');
        if (searchInput) searchInput.value = '';
        searchQuery = '';

        var master = document.getElementById('user_check_all');
        if (master) master.checked = true;
        var checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(function(cb) { cb.checked = true; });
        isAllUsersSelected = true;
        selectedUserNames = [];
        selectedUserIds = [];
        updateUserLabels();

        currentPage = 1;
        renderTable();

        setTimeout(function() {
            if (refreshIcon) refreshIcon.classList.remove('fa-spin');
        }, 300);
    };

    window.handleExport = function() {
        var params = new URLSearchParams();
        var total = document.querySelectorAll('.user-checkbox').length;
        var checked = document.querySelectorAll('.user-checkbox:checked');
        
        if (checked.length > 0 && checked.length < total) {
            checked.forEach(function(cb) { params.append('users[]', cb.value); });
        }

        window.location.href = "{{ route('aiAnalysisExport') }}?" + params.toString();
    };

    document.addEventListener('click', function(e) {
        var wrapper = document.getElementById('userMultiselectWrapper');
        var menu = document.getElementById('userMultiselectMenu');
        if (wrapper && menu && !wrapper.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    updateUserLabels();
    renderTable();

})();
</script>
@endpush
