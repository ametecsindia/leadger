@extends('dashboard.layouts.master')
@section('title', 'Transfer Leads - LeadGER')

@push("after-styles")
<style>
/* ==========================================================================
   LeadGER Corporate Modern Design System - Transfer Leads Page
   ========================================================================== */

.lg-transfer-dashboard {
    padding: 24px 30px 40px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #0F172A;
}

/* Page Header */
.lg-page-header {
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
    gap: 14px;
}

.lg-header-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lg-accent);
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
}

.lg-page-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 22px;
    line-height: 1.25;
    letter-spacing: -0.02em;
}

.lg-page-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 400;
}

/* Workspace Card (Single Middle White Container) */
.lg-workspace-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

/* 1. Action Type Selection Row */
.lg-action-type-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 2px;
}

.lg-action-type-left {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.lg-action-type-label {
    font-size: 13.5px;
    font-weight: 700;
    color: #0F172A;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: nowrap;
    user-select: none;
}

.lg-action-radio-group {
    display: flex;
    align-items: center;
    gap: 28px;
    flex-wrap: wrap;
}

.lg-action-radio-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    margin: 0;
    transition: color 0.15s ease;
}

.lg-action-radio-input {
    appearance: none;
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border: 1.5px solid #CBD5E1;
    border-radius: 50%;
    margin: 0;
    outline: none;
    cursor: pointer;
    background-color: #FFFFFF;
    position: relative;
    display: inline-grid;
    place-content: center;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.lg-action-radio-item:hover .lg-action-radio-input {
    border-color: var(--lg-accent);
}

.lg-action-radio-input:checked {
    border-color: var(--lg-accent);
    background-color: #FFFFFF;
}

.lg-action-radio-input:checked::before {
    content: "";
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: var(--lg-accent);
}

.lg-action-radio-input:focus-visible {
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.18);
}

.lg-action-radio-text {
    font-size: 14px;
    font-weight: 500;
    color: #1E293B;
    line-height: 1.4;
    white-space: nowrap;
}

.lg-action-radio-input:checked ~ .lg-action-radio-text {
    font-weight: 600;
    color: #0F172A;
}

.lg-action-type-right {
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

/* Clean Divider between Action Type and Filters */
.lg-action-divider {
    height: 1px;
    background-color: #E2E8F0;
    margin: 16px 0 20px 0;
    width: 100%;
}

/* 2. Filter Toolbar */
.lg-filter-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 22px;
    width: 100%;
}

.lg-filter-col {
    position: relative;
}

.lg-filter-col.lg-col-search {
    flex: 1.4 1 210px;
    min-width: 175px;
}

.lg-search-wrap {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
}

.lg-search-icon {
    position: absolute;
    left: 14px;
    color: #94A3B8;
    pointer-events: none;
    transition: color 0.15s ease;
}

.lg-input-search {
    width: 100%;
    height: 46px;
    padding: 0 14px 0 40px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
}

.lg-input-search:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-input-search:focus ~ .lg-search-icon,
.lg-search-wrap:focus-within .lg-search-icon {
    color: var(--lg-accent);
}

.lg-input-search::placeholder {
    color: #94A3B8;
    font-weight: 400;
}

@keyframes lg-spin {
    to { transform: rotate(360deg); }
}
.lg-spin {
    animation: lg-spin 0.8s linear infinite;
    display: inline-block;
}

.lg-filter-col.lg-col-campaign {
    flex: 1 1 135px;
    min-width: 105px;
}

.lg-filter-col.lg-col-team {
    flex: 0.95 1 120px;
    min-width: 95px;
}

.lg-filter-col.lg-col-executive {
    flex: 1.1 1 140px;
    min-width: 115px;
}

.lg-filter-col-show {
    flex: 0 0 auto;
}

.lg-filter-col-btn {
    flex: 0 0 auto;
}

.lg-show-entities-wrap {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 46px;
    white-space: nowrap;
}

.lg-entities-label {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    user-select: none;
}

/* Custom Single-Select Dropdown Styling */
.lg-custom-dropdown-wrap {
    position: relative;
    width: 100%;
}

.lg-custom-dropdown-trigger {
    width: 100%;
    height: 46px;
    padding: 0 10px 0 12px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    background-color: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    outline: none;
    box-sizing: border-box;
}

.lg-custom-dropdown-trigger:hover,
.lg-custom-dropdown-trigger:focus,
.lg-custom-dropdown-wrap.open .lg-custom-dropdown-trigger {
    background-color: #FFFFFF;
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-custom-dropdown-arrow {
    transition: transform 0.2s ease;
    flex-shrink: 0;
    margin-left: 8px;
}

.lg-custom-dropdown-wrap.open .lg-custom-dropdown-arrow {
    transform: rotate(180deg);
    stroke: var(--lg-accent);
}

.lg-custom-dropdown-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-right: 8px;
    flex: 1;
    text-align: left;
}

.lg-custom-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    width: 100%;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    z-index: 1050;
    max-height: 240px;
    overflow-y: auto;
    padding: 6px;
}

.lg-custom-dropdown-menu::-webkit-scrollbar {
    width: 6px;
}
.lg-custom-dropdown-menu::-webkit-scrollbar-track {
    background: #F8FAFC;
    border-radius: 4px;
}
.lg-custom-dropdown-menu::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 4px;
}
.lg-custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
    background: #94A3B8;
}

.lg-custom-dropdown-wrap.open .lg-custom-dropdown-menu {
    display: block;
}

.lg-custom-dropdown-option {
    padding: 10px 12px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lg-custom-dropdown-option:hover {
    background-color: #F8FAFC;
    color: var(--lg-accent);
}

.lg-custom-dropdown-option.selected {
    background-color: #EFF6FF;
    color: var(--lg-accent);
    font-weight: 600;
}

/* Action Buttons */
.lg-btn-filter {
    height: 46px;
    padding: 0 18px;
    background: var(--lg-accent);
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    white-space: nowrap;
}

.lg-btn-filter:hover {
    background: #0F52CC;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}

.lg-btn-reset-toolbar {
    height: 46px;
    padding: 0 16px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.lg-btn-reset-toolbar:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}

/* Account Table */
.lg-table-card {
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    background: #FFFFFF;
    margin-bottom: 20px;
}

.lg-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

.lg-table th {
    background: #F8FAFC;
    color: #475569;
    font-weight: 700;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #E2E8F0;
    font-size: 12.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.lg-table td {
    padding: 14px 16px;
    color: #1E293B;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
}

.lg-table tbody tr:last-child td {
    border-bottom: none;
}

.lg-table tbody tr:hover {
    background: #F8FAFC;
}

.lg-chk-input {
    width: 17px;
    height: 17px;
    accent-color: var(--lg-accent);
    cursor: pointer;
}

.lg-acc-id {
    font-family: monospace;
    font-weight: 700;
    color: var(--lg-accent);
}

.lg-owner-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #EFF6FF;
    color: var(--lg-accent-hover);
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
}

.lg-owner-pill.unassigned {
    background: #F1F5F9;
    color: #64748B;
}

.lg-badge-status {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: #ECFDF5;
    color: #047857;
}

/* Footer Action Area */
.lg-footer-action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 16px;
    border-top: 1px solid #F1F5F9;
    flex-wrap: wrap;
    gap: 16px;
}

.lg-selection-summary {
    font-size: 14px;
    font-weight: 600;
    color: #475569;
}

.lg-selection-count {
    color: var(--lg-accent);
    font-weight: 700;
}

.lg-btn-primary-action {
    height: 44px;
    padding: 0 24px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(37, 99, 235, 0.25);
    transition: all 0.15s ease;
}

.lg-btn-primary-action:hover {
    background: var(--lg-accent-hover);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
}

.lg-btn-primary-action:disabled {
    background: #94A3B8;
    box-shadow: none;
    cursor: not-allowed;
}

/* Pagination Bar */
.lg-pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 16px;
    font-size: 13px;
    color: #64748B;
    flex-wrap: wrap;
    gap: 12px;
}

.lg-pagination-nav {
    display: flex;
    align-items: center;
    gap: 4px;
}

.lg-page-btn {
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-page-btn.active {
    background: var(--lg-accent);
    color: #FFFFFF;
    border-color: var(--lg-accent);
}

.lg-page-btn:hover:not(.active):not(:disabled) {
    background: #F8FAFC;
    border-color: #94A3B8;
}

@media (max-width: 991px) {
    .lg-filter-toolbar {
        flex-wrap: wrap;
        gap: 10px;
    }
    .lg-filter-col.lg-col-search {
        flex: 1 1 100%;
        min-width: 100%;
    }
    .lg-filter-col.lg-col-campaign,
    .lg-filter-col.lg-col-team,
    .lg-filter-col.lg-col-executive {
        flex: 1 1 calc(50% - 10px);
        min-width: 140px;
    }
}

@media (max-width: 767px) {
    .lg-action-type-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 14px;
    }
    .lg-action-type-left {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        width: 100%;
    }
    .lg-action-radio-group {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        width: 100%;
    }
    .lg-filter-toolbar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .lg-filter-col, .lg-filter-col-show, .lg-filter-col-btn {
        width: 100%;
        min-width: 100%;
    }
    .lg-show-entities-wrap {
        width: 100%;
        justify-content: flex-start;
    }
    .lg-btn-filter, .lg-btn-reset-toolbar {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush

@section('content')

<div class="lg-transfer-dashboard">

    <!-- 1. PAGE HEADER -->
    <div class="lg-page-header">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 1 21 5 17 9"></polyline>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                    <polyline points="7 23 3 19 7 15"></polyline>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                </svg>
            </div>
            <div>
                <h1 class="lg-page-title">Transfer Leads</h1>
                <p class="lg-page-subtitle">Assign, unassign, and transfer leads between users and teams.</p>
            </div>
        </div>
    </div>

    <!-- 2. MAIN WORKSPACE CARD (Single Unified White Middle Container) -->
    <div class="lg-workspace-card">

        <!-- 2.1 ACTION TYPE ROW -->
        <div class="lg-action-type-row">
            <div class="lg-action-type-left">
                <span class="lg-action-type-label">ACTION TYPE:</span>
                <div class="lg-action-radio-group">
                    @php
                        $selectedAction = request()->get('action', request()->get('mode', 'assign'));
                    @endphp
                    <label class="lg-action-radio-item" for="actionTypeAssign">
                        <input type="radio" id="actionTypeAssign" name="action_type" value="assign" class="lg-action-radio-input" {{ $selectedAction === 'assign' ? 'checked' : '' }}>
                        <span class="lg-action-radio-text">Assign Leads</span>
                    </label>

                    <label class="lg-action-radio-item" for="actionTypeUnassign">
                        <input type="radio" id="actionTypeUnassign" name="action_type" value="unassign" class="lg-action-radio-input" {{ $selectedAction === 'unassign' ? 'checked' : '' }}>
                        <span class="lg-action-radio-text">Un-Assign Leads</span>
                    </label>

                    <label class="lg-action-radio-item" for="actionTypeTransfer">
                        <input type="radio" id="actionTypeTransfer" name="action_type" value="transfer" class="lg-action-radio-input" {{ $selectedAction === 'transfer' ? 'checked' : '' }}>
                        <span class="lg-action-radio-text">Transfer Leads</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 2.2 CLEAN DIVIDER -->
        <div class="lg-action-divider"></div>

        <!-- 2.3 ACTION-SPECIFIC FILTER ROW -->
        <div class="lg-filter-toolbar">
            <!-- 0. Search Input -->
            <div class="lg-filter-col lg-col-search">
                <div class="lg-search-wrap">
                    <svg class="lg-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="find_q" class="lg-input-search" placeholder="Search by name, mobile or lead ID" autocomplete="off">
                </div>
            </div>

            <!-- 1. All Campaigns Dropdown -->
            <div class="lg-filter-col lg-col-campaign">
                <div class="lg-custom-dropdown-wrap" data-select-id="filterCampaign">
                    <input type="hidden" id="filterCampaign" value="">
                    <div class="lg-custom-dropdown-trigger" role="combobox" aria-expanded="false" tabindex="0">
                        <span class="lg-custom-dropdown-label">All Campaigns</span>
                        <svg class="lg-custom-dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="lg-custom-dropdown-menu">
                        <div class="lg-custom-dropdown-option selected" data-val="" data-default-label="All Campaigns"><span>All Campaigns</span></div>
                        @foreach($Campaigns as $c)
                            <div class="lg-custom-dropdown-option" data-val="{{ $c->id }}"><span>{{ $c->name }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 2. All Teams Dropdown -->
            <div class="lg-filter-col lg-col-team">
                <div class="lg-custom-dropdown-wrap" data-select-id="filterTeam">
                    <input type="hidden" id="filterTeam" value="">
                    <div class="lg-custom-dropdown-trigger" role="combobox" aria-expanded="false" tabindex="0">
                        <span class="lg-custom-dropdown-label">All Teams</span>
                        <svg class="lg-custom-dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="lg-custom-dropdown-menu">
                        <div class="lg-custom-dropdown-option selected" data-val="" data-default-label="All Teams"><span>All Teams</span></div>
                        @foreach($Teams as $t)
                            <div class="lg-custom-dropdown-option" data-val="{{ $t->id }}"><span>{{ $t->name }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 3. All Executives Dropdown (Assign & Un-Assign modes) -->
            <div class="lg-filter-col lg-col-executive" id="colExecutiveSingle" style="{{ $selectedAction === 'transfer' ? 'display: none;' : '' }}">
                <div class="lg-custom-dropdown-wrap" data-select-id="filterUser">
                    <input type="hidden" id="filterUser" value="">
                    <div class="lg-custom-dropdown-trigger" role="combobox" aria-expanded="false" tabindex="0">
                        <span class="lg-custom-dropdown-label">All Executives</span>
                        <svg class="lg-custom-dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="lg-custom-dropdown-menu">
                        <div class="lg-custom-dropdown-option selected" data-val="" data-default-label="All Executives"><span>All Executives</span></div>
                        @foreach($Users as $u)
                            <div class="lg-custom-dropdown-option" data-val="{{ $u->id }}"><span>{{ $u->name }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 4. From Executive Dropdown (Transfer mode only) -->
            <div class="lg-filter-col lg-col-executive" id="colFromExecutive" style="{{ $selectedAction === 'transfer' ? '' : 'display: none;' }}">
                <div class="lg-custom-dropdown-wrap" data-select-id="filterFromUser">
                    <input type="hidden" id="filterFromUser" value="">
                    <div class="lg-custom-dropdown-trigger" role="combobox" aria-expanded="false" tabindex="0">
                        <span class="lg-custom-dropdown-label">From Executive</span>
                        <svg class="lg-custom-dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="lg-custom-dropdown-menu">
                        <div class="lg-custom-dropdown-option selected" data-val="" data-default-label="From Executive"><span>From Executive</span></div>
                        @foreach($Users as $u)
                            <div class="lg-custom-dropdown-option" data-val="{{ $u->id }}"><span>{{ $u->name }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 5. To Executive Dropdown (Transfer mode only) -->
            <div class="lg-filter-col lg-col-executive" id="colToExecutive" style="{{ $selectedAction === 'transfer' ? '' : 'display: none;' }}">
                <div class="lg-custom-dropdown-wrap" data-select-id="filterToUser">
                    <input type="hidden" id="filterToUser" value="">
                    <div class="lg-custom-dropdown-trigger" role="combobox" aria-expanded="false" tabindex="0">
                        <span class="lg-custom-dropdown-label">To Executive</span>
                        <svg class="lg-custom-dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="lg-custom-dropdown-menu">
                        <div class="lg-custom-dropdown-option selected" data-val="" data-default-label="To Executive"><span>To Executive</span></div>
                        @foreach($Users as $u)
                            <div class="lg-custom-dropdown-option" data-val="{{ $u->id }}"><span>{{ $u->name }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 6. Show Entities Dropdown -->
            <div class="lg-filter-col-show">
                <div class="lg-show-entities-wrap">
                    <span class="lg-entities-label">Show</span>
                    <div class="lg-custom-dropdown-wrap" data-select-id="filterLength" style="width: 86px;">
                        <input type="hidden" id="filterLength" value="10">
                        <div class="lg-custom-dropdown-trigger" role="combobox" aria-expanded="false" tabindex="0" style="padding: 0 10px;">
                            <span class="lg-custom-dropdown-label">10</span>
                            <svg class="lg-custom-dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="lg-custom-dropdown-menu" style="min-width: 86px;">
                            <div class="lg-custom-dropdown-option selected" data-val="10"><span>10</span></div>
                            <div class="lg-custom-dropdown-option" data-val="25"><span>25</span></div>
                            <div class="lg-custom-dropdown-option" data-val="50"><span>50</span></div>
                            <div class="lg-custom-dropdown-option" data-val="100"><span>100</span></div>
                            <div class="lg-custom-dropdown-option" data-val="200"><span>200</span></div>
                            <div class="lg-custom-dropdown-option" data-val="500"><span>500</span></div>
                        </div>
                    </div>
                    <span class="lg-entities-label">entities</span>
                </div>
            </div>

            <!-- 7. Filter Button -->
            <div class="lg-filter-col-btn">
                <button type="button" class="lg-btn-filter" id="btnApplyFilter">
                    <svg class="lg-btn-filter-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    <span class="lg-btn-filter-text">Filter</span>
                </button>
            </div>

            <!-- 8. Reset Button -->
            <div class="lg-filter-col-btn">
                <button type="button" class="lg-btn-reset-toolbar" id="btnResetFilters" title="Reset all filters">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <!-- ACCOUNT TABLE -->
        <div class="lg-table-card">
            <table class="lg-table" id="accountsTable">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input type="checkbox" id="selectAllCheckbox" class="lg-chk-input">
                        </th>
                        <th>Account / Lead ID</th>
                        <th>Account Name</th>
                        <th>Mobile</th>
                        <th>Campaign</th>
                        <th>Current Owner</th>
                        <th>Team</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Loaded dynamically via JS -->
                </tbody>
            </table>
        </div>

        <!-- FOOTER SELECTION SUMMARY & ACTION BUTTON -->
        <div class="lg-footer-action-bar" style="justify-content: flex-start; gap: 16px;">

            <button type="button" class="lg-btn-primary-action" id="btnSubmitAction">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" id="actionBtnIcon">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                <span id="actionBtnText">Transfer Leads</span>
            </button>

            <div class="lg-selection-summary">
                <span class="lg-selection-count" id="selectedCountText">0</span> leads selected
            </div>
        </div>

        <!-- PAGINATION BAR -->
        <div class="lg-pagination-wrapper">
            <div id="paginationInfo">Showing 0 entries</div>
            <div class="lg-pagination-nav" id="paginationNav">
                <!-- Page buttons rendered by JS -->
            </div>
        </div>

    </div>

</div>

<!-- CONFIRMATION / TRANSFER MODAL -->
<div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 460px;">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.12);">
            <div class="modal-body" style="padding: 28px;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: #EFF6FF; color: var(--lg-accent); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="17 1 21 5 17 9"></polyline>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <polyline points="7 23 3 19 7 15"></polyline>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                </div>
                <h4 style="font-weight: 700; color: #0F172A; font-size: 18px; margin-bottom: 8px; text-align: center;" id="modalTitle">Transfer Selected Leads</h4>
                <p style="font-size: 13.5px; color: #64748B; margin-bottom: 20px; line-height: 1.5; text-align: center;" id="modalMessage">
                    Select the target executive to assign or transfer the <strong id="modalLeadCount" style="color: var(--lg-accent);">0</strong> selected lead(s).
                </p>

                <div id="modalTargetUserGroup" style="margin-bottom: 20px; text-align: left;">
                    <label id="modalTargetUserLabel" style="font-size: 12.5px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;">Assign / Transfer To Executive</label>
                    <select id="modal_target_user_id" class="form-control" style="height: 42px; border-radius: 10px; border: 1px solid #CBD5E1; font-size: 13.5px; font-weight: 500; color: #0F172A;">
                        <option value="">-- Choose Target Executive --</option>
                        @foreach($Users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                    <button type="button" id="btnUnassignExecute" class="btn btn-outline-danger" style="height: 42px; border-radius: 8px; font-weight: 600; padding: 0 16px; margin-right: auto; font-size: 13px; display: none;" title="Remove assignment from selected leads">Unassign Leads</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="height: 42px; border-radius: 8px; font-weight: 600; padding: 0 18px; background: #F1F5F9; color: #475569; border: none;">Cancel</button>
                    <button type="button" id="btnConfirmExecute" class="btn btn-primary" style="height: 42px; border-radius: 8px; font-weight: 700; padding: 0 22px; background: var(--lg-accent); color: #FFFFFF; border: none;">Transfer Leads</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push("after-scripts")
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let pageSize = 10;
    let totalRecords = 0;
    let selectedAccountIds = new Set();
    let currentAccountsData = [];
    let currentMode = 'assign';
    let searchDebounceTimer = null;
    let isLoading = false;

    const csrfToken = "{{ csrf_token() }}";
    const dataUrl = "{{ route('campaignsTransferAccountsData') }}";
    const transferUrl = "{{ route('leadsTransferAction') }}";
    const unassignUrl = "{{ route('leadsUnassignAction') }}";

    const allCampaignsData = @json($Campaigns->map(fn($c) => ['id' => $c->id, 'name' => $c->name]));
    const allTeamsData = @json($Teams->map(fn($t) => ['id' => $t->id, 'name' => $t->name]));
    const allUsersData = @json($Users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]));
    const campaignTeamsMap = @json($campaignTeamsMap ?? []);
    const teamUsersMap = @json($teamUsersMap ?? []);
    const campaignUsersMap = @json($campaignUsersMap ?? []);

    // Dropdown options updater helper
    function updateDropdownOptions(selectId, items, defaultLabel, currentVal) {
        const wrap = $(`[data-select-id="${selectId}"]`);
        if (!wrap.length) return;

        let selectedVal = '';
        let selectedLabel = defaultLabel;

        const hasCurrent = items.some(item => String(item.id) === String(currentVal));
        if (hasCurrent && currentVal !== '' && currentVal !== null) {
            selectedVal = String(currentVal);
            const match = items.find(item => String(item.id) === String(currentVal));
            if (match) selectedLabel = match.name;
        }

        wrap.find('input[type="hidden"]').val(selectedVal);
        wrap.find('.lg-custom-dropdown-label').text(selectedLabel);

        let menuHtml = `<div class="lg-custom-dropdown-option ${selectedVal === '' ? 'selected' : ''}" data-val="" data-default-label="${defaultLabel}"><span>${defaultLabel}</span></div>`;
        items.forEach(function (item) {
            const isSel = String(item.id) === selectedVal;
            menuHtml += `<div class="lg-custom-dropdown-option ${isSel ? 'selected' : ''}" data-val="${item.id}"><span>${item.name}</span></div>`;
        });

        wrap.find('.lg-custom-dropdown-menu').html(menuHtml);
    }

    // Modal target executive updater helper
    function updateModalTargetUserOptions(items, currentVal) {
        let html = '<option value="">-- Choose Target Executive --</option>';
        items.forEach(function (item) {
            const sel = String(item.id) === String(currentVal) ? 'selected' : '';
            html += `<option value="${item.id}" ${sel}>${item.name}</option>`;
        });
        $('#modal_target_user_id').html(html);
    }

    // Cascading dropdowns synchronizer
    function syncCascadingFilters(triggerSource) {
        const selectedCampId = $('#filterCampaign').val();
        let selectedTeamId = $('#filterTeam').val();

        // 1. Teams: If campaign selected, show only teams belonging to that campaign
        let availableTeams = allTeamsData;
        if (selectedCampId && campaignTeamsMap[selectedCampId]) {
            const allowedTeamIds = campaignTeamsMap[selectedCampId];
            availableTeams = allTeamsData.filter(t => allowedTeamIds.includes(t.id));
        }

        const hasTeam = availableTeams.some(t => String(t.id) === String(selectedTeamId));
        if (!hasTeam) {
            selectedTeamId = '';
        }
        updateDropdownOptions('filterTeam', availableTeams, 'All Teams', selectedTeamId);

        // 2. Executives: depend on selected team and selected campaign
        let availableUsers = allUsersData;

        let campUserIds = null;
        if (selectedCampId && campaignUsersMap[selectedCampId]) {
            campUserIds = campaignUsersMap[selectedCampId];
        }

        let teamUserIds = null;
        if (selectedTeamId && teamUsersMap[selectedTeamId]) {
            teamUserIds = teamUsersMap[selectedTeamId];
        }

        if (campUserIds !== null && teamUserIds !== null) {
            const intersection = campUserIds.filter(id => teamUserIds.includes(id));
            availableUsers = allUsersData.filter(u => intersection.includes(u.id));
        } else if (campUserIds !== null) {
            availableUsers = allUsersData.filter(u => campUserIds.includes(u.id));
        } else if (teamUserIds !== null) {
            availableUsers = allUsersData.filter(u => teamUserIds.includes(u.id));
        }

        const curUser = $('#filterUser').val();
        const curFromUser = $('#filterFromUser').val();
        const curToUser = $('#filterToUser').val();

        updateDropdownOptions('filterUser', availableUsers, 'All Executives', curUser);
        updateDropdownOptions('filterFromUser', availableUsers, 'From Executive', curFromUser);
        updateDropdownOptions('filterToUser', availableUsers, 'To Executive', curToUser);
        updateModalTargetUserOptions(availableUsers, curToUser || curUser);
    }

    // Action Type Radio Toggle
    $('input[name="action_type"]').on('change', function () {
        currentMode = $(this).val();

        if (currentMode === 'transfer') {
            $('#colExecutiveSingle').hide();
            $('#colFromExecutive').show();
            $('#colToExecutive').show();

            $('#actionBtnText').text('Transfer Leads');
            $('#actionBtnIcon').html('<polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path>');
            $('#btnSubmitAction').css('background', '#1464F4');
        } else if (currentMode === 'unassign') {
            $('#colExecutiveSingle').show();
            $('#colFromExecutive').hide();
            $('#colToExecutive').hide();

            $('#actionBtnText').text('Un-Assign Leads');
            $('#actionBtnIcon').html('<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="17" y1="11" x2="23" y2="11"></line>');
            $('#btnSubmitAction').css('background', '#DC2626');
        } else { // assign
            $('#colExecutiveSingle').show();
            $('#colFromExecutive').hide();
            $('#colToExecutive').hide();

            $('#actionBtnText').text('Assign Leads');
            $('#actionBtnIcon').html('<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line>');
            $('#btnSubmitAction').css('background', '#1464F4');
        }

        // Clear lead selection
        selectedAccountIds.clear();
        updateSelectedCountUI();

        // Reload data for this action mode
        currentPage = 1;
        loadAccountsData();
    });

    // Custom Dropdown Open/Close
    $(document).on('click', '.lg-custom-dropdown-trigger', function (e) {
        e.stopPropagation();
        const wrap = $(this).closest('.lg-custom-dropdown-wrap');
        const isOpen = wrap.hasClass('open');
        $('.lg-custom-dropdown-wrap').removeClass('open');
        $('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
        if (!isOpen) {
            wrap.addClass('open');
            $(this).attr('aria-expanded', 'true');
        }
    });

    // Custom Dropdown Option Selection
    $(document).on('click', '.lg-custom-dropdown-option', function (e) {
        e.stopPropagation();
        const option = $(this);
        const wrap = option.closest('.lg-custom-dropdown-wrap');
        const val = option.attr('data-val');
        const defaultLabel = option.attr('data-default-label');
        const labelText = defaultLabel || option.find('span').text().trim();
        const hiddenInput = wrap.find('input[type="hidden"]');
        const inputId = hiddenInput.attr('id');

        hiddenInput.val(val);
        wrap.find('.lg-custom-dropdown-label').text(labelText);
        wrap.find('.lg-custom-dropdown-option').removeClass('selected');
        option.addClass('selected');
        wrap.removeClass('open');
        wrap.find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');

        // Handle cascading and filtering
        if (inputId === 'filterCampaign') {
            syncCascadingFilters('campaign');
            currentPage = 1;
            selectedAccountIds.clear();
            updateSelectedCountUI();
            loadAccountsData();
        } else if (inputId === 'filterTeam') {
            syncCascadingFilters('team');
            currentPage = 1;
            selectedAccountIds.clear();
            updateSelectedCountUI();
            loadAccountsData();
        } else if (inputId === 'filterLength') {
            pageSize = parseInt(val) || 10;
            currentPage = 1;
            selectedAccountIds.clear();
            updateSelectedCountUI();
            loadAccountsData();
        } else if (inputId === 'filterUser' && currentMode === 'assign') {
            // Assign mode: this is the executive to assign to (the list shows unassigned leads), keep the ticked leads
        } else if (inputId === 'filterUser' || inputId === 'filterFromUser') {
            currentPage = 1;
            selectedAccountIds.clear();
            updateSelectedCountUI();
            loadAccountsData();
        } else if (inputId === 'filterToUser') {
            if (val) {
                $('#modal_target_user_id').val(val);
            }
        }
    });

    // Close custom dropdowns on click outside or Escape
    $(document).on('click', function () {
        $('.lg-custom-dropdown-wrap').removeClass('open');
        $('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.lg-custom-dropdown-wrap').removeClass('open');
            $('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
        }
    });

    // Search Input Events (Debounced & Enter)
    $('#find_q').on('input', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function () {
            currentPage = 1;
            selectedAccountIds.clear();
            updateSelectedCountUI();
            loadAccountsData();
        }, 400);
    });

    $('#find_q').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchDebounceTimer);
            currentPage = 1;
            selectedAccountIds.clear();
            updateSelectedCountUI();
            loadAccountsData();
        }
    });

    // Reset Filters Button
    $('#btnResetFilters').on('click', function () {
        $('#find_q').val('');

        $('#filterCampaign').val('');
        $('#filterTeam').val('');
        $('#filterUser').val('');
        $('#filterFromUser').val('');
        $('#filterToUser').val('');
        $('#filterLength').val('10');

        updateDropdownOptions('filterCampaign', allCampaignsData, 'All Campaigns', '');
        updateDropdownOptions('filterTeam', allTeamsData, 'All Teams', '');
        updateDropdownOptions('filterUser', allUsersData, 'All Executives', '');
        updateDropdownOptions('filterFromUser', allUsersData, 'From Executive', '');
        updateDropdownOptions('filterToUser', allUsersData, 'To Executive', '');
        updateModalTargetUserOptions(allUsersData, '');

        $('[data-select-id="filterLength"] .lg-custom-dropdown-label').text('10');
        $('[data-select-id="filterLength"] .lg-custom-dropdown-option').removeClass('selected');
        $('[data-select-id="filterLength"] .lg-custom-dropdown-option[data-val="10"]').addClass('selected');

        pageSize = 10;
        currentPage = 1;
        selectedAccountIds.clear();
        updateSelectedCountUI();
        loadAccountsData();
    });

    // Filter Apply Button
    $('#btnApplyFilter').on('click', function () {
        currentPage = 1;
        selectedAccountIds.clear();
        updateSelectedCountUI();
        loadAccountsData();
    });

    // Load Accounts Data via AJAX
    function loadAccountsData() {
        if (isLoading) return;
        isLoading = true;

        const lengthVal = parseInt($('#filterLength').val()) || pageSize || 10;
        pageSize = lengthVal;
        const start = (currentPage - 1) * pageSize;
        const postData = {
            _token: csrfToken,
            draw: currentPage,
            start: start,
            length: pageSize,
            action_mode: currentMode,
            find_q: $('#find_q').val() ? $('#find_q').val().trim() : '',
            find_campaign: $('#filterCampaign').val() || '',
            find_team: $('#filterTeam').val() || '',
            find_user: currentMode === 'transfer' ? '' : ($('#filterUser').val() || ''),
            find_from_user: currentMode === 'transfer' ? ($('#filterFromUser').val() || '') : '',
            find_to_user: currentMode === 'transfer' ? ($('#filterToUser').val() || '') : ''
        };

        // Loading state on button and table
        $('#btnApplyFilter').prop('disabled', true);
        $('#btnApplyFilter .lg-btn-filter-icon').html('<path d="M21 12a9 9 0 1 1-6.219-8.56"/>').addClass('lg-spin');
        $('#btnApplyFilter .lg-btn-filter-text').text('Filtering...');

        $('#tableBody').html(`
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #64748B;">
                    <div style="display: inline-flex; align-items: center; gap: 10px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lg-spin">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                        <span style="font-weight: 500;">Loading leads...</span>
                    </div>
                </td>
            </tr>
        `);

        $.ajax({
            url: dataUrl,
            type: 'POST',
            data: postData,
            success: function (res) {
                totalRecords = res.recordsTotal || 0;
                currentAccountsData = res.data || [];
                renderTableRows(currentAccountsData);
                renderPagination(totalRecords, currentPage, pageSize);
            },
            error: function () {
                $('#tableBody').html(`
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #EF4444;">
                            Failed to load leads. Please check connection and try again.
                        </td>
                    </tr>
                `);
            },
            complete: function () {
                isLoading = false;
                $('#btnApplyFilter').prop('disabled', false);
                $('#btnApplyFilter .lg-btn-filter-icon').html('<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>').removeClass('lg-spin');
                $('#btnApplyFilter .lg-btn-filter-text').text('Filter');
            }
        });
    }

    // Render Table Rows
    function renderTableRows(accounts) {
        if (!accounts || accounts.length === 0) {
            $('#tableBody').html(`
                <tr>
                    <td colspan="8" style="text-align: center; padding: 48px 16px; color: #64748B;">
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <span style="font-weight: 600; font-size: 14.5px; color: #334155;">No leads found</span>
                            <span style="font-size: 13px; color: #94A3B8;">Try adjusting your filters or search keywords</span>
                        </div>
                    </td>
                </tr>
            `);
            $('#selectAllCheckbox').prop('checked', false);
            return;
        }

        let html = '';
        accounts.forEach(function (acc) {
            const isChecked = selectedAccountIds.has(String(acc.id)) ? 'checked' : '';
            const ownerBadge = acc.assigned_to ? `<span class="lg-owner-pill">${acc.owner_name}</span>` : `<span class="lg-owner-pill unassigned">Unassigned</span>`;

            html += `
                <tr>
                    <td style="text-align: center;">
                        <input type="checkbox" class="lg-chk-input row-checkbox" value="${acc.id}" ${isChecked}>
                    </td>
                    <td class="lg-acc-id"><a href="javascript:void(0)" class="lg-lead-id-pill lg-lead-id-link" data-lead-id="${acc.id}" title="View lead ${acc.id}">${acc.id}</a></td>
                    <td style="font-weight: 600;">${acc.name}</td>
                    <td>${acc.phonenumber}</td>
                    <td>${acc.campaign_name}</td>
                    <td>${ownerBadge}</td>
                    <td>${acc.team_name}</td>
                    <td><span class="lg-badge-status">${acc.status_name}</span></td>
                </tr>
            `;
        });

        $('#tableBody').html(html);
        updateSelectAllCheckboxState();
    }

    // Row Checkbox Toggle Event
    $(document).on('change', '.row-checkbox', function () {
        const id = String($(this).val());
        if ($(this).is(':checked')) {
            selectedAccountIds.add(id);
        } else {
            selectedAccountIds.delete(id);
        }
        updateSelectedCountUI();
        updateSelectAllCheckboxState();
    });

    // Select All Checkbox Toggle Event
    $('#selectAllCheckbox').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.row-checkbox').each(function () {
            const id = String($(this).val());
            $(this).prop('checked', isChecked);
            if (isChecked) {
                selectedAccountIds.add(id);
            } else {
                selectedAccountIds.delete(id);
            }
        });
        updateSelectedCountUI();
    });

    function updateSelectAllCheckboxState() {
        const totalRows = $('.row-checkbox').length;
        const checkedRows = $('.row-checkbox:checked').length;
        if (totalRows > 0 && totalRows === checkedRows) {
            $('#selectAllCheckbox').prop('checked', true);
        } else {
            $('#selectAllCheckbox').prop('checked', false);
        }
    }

    function updateSelectedCountUI() {
        const count = selectedAccountIds.size;
        $('#selectedCountText').text(count);
        $('#modalLeadCount').text(count);
    }

    // Pagination Controls Rendering
    function renderPagination(total, page, size) {
        if (total === 0) {
            $('#paginationInfo').text('Showing 0 entries');
            $('#paginationNav').empty();
            return;
        }

        const start = (page - 1) * size + 1;
        const end = Math.min(page * size, total);
        $('#paginationInfo').text(`Showing ${start} to ${end} of ${total} entries`);

        const totalPages = Math.ceil(total / size);
        let navHtml = '';

        if (page > 1) {
            navHtml += `<button class="lg-page-btn" data-page="${page - 1}">‹</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                const activeClass = (i === page) ? 'active' : '';
                navHtml += `<button class="lg-page-btn ${activeClass}" data-page="${i}">${i}</button>`;
            } else if (i === page - 3 || i === page + 3) {
                navHtml += `<span style="padding: 0 4px; color: #94A3B8;">…</span>`;
            }
        }

        if (page < totalPages) {
            navHtml += `<button class="lg-page-btn" data-page="${page + 1}">›</button>`;
        }

        $('#paginationNav').html(navHtml);
    }

    // Pagination button click event
    $(document).on('click', '.lg-page-btn', function () {
        const targetPage = parseInt($(this).data('page'));
        if (targetPage && targetPage !== currentPage) {
            currentPage = targetPage;
            selectedAccountIds.clear();
            updateSelectedCountUI();
            loadAccountsData();
        }
    });

    function lgNotify(type, msg) {
        if (typeof toastr !== 'undefined') { toastr[type](msg); } else { alert(msg); }
    }

    // One request at a time: the action button is locked until the server answers
    function submitAssignment(idsArray, targetStaff) {
        const $btn = $('#btnSubmitAction');
        if ($btn.prop('disabled')) return;
        const label = $('#actionBtnText').text();
        $btn.prop('disabled', true).css('opacity', 0.7);
        $('#actionBtnText').text(currentMode === 'assign' ? 'Assigning...' : 'Transferring...');
        $.ajax({
            url: transferUrl,
            type: 'POST',
            data: { _token: csrfToken, leadsArray: idsArray, staff: targetStaff },
            success: function (res) {
                selectedAccountIds.clear();
                updateSelectedCountUI();
                lgNotify('success', currentMode === 'assign' ? idsArray.length + ' lead(s) assigned successfully.' : (res && res.message ? res.message : 'Leads transferred successfully!'));
                loadAccountsData();
            },
            error: function (xhr) {
                const j = xhr.responseJSON || {};
                const firstErr = j.errors ? Object.values(j.errors)[0][0] : null;
                lgNotify('error', firstErr || j.message || ('Failed to complete lead ' + currentMode + '. Please try again.'));
            },
            complete: function () {
                $btn.prop('disabled', false).css('opacity', '');
                $('#actionBtnText').text(label);
            }
        });
    }

    // Primary Action Submit Click Event (Assign submits directly; Un-Assign / Transfer confirm in a popup)
    $('#btnSubmitAction').on('click', function () {
        const count = selectedAccountIds.size;
        if (count === 0) {
            lgNotify('warning', 'Please select at least one lead from the table.');
            return;
        }

        if (currentMode === 'assign') {
            const target = $('#filterUser').val();
            if (!target) {
                lgNotify('error', 'Select the executive to assign the leads to.');
                $('#colExecutiveSingle .lg-custom-dropdown-trigger').trigger('click');
                return;
            }
            submitAssignment(Array.from(selectedAccountIds), target);
            return;
        }

        $('#modalLeadCount').text(count);

        if (currentMode === 'unassign') {
            $('#modalTitle').text('Unassign Selected Leads');
            $('#modalMessage').html('Are you sure you want to remove executive assignment from <strong style="color: #DC2626;">' + count + '</strong> selected lead(s)?');
            $('#modalTargetUserGroup').hide();
            $('#btnUnassignExecute').hide();
            $('#btnConfirmExecute').text('Confirm Unassign').removeClass('btn-primary').addClass('btn-danger').show();
        } else if (currentMode === 'assign') {
            $('#modalTitle').text('Assign Selected Leads');
            $('#modalMessage').html('Select the target executive to assign the <strong style="color: var(--lg-accent);">' + count + '</strong> selected lead(s).');
            $('#modalTargetUserLabel').text('Assign To Executive');
            $('#modalTargetUserGroup').show();
            const toolbarUser = $('#filterUser').val();
            if (toolbarUser) {
                $('#modal_target_user_id').val(toolbarUser);
            } else {
                $('#modal_target_user_id').val('');
            }
            $('#btnUnassignExecute').hide();
            $('#btnConfirmExecute').text('Assign Leads').removeClass('btn-danger').addClass('btn-primary').show();
        } else { // transfer
            $('#modalTitle').text('Transfer Selected Leads');
            $('#modalMessage').html('Select the target executive to transfer the <strong style="color: var(--lg-accent);">' + count + '</strong> selected lead(s).');
            $('#modalTargetUserLabel').text('Transfer To Executive');
            $('#modalTargetUserGroup').show();
            const toolbarToUser = $('#filterToUser').val();
            if (toolbarToUser) {
                $('#modal_target_user_id').val(toolbarToUser);
            } else {
                $('#modal_target_user_id').val('');
            }
            $('#btnUnassignExecute').show();
            $('#btnConfirmExecute').text('Transfer Leads').removeClass('btn-danger').addClass('btn-primary').show();
        }

        $('#confirmationModal').modal('show');
    });

    // Modal Confirm Click Event
    $('#btnConfirmExecute').on('click', function () {
        const idsArray = Array.from(selectedAccountIds);

        if (currentMode === 'unassign') {
            executeUnassign(idsArray);
            return;
        }

        const targetStaff = $('#modal_target_user_id').val();
        if (!targetStaff) {
            alert('Please select a target Executive.');
            return;
        }

        $('#confirmationModal').modal('hide');
        submitAssignment(idsArray, targetStaff);
    });

    function executeUnassign(idsArray) {
        $('#confirmationModal').modal('hide');
        const postData = {
            _token: csrfToken,
            leadsArray: idsArray
        };

        $.ajax({
            url: unassignUrl,
            type: 'POST',
            data: postData,
            success: function (res) {
                selectedAccountIds.clear();
                updateSelectedCountUI();
                if (typeof toastr !== 'undefined') {
                    toastr.success(res && res.message ? res.message : 'Leads unassigned successfully!');
                } else {
                    alert(res && res.message ? res.message : 'Leads unassigned successfully!');
                }
                loadAccountsData();
            },
            error: function () {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to unassign leads. Please try again.');
                } else {
                    alert('Failed to unassign leads. Please try again.');
                }
            }
        });
    }

    // Modal Unassign Click Event (from Transfer modal unassign shortcut button)
    $('#btnUnassignExecute').on('click', function () {
        const idsArray = Array.from(selectedAccountIds);
        if (!confirm(`Are you sure you want to remove assignment from ${idsArray.length} selected lead(s)?`)) {
            return;
        }
        executeUnassign(idsArray);
    });

    // Initial load - initialize mode, cascade and load data
    $('input[name="action_type"]:checked').trigger('change');
});
</script>
@endpush
