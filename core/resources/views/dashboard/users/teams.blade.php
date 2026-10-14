@extends('dashboard.layouts.master')
@section('title', 'Teams - LeadGER')
@section('content')

@php
    $AllUsers = \App\Models\User::orderBy('name', 'asc')->get();
    $AllTeams = \App\Models\Team::orderBy('name', 'asc')->get();
@endphp

<style>
/* LeadGER Corporate Modern Teams Page Styling */
.lg-groups-wrapper {
    font-family: var(--lg-font-sans);
    color: #0F172A;
    background: #F8FAFC;
    padding: 24px;
    min-height: calc(100vh - 70px);
}

.lg-header-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}

.lg-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-header-badge {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(20, 100, 244, 0.1);
    color: var(--lg-accent);
    border: 1px solid rgba(20, 100, 244, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.12);
}

.lg-title-main {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-title-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 2px;
    font-weight: 400;
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
    color: var(--lg-accent);
    text-decoration: none;
}

.lg-breadcrumb-nav a:hover {
    text-decoration: underline;
}

/* Main Container Card */
.lg-main-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
}

/* Action Bar */
.lg-top-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}

.lg-btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    font-size: 13.5px;
    font-weight: 600;
    padding: 9.5px 20px;
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-btn-add::before,
.lg-btn-action::before {
    content: none !important;
    display: none !important;
}

.lg-btn-add:hover {
    background: #0F52CC;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}

.lg-btn-view-teams {
    background: var(--lg-accent);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}

.lg-btn-view-teams:hover {
    background: var(--lg-accent-hover);
}

/* Table Toolbar (Search & Dropdowns) */
.lg-table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}

.lg-search-box {
    position: relative;
    width: 300px;
    max-width: 100%;
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
    height: 40px;
    padding: 0 14px 0 40px;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
}

.lg-search-input:focus {
    background: #FFFFFF;
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-entries-box {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
}

.lg-entries-select {
    height: 38px;
    padding: 0 28px 0 12px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    color: #0F172A;
    font-weight: 600;
    cursor: pointer;
    appearance: none;
}

/* Modern Teams Table */
.lg-table-wrapper {
    overflow-x: auto;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
}

.lg-groups-table {
    width: 100%;
    border-collapse: collapse;
    background: #FFFFFF;
    font-size: 13.5px;
    text-align: left;
}

.lg-groups-table th {
    background: #F8FAFC;
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 14px 18px;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}

.lg-groups-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #F1F5F9;
    color: #334155;
    vertical-align: middle;
}

.lg-groups-table tbody tr {
    transition: background-color 0.15s ease;
}

.lg-groups-table tbody tr:hover {
    background-color: #F8FAFC;
}

.lg-id-badge {
    font-weight: 700;
    color: var(--lg-accent);
    background: #EFF6FF;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12.5px;
    display: inline-block;
}

.lg-group-name-box {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #0F172A;
}

.lg-group-icon-badge {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: #EFF6FF;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #BFDBFE;
}

.lg-action-btn-group {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.lg-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-action-btn.edit {
    background: #EFF6FF;
    color: var(--lg-accent);
    border: 1px solid #BFDBFE;
}
.lg-action-btn.edit:hover {
    background: var(--lg-accent);
    color: #FFFFFF;
}

.lg-action-btn.members {
    background: #F1F5F9;
    color: #475569;
    border: 1px solid #CBD5E1;
}
.lg-action-btn.members:hover {
    background: #475569;
    color: #FFFFFF;
}

.lg-action-btn.delete {
    background: #FEF2F2;
    color: #EF4444;
    border: 1px solid #FCA5A5;
}
.lg-action-btn.delete:hover {
    background: #EF4444;
    color: #FFFFFF;
}

/* Footer Controls & Export */
.lg-table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #F1F5F9;
}

.lg-export-btn-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.lg-export-btn {
    background: #FFFFFF;
    color: #475569;
    font-size: 12.5px;
    font-weight: 600;
    padding: 7px 14px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-export-btn:hover {
    background: var(--lg-accent);
    color: #FFFFFF;
    border-color: var(--lg-accent);
}

.lg-pagination-wrapper .pagination {
    margin: 0;
    display: flex;
    gap: 4px;
}

.lg-pagination-wrapper .page-item .page-link {
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    color: #475569;
    font-weight: 600;
    padding: 6px 12px;
    font-size: 13px;
}

.lg-pagination-wrapper .page-item.active .page-link {
    background: var(--lg-accent);
    border-color: var(--lg-accent);
    color: #FFFFFF;
}

/* ==========================================================================
   LEADGER MODALS OVERLAY (FULL-SCREEN PROPER CENTERING)
   ========================================================================== */
.modal:not(.show):not(.in) {
    display: none !important;
    pointer-events: none !important;
    z-index: -1 !important;
}

body > #viewTeamsModal.modal.show,
body > #addTeamModal.modal.show,
body > [id^="editTeamModal"].modal.show,
body > #viewMembers.modal.show,
body > [id^="m-"].modal.show,
#viewTeamsModal.modal.show,
#addTeamModal.modal.show,
[id^="editTeamModal"].modal.show,
#viewMembers.modal.show,
[id^="m-"].modal.show,
body > #viewTeamsModal.modal.in,
body > #addTeamModal.modal.in,
body > [id^="editTeamModal"].modal.in,
body > #viewMembers.modal.in,
body > [id^="m-"].modal.in,
#viewTeamsModal.modal.in,
#addTeamModal.modal.in,
[id^="editTeamModal"].modal.in,
#viewMembers.modal.in,
[id^="m-"].modal.in {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    z-index: 99999 !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    pointer-events: auto !important;
}

body > #viewTeamsModal .modal-dialog,
body > #addTeamModal .modal-dialog,
#viewTeamsModal .modal-dialog,
#addTeamModal .modal-dialog {
    position: relative !important;
    width: 85% !important;
    max-width: 1050px !important;
    margin: 40px auto !important;
    z-index: 100000 !important;
    left: auto !important;
    right: auto !important;
    transform: none !important;
}

body > [id^="editTeamModal"] .modal-dialog,
[id^="editTeamModal"] .modal-dialog {
    position: relative !important;
    width: 90% !important;
    max-width: 520px !important;
    margin: auto !important;
    z-index: 100000 !important;
    transform: none !important;
}

#viewTeamsModal .modal-content,
#addTeamModal .modal-content,
[id^="editTeamModal"] .modal-content {
    background-color: #FFFFFF !important;
    color: #0F172A !important;
    border-radius: 16px !important;
    border: 1px solid #CBD5E1 !important;
    box-shadow: 0 25px 60px -10px rgba(15, 23, 42, 0.4) !important;
    overflow: visible !important;
    display: flex !important;
    flex-direction: column !important;
}

#viewTeamsModal .modal-header,
#addTeamModal .modal-header,
[id^="editTeamModal"] .modal-header {
    background-color: #FFFFFF !important;
    border-bottom: 1px solid #E2E8F0 !important;
    padding: 20px 24px !important;
    flex-shrink: 0 !important;
}

#viewTeamsModal .modal-body,
#addTeamModal .modal-body {
    padding: 24px !important;
    overflow-y: auto !important;
    max-height: calc(100vh - 200px) !important;
    background-color: #FAFAFA !important;
}

[id^="editTeamModal"] .modal-body {
    padding: 24px !important;
    background-color: #FAFAFA !important;
    overflow: visible !important;
}

#viewTeamsModal .modal-footer,
#addTeamModal .modal-footer,
[id^="editTeamModal"] .modal-footer {
    background-color: #FFFFFF !important;
    border-top: 1px solid #E2E8F0 !important;
    padding: 16px 24px !important;
    flex-shrink: 0 !important;
}

/* Hide extra select2 containers generated for staff-multiselect-dropdown */
.staff-multiselect-dropdown + .select2,
.staff-multiselect-dropdown + .select2-container,
.staff-multiselect-dropdown ~ .select2-container,
select.staff-multiselect-dropdown + .select2-container,
select[name="team_members[]"] + .select2-container {
    display: none !important;
    visibility: hidden !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    overflow: hidden !important;
}

/* Always-Visible Staff Members Multi-Select List Box */
.lg-staff-listbox-container {
    width: 100%;
    position: relative;
}

.lg-staff-listbox-search-wrap {
    position: relative;
    width: 100%;
    margin-bottom: 8px;
}

.lg-staff-listbox-search {
    width: 100%;
    height: 38px;
    padding: 8px 12px;
    font-size: 13.5px;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    background: #FFFFFF;
    color: #0F172A;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lg-staff-listbox-search::placeholder {
    color: #94A3B8;
    font-size: 13px;
}

.lg-staff-listbox-search:focus {
    border-color: #3b9cf0;
    box-shadow: 0 0 0 3px rgba(59, 156, 240, 0.15);
}

.lg-staff-listbox {
    width: 100%;
    height: 240px;
    overflow-y: auto;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    box-sizing: border-box;
    user-select: none;
    outline: none;
}

/* Slim scrollbar */
.lg-staff-listbox::-webkit-scrollbar {
    width: 5px;
}

.lg-staff-listbox::-webkit-scrollbar-track {
    background: #F8FAFC;
    border-radius: 4px;
}

.lg-staff-listbox::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 4px;
}

.lg-staff-listbox::-webkit-scrollbar-thumb:hover {
    background: #94A3B8;
}

.lg-staff-listbox-item {
    padding: 10px 12px;
    font-size: 13.5px;
    color: #475569;
    cursor: pointer;
    line-height: 1.4;
    transition: background-color 0.1s ease, color 0.1s ease;
    border-bottom: 1px solid #F1F5F9;
}

.lg-staff-listbox-item:last-child {
    border-bottom: none;
}

.lg-staff-listbox-item:hover:not(.selected) {
    background-color: #F8FAFC;
    color: #1E293B;
}

.lg-staff-listbox-item.selected {
    background-color: #3b9cf0 !important;
    color: #FFFFFF !important;
    font-weight: 500;
}

.lg-staff-listbox-item.selected:hover {
    background-color: #2b8ce0 !important;
    color: #FFFFFF !important;
}

.lg-staff-listbox-no-results {
    padding: 16px;
    text-align: center;
    color: #94A3B8;
    font-size: 13px;
    font-style: italic;
}

/* Modal Backdrop - Full Viewport Overlay */
.modal-backdrop:not(.show):not(.in) {
    display: none !important;
    pointer-events: none !important;
}

body > .modal-backdrop.show,
body > .modal-backdrop.in,
.modal-backdrop.show,
.modal-backdrop.in {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 99990 !important;
    background-color: rgba(15, 23, 42, 0.6) !important;
    opacity: 1 !important;
    display: block !important;
    pointer-events: auto !important;
}

@media (max-width: 991px) {
    .lg-form-3col {
        grid-template-columns: 1fr !important;
    }
    .lg-form-2col {
        grid-template-columns: 1fr !important;
    }
    body > #viewTeamsModal .modal-dialog,
    body > #addTeamModal .modal-dialog,
    body > [id^="editTeamModal"] .modal-dialog,
    #viewTeamsModal .modal-dialog,
    #addTeamModal .modal-dialog,
    [id^="editTeamModal"] .modal-dialog {
        width: 94% !important;
        margin: 20px auto !important;
    }
}
</style>

<div class="lg-groups-wrapper">

    <!-- 1. PAGE HEADER -->
    <div class="lg-header-box">
        <div class="lg-header-left">
            <div class="lg-header-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>
                </svg>
            </div>
            <div>
                <h1 class="lg-title-main">Teams</h1>
                <p class="lg-title-sub">Manage user teams and team assignments</p>
            </div>
        </div>
        <div class="lg-breadcrumb-nav">
            <a href="{{ route('adminHome') }}">Home</a>
            <span>/</span>
            <a href="{{ route('users') }}">Users</a>
            <span>/</span>
            <span>Teams</span>
        </div>
    </div>

    <!-- 2. MAIN TEAMS CONTAINER CARD -->
    <div class="lg-main-card">

        <!-- 3. TOP ACTION & CONTROLS -->
        <div class="lg-top-actions-bar">
            @if(in_array(@Auth::user()->permissionsGroup->name, ['Webmaster', 'Admin', 'Manager']))
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="lg-btn-add" data-toggle="modal" data-target="#addTeamModal">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Add New Team</span>
                    </button>
                    <button type="button" class="lg-btn-add lg-btn-view-teams" data-toggle="modal" data-target="#viewTeamsModal">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span>View Teams</span>
                    </button>
                </div>
            @else
                <div></div>
            @endif

            <div class="lg-search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="lgGroupSearch" class="lg-search-input" placeholder="Search by team name…">
            </div>
        </div>

        <div class="lg-table-toolbar">
            <div class="lg-entries-box">
                <span>Show</span>
                <select id="lgGroupEntries" class="lg-entries-select">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>
        </div>

        <!-- 4. TEAMS TABLE -->
        @if($Teams->total() == 0)
            <div class="text-center py-12" style="padding: 48px 0; text-align: center;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #F1F5F9; color: #94A3B8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
                </div>
                <p style="font-size: 14px; font-weight: 600; color: #475569; margin: 0;">{{ __('backend.noData') }}</p>
                @if(in_array(@Auth::user()->permissionsGroup->name, ['Webmaster', 'Admin', 'Manager']))
                    <button type="button" class="lg-btn-add" data-toggle="modal" data-target="#addTeamModal" style="margin-top: 16px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Add New Team</span>
                    </button>
                @endif
            </div>
        @else
            <div class="lg-table-wrapper">
                <table class="lg-groups-table" id="lgGroupsTable">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>NAME</th>
                            <th>CREATED AT</th>
                            @if(in_array(@Auth::user()->permissionsGroup->name, ['Webmaster', 'Admin', 'Manager']))
                                <th class="text-center" style="width: 140px;">ACTION</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($Teams as $Team)
                            <tr class="lg-group-row" 
                                data-name="{{ strtolower($Team->name ?? '') }}" 
                                data-desc="{{ strtolower($Team->description ?? '') }}">
                                <td>
                                    <span class="lg-id-badge">{{ $Team->id }}</span>
                                </td>
                                <td>
                                    <div class="lg-group-name-box">
                                        <div class="lg-group-icon-badge">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
                                        </div>
                                        <span>{!! $Team->name !!}</span>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: #64748B; font-size: 12.5px; font-weight: 500;">
                                        {{ $Team->created_at ? \Carbon\Carbon::parse($Team->created_at)->format('d M, Y') : '—' }}
                                    </span>
                                </td>
                                @if(in_array(@Auth::user()->permissionsGroup->name, ['Webmaster', 'Admin', 'Manager']))
                                    <td class="text-center">
                                        <div class="lg-action-btn-group">
                                            <button type="button" class="lg-action-btn edit" data-toggle="modal" data-target="#editTeamModal_{{ $Team->id }}" title="Edit Team">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <button type="button" class="lg-action-btn members" data-toggle="modal" data-target="#viewMembers" onclick="getTeamMembers('{{ $Team->id }}')" title="View Team Members">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </button>
                                            @if(Helper::canDelete())
                                                <button type="button" class="lg-action-btn delete" data-toggle="modal" data-target="#m-{{ $Team->id }}" title="Delete Team">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>

                            <!-- DELETE CONFIRMATION MODAL -->
                            <div id="m-{{ $Team->id }}" class="modal fade" data-backdrop="true">
                                <div class="modal-dialog">
                                    <div class="modal-content" style="border-radius: 16px; border: 1px solid #CBD5E1; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(15,23,42,0.45); background: #FFFFFF;">
                                        <div class="modal-header" style="background: #FEF2F2 !important; border-bottom: 1px solid #FEE2E2 !important; padding: 18px 24px;">
                                            <h5 class="modal-title" style="color: #991B1B !important; font-weight: 700;">Delete Team</h5>
                                        </div>
                                        <div class="modal-body text-center" style="padding: 24px; background: #FFFFFF !important;">
                                            <p style="color: #475569; font-size: 14px; margin: 0;">
                                                Are you sure you want to delete this team?
                                                <br>
                                                <strong style="color: #0F172A; display: inline-block; margin-top: 8px;">[ {{ $Team->name }} ]</strong>
                                            </p>
                                        </div>
                                        <div class="modal-footer" style="border-top: 1px solid #F1F5F9 !important; padding: 14px 20px; background: #F8FAFC !important;">
                                            <button type="button" class="btn white p-x-md" data-dismiss="modal" style="border-radius: 8px; font-weight: 600; border: 1px solid #CBD5E1;">{{ __('backend.no') }}</button>
                                            <a href="{{ route('teamsDestroy', ['id' => $Team->id]) }}" class="btn danger p-x-md" style="border-radius: 8px; font-weight: 600; background: #EF4444; color: #FFFFFF; border: none;">{{ __('backend.yes') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ADD STAFF TO TEAM / EDIT TEAM MODAL FOR TEAM ID {{ $Team->id }} -->
                            <div id="editTeamModal_{{ $Team->id }}" class="modal fade lg-edit-team-modal" data-backdrop="static" data-keyboard="false">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        
                                        <!-- Modal Header -->
                                        <div class="modal-header">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 42px; height: 42px; border-radius: 12px; background: #EFF6FF; color: var(--lg-accent); display: flex; align-items: center; justify-content: center; border: 1px solid #BFDBFE; flex-shrink: 0;">
                                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                        <circle cx="9" cy="7" r="4"/>
                                                        <path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h5 class="modal-title" style="color: #0F172A !important; font-weight: 800; margin: 0; font-size: 18px; line-height: 1.2;">Add Staff To Team</h5>
                                                    <p style="margin: 2px 0 0 0; font-size: 13px; color: #64748B; font-weight: 500;">Assign staff members to a team</p>
                                                </div>
                                            </div>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 26px; font-weight: 600; color: #64748B; opacity: 0.8; border: none; background: transparent; cursor: pointer; padding: 4px 8px; line-height: 1;">&times;</button>
                                        </div>

                                        <!-- Modal Form Body -->
                                        {{ Form::open(['route' => ['teamsUpdate', $Team->id], 'method' => 'POST', 'id' => 'editTeamModalForm_'.$Team->id]) }}
                                        <input type="hidden" name="team_members_submitted" value="1">

                                        <div class="modal-body" style="padding: 24px;">
                                            
                                            <!-- SINGLE CLEAN CARD FOR FORM FIELDS -->
                                            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                                                
                                                <!-- 1. CHOOSE TEAM FIELD -->
                                                <div style="margin-bottom: 20px;">
                                                    <label style="font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 8px; display: block;">
                                                        Choose Team <span style="color: #EF4444;">*</span>
                                                    </label>
                                                    <div style="position: relative;">
                                                        <select name="team_id" class="team-select-dropdown" data-modal-id="{{ $Team->id }}" style="width: 100%; height: 42px; padding: 0 14px; font-size: 13.5px; border-radius: 10px; background-color: #F8FAFC; border: 1px solid #CBD5E1; color: #0F172A; font-weight: 600; outline: none; appearance: none; -webkit-appearance: none; background-image: url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2364748B\' stroke-width=\'2.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'6 9 12 15 18 9\'%3E%3C/polyline%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 14px center;">
                                                            @foreach($AllTeams as $tOption)
                                                                <option value="{{ $tOption->id }}" {{ $tOption->id == $Team->id ? 'selected' : '' }}>
                                                                    {{ $tOption->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- 2. STAFF MEMBERS LIST BOX (Always-visible multi-select with search) -->
                                                <div>
                                                    <label style="font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 8px; display: block;">
                                                        Staff Members <span style="color: #EF4444;">*</span>
                                                    </label>
                                                    {{-- Hidden native multi-select for form submission --}}
                                                    <select name="team_members[]" multiple class="staff-multiselect-dropdown" id="staffSelect_{{ $Team->id }}" data-modal-id="{{ $Team->id }}" style="display: none !important;">
                                                        @foreach($AllUsers as $staff)
                                                            @php
                                                                $userTeams = array_filter(explode(',', $staff->team_id ?? ''));
                                                                $isAssigned = in_array((string)$Team->id, array_map('strval', $userTeams));
                                                            @endphp
                                                            <option value="{{ $staff->id }}" {{ $isAssigned ? 'selected' : '' }} data-teams="{{ $staff->team_id ?? '' }}">
                                                                {{ $staff->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    {{-- Always-Visible Multi-Select List Box with Search --}}
                                                    <div class="lg-staff-listbox-container" data-staff-select="staffSelect_{{ $Team->id }}">
                                                        <div class="lg-staff-listbox-search-wrap">
                                                            <input type="text" class="lg-staff-listbox-search" placeholder="Search staff..." autocomplete="off">
                                                        </div>
                                                        <div class="lg-staff-listbox" role="listbox" aria-multiselectable="true">
                                                             @foreach($AllUsers as $staff)
                                                                 @php
                                                                     $userTeams2 = array_filter(explode(',', $staff->team_id ?? ''));
                                                                     $isAssigned2 = in_array((string)$Team->id, array_map('strval', $userTeams2));
                                                                     $uName = !empty($staff->username) ? $staff->username : ($staff->name ?? '');
                                                                     $fName = !empty($staff->full_name) ? $staff->full_name : (!empty($staff->username) && !empty($staff->name) && $staff->name !== $staff->username ? $staff->name : '');
                                                                     $displayText = !empty($fName) ? "{$uName} ({$fName})" : "{$uName} ()";
                                                                 @endphp
                                                                 <div class="lg-staff-listbox-item {{ $isAssigned2 ? 'selected' : '' }}" 
                                                                      data-value="{{ $staff->id }}" 
                                                                      data-teams="{{ $staff->team_id ?? '' }}"
                                                                      data-search="{{ strtolower($uName . ' ' . $fName . ' ' . $displayText) }}"
                                                                      role="option"
                                                                      aria-selected="{{ $isAssigned2 ? 'true' : 'false' }}">
                                                                     {{ $displayText }}
                                                                 </div>
                                                             @endforeach
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>

                                        <!-- Modal Footer Buttons -->
                                        <div class="modal-footer" style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                                            <button type="button" class="btn white p-x-md" data-dismiss="modal" style="height: 42px; border-radius: 10px; font-weight: 700; border: 1px solid #CBD5E1; background: #FFFFFF; color: #334155; padding: 0 22px; display: inline-flex; align-items: center; justify-content: center;">
                                                Cancel
                                            </button>
                                            <button type="submit" class="lg-btn-add" style="height: 42px; padding: 0 24px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                <span>Save Changes</span>
                                            </button>
                                        </div>
                                        {{ Form::close() }}

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- 5. TABLE FOOTER (EXPORT & PAGINATION) -->
            <div class="lg-table-footer">
                <div class="lg-export-btn-group">
                    <button type="button" class="lg-export-btn" onclick="lgExportGroups('copy')">Copy</button>
                    <button type="button" class="lg-export-btn" onclick="lgExportGroups('excel')">Excel</button>
                    <button type="button" class="lg-export-btn" onclick="lgExportGroups('csv')">CSV</button>
                    <button type="button" class="lg-export-btn" onclick="lgExportGroups('pdf')">PDF</button>
                </div>

                <div style="font-size: 13px; color: #64748B; font-weight: 500;">
                    {{ __('backend.showing') }} {{ $Teams->firstItem() }} - {{ $Teams->lastItem() }} {{ __('backend.of') }}
                    <strong style="color: #0F172A;">{{ $Teams->total() }}</strong> {{ __('backend.records') }}
                </div>

                <div class="lg-pagination-wrapper">
                    {!! $Teams->links() !!}
                </div>
            </div>
        @endif

    </div>
</div>

<!-- ADD NEW TEAM MODAL (HIGH VISIBILITY LEADGER MODERN DESIGN) -->
<div id="addTeamModal" class="modal fade" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            
            <!-- Modal Header -->
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: #EFF6FF; color: var(--lg-accent); display: flex; align-items: center; justify-content: center; border: 1px solid #BFDBFE; flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title" style="color: #0F172A !important; font-weight: 800; margin: 0; font-size: 18px; line-height: 1.2;">Add New Team</h5>
                        <p style="margin: 2px 0 0 0; font-size: 13px; color: #64748B; font-weight: 500;">Create a new team and assign team details</p>
                    </div>
                </div>
            </div>

            <!-- Modal Form Body -->
            {{ Form::open(['route' => ['teamsStore'], 'method' => 'POST', 'id' => 'addTeamModalForm']) }}
            <div class="modal-body">
                
                <!-- SECTION 1: BASIC DETAILS CARD -->
                <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                    <div style="font-size: 14px; font-weight: 700; color: #0F172A; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--lg-accent);"></span>
                        Basic Details
                    </div>

                    <div>
                        <!-- 1. Team Name * -->
                        <div>
                            <label style="font-size: 12.5px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;">
                                Team Name <span style="color: #EF4444;">*</span>
                            </label>
                            <input type="text" name="name" class="lg-search-input" placeholder="Enter team name" required style="padding-left: 14px; width: 100%; box-sizing: border-box;">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer Buttons -->
            <div class="modal-footer" style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn white p-x-md" data-dismiss="modal" style="height: 42px; border-radius: 10px; font-weight: 700; border: 1px solid #CBD5E1; background: #FFFFFF; color: #334155; padding: 0 22px; display: inline-flex; align-items: center; justify-content: center;">
                    Cancel
                </button>
                <button type="submit" class="lg-btn-add" style="height: 42px; padding: 0 24px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Create Team</span>
                </button>
            </div>
            {{ Form::close() }}

        </div>
    </div>
</div>

<!-- VIEW TEAMS MODAL (HIGH VISIBILITY CRISP WHITE OVERLAY MODAL) -->
<div id="viewTeamsModal" class="modal fade" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #EFF6FF; color: var(--lg-accent); display: flex; align-items: center; justify-content: center; border: 1px solid #BFDBFE;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
                    </div>
                    <div>
                        <h5 class="modal-title" style="color: #0F172A !important; font-weight: 800; margin: 0; font-size: 17px; line-height: 1.2;">Available Teams</h5>
                        <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748B; font-weight: 500;">Manage available teams and perform edits or deletions</p>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 26px; font-weight: 600; color: #64748B; opacity: 0.8; border: none; background: transparent; cursor: pointer; padding: 4px 8px; line-height: 1;">&times;</button>
            </div>
            <div class="modal-body">
                <div class="lg-table-wrapper" style="border: 1px solid #E2E8F0; border-radius: 12px; background: #FFFFFF;">
                    <table class="lg-groups-table" style="width: 100%; background: #FFFFFF;">
                        <thead>
                            <tr style="background: #F8FAFC;">
                                <th style="width: 65px; color: #475569; font-weight: 700;">ID</th>
                                <th style="color: #475569; font-weight: 700;">TEAM NAME</th>
                                <th class="text-center" style="width: 130px; color: #475569; font-weight: 700;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($Teams as $TeamItem)
                                <tr style="background: #FFFFFF;">
                                    <td><span class="lg-id-badge" style="background: #EFF6FF; color: var(--lg-accent); font-weight: 700;">{{ $TeamItem->id }}</span></td>
                                    <td style="font-weight: 700; color: #0F172A; font-size: 14px;">{!! $TeamItem->name !!}</td>
                                    <td class="text-center">
                                        <div class="lg-action-btn-group" style="justify-content: center; gap: 8px;">
                                            <button type="button" class="lg-action-btn edit" data-toggle="modal" data-target="#editTeamModal_{{ $TeamItem->id }}" onclick="$('#viewTeamsModal').modal('hide');" title="Edit Team" style="width: 34px; height: 34px;">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            @if(Helper::canDelete())
                                                <button type="button" class="lg-action-btn delete" data-toggle="modal" data-target="#m-{{ $TeamItem->id }}" onclick="$('#viewTeamsModal').modal('hide');" title="Delete Team" style="width: 34px; height: 34px;">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn white p-x-md" data-dismiss="modal" style="border-radius: 10px; font-weight: 700; border: 1px solid #CBD5E1; background: #FFFFFF; color: #334155; padding: 8px 20px;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- VIEW MEMBERS MODAL -->
<div id="viewMembers" class="modal fade" data-backdrop="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #CBD5E1; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(15,23,42,0.45); background: #FFFFFF !important;">
            <div class="modal-header" style="background: #FFFFFF !important; border-bottom: 1px solid #F1F5F9 !important; padding: 18px 24px;">
                <h5 class="modal-title" style="color: #0F172A !important; font-weight: 700;">View Team Members</h5>
            </div>
            <div class="modal-body text-center p-lg" style="padding: 24px; background: #FFFFFF !important;">
                <!-- Dynamically loaded -->
            </div>
            <div class="modal-footer" style="border-top: 1px solid #F1F5F9 !important; padding: 14px 20px; background: #F8FAFC !important;">
                <button type="button" class="btn white p-x-md" data-dismiss="modal" style="border-radius: 8px; font-weight: 600; border: 1px solid #CBD5E1;">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push("after-scripts")
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        // Relocate viewTeamsModal, addTeamModal, and editTeamModals to body to prevent container offset/stacking context bugs
        const vModal = document.getElementById('viewTeamsModal');
        if (vModal && vModal.parentNode !== document.body) {
            document.body.appendChild(vModal);
        }

        const aModal = document.getElementById('addTeamModal');
        if (aModal && aModal.parentNode !== document.body) {
            document.body.appendChild(aModal);
        }

        const mModal = document.getElementById('viewMembers');
        if (mModal && mModal.parentNode !== document.body) {
            document.body.appendChild(mModal);
        }

        document.querySelectorAll('[id^="editTeamModal_"]').forEach(function(editModal) {
            if (editModal.parentNode !== document.body) {
                document.body.appendChild(editModal);
            }
        });

        // Real-time Search for Teams Table
        const searchInput = document.getElementById('lgGroupSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('.lg-group-row');
                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const desc = row.getAttribute('data-desc') || '';
                    if (!query || name.includes(query) || desc.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Table Entries dropdown handler
        const entriesSelect = document.getElementById('lgGroupEntries');
        if (entriesSelect) {
            entriesSelect.addEventListener('change', function() {
                const limit = parseInt(this.value, 10);
                const rows = document.querySelectorAll('.lg-group-row');
                rows.forEach((row, idx) => {
                    if (idx < limit) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // ====== ALWAYS-VISIBLE STAFF MEMBERS LIST BOX LOGIC ======

        function lgSyncStaffListboxUI(container) {
            if (!container || !container.length) return;
            var selectId = container.attr('data-staff-select');
            var hiddenSelect = $('#' + selectId);
            if (!hiddenSelect.length) return;

            var selectedVals = (hiddenSelect.val() || []).map(String);
            var listbox = container.find('.lg-staff-listbox');

            listbox.find('.lg-staff-listbox-item').each(function() {
                var val = String($(this).attr('data-value'));
                var isSelected = selectedVals.indexOf(val) !== -1;
                if (isSelected) {
                    $(this).addClass('selected').attr('aria-selected', 'true');
                } else {
                    $(this).removeClass('selected').attr('aria-selected', 'false');
                }
            });
        }

        // Initialize all listboxes on page load & destroy unwanted select2 instances
        if (typeof $.fn.select2 !== 'undefined') {
            $('.staff-multiselect-dropdown').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    try { $(this).select2('destroy'); } catch(e){}
                }
            });
        }

        $('.lg-staff-listbox-container').each(function() {
            lgSyncStaffListboxUI($(this));
        });

        // Track last clicked item index for Shift+click range selection
        var lastClickedItem = null;

        // Click / Multi-selection on listbox item (normal click, Ctrl/Cmd click, Shift range click)
        $(document).on('click', '.lg-staff-listbox-item', function(e) {
            var container = $(this).closest('.lg-staff-listbox-container');
            var selectId = container.attr('data-staff-select');
            var hiddenSelect = $('#' + selectId);
            var items = container.find('.lg-staff-listbox-item:visible');
            var currentIndex = items.index(this);

            if (e.shiftKey && lastClickedItem && lastClickedItem.closest('.lg-staff-listbox-container').is(container)) {
                // Range selection with Shift
                var prevIndex = items.index(lastClickedItem);
                var start = Math.min(prevIndex, currentIndex);
                var end = Math.max(prevIndex, currentIndex);

                for (var i = start; i <= end; i++) {
                    $(items[i]).addClass('selected').attr('aria-selected', 'true');
                }
            } else {
                // Normal click or Ctrl/Cmd click toggles item
                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected').attr('aria-selected', 'false');
                } else {
                    $(this).addClass('selected').attr('aria-selected', 'true');
                }
            }

            lastClickedItem = $(this);

            // Sync with hidden select
            var newSelectedVals = [];
            container.find('.lg-staff-listbox-item.selected').each(function() {
                newSelectedVals.push($(this).attr('data-value'));
            });

            // Update options in hidden select
            hiddenSelect.find('option').each(function() {
                var optVal = $(this).val();
                $(this).prop('selected', newSelectedVals.indexOf(optVal) !== -1);
            });
            hiddenSelect.val(newSelectedVals).trigger('change');
        });

        // Search input filters list items dynamically as user types
        $(document).on('input', '.lg-staff-listbox-search', function() {
            var query = $(this).val().toLowerCase().trim();
            var container = $(this).closest('.lg-staff-listbox-container');
            var listbox = container.find('.lg-staff-listbox');
            var anyVisible = false;

            listbox.find('.lg-staff-listbox-item').each(function() {
                var searchData = $(this).attr('data-search') || $(this).text().toLowerCase();
                if (!query || searchData.indexOf(query) !== -1) {
                    $(this).show();
                    anyVisible = true;
                } else {
                    $(this).hide();
                }
            });

            listbox.find('.lg-staff-listbox-no-results').remove();
            if (!anyVisible) {
                listbox.append('<div class="lg-staff-listbox-no-results">No staff found</div>');
            }
        });

        // Re-sync UI when modal opens & cleanup select2
        $(document).on('shown.bs.modal', function () {
            if (typeof $.fn.select2 !== 'undefined') {
                $('.staff-multiselect-dropdown').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        try { $(this).select2('destroy'); } catch(e){}
                    }
                });
            }
            $('.lg-staff-listbox-container').each(function() {
                lgSyncStaffListboxUI($(this));
            });
        });

        // Explicit modal click delegates to ensure buttons open/close modals reliably
        $(document).on('click', '[data-toggle="modal"]', function(e) {
            var target = $(this).attr('data-target') || $(this).attr('href');
            if (target && $(target).length) {
                e.preventDefault();
                $(target).modal('show');
            }
        });

        $(document).on('click', '[data-dismiss="modal"]', function(e) {
            var modal = $(this).closest('.modal');
            if (modal.length) {
                modal.modal('hide');
            }
        });

        // Real-time Staff Members update when Choose Team dropdown changes
        $(document).on('change', '.team-select-dropdown', function() {
            var selectedTeamId = $(this).val();
            var modalId = $(this).data('modal-id');
            var staffSelect = $('#staffSelect_' + modalId);
            
            if (staffSelect.length) {
                var newSelectedValues = [];
                staffSelect.find('option').each(function() {
                    var teamsStr = $(this).attr('data-teams') || '';
                    var userTeams = teamsStr.split(',').map(function(t) { return t.trim(); });
                    var isAssigned = userTeams.indexOf(selectedTeamId.toString()) !== -1;
                    $(this).prop('selected', isAssigned);
                    if (isAssigned) {
                        newSelectedValues.push($(this).val());
                    }
                });
                staffSelect.val(newSelectedValues).trigger('change');

                // Sync listbox UI
                var container = staffSelect.closest('.modal').find('.lg-staff-listbox-container[data-staff-select="staffSelect_' + modalId + '"]');
                if (container.length) {
                    lgSyncStaffListboxUI(container);
                }
            }
        });
    });

    function getTeamMembers(id) {
        var url = "{{ route('getTeamMembers', [':id']) }}";
        url = url.replace(':id', id);
        $('#viewMembers .modal-body').html('<div style="padding: 20px; color: #64748B;">Loading team members...</div>');
        $.ajax({
            type: "GET",
            url: url,
            dataType: "html",
            success: function (html) {
                $('#viewMembers .modal-body').html(html);
            }
        });
    }

    function lgExportGroups(format) {
        const table = document.getElementById('lgGroupsTable');
        if (!table) return;

        let data = [];
        const rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            let rowData = [];
            const cols = row.querySelectorAll('th, td');
            cols.forEach((col, idx) => {
                if (idx === cols.length - 1) return; // Skip actions column
                let text = col.innerText.replace(/\s+/g, ' ').trim();
                rowData.push('"' + text.replace(/"/g, '""') + '"');
            });
            if (rowData.length > 0) {
                data.push(rowData.join(','));
            }
        });

        const csvContent = data.join('\n');

        if (format === 'copy') {
            const rawText = data.map(r => r.replace(/"/g, '')).join('\n');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(rawText)
                    .then(() => alert('Teams data copied to clipboard!'))
                    .catch(() => alert('Teams data copied to clipboard!'));
            } else {
                var temp = $('<textarea>');
                $('body').append(temp);
                temp.val(rawText).select();
                document.execCommand('copy');
                temp.remove();
                alert('Teams data copied to clipboard!');
            }
        } else if (format === 'pdf') {
            window.print();
        } else {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `teams_export_${new Date().toISOString().slice(0,10)}.${format === 'excel' ? 'csv' : 'csv'}`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
</script>
@endpush
