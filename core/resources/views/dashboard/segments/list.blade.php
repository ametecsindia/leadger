@extends('dashboard.layouts.master')
@section('title', 'Segments')

@push("after-styles")
<style>
/* ==========================================================================
   LeadGER Corporate Segments Page & Modal Styling
   Scoped strictly under .segments-page container and #lgSegmentModal / #lgDeleteSegmentModal
   ========================================================================== */
.segments-page {
    font-family: var(--lg-font-sans);
    background-color: #F8FAFC;
    padding: 24px 32px;
    min-height: auto;
    color: #0F172A;
    box-sizing: border-box;
}

/* Page Header & Breadcrumbs */
.segments-page .lg-segments-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.segments-page .lg-segments-breadcrumbs {
    font-size: 13px;
    font-weight: 500;
    color: #94A3B8;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.segments-page .lg-segments-breadcrumbs a {
    color: #94A3B8;
    text-decoration: none;
    transition: color 0.15s ease;
}

.segments-page .lg-segments-breadcrumbs a:hover {
    color: var(--lg-accent);
}

.segments-page .lg-segments-breadcrumbs .sep {
    color: #CBD5E1;
}

.segments-page .lg-segments-breadcrumbs .active {
    color: #64748B;
    font-weight: 600;
}

.segments-page .lg-segments-title {
    font-size: 28px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 4px 0;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.segments-page .lg-segments-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
    font-weight: 400;
}

/* Primary Action Button (+ Add a new Segment) */
.segments-page .lg-segments-btn-add {
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
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    white-space: nowrap;
    cursor: pointer;
}

.segments-page .lg-segments-btn-add:hover {
    background: var(--lg-accent-hover);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
}

/* Main Card Container */
.segments-page .lg-segments-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}

/* Table Toolbar Controls */
.segments-page .lg-segments-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: #FFFFFF;
    gap: 16px;
    flex-wrap: wrap;
}

.segments-page .lg-segments-entries {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    color: #475569;
    font-weight: 500;
}

.segments-page .lg-segments-select-entries {
    height: 38px;
    padding: 0 28px 0 14px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
    outline: none;
    cursor: pointer;
    appearance: none;
    transition: border-color 0.15s ease;
}

.segments-page .lg-segments-select-entries:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.segments-page .lg-segments-search-wrapper {
    position: relative;
    width: 280px;
}

.segments-page .lg-segments-search-wrapper svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    pointer-events: none;
}

.segments-page .lg-segments-search-input {
    width: 100%;
    height: 40px;
    padding: 0 14px 0 38px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.segments-page .lg-segments-search-input::placeholder {
    color: #94A3B8;
}

.segments-page .lg-segments-search-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

/* Data Table Styling */
.segments-page .lg-segments-table-responsive {
    overflow-x: auto;
    width: 100%;
}

.segments-page .lg-segments-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.segments-page .lg-segments-table th {
    background: #F8FAFC;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 14px 24px;
    border-top: 1px solid #F1F5F9;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}

.segments-page .lg-segments-table td {
    padding: 16px 24px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    color: #0F172A;
    font-size: 14px;
}

.segments-page .lg-segments-table tbody tr {
    transition: background-color 0.15s ease;
}

.segments-page .lg-segments-table tbody tr:hover {
    background-color: #F8FAFC;
}

/* Segment Cell Badge */
.segments-page .lg-segment-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.segments-page .lg-segment-icon-badge {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #EFF6FF;
    color: var(--lg-accent);
}

.segments-page .lg-segment-name-text {
    font-weight: 700;
    color: #0F172A;
    font-size: 14px;
}

.segments-page .lg-segment-desc-text {
    color: #64748B;
    font-size: 13.5px;
    margin: 0;
    line-height: 1.4;
    font-weight: 400;
}

.segments-page .lg-segment-index {
    font-weight: 600;
    color: #64748B;
    font-size: 13.5px;
}

/* Action Buttons */
.segments-page .lg-segments-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
    position: relative;
}

.segments-page .lg-segments-btn-edit {
    display: inline-flex;
    align-items: center;
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
}

.segments-page .lg-segments-btn-edit:hover {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
}

.segments-page .lg-segments-btn-more {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    color: #64748B;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.segments-page .lg-segments-btn-more:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #CBD5E1;
}

/* Action Dropdown Menu */
.lg-action-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 4px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
    min-width: 140px;
    z-index: 100;
    display: none;
    padding: 6px 0;
}

.lg-action-dropdown-menu.show {
    display: block;
}

.lg-action-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s ease;
}

.lg-action-dropdown-item:hover {
    background: #F8FAFC;
    color: #0F172A;
}

.lg-action-dropdown-item.danger {
    color: #EF4444;
}

.lg-action-dropdown-item.danger:hover {
    background: #FEF2F2;
    color: #DC2626;
}

/* Footer Pagination */
.segments-page .lg-segments-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: #FFFFFF;
    gap: 16px;
    flex-wrap: wrap;
    border-top: 1px solid #F1F5F9;
}

.segments-page .lg-segments-info {
    font-size: 13.5px;
    color: #64748B;
    font-weight: 500;
}

.segments-page .lg-segments-info strong {
    color: #0F172A;
    font-weight: 600;
}

/* Pagination Links Styling */
.segments-page .pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.segments-page .pagination li a,
.segments-page .pagination li span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 34px;
    min-width: 34px;
    padding: 0 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.segments-page .pagination li.active span,
.segments-page .pagination li.active a {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}

.segments-page .pagination li a:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #CBD5E1;
}

.segments-page .pagination li.disabled span,
.segments-page .pagination li.disabled a {
    opacity: 0.5;
    cursor: not-allowed;
    background: #F8FAFC;
}

/* Empty State */
.segments-page .lg-segments-empty {
    padding: 60px 20px;
    text-align: center;
    color: #64748B;
}

.segments-page .lg-segments-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: #F1F5F9;
    color: #94A3B8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.segments-page .lg-segments-empty-title {
    font-size: 16px;
    font-weight: 600;
    color: #0F172A;
    margin-bottom: 6px;
}

.segments-page .lg-segments-empty-desc {
    font-size: 13.5px;
    color: #64748B;
    margin-bottom: 20px;
}

/* ==========================================================================
   CENTERED POPUP / MODAL SYSTEM (LeadGER Corporate Modal)
   ========================================================================== */
.lg-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease-in-out;
}

.lg-modal-backdrop.active {
    opacity: 1;
    visibility: visible;
}

.lg-modal-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 20px 50px -10px rgba(15, 23, 42, 0.25);
    width: 100%;
    max-width: 480px;
    transform: scale(0.95) translateY(10px);
    transition: all 0.25s ease-in-out;
    overflow: hidden;
    box-sizing: border-box;
}

.lg-modal-backdrop.active .lg-modal-card {
    transform: scale(1) translateY(0);
}

.lg-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #F1F5F9;
    background: #FFFFFF;
}

.lg-modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.lg-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #F8FAFC;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-modal-close:hover {
    background: #F1F5F9;
    color: #0F172A;
}

.lg-modal-body {
    padding: 24px;
}

.lg-modal-field {
    margin-bottom: 20px;
}

.lg-modal-field:last-child {
    margin-bottom: 0;
}

.lg-modal-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.lg-modal-label .req {
    color: #EF4444;
}

.lg-modal-input,
.lg-modal-textarea {
    width: 100%;
    padding: 10px 14px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 14px;
    color: #0F172A;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.lg-modal-textarea {
    min-height: 90px;
    resize: vertical;
}

.lg-modal-input:focus,
.lg-modal-textarea:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-modal-input.is-invalid,
.lg-modal-textarea.is-invalid {
    border-color: #EF4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.lg-modal-error-text {
    font-size: 12px;
    color: #EF4444;
    margin-top: 6px;
    display: none;
}

.lg-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: #F8FAFC;
    border-top: 1px solid #F1F5F9;
}

.lg-modal-btn-cancel {
    height: 40px;
    padding: 0 18px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-modal-btn-cancel:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
}

.lg-modal-btn-submit {
    height: 40px;
    padding: 0 20px;
    background: var(--lg-accent);
    border: none;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    color: #FFFFFF;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}

.lg-modal-btn-submit:hover {
    background: var(--lg-accent-hover);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
}

.lg-modal-btn-danger {
    height: 40px;
    padding: 0 20px;
    background: #DC2626;
    border: none;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    color: #FFFFFF;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
}

.lg-modal-btn-danger:hover {
    background: #B91C1C;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35);
}
</style>
@endpush

@php
if (!function_exists('lgGetSegmentBadgeDetails')) {
    function lgGetSegmentBadgeDetails($name) {
        $n = strtolower($name);
        if (str_contains($n, 'bank') || str_contains($n, 'financ') || str_contains($n, 'loan') || str_contains($n, 'debt')) {
            return [
                'bg' => '#EFF6FF',
                'color' => '#1464F4',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>'
            ];
        } elseif (str_contains($n, 'health') || str_contains($n, 'medic') || str_contains($n, 'hospit')) {
            return [
                'bg' => '#ECFDF5',
                'color' => '#10B981',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>'
            ];
        } elseif (str_contains($n, 'educat') || str_contains($n, 'school') || str_contains($n, 'student') || str_contains($n, 'primary')) {
            return [
                'bg' => '#F5F3FF',
                'color' => '#8B5CF6',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>'
            ];
        } elseif (str_contains($n, 'estate') || str_contains($n, 'property') || str_contains($n, 'land')) {
            return [
                'bg' => '#FFF7ED',
                'color' => '#EA580C',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'
            ];
        } elseif (str_contains($n, 'tech') || str_contains($n, 'software') || str_contains($n, 'sim') || str_contains($n, 'telecom')) {
            return [
                'bg' => '#E0F2FE',
                'color' => '#0284C7',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'
            ];
        } else {
            return [
                'bg' => '#EFF6FF',
                'color' => '#1464F4',
                'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>'
            ];
        }
    }
}
@endphp

@section('content')
<div class="segments-page">

    <!-- Flash Notifications -->
    @if(session('doneMessage'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 500; margin-bottom: 20px;">
            {{ session('doneMessage') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- 1. PAGE HEADER -->
    <div class="lg-segments-header">
        <div>
            <h1 class="lg-segments-title">Segments</h1>
            <p class="lg-segments-subtitle">Manage and organize your customer segments and industries.</p>
        </div>

        @if(@Auth::user() ? (in_array(@Auth::user()->permissionsGroup->name ?? '', ['Webmaster', 'Admin', 'Manager']) || @Auth::user()->permissions_id == 1) : true)
            <div>
                <button type="button" class="lg-segments-btn-add" onclick="lgOpenAddSegmentModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Add a new Segment</span>
                </button>
            </div>
        @endif
    </div>

    <!-- 2. MAIN CARD CONTAINER -->
    <div class="lg-segments-card">

        <!-- TOOLBAR: SHOW ENTRIES & SEARCH -->
        <div class="lg-segments-toolbar">
            <div class="lg-segments-entries">
                <span>Show</span>
                <select id="lgEntriesSelect" class="lg-segments-select-entries" onchange="lgChangeEntries(this.value)">
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
                <span>entries</span>
            </div>

            <div class="lg-segments-search-wrapper">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="lgSegmentsSearch" class="lg-segments-search-input" placeholder="Search segments..." onkeyup="lgFilterSegments()">
            </div>
        </div>

        <!-- TABLE SECTION -->
        @if($Segments->total() > 0)
            <div class="lg-segments-table-responsive">
                <table class="lg-segments-table" id="lgSegmentsTable">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 35%;">SEGMENT NAME</th>
                            <th style="width: 45%;">DESCRIPTION</th>
                            @if(@Auth::user() ? (in_array(@Auth::user()->permissionsGroup->name ?? '', ['Webmaster', 'Admin', 'Manager']) || @Auth::user()->permissions_id == 1) : true)
                                <th class="text-right" style="width: 15%; text-align: right; padding-right: 24px;">OPTIONS</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($Segments as $Segment)
                            @php
                                $badgeData = lgGetSegmentBadgeDetails($Segment->name);
                            @endphp
                            <tr class="lg-segment-row" data-id="{{ $Segment->id }}" data-name="{{ $Segment->name }}" data-description="{{ $Segment->description }}">
                                <td>
                                    <span class="lg-segment-index">{{ $loop->iteration }}</span>
                                </td>
                                <td>
                                    <div class="lg-segment-cell">
                                        <div class="lg-segment-icon-badge" style="background-color: {{ $badgeData['bg'] }}; color: {{ $badgeData['color'] }};">
                                            {!! $badgeData['svg'] !!}
                                        </div>
                                        <span class="lg-segment-name-text">{{ $Segment->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="lg-segment-desc-text">{{ $Segment->description ? $Segment->description : '—' }}</span>
                                </td>
                                @if(@Auth::user() ? (in_array(@Auth::user()->permissionsGroup->name ?? '', ['Webmaster', 'Admin', 'Manager']) || @Auth::user()->permissions_id == 1) : true)
                                    <td class="text-right" style="text-align: right; padding-right: 24px;">
                                        <div class="lg-segments-actions">
                                            <button type="button" class="lg-segments-btn-edit" onclick="lgOpenEditSegmentModal({{ $Segment->id }}, '{{ addslashes($Segment->name) }}', '{{ addslashes($Segment->description ?? '') }}')" title="Edit Segment">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" class="lg-segments-btn-more" onclick="lgToggleMoreMenu(event, {{ $Segment->id }})" title="More Options">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                                    <circle cx="12" cy="5" r="2"/>
                                                    <circle cx="12" cy="12" r="2"/>
                                                    <circle cx="12" cy="19" r="2"/>
                                                </svg>
                                            </button>
                                            <div class="lg-action-dropdown-menu" id="lgActionMenu-{{ $Segment->id }}">
                                                <div class="lg-action-dropdown-item" onclick="lgOpenEditSegmentModal({{ $Segment->id }}, '{{ addslashes($Segment->name) }}', '{{ addslashes($Segment->description ?? '') }}')">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    <span>Edit</span>
                                                </div>
                                                @if(Helper::canDelete())
                                                    <div class="lg-action-dropdown-item danger" onclick="lgConfirmDeleteSegment({{ $Segment->id }}, '{{ addslashes($Segment->name) }}')">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                        <span>Delete</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- FOOTER & PAGINATION -->
            <div class="lg-segments-footer">
                <div class="lg-segments-info">
                    Showing <strong>{{ $Segments->firstItem() }}</strong> to <strong>{{ $Segments->lastItem() }}</strong> of <strong>{{ $Segments->total() }}</strong> entries
                </div>
                <div class="lg-segments-pagination-wrapper">
                    {!! $Segments->links() !!}
                </div>
            </div>

        @else
            <!-- EMPTY STATE -->
            <div class="lg-segments-empty">
                <div class="lg-segments-empty-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                        <polyline points="2 17 12 22 22 17"/>
                        <polyline points="2 12 12 17 22 12"/>
                    </svg>
                </div>
                <div class="lg-segments-empty-title">No segments found</div>
                <div class="lg-segments-empty-desc">Create your first customer segment or industry category to start organizing your business data.</div>
                @if(@Auth::user() ? (in_array(@Auth::user()->permissionsGroup->name ?? '', ['Webmaster', 'Admin', 'Manager']) || @Auth::user()->permissions_id == 1) : true)
                    <button type="button" class="lg-segments-btn-add" onclick="lgOpenAddSegmentModal()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>Add a new Segment</span>
                    </button>
                @endif
            </div>
        @endif

    </div>

</div>

<!-- ==========================================================================
     ADD / EDIT SEGMENT MODAL
     ========================================================================== -->
<div class="lg-modal-backdrop" id="lgSegmentModal">
    <div class="lg-modal-card">
        <div class="lg-modal-header">
            <h3 class="lg-modal-title" id="lgSegmentModalTitle">Add Segment</h3>
            <button type="button" class="lg-modal-close" onclick="lgCloseSegmentModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="lgSegmentForm" onsubmit="lgHandleSegmentSubmit(event)">
            @csrf
            <input type="hidden" id="lgSegmentId" name="segment_id" value="">
            <div class="lg-modal-body">
                
                <div class="lg-modal-field">
                    <label class="lg-modal-label" for="lgSegmentName">Segment Name <span class="req">*</span></label>
                    <input type="text" id="lgSegmentName" name="name" class="lg-modal-input" placeholder="Enter segment name" autocomplete="off" required>
                    <div class="lg-modal-error-text" id="lgSegmentNameError"></div>
                </div>

                <div class="lg-modal-field">
                    <label class="lg-modal-label" for="lgSegmentDescription">Description</label>
                    <textarea id="lgSegmentDescription" name="description" class="lg-modal-textarea" placeholder="Enter description"></textarea>
                    <div class="lg-modal-error-text" id="lgSegmentDescError"></div>
                </div>

            </div>
            <div class="lg-modal-footer">
                <button type="button" class="lg-modal-btn-cancel" onclick="lgCloseSegmentModal()">Cancel</button>
                <button type="submit" class="lg-modal-btn-submit" id="lgSegmentSubmitBtn">Add Segment</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     DELETE CONFIRMATION MODAL
     ========================================================================== -->
<div class="lg-modal-backdrop" id="lgDeleteSegmentModal">
    <div class="lg-modal-card" style="max-width: 420px;">
        <div class="lg-modal-header">
            <h3 class="lg-modal-title">Delete Segment</h3>
            <button type="button" class="lg-modal-close" onclick="lgCloseDeleteModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="lg-modal-body" style="text-align: center; padding: 28px 24px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #FEF2F2; color: #EF4444; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <p style="font-size: 15px; font-weight: 600; color: #0F172A; margin: 0 0 6px 0;">Are you sure you want to delete this segment?</p>
            <p style="font-size: 13.5px; color: #64748B; margin: 0;" id="lgDeleteSegmentNameText"></p>
        </div>
        <div class="lg-modal-footer">
            <button type="button" class="lg-modal-btn-cancel" onclick="lgCloseDeleteModal()">Cancel</button>
            <button type="button" class="lg-modal-btn-danger" id="lgConfirmDeleteBtn" onclick="lgExecuteDeleteSegment()">Delete</button>
        </div>
    </div>
</div>

@endsection

@push("after-scripts")
<script type="text/javascript">
let currentDeleteSegmentId = null;

function lgFilterSegments() {
    const input = document.getElementById('lgSegmentsSearch');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('lgSegmentsTable');
    if (!table) return;
    
    const trs = table.getElementsByClassName('lg-segment-row');

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
    const table = document.getElementById('lgSegmentsTable');
    if (!table) return;
    const trs = table.getElementsByClassName('lg-segment-row');
    const limit = parseInt(val);

    for (let i = 0; i < trs.length; i++) {
        if (i < limit) {
            trs[i].style.display = '';
        } else {
            trs[i].style.display = 'none';
        }
    }
}

/* Modal Functions */
function lgOpenAddSegmentModal() {
    document.getElementById('lgSegmentId').value = '';
    document.getElementById('lgSegmentName').value = '';
    document.getElementById('lgSegmentDescription').value = '';
    document.getElementById('lgSegmentModalTitle').innerText = 'Add Segment';
    document.getElementById('lgSegmentSubmitBtn').innerText = 'Add Segment';
    
    lgClearErrors();
    document.getElementById('lgSegmentModal').classList.add('active');
    setTimeout(() => document.getElementById('lgSegmentName').focus(), 100);
}

function lgOpenEditSegmentModal(id, name, description) {
    document.getElementById('lgSegmentId').value = id;
    document.getElementById('lgSegmentName').value = name;
    document.getElementById('lgSegmentDescription').value = description;
    document.getElementById('lgSegmentModalTitle').innerText = 'Edit Segment';
    document.getElementById('lgSegmentSubmitBtn').innerText = 'Save Changes';
    
    lgClearErrors();
    document.getElementById('lgSegmentModal').classList.add('active');
    setTimeout(() => document.getElementById('lgSegmentName').focus(), 100);
}

function lgCloseSegmentModal() {
    document.getElementById('lgSegmentModal').classList.remove('active');
    lgClearErrors();
}

function lgClearErrors() {
    const nameInput = document.getElementById('lgSegmentName');
    const errText = document.getElementById('lgSegmentNameError');
    if (nameInput) nameInput.classList.remove('is-invalid');
    if (errText) {
        errText.innerText = '';
        errText.style.display = 'none';
    }
}

function lgToggleMoreMenu(event, id) {
    event.stopPropagation();
    const dropdowns = document.querySelectorAll('.lg-action-dropdown-menu');
    dropdowns.forEach(d => {
        if (d.id !== 'lgActionMenu-' + id) {
            d.classList.remove('show');
        }
    });

    const menu = document.getElementById('lgActionMenu-' + id);
    if (menu) {
        menu.classList.toggle('show');
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.lg-segments-actions')) {
        document.querySelectorAll('.lg-action-dropdown-menu').forEach(d => d.classList.remove('show'));
    }
});

/* Handle Add / Edit Submit */
function lgHandleSegmentSubmit(e) {
    e.preventDefault();
    lgClearErrors();

    const id = document.getElementById('lgSegmentId').value;
    const name = document.getElementById('lgSegmentName').value.trim();
    const description = document.getElementById('lgSegmentDescription').value.trim();
    const token = document.querySelector('input[name="_token"]').value;

    if (!name) {
        const nameInput = document.getElementById('lgSegmentName');
        const errText = document.getElementById('lgSegmentNameError');
        nameInput.classList.add('is-invalid');
        errText.innerText = 'Segment Name is required.';
        errText.style.display = 'block';
        return;
    }

    const submitBtn = document.getElementById('lgSegmentSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerText = 'Saving...';

    const url = id ? '{{ url("/admin/segments") }}/' + id + '/update' : '{{ route("segmentsStore") }}';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            name: name,
            description: description
        })
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        submitBtn.disabled = false;
        submitBtn.innerText = id ? 'Save Changes' : 'Add Segment';

        if (res.body.success) {
            lgCloseSegmentModal();
            window.location.reload();
        } else {
            if (res.body.errors && res.body.errors.name) {
                const nameInput = document.getElementById('lgSegmentName');
                const errText = document.getElementById('lgSegmentNameError');
                nameInput.classList.add('is-invalid');
                errText.innerText = res.body.errors.name[0];
                errText.style.display = 'block';
            } else if (res.body.message) {
                alert(res.body.message);
            }
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerText = id ? 'Save Changes' : 'Add Segment';
        alert('An unexpected error occurred. Please try again.');
    });
}

/* Delete Functions */
function lgConfirmDeleteSegment(id, name) {
    currentDeleteSegmentId = id;
    document.getElementById('lgDeleteSegmentNameText').innerText = name;
    document.querySelectorAll('.lg-action-dropdown-menu').forEach(d => d.classList.remove('show'));
    document.getElementById('lgDeleteSegmentModal').classList.add('active');
}

function lgCloseDeleteModal() {
    currentDeleteSegmentId = null;
    document.getElementById('lgDeleteSegmentModal').classList.remove('active');
}

function lgExecuteDeleteSegment() {
    if (!currentDeleteSegmentId) return;

    const token = document.querySelector('input[name="_token"]').value;
    const btn = document.getElementById('lgConfirmDeleteBtn');
    btn.disabled = true;
    btn.innerText = 'Deleting...';

    fetch('{{ url("/admin/segments") }}/' + currentDeleteSegmentId, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = 'Delete';
        if (data.success) {
            lgCloseDeleteModal();
            window.location.reload();
        } else {
            alert(data.message || 'Failed to delete segment.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = 'Delete';
        alert('An unexpected error occurred while deleting.');
    });
}
</script>
@endpush
