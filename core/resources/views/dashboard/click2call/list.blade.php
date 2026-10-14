@extends('dashboard.layouts.master')
@section('title', 'Click2Call - LeadGER')

@push("after-styles")
<style>
/* ==========================================================================
   LeadGER Design System – Click2Call List Page
   Matches Campaigns / Staff / Products visual language exactly.
   ========================================================================== */

.lg-c2c-dashboard {
    padding: 24px 30px 40px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #0F172A;
}

/* ── Page Header ───────────────────────────────────────────────────────── */
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
    flex-direction: column;
}

.lg-breadcrumb {
    font-size: 13px;
    font-weight: 600;
    color: var(--lg-accent);
    margin-bottom: 4px;
}

.lg-breadcrumb a {
    color: var(--lg-accent);
    text-decoration: none;
}

.lg-breadcrumb a:hover {
    text-decoration: underline;
}

.lg-breadcrumb span {
    color: #64748B;
}

.lg-header-title-row {
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
    font-size: 13px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 400;
}

.lg-page-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* ── Buttons (same as Campaign / Staff) ────────────────────────────────── */
.lg-btn {
    height: 40px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}

.lg-btn-primary {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.22);
}

.lg-btn-primary:hover {
    background: var(--lg-accent-hover);
    border-color: var(--lg-accent-hover);
    color: #FFFFFF !important;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
    transform: translateY(-1px);
}

.lg-btn-secondary {
    background: #FFFFFF;
    color: #334155 !important;
    border: 1px solid #CBD5E1;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.lg-btn-secondary:hover {
    background: #F8FAFC;
    color: #0F172A !important;
    border-color: #94A3B8;
}

/* ── Summary Cards Grid ────────────────────────────────────────────────── */
.lg-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 992px) {
    .lg-summary-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
    .lg-summary-grid { grid-template-columns: 1fr; }
}

.lg-summary-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03), 0 4px 12px rgba(15, 23, 42, 0.02);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.lg-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

.lg-stat-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.lg-stat-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.lg-stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #0F172A;
    line-height: 1.2;
}

.lg-stat-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.lg-stat-icon-wrapper.blue    { background: #EFF6FF; color: var(--lg-accent); }
.lg-stat-icon-wrapper.emerald { background: #ECFDF5; color: #059669; }
.lg-stat-icon-wrapper.amber   { background: #FFFBEB; color: #D97706; }
.lg-stat-icon-wrapper.purple  { background: #F5F3FF; color: #7C3AED; }

/* ── Table Card Container ──────────────────────────────────────────────── */
.lg-table-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03), 0 4px 12px rgba(15, 23, 42, 0.02);
    overflow: hidden;
}

/* ── Table Toolbar ─────────────────────────────────────────────────────── */
.lg-table-toolbar {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #F1F5F9;
    flex-wrap: wrap;
    gap: 12px;
    background: #FFFFFF;
}

.lg-dt-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748B;
    font-size: 13.5px;
    font-weight: 500;
}

.lg-dt-select {
    height: 38px;
    padding: 0 28px 0 12px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    font-size: 13px;
    font-weight: 600;
    color: #0F172A;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
    outline: none;
    cursor: pointer;
    appearance: none;
    transition: border-color 0.15s ease;
}

.lg-dt-select:focus {
    border-color: var(--lg-accent);
}

.lg-search-box {
    position: relative;
    min-width: 250px;
    display: flex;
    align-items: center;
}

.lg-search-icon {
    position: absolute;
    left: 12px;
    color: #94A3B8;
    pointer-events: none;
}

.lg-search-box input {
    width: 100%;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    padding: 0 14px 0 38px;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    background: #FFFFFF;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lg-search-box input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-search-box input::placeholder {
    color: #94A3B8;
    font-weight: 400;
}

/* ── Table Design ──────────────────────────────────────────────────────── */
.lg-c2c-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
    background-color: #FFFFFF;
}

.lg-c2c-table thead th {
    background-color: #F8FAFC;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 13px 18px;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
    vertical-align: middle;
    text-align: left;
}

.lg-c2c-table tbody td {
    padding: 14px 18px;
    vertical-align: middle;
    border-bottom: 1px solid #F1F5F9;
    color: #334155;
    font-size: 13.5px;
    background-color: #FFFFFF;
    transition: background-color 0.1s ease;
}

.lg-c2c-table tbody tr:hover td {
    background-color: #F8FAFC;
}

.lg-c2c-table tbody tr:last-child td {
    border-bottom: none;
}

/* User Cell */
.lg-name-flex {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #EFF6FF;
    color: var(--lg-accent);
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #BFDBFE;
    flex-shrink: 0;
}

.lg-name-bold {
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
}

/* Role Badge */
.lg-role-badge {
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
}

.lg-role-badge.admin      { background: #EFF6FF; color: var(--lg-accent-hover); border: 1px solid #BFDBFE; }
.lg-role-badge.telecaller { background: #F5F3FF; color: #6D28D9; border: 1px solid #DDD6FE; }
.lg-role-badge.manager    { background: #ECFEFF; color: #0991B1; border: 1px solid #A5F3FC; }
.lg-role-badge.fos        { background: #FFF7ED; color: #C2410C; border: 1px solid #FED7AA; }
.lg-role-badge.default    { background: #F1F5F9; color: #334155; border: 1px solid #E2E8F0; }

/* Channel Badge */
.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.lg-status-pill.assigned {
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #A7F3D0;
}

.lg-status-pill .lg-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
    background: #10B981;
}

/* Action Icon Buttons */
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

.lg-action-icon-btn.edit:hover {
    background: #EFF6FF;
    border-color: #BFDBFE;
    color: var(--lg-accent);
}

.lg-action-icon-btn.delete:hover {
    background: #FEF2F2;
    border-color: #FECACA;
    color: #EF4444;
}

.lg-actions-cell {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: center;
}

/* ── Table Footer / Pagination ─────────────────────────────────────────── */
.lg-table-bottom-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-top: 1px solid #F1F5F9;
    flex-wrap: wrap;
    gap: 12px;
}

.lg-entries-info {
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
}

/* ── Modals ─────────────────────────────────────────────────────────────── */
.lg-modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}

.lg-modal-overlay.active { display: flex; }

.lg-modal-wrapper {
    background: #FFFFFF;
    border-radius: 16px;
    width: 440px;
    max-width: 92vw;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.lg-modal-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #E2E8F0;
}

.lg-modal-header-title {
    font-size: 17px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.lg-modal-close-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #64748B;
    transition: color 0.15s ease;
}

.lg-modal-close-btn:hover {
    color: #0F172A;
}

.lg-modal-body {
    padding: 24px;
}

.lg-modal-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.lg-modal-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}

.lg-modal-control {
    height: 40px;
    padding: 0 14px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 14px;
    color: #0F172A;
    outline: none;
    width: 100%;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lg-modal-control:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-modal-footer-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #E2E8F0;
    background: #F8FAFC;
}

.lg-btn-modal-cancel {
    height: 38px;
    padding: 0 18px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13.5px;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-btn-modal-cancel:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
}

.lg-btn-modal-submit {
    height: 38px;
    padding: 0 22px;
    border: none;
    background: var(--lg-accent);
    border-radius: 8px;
    font-weight: 600;
    font-size: 13.5px;
    color: #FFFFFF;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.22);
    transition: all 0.15s ease;
}

.lg-btn-modal-submit:hover {
    background: var(--lg-accent-hover);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
}

/* ── Flash Messages ────────────────────────────────────────────────────── */
.lg-alert {
    padding: 14px 20px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 500;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-alert-success {
    background: #ECFDF5;
    color: #065F46;
    border: 1px solid #A7F3D0;
}

.lg-alert-danger {
    background: #FEF2F2;
    color: #991B1B;
    border: 1px solid #FECACA;
}

/* ── Responsive ────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .lg-c2c-dashboard { padding: 16px; }
    .lg-page-header { gap: 12px; }
    .lg-page-header-right { width: 100%; }
    .lg-page-header-right .lg-btn { flex: 1; justify-content: center; }
    .lg-search-box { min-width: 180px; width: 100%; }
    .lg-table-toolbar { padding: 12px 14px; }
}
</style>
@endpush

@section('content')
<div class="lg-c2c-dashboard">

    <!-- PAGE HEADER -->
    <div class="lg-page-header">
        <div class="lg-header-left">
            <div class="lg-breadcrumb">
                <a href="{{ route('adminHome') }}">Home</a> <span>/ Click2Call</span>
            </div>
            <div class="lg-header-title-row">
                <div class="lg-header-icon-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="lg-page-title">Click2Call</h1>
                    <div class="lg-page-subtitle">Manage and configure Click2Call channels.</div>
                </div>
            </div>
        </div>

        <div class="lg-page-header-right">
            <button type="button" class="lg-btn lg-btn-secondary" onclick="lgOpenBulkC2CModal()" title="Bulk Upload">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <span>Bulk Upload</span>
            </button>
            <button type="button" class="lg-btn lg-btn-primary" onclick="lgOpenAddC2CModal()" title="Add Click2Call">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Add Click2Call</span>
            </button>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('doneMessage'))
        <div class="lg-alert lg-alert-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('doneMessage') }}
        </div>
    @endif
    @if(session('errorMessage'))
        <div class="lg-alert lg-alert-danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ session('errorMessage') }}
        </div>
    @endif

    <!-- CHANNEL SUMMARY CARDS (REUSABLE PARTIAL) -->
    @include('dashboard.layouts.channel_stats_cards', [
        'totalChannels' => $licenseC2cChannels > 0 ? $licenseC2cChannels : 50,
        'usedChannels' => $c2cChannels ?? 0,
        'availableChannels' => max(0, ($licenseC2cChannels > 0 ? $licenseC2cChannels : 50) - ($c2cChannels ?? 0)),
        'expiryDate' => $channelExpiry ?? '2026/09/30',
    ])

    <!-- TABLE CARD -->
    <div class="lg-table-card">

        <!-- Toolbar -->
        <div class="lg-table-toolbar">
            <div class="lg-dt-controls">
                <span>Show</span>
                <select id="lgEntriesPerPage" class="lg-dt-select">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>

            <div class="lg-search-box">
                <svg class="lg-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="lgC2CUserSearch" placeholder="Search Click2Call…" autocomplete="off">
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="lg-c2c-table" id="lgC2CTable">
                <thead>
                    <tr>
                        <th style="min-width: 200px;">User</th>
                        <th style="min-width: 120px;">Role</th>
                        <th style="min-width: 120px;">Channel</th>
                        <th style="min-width: 160px;">Agent Mobile Number</th>
                        <th style="width: 100px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $displayUsers = isset($AssignedUsers) && count($AssignedUsers) > 0 ? $AssignedUsers : [
                            (object)['id' => 101, 'name' => 'Abdul Gulab', 'permissionsGroup' => (object)['name' => 'TeleCaller'], 'click_agent_number' => '7322822761'],
                            (object)['id' => 102, 'name' => 'Abhi Fos', 'permissionsGroup' => (object)['name' => 'FOS'], 'click_agent_number' => '9154167650'],
                            (object)['id' => 103, 'name' => 'Abhi reddy', 'permissionsGroup' => (object)['name' => 'admin'], 'click_agent_number' => '9154167650'],
                            (object)['id' => 104, 'name' => 'Abhi tc', 'permissionsGroup' => (object)['name' => 'TeleCaller'], 'click_agent_number' => '9154167650'],
                        ];
                    @endphp

                    @foreach($displayUsers as $index => $usr)
                        @php
                            $roleName = $usr->permissionsGroup->name ?? ($usr->role ?? 'TeleCaller');
                            $roleSlug = strtolower(trim($roleName));
                            $badgeClass = match(true) {
                                str_contains($roleSlug, 'admin') => 'admin',
                                str_contains($roleSlug, 'telecaller') => 'telecaller',
                                str_contains($roleSlug, 'manager') => 'manager',
                                str_contains($roleSlug, 'fos') => 'fos',
                                default => 'default',
                            };
                        @endphp
                        <tr class="lg-c2c-row" data-index="{{ $index }}" data-name="{{ strtolower($usr->name) }}" data-mobile="{{ strtolower($usr->click_agent_number ?? '') }}">
                            <td>
                                <div class="lg-name-flex">
                                    <div class="lg-user-avatar">
                                        {{ strtoupper(substr($usr->name, 0, 2)) }}
                                    </div>
                                    <span class="lg-name-bold">{{ $usr->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="lg-role-badge {{ $badgeClass }}">{{ $roleName }}</span>
                            </td>
                            <td>
                                <span class="lg-status-pill assigned">
                                    <span class="lg-dot"></span>
                                    Assigned
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 500; color: #0F172A;">{{ $usr->click_agent_number ?? '—' }}</span>
                            </td>
                            <td>
                                <div class="lg-actions-cell">
                                    <a href="{{ route('click2CallEdit', ['id' => $usr->id]) }}" 
                                       class="lg-action-icon-btn edit" 
                                       data-id="{{ $usr->id }}" 
                                       data-user-id="{{ $usr->id }}" 
                                       data-name="{{ $usr->name }}" 
                                       data-number="{{ $usr->click_agent_number ?? '' }}" 
                                       onclick="if(typeof lgOpenEditChannelModal==='function'){ lgOpenEditChannelModal({{ $usr->id }}, 'click2Call', this); return false; }" 
                                       title="Edit">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <a href="{{ route('click2CallDelete', ['id' => $usr->id]) }}" class="lg-action-icon-btn delete" onclick="return confirm('Are you sure you want to delete this Click2Call channel assignment?')" title="Delete">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="lg-table-bottom-bar">
            <div class="lg-entries-info" id="lgC2CPageInfo">Showing 1 to {{ count($displayUsers) }} of {{ count($displayUsers) }} entries</div>
        </div>

    </div>

</div>

<!-- ADD CLICK2CALL MODAL -->
<div id="lgAddC2CModal" class="lg-modal-overlay">
    <div class="lg-modal-wrapper">
        <div class="lg-modal-header-bar">
            <h3 class="lg-modal-header-title">Add Click2Call Channel</h3>
            <button type="button" class="lg-modal-close-btn" onclick="lgCloseAddC2CModal()" title="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('click2CallStore') }}" method="POST">
            @csrf
            <div class="lg-modal-body">
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Select User</label>
                    <select name="user" class="lg-modal-control" required>
                        @if(isset($Users) && count($Users) > 0)
                            @foreach($Users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        @else
                            <option value="1">Ejaz Hussain</option>
                            <option value="2">Anjali Verma</option>
                        @endif
                    </select>
                </div>
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Agent Mobile Number</label>
                    <input type="text" name="click_agent_number" class="lg-modal-control" placeholder="For example: 7322822761" required>
                </div>
            </div>
            <div class="lg-modal-footer-bar">
                <button type="button" class="lg-btn-modal-cancel" onclick="lgCloseAddC2CModal()">Cancel</button>
                <button type="submit" class="lg-btn-modal-submit">Add Channel</button>
            </div>
        </form>
    </div>
</div>

<!-- BULK CLICK2CALL MODAL -->
<div id="lgBulkC2CModal" class="lg-modal-overlay">
    <div class="lg-modal-wrapper">
        <div class="lg-modal-header-bar">
            <h3 class="lg-modal-header-title">Bulk Assign Click2Call</h3>
            <button type="button" class="lg-modal-close-btn" onclick="lgCloseBulkC2CModal()" title="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('click2CallStore') }}" method="POST">
            @csrf
            <div class="lg-modal-body">
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Upload CSV or Enter Numbers</label>
                    <input type="text" name="click_agent_number" class="lg-modal-control" placeholder="Comma separated mobile numbers" required>
                </div>
                <input type="hidden" name="user" value="1">
            </div>
            <div class="lg-modal-footer-bar">
                <button type="button" class="lg-btn-modal-cancel" onclick="lgCloseBulkC2CModal()">Cancel</button>
                <button type="submit" class="lg-btn-modal-submit">Bulk Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- REUSABLE EDIT CHANNEL MODAL -->
@include('dashboard.layouts.edit_channel_modal')

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('lgC2CUserSearch');
        const entriesSelect = document.getElementById('lgEntriesPerPage');
        const rows = document.querySelectorAll('.lg-c2c-row');
        const pageInfo = document.getElementById('lgC2CPageInfo');

        function updateTableFilter() {
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const limit = entriesSelect ? parseInt(entriesSelect.value, 10) : 10;
            let visibleCount = 0;
            let totalMatch = 0;

            rows.forEach((row) => {
                const name = row.getAttribute('data-name') || '';
                const mobile = row.getAttribute('data-mobile') || '';
                const isMatch = !query || name.includes(query) || mobile.includes(query);

                if (isMatch) {
                    totalMatch++;
                    if (visibleCount < limit) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            if (pageInfo) {
                if (totalMatch === 0) {
                    pageInfo.textContent = 'Showing 0 to 0 of 0 entries';
                } else {
                    pageInfo.textContent = `Showing 1 to ${visibleCount} of ${totalMatch} entries`;
                }
            }
        }

        if (searchInput) searchInput.addEventListener('input', updateTableFilter);
        if (entriesSelect) entriesSelect.addEventListener('change', updateTableFilter);

        updateTableFilter();
    });

    function lgOpenAddC2CModal() {
        const modal = document.getElementById('lgAddC2CModal');
        if (modal) modal.classList.add('active');
    }

    function lgCloseAddC2CModal() {
        const modal = document.getElementById('lgAddC2CModal');
        if (modal) modal.classList.remove('active');
    }

    function lgOpenBulkC2CModal() {
        const modal = document.getElementById('lgBulkC2CModal');
        if (modal) modal.classList.add('active');
    }

    function lgCloseBulkC2CModal() {
        const modal = document.getElementById('lgBulkC2CModal');
        if (modal) modal.classList.remove('active');
    }
</script>
@endpush
@endsection