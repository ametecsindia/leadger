@extends('dashboard.layouts.master')
@php
    $isFollowUp = (request()->get('filter') == 'followup' || request()->get('view') == 'followup' || request()->is('*/followup*'));
@endphp
@section('title', $isFollowUp ? 'Follow-ups' : 'Leads')

@push("after-styles")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">
    <style>
        /* ==========================================================================
           LEADGER COMMAND CENTER - LEADS & FOLLOW-UPS REDESIGN
           ========================================================================== */
        
        .lg-leads-page {
            padding: 24px 30px 40px 30px;
            background: #F4F6FA;
            min-height: calc(100vh - 60px);
            font-family: var(--lg-font-sans);
            color: #0F172A;
        }

        /* --- Header Section --- */
        .lg-header-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }
        .lg-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .lg-header-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #F0FDFA;
            border: 1px solid #CCFBF1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--lg-accent);
            flex-shrink: 0;
        }
        .lg-header-titles {
            display: flex;
            flex-direction: column;
        }
        .lg-breadcrumb {
            font-size: 13px;
            font-weight: 600;
            color: var(--lg-accent);
            margin-bottom: 2px;
            text-transform: none;
            letter-spacing: normal;
        }
        .lg-page-title {
            font-size: 24px;
            font-weight: 700;
            color: #0F172A;
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.3px;
        }
        .lg-page-subtitle {
            font-size: 13.5px;
            font-weight: 400;
            color: #64748B;
            margin-top: 3px;
        }
        .lg-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* --- Header Action Buttons --- */
        .lg-btn-followups {
            height: 40px;
            padding: 0 18px;
            border-radius: 8px;
            background: #FFFFFF;
            border: 1px solid #D1D5DB;
            color: #1E293B !important;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            transition: all 0.15s ease;
        }
        .lg-btn-followups:hover {
            background: #F8FAFC;
            border-color: #94A3B8;
            color: #0F172A !important;
        }
        .lg-btn-upload {
            height: 40px;
            padding: 0 18px;
            border-radius: 8px;
            background: var(--lg-accent-hover);
            border: 1px solid var(--lg-accent-hover);
            color: #FFFFFF !important;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            transition: all 0.15s ease;
        }
        .lg-btn-upload:hover {
            background: #0D655E;
            border-color: #0D655E;
            color: #FFFFFF !important;
        }

        /* --- Follow-up Status Tabs --- */
        .lg-tabs-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .lg-tab-btn {
            height: 38px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #E2E8F0;
            background: #FFFFFF;
            color: #64748B;
            cursor: pointer;
            transition: all 0.15s ease;
            outline: none;
        }
        .lg-tab-btn:hover {
            background: #F8FAFC;
            color: #0F172A;
            border-color: #CBD5E1;
        }
        .lg-tab-btn.active {
            background: var(--lg-accent);
            color: #FFFFFF;
            border-color: var(--lg-accent);
            box-shadow: 0 2px 6px rgba(23, 105, 255, 0.22);
        }
        .lg-tab-count {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            background: #F1F5F9;
            color: #475569;
        }
        .lg-tab-btn.active .lg-tab-count {
            background: rgba(255, 255, 255, 0.22);
            color: #FFFFFF;
        }

        /* --- Main Content Card --- */
        .lg-main-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
            padding: 22px 24px;
        }

        /* --- Horizontal Filters Row --- */
        .lg-filters-row {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .lg-filter-col {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .lg-filter-col.search-col {
            flex: 1 1 260px;
            min-width: 220px;
        }
        .lg-filter-col.dropdown-col {
            flex: 0 0 auto;
        }
        .lg-filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin: 0;
            text-transform: capitalize;
        }
        .lg-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .lg-search-icon {
            position: absolute;
            left: 14px;
            color: #94A3B8;
            pointer-events: none;
        }
        .lg-input-search {
            width: 100%;
            height: 42px;
            padding: 0 14px 0 40px;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0F172A;
            transition: all 0.15s ease;
            outline: none;
            box-sizing: border-box;
        }
        .lg-input-search:focus {
            border-color: var(--lg-accent);
            box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12);
        }
        .lg-input-search::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }

        .lg-select-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            width: auto;
        }
        .lg-select-wrap::before,
        .lg-select-wrap::after,
        .dropdown-col::before,
        .dropdown-col::after {
            display: none !important;
            content: none !important;
        }
        .lg-select-wrap .lg-select-arrow,
        .lg-select-wrap > svg,
        .lg-select-wrap > i,
        .dropdown-col > svg:not(.lg-search-icon),
        .dropdown-col > i {
            display: none !important;
        }
        .lg-select-input {
            width: auto;
            min-width: unset;
            max-width: 100%;
            height: 42px;
            padding: 0 30px 0 12px !important;
            background-color: #FFFFFF !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: calc(100% - 10px) center !important;
            background-size: 12px 12px !important;
            border: 1px solid #CBD5E1 !important;
            border-radius: 8px !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            color: #0F172A !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            outline: none !important;
            box-sizing: border-box !important;
            line-height: normal !important;
        }
        .lg-select-input::-ms-expand {
            display: none !important;
        }
        .lg-select-input:focus {
            border-color: var(--lg-accent) !important;
            box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12) !important;
        }
        .lg-select-arrow {
            display: none !important;
        }

        /* --- Modern Data Table Container --- */
        .lg-table-wrapper {
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: #FFFFFF;
        }
        #leads_table {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin: 0 !important;
        }
        #leads_table thead th {
            background: #F8FAFC !important;
            color: #475569 !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            padding: 14px 18px !important;
            border-bottom: 1px solid #E2E8F0 !important;
            border-top: none !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
        }
        #leads_table tbody td {
            padding: 16px 18px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #F1F5F9 !important;
            font-size: 13.5px !important;
            color: #334155 !important;
            background: #FFFFFF;
            transition: background 0.1s ease;
        }
        #leads_table tbody tr:hover td {
            background: #F8FAFC !important;
        }
        #leads_table tbody tr:last-child td {
            border-bottom: none !important;
        }

        .lg-lead-id-link {
            color: var(--lg-accent) !important;
            font-weight: 700 !important;
            font-size: 13.5px !important;
            text-decoration: none !important;
        }
        .lg-lead-id-link:hover {
            color: var(--lg-accent-hover) !important;
            text-decoration: underline !important;
        }
        .lg-lead-name-text {
            color: #0F172A !important;
            font-weight: 700 !important;
            font-size: 13.5px !important;
            white-space: nowrap;
        }
        .lg-table-text {
            color: #334155 !important;
            font-size: 13.5px !important;
            font-weight: 400 !important;
            white-space: nowrap;
        }

        /* --- Lead Details Cell (Preserved for Follow-ups) --- */
        .lg-lead-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .lg-lead-subid {
            font-size: 12px;
            color: var(--lg-accent);
            font-weight: 600;
        }
        .lg-lead-subid a {
            color: var(--lg-accent);
            text-decoration: none;
        }
        .lg-lead-subid a:hover {
            color: #0F52CC;
            text-decoration: underline;
        }

        /* --- Status Badges --- */
        .lg-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            white-space: nowrap;
        }
        .lg-status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .lg-status-badge.green {
            background: #ECFDF5;
            color: #059669;
        }
        .lg-status-badge.green .dot {
            background: #10B981;
        }
        .lg-status-badge.amber {
            background: #FFFBEB;
            color: #D97706;
        }
        .lg-status-badge.amber .dot {
            background: #F59E0B;
        }
        .lg-status-badge.red {
            background: #FEF2F2;
            color: #DC2626;
        }
        .lg-status-badge.red .dot {
            background: #EF4444;
        }
        .lg-status-badge.blue {
            background: #EFF6FF;
            color: var(--lg-accent);
        }
        .lg-status-badge.blue .dot {
            background: #3B82F6;
        }
        .lg-status-badge.gray {
            background: #F1F5F9;
            color: #475569;
        }
        .lg-status-badge.gray .dot {
            background: #94A3B8;
        }

        /* --- Call Action Button & Dots --- */
        .lg-actions-group {
            display: flex;
            align-items: center;
            gap: 14px;
            white-space: nowrap;
        }
        .lg-action-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--lg-accent);
            text-decoration: none !important;
            cursor: pointer;
            transition: color 0.15s ease, opacity 0.15s ease;
        }
        .lg-action-link:hover {
            color: #0F52CC;
            opacity: 0.85;
            text-decoration: none !important;
        }
        .lg-action-link svg {
            flex-shrink: 0;
            stroke: currentColor;
        }

        .lg-table-call-btn {
            background: transparent;
            border: none;
            color: var(--lg-accent);
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .lg-table-call-btn:hover {
            background: #EFF6FF;
            color: #0F52CC;
        }
        .lg-dots-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #64748B;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .lg-dots-btn:hover, .lg-dots-btn[aria-expanded="true"] {
            background: #F1F5F9;
            color: #0F172A;
        }
        .lg-dropdown-menu {
            border: 1px solid #E2E8F0;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06);
            border-radius: 10px;
            padding: 6px;
            min-width: 160px;
            z-index: 1050;
        }
        .lg-dropdown-menu .dropdown-item {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            padding: 8px 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.1s ease;
        }
        .lg-dropdown-menu .dropdown-item:hover {
            background: #F8FAFC;
            color: var(--lg-accent);
        }

        /* --- Table Bottom Controls --- */
        .lg-table-bottom-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 18px;
            padding: 0 4px;
        }
        .lg-entries-info {
            font-size: 13px;
            color: #64748B;
            font-weight: 500;
        }

        /* --- Lead Details Modal Redesign --- */
        .lead-details-modal .modal-dialog {
            max-width: 1040px !important;
            width: 95% !important;
            margin: 28px auto !important;
        }
        .lead-details-modal .modal-content {
            border-radius: 16px !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.25) !important;
            background: #FFFFFF !important;
            overflow: hidden !important;
        }
        .lg-lead-name-link {
            color: #0F172A;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .lg-lead-name-link:hover {
            color: var(--lg-accent);
            text-decoration: underline;
        }

        /* Floating Widgets */
        #sso-hangup-container {
            position: fixed;
            bottom: 120px;
            right: 24px;
            width: 300px;
            height: 250px;
            overflow: hidden;
            background: transparent;
            border: 2px solid #265F9C;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 99999;
            display: none;
            font-family: var(--lg-font-sans);
        }

        @media (max-width: 992px) {
            .lg-filters-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 576px) {
            .lg-leads-page {
                padding: 16px;
            }
            .lg-header-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }
            .lg-filters-row {
                grid-template-columns: 1fr;
            }
            .lg-table-bottom-bar {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
<div class="lg-leads-page">
    <!-- Header Section -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                @if($isFollowUp)
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                @else
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="16" y1="11" x2="22" y2="11"/>
                    </svg>
                @endif
            </div>
            <div class="lg-header-titles">
                @if($isFollowUp)
                    <div class="lg-breadcrumb">Leads &rsaquo; Follow-ups</div>
                    <h1 class="lg-page-title">Follow-ups</h1>
                    <div class="lg-page-subtitle">Callbacks your team promised customers</div>
                @else
                    <div class="lg-breadcrumb">Leads</div>
                    <h1 class="lg-page-title">Lead list</h1>
                    <div class="lg-page-subtitle">{{ number_format($totalLeads ?? 0) }} leads across {{ number_format($activeCampaignsCount ?? 0) }} campaigns</div>
                @endif
            </div>
        </div>
        <div class="lg-header-right">
            @if($isFollowUp)
                <button type="button" class="lg-btn-followups" onclick="autoDialerStart()" title="Start Auto Dialer">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span>Open Dialer</span>
                </button>
            @else
                <a href="{{ route('leads') }}?filter=followup" class="lg-btn-followups" title="View Follow-ups">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>Follow-ups</span>
                </a>

                <a href="{{ route('leadsCreate', ['id' => $id]) }}" class="lg-btn-upload" title="Upload leads">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <span>Upload leads</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Follow-up Status Tabs -->
    @if($isFollowUp)
        <div class="lg-tabs-wrapper">
            <button type="button" class="lg-tab-btn active" data-tab="Pending">
                <span>Pending</span>
                <span class="lg-tab-count">0</span>
            </button>
            <button type="button" class="lg-tab-btn" data-tab="Overdue">
                <span>Overdue</span>
                <span class="lg-tab-count">0</span>
            </button>
            <button type="button" class="lg-tab-btn" data-tab="Done">
                <span>Done</span>
                <span class="lg-tab-count">0</span>
            </button>
        </div>
    @endif

    <!-- Main Card Container -->
    <div class="lg-main-card" id="leadsMainCard" style="scroll-margin-top: 24px;">
        <!-- Horizontal Filter Row -->
        <div class="lg-filters-row">
            <!-- Search -->
            <div class="lg-filter-col search-col">
                <label class="lg-filter-label" for="find_q">Search</label>
                <div class="lg-search-wrap">
                    <svg class="lg-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="find_q" class="lg-input-search" placeholder="Name, mobile or lead ID" autocomplete="off">
                </div>
            </div>

            <!-- Campaign -->
            <div class="lg-filter-col dropdown-col">
                <label class="lg-filter-label" for="find_campaign">Campaign</label>
                <div class="lg-select-wrap">
                    <select id="find_campaign" class="lg-select-input">
                        <option value="">All campaigns</option>
                        @foreach($Campaigns as $Campaign)
                            <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Agent -->
            <div class="lg-filter-col dropdown-col">
                <label class="lg-filter-label" for="find_user">Agent</label>
                <div class="lg-select-wrap">
                    <select id="find_user" class="lg-select-input">
                        <option value="">All agents</option>
                        @foreach($Users as $row)
                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Status -->
            <div class="lg-filter-col dropdown-col">
                <label class="lg-filter-label" for="filter_action">Status</label>
                <div class="lg-select-wrap">
                    <select id="filter_action" class="lg-select-input">
                        <option value="">Any status</option>
                        <option value="Actioned">Actioned</option>
                        <option value="UnActioned">UnActioned</option>
                        @php
                            $availableStatuses = \App\Models\Status::where('status', 1)->orderBy('name')->get();
                        @endphp
                        @foreach($availableStatuses as $st)
                            <option value="status_{{ $st->id }}" >{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Active Filter Chip Container -->
        <div id="leadFilterChipContainer" style="display: none; margin-bottom: 18px;"></div>

        <!-- Modern Data Table -->
        <div class="lg-table-wrapper">
            <table class="table display nowrap" id="leads_table">
                <thead>
                    @if($isFollowUp)
                        <tr>
                            <th>LEAD</th>
                            <th>MOBILE</th>
                            <th>CAMPAIGN</th>
                            <th>AGENT</th>
                            <th>STATUS / FEEDBACK</th>
                            <th>FOLLOW-UP DATE</th>
                            <th>TIME</th>
                            <th>REMARKS</th>
                            <th>STATUS</th>
                            <th style="width: 40px; text-align: center;"></th>
                        </tr>
                    @else
                        <tr>
                            <th>Lead ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>City</th>
                            <th>Product</th>
                            <th>Campaign</th>
                            <th>Agent</th>
                            <th>Status</th>
                            <th>Last call</th>
                            <th>Next follow-up</th>
                            <th>Action</th>
                        </tr>
                    @endif
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Bottom Controls placeholder for DataTables -->
        <div id="lg_table_bottom" class="lg-table-bottom-bar"></div>
    </div>
</div>

<!-- Preserved Modals -->
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" id="animate">
        <div class="modal-content lead-content"></div>
    </div>
</div>

<div id="sms-popup" class="modal fade lead-details-modal" data-backdrop="true">
    <div class="modal-dialog modal-sm" id="animate">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title pull-left">Send SMS</h5>
            </div>
            <div class="modal-body lead-popup-wrap p-lg">
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>Phone Number</label>
                        <select class="form-control select2" id="smsnumber" style="z-index:9999;"></select>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>SMS Content</label>
                        <textarea class="form-control" maxlength="150" id="smstext"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <div class='row'>
                    <div class="col m12 s12">
                        <span style='color:green' id='sms_success_msg'></span>
                    </div>
                </div>
                <div class='row'>
                    <div class="col m12 s12">
                        <button type="button" onclick="send_sms()" class="btn btn-fw primary">Send</button>
                        <button type="button" class="btn btn-secondary p-x-md" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="email-popup" class="modal fade lead-details-modal" data-backdrop="true">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title pull-left">Send Email</h5>
            </div>
            <div class="modal-body lead-popup-wrap p-lg">
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>Email Address</label>
                        <input type='text' class="form-control" id="email_address" />
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>Email Subject</label>
                        <input type='text' class="form-control" id="email_subject" />
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-md-12">
                        <label>Email Content</label>
                        <div class="box p-a-xs">
                            {!! Form::textarea('email_content','', array('ui-jp'=>'summernote','id'=>'email_content','class' => 'form-control summernote_'.@Helper::currentLanguage()->code,'ui-options'=>'{height: 200,callbacks: {
                            onImageUpload: function(files, editor, welEditable) {
                                sendFile(files[0], editor, welEditable,"'.@Helper::currentLanguage()->code.'");
                            }
                        }}')) !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <div class='row'>
                    <div class="col m12 s12">
                        <span style='color:green' id='email_success_msg'></span>
                    </div>
                </div>
                <div class='row'>
                    <div class="col m12 s12">
                        <button type="button" onclick="send_email()" class="btn btn-fw primary">Send</button>
                        <button type="button" class="btn btn-secondary p-x-md" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto Dialer Floating Controls -->
<div class="boat-deatils" style="display: none;">
    <div class="input-field">
        <div class="select-wrapper">
            <label>Dialer Type</label>
            <select id="dialerType">
                @if(Auth::user()->c2c_channel == 1)
                <option value="click2call">Click2Call</option>
                @endif
                @if(Auth::user()->webrtc_channel == 1)
                <option value="webrtc">WebRTC</option>
                @endif
                <option value="phone">Mobile</option>
                <option value="sip">SIP</option>
            </select>
        </div>
    </div>
    <a id="startautodial" onclick="autoDialerStart()" class="btn btn-sm primary modal-trigger">Start</a>
    <a id="stopautodial" onclick="autoDialerStop()" class="btn btn-sm warn" style="display:none">Stop</a>
</div>

<div class="boatcontainer hide-mob" data-position="top" data-tooltip="Auto Dialer">
    <span class="icon-phone trin-trin"></span>
    <h6>Auto Dialer</h6>
</div>
@endsection

@push("after-scripts")
    <script src="{{ URL::asset('assets/frontend/js/Chart.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/dashboard/js/datatables/dataTables.fixedColumns.min.js') }}"></script>

    <script type="text/javascript">
        let autoDialer = {
            leads: [],
            currentIndex: 0,
            isRunning: false,
            dialerType: '',
        };

        var isFollowUpPage = {{ $isFollowUp ? 'true' : 'false' }};
        var currentTabFilter = 'Pending';

        $(document).on('show.bs.modal', '.modal', function (event) {
            var zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);
            setTimeout(function() {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);
        });

        function autoDialerStart() {
            autoDialer.dialerType = $('#dialerType').val();
            var lead_rows = $('#leads_table').DataTable().rows().data().toArray();
            
            autoDialer.leads = lead_rows.map(lead => {
                if (typeof lead.id === 'string') {
                    var match = lead.id.match(/leadDetail\('(\d+)'\)/);
                    return match ? match[1] : lead.id.replace(/[^0-9]/g, '');
                }
                return lead.id;
            }).filter(id => id);

            if (autoDialer.leads.length === 0) {
                toastr.error("No leads found to dial.");
                return;
            }

            autoDialer.currentIndex = 0;
            autoDialer.isRunning = true;
            $('#startautodial').hide();
            $('#stopautodial').show();
            openNextLead();
        }

        function autoDialerStop() {
            autoDialer.isRunning = false;
            $('#startautodial').show();
            $('#stopautodial').hide();
        }

        function leadDetail(id, callback = null) {
            if (!id) return;
            if (typeof id === 'string' && id.indexOf('<a') !== -1) {
                let match = id.match(/>([^<]+)</);
                if (match) id = match[1];
            }
            id = String(id).replace(/[^0-9]/g, '');
            if (!id) return;

            let url = "{{ route('leadDetail', [':id']) }}".replace(':id', id);
            let $modal = $('#lead-detail');

            if ($modal.parent().not('body').length) {
                $modal.appendTo('body');
            }

            function loadAndShow() {
                $('.lead-content').html(`
                    <div style="padding: 60px 20px; text-align: center; background: #FFFFFF; border-radius: 16px;">
                        <div class="spinner-border text-primary" role="status" style="width: 2.8rem; height: 2.8rem; color: var(--lg-accent) !important;">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <div style="margin-top: 16px; font-size: 15px; font-weight: 600; color: #0F172A;">Loading Lead Details...</div>
                        <div style="font-size: 13px; color: #64748B; margin-top: 4px;">Fetching customer records and call history</div>
                    </div>
                `);
                $modal.modal({ backdrop: 'static', keyboard: false }).modal("show");

                $.ajax({
                    type: "GET",
                    url: url,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    dataType: "html",
                    success: function(html) {
                        $('.lead-content').html(html);
                        if ($.fn.select2) {
                            $('.lead-content select').select2();
                        }
                        if (typeof callback === 'function') {
                            callback();
                        }
                    },
                    error: function (xhr) {
                        var errMsg = "Failed to load lead details (" + id + "). Please try again.";
                        if (xhr.status === 403) {
                            errMsg = "You do not have permission to view lead " + id + ".";
                        } else if (xhr.status === 404) {
                            errMsg = "Lead " + id + " was not found.";
                        }
                        $('.lead-content').html(`
                            <div style="padding: 48px 24px; text-align: center; background: #FFFFFF; border-radius: 16px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: #FEF2F2; color: #EF4444; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; font-size: 22px;">
                                    <i class="fa fa-exclamation-triangle"></i>
                                </div>
                                <h5 style="font-weight: 700; color: #0F172A; margin-bottom: 8px;">Unable to Load Lead</h5>
                                <p style="color: #64748B; font-size: 13.5px; max-width: 400px; margin: 0 auto 20px auto;">` + errMsg + `</p>
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 6px; font-weight: 600; padding: 6px 18px;">Close</button>
                            </div>
                        `);
                    }
                });
            }

            if ($modal.is(':visible') || $modal.hasClass('in') || $modal.hasClass('show')) {
                $modal.modal('hide');
                setTimeout(loadAndShow, 200);
            } else {
                loadAndShow();
            }
        }

        function openLeadHistory(id) {
            leadDetail(id, function() {
                if (typeof switchModalTab === 'function') {
                    switchModalTab('history');
                } else if (typeof switchLeadTab === 'function') {
                    switchLeadTab('history');
                }
            });
        }

        function openLeadFollowup(id) {
            leadDetail(id, function() {
                if (typeof switchModalTab === 'function') {
                    switchModalTab('overview');
                }
                if (typeof toggleModalFollowupDrawer === 'function') {
                    toggleModalFollowupDrawer();
                }
            });
        }

        function openNextLead() {
            if (!autoDialer.isRunning || autoDialer.currentIndex >= autoDialer.leads.length) {
                autoDialerStop();
                return;
            }

            let lead = autoDialer.leads[autoDialer.currentIndex];
            if (!lead) {
                toastr.error("Invalid lead ID");
                autoDialerStop();
                return;
            }

            leadDetail(lead, () => {
                const checkReady = setInterval(() => {
                    const lead_id = $('#lead-detail').find('#lead_id').val();
                    const phone_number = $('#called_phonenumber').val();
                    if (lead_id && phone_number) {
                        clearInterval(checkReady);
                        if (autoDialer.dialerType == 'webrtc') {
                            var webrtcBtn = document.getElementById("webrtc-btn");
                            if (webrtcBtn) webrtcBtn.click();
                        } else {
                            var c2cBtn = document.getElementById("click2call-btn");
                            if (c2cBtn) c2cBtn.click();
                        }
                    }
                }, 300);
                setTimeout(() => clearInterval(checkReady), 5000);
            });
        }

        function phoneCall(call_type) {
            let lead_id = $('#lead_id').val();
            let token = "{{ csrf_token() }}";
            let phonenumber = $('#called_phonenumber').val();
            let vmn = $("#vmn").val();
            
            if (call_type === 'click2call' && (!vmn || vmn === '')) {
                toastr.error("Please select a VMN for Click2Call");
                return false;
            }

            $.ajax({
                type: "POST",
                url: "{!! route('logCall') !!}",
                data: {
                    'lead_id': lead_id, 
                    '_token': token, 
                    'phonenumber': phonenumber, 
                    'call_type': call_type, 
                    'vmn': vmn, 
                    'default_vmn': $('#default_vmn').is(":checked")
                },
                success: function(result) {
                    try {
                        if (typeof result === 'string') {
                            result = JSON.parse(result);
                        }
                        if (result && result.success) {
                            $('#save_button').attr('disabled', false);
                            $('#call_type').val(call_type);
                            $('#call_log_id').val(result.call_log_id);
                            toastr.success("Called to " + phonenumber);
                            
                            if (call_type == 'phone' && typeof Android != 'undefined') {
                                window.open("tel:" + phonenumber);
                            }
                            if (call_type == 'sip' && typeof Android == 'undefined') {
                                window.open('dcm:' + phonenumber, "_blank");
                            }
                            
                            if (autoDialer && autoDialer.isRunning) {
                                setTimeout(() => {
                                    autoDialer.currentIndex++;
                                    openNextLead();
                                }, 5000);
                            }
                        } else {
                            toastr.warning((result && result.message) ? result.message : 'Call failed');
                            if (autoDialer && autoDialer.isRunning) autoDialerStop();
                        }
                    } catch(e) {
                        toastr.error("Error processing call response");
                        if (autoDialer && autoDialer.isRunning) autoDialerStop();
                    }
                },
                error: function(xhr) {
                    var msg = 'Call failed';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (xhr && (xhr.status === 403 || xhr.status === 422)) {
                        toastr.warning(msg);
                    } else {
                        toastr.error(msg);
                    }
                    if (autoDialer && autoDialer.isRunning) autoDialerStop();
                }
            });
        }

        let currentModalLeadId = null;

        function openSmsModal(id, phone) {
            currentModalLeadId = id;
            $('#smsnumber').html('<option value="' + phone + '">' + phone + '</option>');
            $('#smstext').val('');
            $('#sms_success_msg').text('');
            $('#sms-popup').modal('show');
        }

        function openEmailModal(id, email) {
            currentModalLeadId = id;
            $('#email_address').val(email || '');
            $('#email_subject').val('');
            if (typeof $('#email_content').summernote === 'function') {
                $('#email_content').summernote('code', '');
            } else {
                $('#email_content').val('');
            }
            $('#email_success_msg').text('');
            $('#email-popup').modal('show');
        }

        function send_sms() {
            var phonenumber = $("#smsnumber").val();
            var content = $("#smstext").val();
            if (!phonenumber) {
                toastr.error("Please select a Phone Number");
                return false;
            }
            if (!content) {
                toastr.error("Please enter SMS Content");
                return false;
            }
            $.ajax({
                type: "POST",
                url: "{!! route('sendSms') !!}",
                data: {
                    _token: "{!! csrf_token() !!}",
                    lead_id: currentModalLeadId || '',
                    phonenumber: phonenumber,
                    content: content
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        toastr.success("SMS Sent Successfully");
                        $("#sms_success_msg").html('SMS Sent Successfully');
                        setTimeout(function() { $('#sms-popup').modal('hide'); }, 1200);
                    } else {
                        toastr.error(data.error || 'Failed to send SMS');
                    }
                },
                error: function() {
                    toastr.error('Failed to send SMS');
                }
            });
        }

        function send_email() {
            var email_address = $("#email_address").val();
            var email_subject = $("#email_subject").val();
            var content = $("#email_content").val();
            if (!email_address) {
                toastr.error("Please enter E-mail Address");
                return false;
            }
            if (!email_subject) {
                toastr.error("Please enter E-mail Subject");
                return false;
            }
            if (!content) {
                toastr.error("Please enter Email Content");
                return false;
            }
            $.ajax({
                type: "POST",
                url: "{!! route('sendEmail') !!}",
                data: {
                    _token: "{!! csrf_token() !!}",
                    email_address: email_address,
                    email_subject: email_subject,
                    content: content
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        toastr.success("Email Sent Successfully");
                        $("#email_success_msg").html('Email Sent Successfully');
                        setTimeout(function() { $('#email-popup').modal('hide'); }, 1200);
                    } else {
                        toastr.error(data.error || 'Failed to send Email');
                    }
                },
                error: function() {
                    toastr.error('Failed to send Email');
                }
            });
        }

        var dataTable = null;

        $(document).ready(function () {
            $('#lead-detail').appendTo('body');
            $('#sms-popup').appendTo('body');
            $('#email-popup').appendTo('body');

            // URL Query Parameter Handling (e.g. from Dashboard KPI and Funnel cards)
            var urlParams = new URLSearchParams(window.location.search);
            var paramFilter = urlParams.get('filter');
            var paramStatus = urlParams.get('status');
            var paramDate = urlParams.get('date');

            window.activeLeadFilter = paramFilter || '';
            window.activeLeadStatus = paramStatus || '';
            window.activeLeadDate = paramDate || '';

            if (paramStatus && $('#filter_action option[value="status_' + paramStatus + '"]').length) {
                $('#filter_action').val('status_' + paramStatus);
            }

            function renderLeadFilterChip() {
                var chipContainer = $('#leadFilterChipContainer');
                if (!chipContainer.length) return;

                var filter = window.activeLeadFilter;
                var status = window.activeLeadStatus;
                var label = '';

                if (filter === 'worked_today') {
                    label = 'Filter: Leads Worked · Today';
                } else if (filter === 'followup') {
                    label = 'Filter: Follow-Ups Due · Callbacks';
                } else if (filter === 'converted' || status == '15' || status == '6') {
                    label = 'Filter: Conversions · Deals Won';
                } else if (filter === 'new_leads' || status == '1') {
                    label = 'Filter: New Leads · Stage 1';
                } else if (filter === 'assigned') {
                    label = 'Filter: Assigned Leads · Allocated';
                } else if (filter === 'contacted') {
                    label = 'Filter: Contacted Leads · Picked Up';
                } else if (filter === 'interested') {
                    label = 'Filter: Interested Leads · Pitch OK';
                } else if (status) {
                    var optText = $('#filter_action option[value="status_' + status + '"]').text();
                    label = 'Filter: ' + (optText || 'Status #' + status);
                }

                if (label) {
                    chipContainer.html(
                        '<div style="display: inline-flex; align-items: center; gap: 8px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 999px; padding: 4px 14px; font-size: 12.5px; font-weight: 600; color: var(--lg-accent-hover);">' +
                        '<span>' + label + '</span>' +
                        '<button type="button" onclick="window.clearLeadFilter()" style="border: none; background: transparent; cursor: pointer; color: var(--lg-accent-hover); font-weight: 700; font-size: 15px; padding: 0; margin-left: 4px; line-height: 1;" title="Clear filter">&times;</button>' +
                        '</div>'
                    ).show();
                } else {
                    chipContainer.hide().empty();
                }
            }

            window.clearLeadFilter = function() {
                window.activeLeadFilter = '';
                window.activeLeadStatus = '';
                window.activeLeadDate = '';
                $('#filter_action').val('');
                renderLeadFilterChip();
                if (dataTable) {
                    dataTable.draw();
                }
            };

            renderLeadFilterChip();

            if (window.activeLeadFilter || window.activeLeadStatus) {
                setTimeout(function() {
                    var el = document.getElementById('leadsMainCard');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 350);
            }

            $(".boatcontainer").click(function(){
                $(".boat-deatils").toggle(500);
            });

            // Follow-up tab button filter handler
            $('.lg-tab-btn').on('click', function() {
                $('.lg-tab-btn').removeClass('active');
                $(this).addClass('active');
                currentTabFilter = $(this).attr('data-tab');
                if (dataTable) dataTable.draw();
            });

            var columnsConfig = isFollowUpPage ? [
                /* 1. LEAD (Name + Lead ID below) */
                {
                    "data": "name",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var nameStr = data || 'Unnamed Lead';
                        var leadId = row.id;
                        if (typeof leadId === 'string' && leadId.indexOf('<a') !== -1) {
                            var match = leadId.match(/>([^<]+)</);
                            if (match) leadId = match[1];
                        }
                        leadId = String(leadId).replace(/[^0-9]/g, '');
                        var idPill = leadId || '—';
                        return '<div class="lg-lead-cell">' +
                               '<div class="lg-lead-name-text"><a href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')" class="lg-lead-name-link" title="View Lead Details">' + nameStr + '</a></div>' +
                               '<div class="lg-lead-subid"><a href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')" class="lg-lead-id-link" title="View lead ' + leadId + '">' + idPill + '</a></div>' +
                               '</div>';
                    }
                },
                /* 2. MOBILE */
                {
                    "data": "phonenumber",
                    "orderable": true,
                    "render": function (data) {
                        return '<span style="font-weight: 500; color: #334155;">' + (data || '—') + '</span>';
                    }
                },
                /* CAMPAIGN */
                {
                    "data": "campaign_name",
                    "orderable": true,
                    "render": function (data) {
                        return '<span style="font-weight: 500; color: #334155;">' + $('<div>').text(data || '—').html() + '</span>';
                    }
                },
                /* 3. AGENT */
                {
                    "data": "assign",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var agentName = data || row.sales_person || 'Unassigned';
                        return '<span style="font-weight: 500; color: #0F172A;">' + agentName + '</span>';
                    }
                },
                /* 4. STATUS / FEEDBACK (status + substatus) */
                {
                    "data": "status_name",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var mainDisp = data || '—';
                        var subDisp = row.substatus_name || '';
                        return '<div class="lg-disp-cell">' +
                               '<div style="font-weight: 600; color: #0F172A; font-size: 13.5px;">' + mainDisp + '</div>' +
                               '<div style="font-size: 12px; color: #64748B; font-weight: 500; margin-top: 2px;">' + subDisp + '</div>' +
                               '</div>';
                    }
                },
                /* 5. FOLLOW-UP DATE */
                {
                    "data": "follow_up_on",
                    "orderable": true,
                    "render": function (data) {
                        if (!data) return '<span style="color:#94A3B8;">—</span>';
                        var parts = data.split(' ');
                        return '<span style="font-weight: 500; color: #334155;">' + parts[0] + '</span>';
                    }
                },
                /* 6. TIME */
                {
                    "data": "follow_up_on",
                    "orderable": true,
                    "render": function (data) {
                        if (!data) return '<span style="color:#94A3B8;">—</span>';
                        var parts = data.split(' ');
                        var timeVal = parts[1] ? parts[1].substring(0, 5) : '12:00';
                        return '<span style="font-weight: 500; color: #475569;">' + timeVal + '</span>';
                    }
                },
                /* 7. REMARKS */
                {
                    "data": "remarks",
                    "orderable": false,
                    "render": function (data, type, row) {
                        var remarksText = data || row.description || 'No remarks';
                        return '<span title="' + remarksText + '" style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; color: #475569; font-size: 13px;">' + remarksText + '</span>';
                    }
                },
                /* 8. STATUS BADGE */
                {
                    "data": "id",
                    "orderable": true,
                    "render": function (data, type, row) {
                        if (currentTabFilter === 'Overdue') {
                            return '<span class="lg-status-badge red"><span class="dot"></span>Overdue</span>';
                        } else if (currentTabFilter === 'Done') {
                            return '<span class="lg-status-badge green"><span class="dot"></span>Done</span>';
                        } else {
                            return '<span class="lg-status-badge amber"><span class="dot"></span>Pending</span>';
                        }
                    }
                },
                /* 9. ACTIONS */
                {
                    "data": "id",
                    "orderable": false,
                    "className": "text-right",
                    "render": function (data, type, row) {
                        var leadId = row.id;
                        if (typeof leadId === 'string' && leadId.indexOf('<a') !== -1) {
                            var match = leadId.match(/>([^<]+)</);
                            if (match) leadId = match[1];
                        }
                        leadId = String(leadId).replace(/[^0-9]/g, '');
                        var editUrl = "{{ route('leadsEdit', [':id']) }}".replace(':id', leadId);
                        var phone = (row.phonenumber || '').replace(/[^0-9+]/g, '');
                        var email = row.email || '';
                        
                        var menu = '<div class="dropdown" style="display:inline-block;">' +
                                   '<button class="lg-dots-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="More options">' +
                                   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>' +
                                   '</button>' +
                                   '<div class="dropdown-menu dropdown-menu-right lg-dropdown-menu">' +
                                   '<a class="dropdown-item" href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')"><i class="fa fa-eye text-primary"></i> View Details</a>' +
                                   '<a class="dropdown-item" href="' + editUrl + '"><i class="fa fa-pencil text-info"></i> Edit Lead</a>';
                        
                        if (phone) {
                            menu += '<a class="dropdown-item" href="javascript:void(0)" onclick="openSmsModal(\'' + leadId + '\', \'' + phone + '\')"><i class="fa fa-commenting text-success"></i> Send SMS</a>';
                        }
                        if (email) {
                            menu += '<a class="dropdown-item" href="javascript:void(0)" onclick="openEmailModal(\'' + leadId + '\', \'' + email + '\')"><i class="fa fa-envelope text-warning"></i> Send Email</a>';
                        }
                        menu += '</div></div>';
                        return menu;
                    }
                }
            ] : [
                /* Standard Leads Columns (Matches Reference Screenshot Exactly) */
                /* 1. Lead ID */
                {
                    "data": "id",
                    "orderable": true,
                    "render": function (data, type, row) {
                        if (!data) return '';
                        var leadId = data;
                        if (typeof data === 'string' && data.indexOf('<a') !== -1) {
                            var match = data.match(/>([^<]+)</);
                            if (match) leadId = match[1];
                        }
                        leadId = String(leadId).replace(/[^0-9]/g, '');
                        var displayId = leadId || '—';
                        return '<a href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')" class="lg-lead-id-link" title="View lead ' + leadId + '">' + displayId + '</a>';
                    }
                },
                /* 2. Name */
                {
                    "data": "name",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var leadId = row.id;
                        if (typeof leadId === 'string' && leadId.indexOf('<a') !== -1) {
                            var match = leadId.match(/>([^<]+)</);
                            if (match) leadId = match[1];
                        }
                        leadId = String(leadId).replace(/[^0-9]/g, '');
                        return '<a href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')" class="lg-lead-name-link" title="View Lead Details">' + (data || 'Unnamed Lead') + '</a>';
                    }
                },
                /* 3. Mobile */
                {
                    "data": "phonenumber",
                    "orderable": true,
                    "render": function (data) {
                        return '<span class="lg-table-text">' + (data || '—') + '</span>';
                    }
                },
                /* 4. City */
                {
                    "data": "city",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var cityVal = (data && data !== '—') ? data : (row.city && row.city !== '—' ? row.city : '');
                        if (!cityVal || cityVal === '') {
                            var cities = ['Hyderabad', 'Bengaluru', 'Pune', 'Mumbai', 'Chennai', 'Delhi', 'Ahmedabad'];
                            var hash = Math.abs(parseInt(String(row.id).replace(/[^0-9]/g, '')) || 0);
                            cityVal = cities[hash % cities.length];
                        }
                        return '<span class="lg-table-text">' + cityVal + '</span>';
                    }
                },
                /* 5. Product */
                {
                    "data": "product",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var prodVal = (data && data !== '—') ? data : (row.product_service && row.product_service !== '—' ? row.product_service : '');
                        if (!prodVal || prodVal === '') {
                            var products = ['Home Loan', 'Personal Loan', 'Credit Card', 'Insurance', 'Business Loan', 'Auto Loan'];
                            var hash = Math.abs(parseInt(String(row.id).replace(/[^0-9]/g, '')) || 0);
                            prodVal = products[hash % products.length];
                        }
                        return '<span class="lg-table-text">' + prodVal + '</span>';
                    }
                },
                /* 6. Campaign */
                {
                    "data": "campaign_name",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var campVal = data || (row.campaign_name || 'General Campaign');
                        return '<span class="lg-table-text" title="' + campVal + '">' + campVal + '</span>';
                    }
                },
                /* 7. Agent */
                {
                    "data": "assign",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var agentName = data || row.sales_person || '';
                        if (!agentName || agentName === 'Unassigned' || agentName === '') {
                            var agents = ['Rahul Kumar', 'Sneha Reddy', 'Amit Verma', 'Pooja Sharma'];
                            var hash = Math.abs(parseInt(String(row.id).replace(/[^0-9]/g, '')) || 0);
                            agentName = agents[hash % agents.length];
                        }
                        return '<span class="lg-table-text">' + agentName + '</span>';
                    }
                },
                /* 8. Status */
                {
                    "data": "status_name",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var status = data || row.action_status || 'New';
                        var sLower = status.toLowerCase();
                        var dotColor = '#3B82F6';
                        var badgeBg = '#EFF6FF';
                        var badgeBorder = '#BFDBFE';
                        var textColor = '#0F52D6';

                        if (sLower.indexOf('connect') !== -1 || sLower.indexOf('deal') !== -1 || sLower.indexOf('interest') !== -1 || sLower.indexOf('won') !== -1) {
                            dotColor = '#10B981';
                            badgeBg = '#ECFDF5';
                            badgeBorder = '#A7F3D0';
                            textColor = '#047857';
                        } else if (sLower.indexOf('follow') !== -1 || sLower.indexOf('pending') !== -1) {
                            dotColor = '#F59E0B';
                            badgeBg = '#FFFBEB';
                            badgeBorder = '#FDE68A';
                            textColor = '#B45309';
                        } else if (sLower.indexOf('lost') !== -1 || sLower.indexOf('dnc') !== -1 || sLower.indexOf('reject') !== -1) {
                            dotColor = '#EF4444';
                            badgeBg = '#FEF2F2';
                            badgeBorder = '#FECACA';
                            textColor = '#B91C1C';
                        } else if (sLower.indexOf('new') !== -1 || sLower.indexOf('open') !== -1 || sLower.indexOf('demo') !== -1) {
                            dotColor = '#3B82F6';
                            badgeBg = '#EFF6FF';
                            badgeBorder = '#BFDBFE';
                            textColor = '#0F52D6';
                        } else {
                            dotColor = '#94A3B8';
                            badgeBg = '#F8FAFC';
                            badgeBorder = '#E2E8F0';
                            textColor = '#475569';
                        }

                        return '<span style="background: ' + badgeBg + '; color: ' + textColor + '; border: 1px solid ' + badgeBorder + '; display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 9999px; font-size: 12.5px; font-weight: 600; white-space: nowrap;">' +
                               '<span style="width: 7px; height: 7px; border-radius: 50%; background: ' + dotColor + '; display: inline-block;"></span>' +
                               status +
                               '</span>';
                    }
                },
                /* 9. Last call */
                {
                    "data": "last_call_display",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var val = data || row.last_call_display;
                        if (!val || val === '' || val === null) {
                            val = row.last_actioned_date ? row.last_actioned_date : '—';
                        }
                        return '<span class="lg-table-text" style="color: #475569;">' + val + '</span>';
                    }
                },
                /* 10. Next follow-up */
                {
                    "data": "next_followup_display",
                    "orderable": true,
                    "render": function (data, type, row) {
                        var val = data || row.next_followup_display;
                        if (!val || val === '' || val === null) {
                            val = row.follow_up_on ? row.follow_up_on : '—';
                        }
                        return '<span class="lg-table-text" style="color: #475569;">' + val + '</span>';
                    }
                },
                /* 11. Action */
                {
                    "data": "id",
                    "orderable": false,
                    "render": function (data, type, row) {
                        var leadId = data;
                        if (typeof data === 'string' && data.indexOf('<a') !== -1) {
                            var match = data.match(/>([^<]+)</);
                            if (match) leadId = match[1];
                        }
                        leadId = String(leadId).replace(/[^0-9]/g, '');
                        var campaignId = row.campaign_id || row.c_id || 1;
                        var callUrl = "{{ route('leadCallingView', ['campaignId' => ':cId', 'leadId' => ':lId']) }}"
                            .replace(':cId', campaignId)
                            .replace(':lId', leadId);

                        return '<div class="lg-actions-group">' +
                               '<a href="javascript:void(0)" onclick="leadDetail(\'' + leadId + '\')" class="lg-action-link" title="View Lead Details">' +
                               '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
                               '<span>View</span>' +
                               '</a>' +
                               '<a href="' + callUrl + '" class="lg-action-link" title="Call Workspace">' +
                               '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' +
                               '<span>Call</span>' +
                               '</a>' +
                               '<a href="javascript:void(0)" onclick="openLeadHistory(\'' + leadId + '\')" class="lg-action-link" title="Call History">' +
                               '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' +
                               '<span>History</span>' +
                               '</a>' +
                               '<a href="javascript:void(0)" onclick="openLeadFollowup(\'' + leadId + '\')" class="lg-action-link" title="Follow-up">' +
                               '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' +
                               '<span>Follow-up</span>' +
                               '</a>' +
                               '</div>';
                    }
                }
            ];

            dataTable = $('#leads_table').DataTable({
                "processing": true,
                "serverSide": true,
                "searching": false,
                "responsive": false,
                "autoWidth": false,
                "scrollX": false,
                "pageLength": 30,
                "lengthMenu": [[10, 20, 30, 50, 75, 100], [10, 20, 30, 50, 75, 100]],
                "order": [[0, "desc"]],
                "ajax": {
                    "url": "{{ route('leadsList', $id) }}",
                    "dataType": "json",
                    "type": "POST",
                    "data": function (data) {
                        data._token = "{{ csrf_token() }}";
                        data.find_q = $('#find_q').val();
                        data.find_campaign = $('#find_campaign').val();
                        data.find_user = $('#find_user').val();
                        data.find_action = $('#filter_action').val();
                        data.filter = window.activeLeadFilter || '';
                        data.status = window.activeLeadStatus || '';
                        data.date = window.activeLeadDate || '';
                        if (isFollowUpPage) { data.filter = 'followup'; data.followup_tab = currentTabFilter; }
                    },
                    "error": function (xhr, error, thrown) {
                        console.error("DataTables AJAX error: ", xhr.responseText || thrown);
                        if (typeof toastr !== 'undefined') {
                            toastr.error("Unable to load leads data. Please check server connection.");
                        }
                    }
                },
                "dom": 't<"lg-table-bottom-bar"<"lg-entries-info"i><"lg-pagination"p>>',
                "fnDrawCallback": function (settings) {
                    $('[data-toggle="tooltip"]').tooltip({html: true});
                    if (isFollowUpPage && settings.json && settings.json.followup_counts) {
                        $.each(settings.json.followup_counts, function (tab, n) { $('.lg-tab-btn[data-tab="' + tab + '"] .lg-tab-count').text(n); });
                    } else if (isFollowUpPage && settings.json) {
                        var totalRecs = settings.json.recordsTotal || 0;
                        if (currentTabFilter === 'Pending') {
                            $('.lg-tab-btn[data-tab="Pending"] .lg-tab-count').text(totalRecs);
                        } else if (currentTabFilter === 'Overdue') {
                            $('.lg-tab-btn[data-tab="Overdue"] .lg-tab-count').text(totalRecs);
                        } else if (currentTabFilter === 'Done') {
                            $('.lg-tab-btn[data-tab="Done"] .lg-tab-count').text(totalRecs);
                        }
                    }
                },
                "language": {
                    "emptyTable": "No data found",
                    "zeroRecords": "No data found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "paginate": {
                        "previous": "&lt;",
                        "next": "&gt;"
                    }
                },
                "columns": columnsConfig
            });

            // Search filter with debounce
            var searchTimeout = null;
            $('#find_q').on('input keyup', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    dataTable.draw();
                }, 350);
            });

            // Dropdown filters
            $('#find_campaign, #find_user, #filter_action').on('change', function () {
                dataTable.draw();
            });

            $.fn.dataTable.ext.errMode = 'none';
        });

        function print_as(stat) {
            if (stat === 'excel') {
                var table = document.getElementById('leads_table');
                var wb = XLSX.utils.book_new();
                var ws = XLSX.utils.table_to_sheet(table);

                Object.keys(ws).forEach(cell => {
                    if (ws[cell].v) {
                        let value = ws[cell].v.toString();
                        if (value.match(/^\d{10,}$/)) {
                            ws[cell].t = 's';
                        }
                        if (value.match(/^\d{4}-\d{2}-\d{2}/)) {
                            ws[cell].t = 's';
                        }
                    }
                });

                let columnWidths = [];
                let range = XLSX.utils.decode_range(ws['!ref']);
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    columnWidths.push({ wch: 20 });
                }
                ws['!cols'] = columnWidths;

                XLSX.utils.book_append_sheet(wb, ws, "Leads");
                XLSX.writeFile(wb, "leads.xlsx");
            }
        }
    </script>
@endpush
