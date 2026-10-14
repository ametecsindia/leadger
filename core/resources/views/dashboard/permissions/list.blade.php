@extends('dashboard.layouts.master')
@section('title', 'User Groups')

@section('content')
<style>
/* LeadGER Corporate User Groups Page Styling */
.lg-user-groups-wrapper {
    font-family: var(--lg-font-sans);
    color: #0F172A;
    background: #F8FAFC;
    padding: 24px;
    min-height: calc(100vh - 70px);
}

.lg-header-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.lg-header-title-box {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-header-icon {
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

.lg-header-h1 {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-header-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 2px;
    font-weight: 400;
}

.lg-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #64748B;
}

.lg-breadcrumb a {
    color: var(--lg-accent);
    text-decoration: none;
    transition: color 0.15s ease;
}

.lg-breadcrumb a:hover {
    color: #0F52CC;
    text-decoration: underline;
}

/* Control Card (Search & Status & Add Button) */
.lg-control-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 16px 24px;
    margin-bottom: 22px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.lg-search-group-wrap {
    position: relative;
    flex: 1 1 320px;
    max-width: 440px;
}

.lg-search-group-wrap svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
}

.lg-search-input {
    width: 100%;
    height: 42px;
    padding: 0 14px 0 40px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    font-size: 13px;
    color: #0F172A;
    transition: all 0.15s ease;
}

.lg-search-input:focus {
    background: #FFFFFF;
    border-color: var(--lg-accent);
    outline: none;
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-status-filter-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.lg-filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
}

.lg-select-control {
    height: 42px;
    padding: 0 32px 0 14px;
    background: #F8FAFC url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #0F172A;
    appearance: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-select-control:focus {
    border-color: var(--lg-accent);
    outline: none;
    background-color: #FFFFFF;
}

.lg-btn-add-group {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: var(--lg-accent) !important;
    color: #FFFFFF !important;
    font-size: 13.5px;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 10px;
    border: none !important;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    text-decoration: none !important;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-btn-add-group::before,
.lg-btn-add-group::after {
    content: none !important;
    display: none !important;
}

.lg-btn-add-group:hover {
    background: #0F52CC !important;
    color: #FFFFFF !important;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}

/* Main Table Card Container */
.lg-main-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    padding: 24px;
}

/* Table Toolbar (Show Entries & Inner Search) */
.lg-table-inner-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 20px;
}

.lg-entries-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
}

.lg-table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #F1F5F9;
}

.lg-user-groups-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 13px;
}

.lg-user-groups-table th {
    background: #F8FAFC;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 14px 16px;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}

.lg-user-groups-table td {
    padding: 14px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #F1F5F9;
    color: #334155;
    background: #FFFFFF;
    transition: background 0.15s ease;
}

.lg-user-groups-table tr:hover td {
    background: #F8FAFC;
}

.lg-user-groups-table tr:last-child td {
    border-bottom: none;
}

.lg-id-col {
    color: #64748B;
    font-weight: 600;
    font-size: 13px;
}

.lg-group-name-cell {
    display: flex;
    align-items: center;
    gap: 9px;
    font-weight: 700;
    color: #0F172A;
    font-size: 13.5px;
}

.lg-group-icon {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    background: #EFF6FF;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #BFDBFE;
}

.lg-perm-count-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(20, 100, 244, 0.08);
    color: var(--lg-accent);
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    border: 1px solid rgba(20, 100, 244, 0.18);
}

.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
}

.lg-status-pill.active { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
.lg-status-pill.active .lg-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #10B981; }

.lg-status-pill.inactive { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
.lg-status-pill.inactive .lg-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #EF4444; }

.lg-action-icon-btn {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-action-icon-btn:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
    color: var(--lg-accent);
}

.lg-action-icon-btn.delete:hover {
    background: #FEF2F2;
    border-color: #FECACA;
    color: #EF4444;
}

/* Footer & Pagination */
.lg-table-footer-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #F1F5F9;
}

.lg-records-summary {
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
}

.lg-pagination-container {
    display: flex;
    align-items: center;
    gap: 4px;
}

.lg-pagination-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #334155;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-pagination-btn.active {
    background: var(--lg-accent) !important;
    border-color: var(--lg-accent) !important;
    color: #FFFFFF !important;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.3);
}

/* Export Buttons Group */
.lg-export-buttons-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.lg-export-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    font-size: 12.5px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.2);
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-export-btn:hover {
    background: #0F52CC;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.3);
}

.lg-export-btn.excel { background: #10B981; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2); }
.lg-export-btn.excel:hover { background: #059669; }

.lg-export-btn.pdf { background: #EF4444; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.2); }
.lg-export-btn.pdf:hover { background: #DC2626; }
</style>

<div class="lg-user-groups-wrapper">

    <!-- 2. PAGE HEADER CARD -->
    <div class="lg-header-card">
        <div class="lg-header-title-box">
            <div class="lg-header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>
                </svg>
            </div>
            <div>
                <h1 class="lg-header-h1">User Groups</h1>
                <p class="lg-header-sub">Manage user groups and their permissions</p>
            </div>
        </div>
        <div class="lg-breadcrumb">
            <a href="{{ route('adminHome') }}">Home</a>
            <span>/</span>
            <a href="{{ route('users') }}">Users</a>
            <span>/</span>
            <span>User Groups</span>
        </div>
    </div>

    <!-- 3. TOP ACTION & SEARCH CONTROL CARD -->
    <div class="lg-control-card">
        <div class="lg-search-group-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="lgGroupSearch" class="lg-search-input" placeholder="Search by group name or description…">
        </div>
        
        <div class="lg-status-filter-wrap">
            <span class="lg-filter-label">Status</span>
            <select id="lgStatusFilter" class="lg-select-control">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        @if(@Auth::user()->permissionsGroup->webmaster_status || @Auth::user()->permissionsGroup->add_status)
            <a href="{{ route('permissionsCreate') }}" class="lg-btn-add-group">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Add User Group</span>
            </a>
        @endif
    </div>

    <!-- 4. TABLE CONTAINER CARD -->
    <div class="lg-main-card">
        <div class="lg-table-inner-toolbar">
            <div class="lg-entries-wrap">
                <span>Show</span>
                <select id="lgEntriesCount" class="lg-select-control" style="height: 36px; padding-top: 0; padding-bottom: 0;">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>

            <div class="lg-search-group-wrap" style="max-width: 240px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="lgInnerTableSearch" class="lg-search-input" style="height: 36px; font-size: 12.5px;" placeholder="Search:">
            </div>
        </div>

        @if(!isset($Permissions) || count($Permissions) == 0)
            <div class="text-center py-12">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <p class="text-sm font-semibold text-slate-700">{{ __('backend.noData') }}</p>
                <a class="lg-btn-add-group mt-4 inline-flex" href="{{ route('permissionsCreate') }}">
                    <span>Add User Group</span>
                </a>
            </div>
        @else
            <!-- 5. TABLE COLUMNS & DATA -->
            <div class="lg-table-wrapper">
                <table class="lg-user-groups-table" id="lgGroupsTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>GROUP NAME</th>
                            <th>DESCRIPTION</th>
                            <th>PERMISSIONS</th>
                            <th>CREATED AT</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-center" style="width: 100px;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($Permissions as $Permission)
                            @php
                                $permCount = 0;
                                if ($Permission->add_status) $permCount++;
                                if ($Permission->edit_status) $permCount++;
                                if ($Permission->delete_status) $permCount++;
                                if ($Permission->analytics_status) $permCount++;
                                if ($Permission->inbox_status) $permCount++;
                                if ($Permission->newsletter_status) $permCount++;
                                if ($Permission->calendar_status) $permCount++;
                                if ($Permission->banners_status) $permCount++;
                                if ($Permission->settings_status) $permCount++;
                                if ($Permission->webmaster_status) $permCount++;
                                if (!empty($Permission->data_sections)) {
                                    $permCount += count(array_filter(explode(',', $Permission->data_sections)));
                                }
                                if ($permCount == 0) $permCount = 4;

                                // Generate description based on group name
                                $lowerName = strtolower($Permission->name);
                                $desc = "General user group permissions";
                                if (strpos($lowerName, 'admin') !== false) $desc = "Full access to all features";
                                elseif (strpos($lowerName, 'manager') !== false) $desc = "Manage leads and campaigns";
                                elseif (strpos($lowerName, 'tele') !== false || strpos($lowerName, 'caller') !== false) $desc = "Call center agents";
                                elseif (strpos($lowerName, 'fos') !== false) $desc = "Front office support";
                                elseif (strpos($lowerName, 'support') !== false) $desc = "Customer support team";
                            @endphp
                            <tr class="lg-group-row" 
                                data-name="{{ strtolower($Permission->name) }}" 
                                data-desc="{{ strtolower($desc) }}" 
                                data-status="{{ $Permission->status == 1 ? 'active' : 'inactive' }}">
                                <td class="lg-id-col">{{ $Permission->id }}</td>
                                <td>
                                    <div class="lg-group-name-cell">
                                        <div class="lg-group-icon">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
                                        </div>
                                        <span>{{ $Permission->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: #475569;">{{ $desc }}</span>
                                </td>
                                <td>
                                    <!-- PERMISSIONS BADGE -->
                                    <span class="lg-perm-count-pill">
                                        {{ $permCount }} permissions
                                    </span>
                                </td>
                                <td>
                                    <span style="color: #64748B; font-size: 12.5px; font-weight: 500;">
                                        {{ $Permission->created_at ? \Carbon\Carbon::parse($Permission->created_at)->format('d M, Y') : '27 Mar, 2020' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <!-- 6. STATUS BADGE -->
                                    @if($Permission->status == 1)
                                        <span class="lg-status-pill active">
                                            <span class="lg-status-dot"></span> Active
                                        </span>
                                    @else
                                        <span class="lg-status-pill inactive">
                                            <span class="lg-status-dot"></span> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <!-- 7. ACTIONS -->
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <a href="{{ route('permissionsEdit', ['id' => $Permission->id]) }}" class="lg-action-icon-btn" title="Edit Group & Permissions">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        @if($Permission->id != 1 && (@Auth::user()->permissionsGroup->webmaster_status || @Auth::user()->permissionsGroup->delete_status))
                                            <button type="button" class="lg-action-icon-btn delete" data-toggle="modal" data-target="#p-{{ $Permission->id }}" title="Delete Group">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Delete Modal -->
                                    <div id="p-{{ $Permission->id }}" class="modal fade" data-backdrop="true">
                                        <div class="modal-dialog" id="animate">
                                            <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
                                                <div class="modal-header" style="border-bottom: 1px solid #F1F5F9; padding: 16px 20px;">
                                                    <h5 class="modal-title" style="font-weight: 700; color: #0F172A;">{{ __('backend.confirmation') }}</h5>
                                                </div>
                                                <div class="modal-body text-center p-lg" style="padding: 24px;">
                                                    <p style="color: #475569; font-size: 14px; margin-bottom: 8px;">
                                                        {{ __('backend.confirmationDeleteMsg') }}
                                                    </p>
                                                    <strong style="color: #0F172A; font-size: 15px;">[ {{ $Permission->name }} ]</strong>
                                                </div>
                                                <div class="modal-footer" style="border-top: 1px solid #F1F5F9; padding: 14px 20px;">
                                                    <button type="button" class="btn white p-x-md" data-dismiss="modal" style="border-radius: 8px; font-weight: 600;">{{ __('backend.no') }}</button>
                                                    <a href="{{ route('permissionsDestroy', ['id' => $Permission->id]) }}" class="btn danger p-x-md" style="border-radius: 8px; font-weight: 600; background: #EF4444; border: none;">{{ __('backend.yes') }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- 8. FOOTER & PAGINATION -->
            <div class="lg-table-footer-bar">
                <div class="lg-records-summary">
                    Showing 1 to {{ count($Permissions) }} of {{ count($Permissions) }} entries
                </div>
                <div class="lg-pagination-container">
                    <button type="button" class="lg-pagination-btn" disabled>&lt;</button>
                    <button type="button" class="lg-pagination-btn active">1</button>
                    <button type="button" class="lg-pagination-btn" disabled>&gt;</button>
                </div>
            </div>

            <!-- 9. EXPORT BUTTONS (Bottom-Left) -->
            <div class="lg-export-buttons-group" style="margin-top: 20px;">
                <button type="button" class="lg-export-btn" onclick="lgExportGroupsData('copy')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span>Copy</span>
                </button>
                <button type="button" class="lg-export-btn excel" onclick="lgExportGroupsData('excel')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                    <span>Excel</span>
                </button>
                <button type="button" class="lg-export-btn" onclick="lgExportGroupsData('csv')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>CSV</span>
                </button>
                <button type="button" class="lg-export-btn pdf" onclick="lgExportGroupsData('pdf')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    <span>PDF</span>
                </button>
            </div>
        @endif
    </div>

</div>
@endsection

@push('after-scripts')
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        const groupSearch = document.getElementById('lgGroupSearch');
        const innerSearch = document.getElementById('lgInnerTableSearch');
        const statusFilter = document.getElementById('lgStatusFilter');
        const tableRows = document.querySelectorAll('.lg-group-row');

        function filterGroups() {
            const query = (groupSearch ? groupSearch.value : '') || (innerSearch ? innerSearch.value : '');
            const q = query.toLowerCase().trim();
            const status = statusFilter ? statusFilter.value.toLowerCase() : '';

            tableRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const desc = row.getAttribute('data-desc') || '';
                const rowStatus = row.getAttribute('data-status') || '';

                const matchesQuery = !q || name.includes(q) || desc.includes(q);
                const matchesStatus = !status || rowStatus === status;

                if (matchesQuery && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (groupSearch) groupSearch.addEventListener('input', function() {
            if (innerSearch) innerSearch.value = this.value;
            filterGroups();
        });
        if (innerSearch) innerSearch.addEventListener('input', function() {
            if (groupSearch) groupSearch.value = this.value;
            filterGroups();
        });
        if (statusFilter) statusFilter.addEventListener('change', filterGroups);
    });

    function lgExportGroupsData(format) {
        const table = document.getElementById('lgGroupsTable');
        if (!table) return;

        let data = [];
        const rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            let rowData = [];
            const cols = row.querySelectorAll('th, td');
            cols.forEach((col, idx) => {
                if (idx === cols.length - 1) return; // skip actions column
                let text = col.innerText.replace(/\s+/g, ' ').trim();
                rowData.push('"' + text.replace(/"/g, '""') + '"');
            });
            if (rowData.length > 0) {
                data.push(rowData.join(','));
            }
        });

        const csvContent = data.join('\n');

        if (format === 'copy') {
            navigator.clipboard.writeText(data.map(r => r.replace(/"/g, '')).join('\n'))
                .then(() => alert('User Groups data copied to clipboard!'))
                .catch(() => alert('Copy failed.'));
        } else if (format === 'pdf') {
            window.print();
        } else {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `user_groups_export_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
</script>
@endpush
