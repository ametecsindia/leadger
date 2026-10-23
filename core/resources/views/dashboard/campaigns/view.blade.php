@extends('dashboard.layouts.master')
@section('title', $Campaign->name . ' - Campaign Performance')

@push("after-styles")
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">

<style>
/* Dashboard Canvas */
.lg-dashboard-container {
    padding: 20px 24px 40px 24px;
    background-color: #F8FAFD;
    min-height: calc(100vh - 60px);
    font-family: var(--lg-font-sans);
}

/* Page Header Card */
.lg-page-header {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
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
.lg-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--lg-accent) 0%, #0F52CC 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 12px rgba(23, 105, 255, 0.25);
    flex-shrink: 0;
}
.lg-header-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 22px;
    line-height: 1.25;
}
.lg-header-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 4px;
    font-weight: 500;
}
.lg-header-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* Status Pill */
.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 20px;
    white-space: nowrap;
}
.lg-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.status-active {
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #A7F3D0;
}
.status-active .lg-dot { background: #059669; }

.status-paused {
    background: #FFFBEB;
    color: #D97706;
    border: 1px solid #FDE68A;
}
.status-paused .lg-dot { background: #D97706; }

/* Buttons */
.lg-btn {
    height: 40px;
    padding: 0 18px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}
.lg-btn-primary {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    box-shadow: 0 2px 6px rgba(23, 105, 255, 0.2);
}
.lg-btn-primary:hover {
    background: #0F52CC;
    border-color: #0F52CC;
    color: #FFFFFF;
}
.lg-btn-secondary {
    background: #FFFFFF;
    color: #334155;
    border: 1px solid #CBD5E1;
}
.lg-btn-secondary:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
}

/* Stats Cards Grid */
.lg-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.lg-stat-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.lg-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.lg-stat-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.lg-stat-val {
    font-size: 26px;
    font-weight: 800;
    color: #0F172A;
    margin: 8px 0 4px 0;
    line-height: 1;
}
.lg-stat-sub {
    font-size: 12px;
    color: #94A3B8;
    font-weight: 500;
}

/* Color Accent Variations */
.lg-stat-card.followups {
    border-left: 4px solid #F59E0B;
}
.lg-stat-card.followups .lg-stat-val {
    color: #D97706;
}
.lg-stat-card.converted {
    border-left: 4px solid #10B981;
}
.lg-stat-card.converted .lg-stat-val {
    color: #059669;
}
.lg-stat-card.interested {
    border-left: 4px solid var(--lg-accent);
}
.lg-stat-card.interested .lg-stat-val {
    color: var(--lg-accent);
}

/* Main Section Split Layout: Funnel + Telecallers */
.lg-section-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    margin-bottom: 24px;
}
@media (max-width: 992px) {
    .lg-section-grid {
        grid-template-columns: 1fr;
    }
}

/* Card Section Base */
.lg-card-box {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.lg-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Funnel Bar Items */
.lg-funnel-item {
    margin-bottom: 14px;
}
.lg-funnel-item:last-child {
    margin-bottom: 0;
}
.lg-funnel-header {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.lg-funnel-bar-bg {
    width: 100%;
    height: 10px;
    background: #F1F5F9;
    border-radius: 10px;
    overflow: hidden;
}
.lg-funnel-bar-fill {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(90deg, var(--lg-accent), #38BDF8);
    transition: width 0.4s ease;
}
.lg-funnel-bar-fill.followups {
    background: linear-gradient(90deg, #F59E0B, #FBBF24);
}
.lg-funnel-bar-fill.converted {
    background: linear-gradient(90deg, #10B981, #34D399);
}

/* Telecallers Table */
.lg-telecaller-table {
    width: 100%;
    border-collapse: collapse;
}
.lg-telecaller-table th {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748B;
    border-bottom: 1px solid #E2E8F0;
    padding: 8px 10px;
    text-align: left;
}
.lg-telecaller-table td {
    padding: 10px 10px;
    font-size: 13px;
    color: #1E293B;
    border-bottom: 1px solid #F1F5F9;
}
.lg-telecaller-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #E0F2FE;
    color: #0369A1;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
}

/* =====================================================
   LEADS IN THIS CAMPAIGN TABLE – Reference Design
   ===================================================== */
.lg-table-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    margin-bottom: 24px;
}

/* Table header bar */
.lg-table-header-bar {
    padding: 22px 26px 18px 26px;
    background: #FFFFFF;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.lg-header-title-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.lg-section-heading {
    font-size: 17px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    display: flex;
    align-items: center;
    line-height: 1.3;
}
.lg-purple-bar {
    display: inline-block;
    width: 4px;
    height: 18px;
    background: #7C3AED;
    border-radius: 3px;
    margin-right: 10px;
    flex-shrink: 0;
}
.lg-leads-count-text {
    font-size: 13px;
    color: #64748B;
    font-weight: 400;
    margin: 0;
    padding-left: 14px;
}

/* DataTable wrapper */
.lg-dt-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Main table */
.lg-leads-table {
    width: 100% !important;
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    margin: 0 !important;
}
.lg-leads-table thead tr {
    background: #F8FAFC;
}
.lg-leads-table thead th {
    background: #F8FAFC !important;
    color: #64748B !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    letter-spacing: normal !important;
    text-transform: none !important;
    padding: 14px 26px !important;
    border-top: none !important;
    border-bottom: 1px solid #F1F5F9 !important;
    white-space: nowrap !important;
    user-select: none;
}
.lg-leads-table thead th.sorting,
.lg-leads-table thead th.sorting_asc,
.lg-leads-table thead th.sorting_desc {
    cursor: pointer;
}

/* Row styling */
.lg-leads-table tbody tr {
    transition: background 0.12s ease;
}
.lg-leads-table tbody tr:hover td {
    background-color: #FAFAFC !important;
}
.lg-leads-table tbody td {
    padding: 16px 26px !important;
    font-size: 13.5px !important;
    color: #0F172A !important;
    border-bottom: 1px solid #F1F5F9 !important;
    vertical-align: middle !important;
    background: #FFFFFF !important;
}
.lg-leads-table tbody tr:last-child td {
    border-bottom: none !important;
}

/* Lead Name ABOVE Lead ID Component */
.lg-lead-info-cell {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 160px;
}
.lg-lead-title-name {
    font-size: 14px;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.35;
    text-decoration: none !important;
    cursor: pointer;
    transition: color 0.15s ease;
    white-space: nowrap;
}
.lg-lead-title-name:hover {
    color: var(--lg-accent);
}
.lg-lead-id-sub {
    font-size: 12px;
    font-weight: 500;
    color: #64748B;
    line-height: 1.2;
    white-space: nowrap;
}
.lg-lead-id-text a {
    color: #64748B;
    text-decoration: none !important;
    cursor: pointer;
    transition: color 0.15s ease;
}
.lg-lead-id-text a:hover {
    color: var(--lg-accent);
}

/* Column typography */
.lg-lead-phone {
    font-size: 13.5px;
    color: #334155;
    white-space: nowrap;
}
.lg-agent-name-text {
    font-size: 13.5px;
    color: #334155;
    white-space: nowrap;
}
.lg-last-call-text {
    font-size: 13.5px;
    color: #334155;
    white-space: nowrap;
}

/* Reference status pill */
.lg-status-pill-ref {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    white-space: nowrap;
}
.lg-status-pill-ref .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.lg-status-pill-ref.green {
    background: #ECFDF5;
    color: #059669;
}
.lg-status-pill-ref.green .dot { background: #10B981; }

.lg-status-pill-ref.blue {
    background: #EFF6FF;
    color: var(--lg-accent);
}
.lg-status-pill-ref.blue .dot { background: #3B82F6; }

.lg-status-pill-ref.amber {
    background: #FFFBEB;
    color: #D97706;
}
.lg-status-pill-ref.amber .dot { background: #F59E0B; }

/* Call action link */
.lg-call-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--lg-accent);
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none !important;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.lg-call-link:hover {
    color: var(--lg-accent-hover);
}
.lg-call-link svg {
    color: var(--lg-accent);
}


.dataTables_wrapper .dataTables_processing {
    background: rgba(255,255,255,0.92);
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 13px;
    color: var(--lg-accent);
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
</style>
@endpush

@section('content')
<div class="lg-dashboard-container">

    <!-- Page Header Card -->
    <div class="lg-page-header">
        <div class="lg-header-left">
            <div class="lg-header-icon">
                <i class="fa fa-bullhorn"></i>
            </div>
            <div>
                <h1 class="lg-header-title">{{ $Campaign->name }}</h1>
                <div class="lg-header-sub">
                    {{ $Campaign->segment_name }} • {{ date('d-M-Y', strtotime($Campaign->created_at)) }} – {{ date('d-M-Y', strtotime($Campaign->expiry_date)) }} • {{ count($telecallersData) }} telecaller(s) • Preview dialing
                </div>
            </div>
        </div>
        <div class="lg-header-right">
            <span class="lg-status-pill {{ $Campaign->status == 1 ? 'status-active' : 'status-paused' }}" id="statusPill">
                <span class="lg-dot"></span>
                <span id="statusLabel">{{ $Campaign->status == 1 ? 'Active' : 'Paused' }}</span>
            </span>

            <button type="button" class="lg-btn lg-btn-secondary" id="toggleStatusBtn" onclick="toggleCampaignStatus({{ $Campaign->id }})">
                <i class="fa {{ $Campaign->status == 1 ? 'fa-pause' : 'fa-play' }}"></i>
                <span id="btnText">{{ $Campaign->status == 1 ? 'Pause Campaign' : 'Resume Campaign' }}</span>
            </button>

            <button type="button" class="lg-btn lg-btn-primary" onclick="startCallingFlow()">
                <i class="fa fa-phone"></i>
                <span>Start Calling</span>
            </button>
        </div>
    </div>

    <!-- 6 Statistics Cards -->
    <div class="lg-stats-grid">
        <div class="lg-stat-card">
            <div class="lg-stat-label">Leads</div>
            <div class="lg-stat-val">{{ number_format($stats['leads']) }}</div>
            <div class="lg-stat-sub">Total uploaded leads</div>
        </div>

        <div class="lg-stat-card">
            <div class="lg-stat-label">Called</div>
            <div class="lg-stat-val">{{ number_format($stats['called']) }}</div>
            <div class="lg-stat-sub">{{ $stats['leads'] > 0 ? round(($stats['called'] / $stats['leads']) * 100, 1) : 0 }}% of total leads</div>
        </div>

        <div class="lg-stat-card">
            <div class="lg-stat-label">Connected</div>
            <div class="lg-stat-val">{{ number_format($stats['connected']) }}</div>
            <div class="lg-stat-sub">{{ $stats['called'] > 0 ? round(($stats['connected'] / $stats['called']) * 100, 1) : 0 }}% of called</div>
        </div>

        <div class="lg-stat-card followups">
            <div class="lg-stat-label">Follow-ups</div>
            <div class="lg-stat-val">{{ number_format($stats['followups']) }}</div>
            <div class="lg-stat-sub">{{ $stats['leads'] > 0 ? round(($stats['followups'] / $stats['leads']) * 100, 1) : 0 }}% follow up rate</div>
        </div>

        <div class="lg-stat-card interested">
            <div class="lg-stat-label">Interested</div>
            <div class="lg-stat-val">{{ number_format($stats['interested']) }}</div>
            <div class="lg-stat-sub">{{ $stats['leads'] > 0 ? round(($stats['interested'] / $stats['leads']) * 100, 1) : 0 }}% interest rate</div>
        </div>

        <div class="lg-stat-card converted">
            <div class="lg-stat-label">Converted</div>
            <div class="lg-stat-val">{{ number_format($stats['converted']) }}</div>
            <div class="lg-stat-sub">{{ $stats['leads'] > 0 ? round(($stats['converted'] / $stats['leads']) * 100, 1) : 0 }}% conversion rate</div>
        </div>
    </div>

    <!-- Funnel Section & Telecallers Section -->
    <div class="lg-section-grid">
        <!-- Campaign Funnel -->
        <div class="lg-card-box">
            <div class="lg-card-title">
                <span>Campaign Funnel</span>
                <small class="text-muted font-weight-normal" style="font-size: 12px;">Lead progress overview</small>
            </div>

            @php
                $leads = max(1, $stats['leads']);
                $pctCalled = min(100, round(($stats['called'] / $leads) * 100));
                $pctConn = min(100, round(($stats['connected'] / $leads) * 100));
                $pctFollow = min(100, round(($stats['followups'] / $leads) * 100));
                $pctInterest = min(100, round(($stats['interested'] / $leads) * 100));
                $pctConv = min(100, round(($stats['converted'] / $leads) * 100));
            @endphp

            <div class="lg-funnel-item">
                <div class="lg-funnel-header">
                    <span>Leads</span>
                    <span>{{ number_format($stats['leads']) }} (100%)</span>
                </div>
                <div class="lg-funnel-bar-bg"><div class="lg-funnel-bar-fill" style="width: 100%;"></div></div>
            </div>

            <div class="lg-funnel-item">
                <div class="lg-funnel-header">
                    <span>Called</span>
                    <span>{{ number_format($stats['called']) }} ({{ $pctCalled }}%)</span>
                </div>
                <div class="lg-funnel-bar-bg"><div class="lg-funnel-bar-fill" style="width: {{ $pctCalled }}%;"></div></div>
            </div>

            <div class="lg-funnel-item">
                <div class="lg-funnel-header">
                    <span>Connected</span>
                    <span>{{ number_format($stats['connected']) }} ({{ $pctConn }}%)</span>
                </div>
                <div class="lg-funnel-bar-bg"><div class="lg-funnel-bar-fill" style="width: {{ $pctConn }}%;"></div></div>
            </div>

            <div class="lg-funnel-item">
                <div class="lg-funnel-header">
                    <span>Follow-ups</span>
                    <span>{{ number_format($stats['followups']) }} ({{ $pctFollow }}%)</span>
                </div>
                <div class="lg-funnel-bar-bg"><div class="lg-funnel-bar-fill followups" style="width: {{ $pctFollow }}%;"></div></div>
            </div>

            <div class="lg-funnel-item">
                <div class="lg-funnel-header">
                    <span>Interested</span>
                    <span>{{ number_format($stats['interested']) }} ({{ $pctInterest }}%)</span>
                </div>
                <div class="lg-funnel-bar-bg"><div class="lg-funnel-bar-fill" style="width: {{ $pctInterest }}%;"></div></div>
            </div>

            <div class="lg-funnel-item">
                <div class="lg-funnel-header">
                    <span>Converted</span>
                    <span>{{ number_format($stats['converted']) }} ({{ $pctConv }}%)</span>
                </div>
                <div class="lg-funnel-bar-bg"><div class="lg-funnel-bar-fill converted" style="width: {{ $pctConv }}%;"></div></div>
            </div>
        </div>

        <!-- Telecallers Section -->
        <div class="lg-card-box">
            <div class="lg-card-title">
                <span>Telecallers</span>
                <span class="badge badge-pill badge-primary" style="background: var(--lg-accent); font-size: 11px;">{{ count($telecallersData) }}</span>
            </div>

            @if(count($telecallersData) > 0)
            <table class="lg-telecaller-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th style="text-align: center;">Calls Today</th>
                        <th style="text-align: right;">Conversions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($telecallersData as $tc)
                    <tr>
                        <td style="font-weight: 600;">
                            <span class="lg-telecaller-avatar">{{ strtoupper(substr($tc->name, 0, 1)) }}</span>
                            {{ $tc->name }}
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #475569;">{{ $tc->calls_today }}</td>
                        <td style="text-align: right; font-weight: 700; color: #059669;">{{ $tc->conversions }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-4 text-muted" style="font-size: 13px;">
                <i class="fa fa-users text-muted mb-2" style="font-size: 24px; opacity: 0.5;"></i>
                <div>No telecallers assigned to this campaign yet.</div>
            </div>
            @endif
        </div>
    </div>

    <!-- ================================================
         Leads in this campaign Section — Reference Design
         ================================================ -->
    <div class="lg-table-card" id="leadsTableSection">

        <!-- Table Header Bar -->
        <div class="lg-table-header-bar">
            <div class="lg-header-title-group">
                <h3 class="lg-section-heading">
                    <span class="lg-purple-bar"></span>
                    Leads in this campaign
                </h3>
                <div class="lg-leads-count-text" id="leads_count_info">
                    {{ number_format($stats['leads']) }} total leads
                </div>
            </div>
            <div class="lg-table-header-actions">
                @if(@Auth::user()->permissionsGroup->name != 'Auditor')
                    <button type="button" class="lg-btn lg-btn-primary" onclick="window.location='{{ route('leadsCreate', ['id' => $id]) }}'" style="height:36px;padding:0 16px;font-size:13px;">
                        <i class="fa fa-plus"></i> Add Lead
                    </button>
                @endif
                <button type="button" class="lg-btn lg-btn-secondary" id="filter_btn" style="height:36px;padding:0 16px;font-size:13px;">
                    <i class="fa fa-sliders"></i> Filters
                </button>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="lg-filter-bar displayNone" id="filter_div">
            {{Form::open(['route'=>['topics',$id],'method'=>'GET','id'=>'filter_form'])}}
            <input type="hidden" name="stat" id="search_submit_stat" value="">
            <div class="row align-items-end">
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="lg-filter-label">Campaign</label>
                    <select name="find_campaign" id="find_campaign" class="lg-filter-input">
                        <option value="">All Campaigns</option>
                        @foreach($Campaigns as $Camp)
                            <option value="{!! $Camp->id !!}" {!! $Camp->id == $id ? 'selected' : '' !!}>{!! $Camp->name !!}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="lg-filter-label">Staff</label>
                    <select name="find_user" id="find_user" class="lg-filter-input">
                        <option value="">All Staff</option>
                        @foreach($Users as $row)
                            <option value="{!! $row->id !!}">{!! $row->name !!}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="lg-filter-label">Action</label>
                    <select name="find_action" id="filter_action" class="lg-filter-input">
                        <option value="">All Actions</option>
                        <option value="Actioned">Actioned</option>
                        <option value="UnActioned">UnActioned</option>
                    </select>
                </div>
                <div class="col-md-4 col-sm-6 mb-2">
                    <label class="lg-filter-label">Search</label>
                    <input type="text" name="find_q" id="find_q" class="lg-filter-input" placeholder="Search by name, phone, company…" autocomplete="off">
                </div>
                <div class="col-md-2 col-sm-6 mb-2 d-flex align-items-end">
                    <button class="lg-btn lg-btn-primary w-100" id="search-btn" type="button" style="height:38px;font-size:13px;">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </div>
            {{Form::close()}}
        </div>

        <!-- DataTable -->
        <div class="lg-dt-wrapper">
            <table class="lg-leads-table display" id="leads_table" style="width:100%">
                <thead>
                    <tr>
                        <th style="min-width: 170px;">Lead</th>
                        <th style="min-width: 140px;">Mobile</th>
                        <th style="min-width: 140px;">Agent</th>
                        <th style="min-width: 130px;">Status</th>
                        <th style="min-width: 150px;">Last call</th>
                        <th style="text-align: right; min-width: 90px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

<!-- Lead Detail Modal -->
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content lead-content"></div>
    </div>
</div>

@endsection

@push("after-scripts")
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/dashboard/js/datatables/dataTables.fixedColumns.min.js') }}"></script>
<script type="text/javascript">

function toggleCampaignStatus(campaignId) {
    let url = "{{ route('campaignsToggleStatus', [':id']) }}".replace(':id', campaignId);
    $.ajax({
        type: "POST",
        url: url,
        data: { _token: "{{ csrf_token() }}" },
        success: function(res) {
            if (res.success) {
                let isAct = (res.status == 1);
                $('#statusPill').removeClass('status-active status-paused').addClass(isAct ? 'status-active' : 'status-paused');
                $('#statusLabel').text(res.status_label);
                $('#btnText').text(isAct ? 'Pause Campaign' : 'Resume Campaign');
                $('#toggleStatusBtn i').removeClass('fa-pause fa-play').addClass(isAct ? 'fa-pause' : 'fa-play');
                toastr.success("Campaign status updated to " + res.status_label);
            }
        },
        error: function() {
            toastr.error("Failed to update campaign status.");
        }
    });
}

// Opens the Campaign Queue on this campaign's first lead the user may open (lead 0 = let the server pick it)
function startCallingFlow() {
    // the calling page lists only active campaigns that have not expired
    @if(!empty($Campaign->expiry_date) && \Carbon\Carbon::parse($Campaign->expiry_date)->startOfDay()->lte(now()->startOfDay()))
    toastr.error("This campaign expired on {{ \Carbon\Carbon::parse($Campaign->expiry_date)->format('d M Y') }}. Extend its expiry date to call its leads.");
    return;
    @endif
    if ($.trim($('#statusLabel').text()) !== 'Active') {
        toastr.error('This campaign is paused. Resume it to start calling.');
        return;
    }
    window.location.href = "{{ route('leadCallingView', ['campaignId' => $Campaign->id, 'leadId' => 0]) }}";
}

function leadDetail(id) {
    if (!id) return;
    if (typeof id === 'string' && id.indexOf('<a') !== -1) {
        let match = id.match(/>([^<]+)</);
        if (match) id = match[1];
    }
    id = String(id).replace(/[^0-9]/g, '');
    if (!id) return;

    let url = "{{ route('leadCallingView', ['campaignId' => $id, 'leadId' => ':id']) }}".replace(':id', id);
    window.location.href = url;
}

$(document).ready(function () {
    $('#lead-detail').appendTo('body');
    $("#filter_btn").click(function () {
        $("#filter_div").slideToggle();
    });

    var dataTable = $('#leads_table').DataTable({
        "processing": true,
        "serverSide": true,
        "searching": false,
        "responsive": false,
        "scrollX": false,
        "autoWidth": false,
        "pageLength": 30,
        "lengthMenu": [[10, 25, 30, 50, 75, 100], [10, 25, 30, 50, 75, 100]],
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
            }
        },
        "columns": [
            {
                "data": "name",
                "orderable": true,
                "render": function (data, type, row) {
                    var leadName = data ? data : 'Unnamed Lead';
                    var leadId = row.id || '';
                    if (typeof leadId === 'string' && leadId.indexOf('<a') !== -1) {
                        var match = leadId.match(/>([^<]+)</);
                        if (match) leadId = match[1];
                    }
                    leadId = String(leadId).replace(/[^0-9]/g, '');

                    var viewUrl = "{{ route('leadCallingView', ['campaignId' => $id, 'leadId' => ':id']) }}".replace(':id', leadId);
                    var idDisplay = leadId ? '<div class="lg-lead-id-sub"><span class="lg-lead-id-text"><a href="javascript:void(0)" class="lg-lead-id-link" data-lead-id="' + leadId + '" title="View lead ' + leadId + '">' + leadId + '</a></span></div>' : '';

                    return '<div class="lg-lead-info-cell">' +
                               '<a href="' + viewUrl + '" class="lg-lead-title-name" title="Open Lead Calling Page">' + leadName + '</a>' +
                               idDisplay +
                           '</div>';
                }
            },
            {
                "data": "phonenumber",
                "orderable": true,
                "render": function(data, type, row) {
                    var phone = data || row.phonenumber_2 || '—';
                    return '<span class="lg-lead-phone">' + phone + '</span>';
                }
            },
            {
                "data": "assign",
                "orderable": true,
                "render": function(data, type, row) {
                    var agent = data || row.sales_person || 'Unassigned';
                    return '<span class="lg-agent-name-text">' + agent + '</span>';
                }
            },
            {
                "data": "status_name",
                "orderable": true,
                "render": function(data, type, row) {
                    var status = data || (row.action_status === 'UnActioned' ? 'New' : 'New');
                    var s = status.toLowerCase();
                    var badgeClass = 'blue';

                    if (s.indexOf('connect') !== -1 || s.indexOf('interest') !== -1 || s.indexOf('deal') !== -1 || s.indexOf('convert') !== -1 || s.indexOf('won') !== -1) {
                        badgeClass = 'green';
                    } else if (s.indexOf('follow') !== -1 || s.indexOf('pending') !== -1) {
                        badgeClass = 'amber';
                    } else if (s.indexOf('new') !== -1 || s.indexOf('unactioned') !== -1 || s.indexOf('ringing') !== -1 || s.indexOf('contactable') !== -1) {
                        badgeClass = 'blue';
                    } else {
                        badgeClass = 'blue';
                    }

                    return '<span class="lg-status-pill-ref ' + badgeClass + '">' +
                               '<span class="dot"></span>' + status +
                           '</span>';
                }
            },
            {
                "data": "last_actioned_date",
                "orderable": true,
                "render": function(data, type, row) {
                    if (!data || data === '—') return '<span class="text-muted">—</span>';
                    return '<span class="lg-last-call-text">' + data + '</span>';
                }
            },
            {
                "data": "id",
                "orderable": false,
                "className": "text-right",
                "render": function(data, type, row) {
                    var leadId = data;
                    if (typeof data === 'string' && data.indexOf('<a') !== -1) {
                        var match = data.match(/>([^<]+)</);
                        if (match) leadId = match[1];
                    }
                    leadId = String(leadId).replace(/[^0-9]/g, '');

                    var viewUrl = "{{ route('leadCallingView', ['campaignId' => $id, 'leadId' => ':id']) }}".replace(':id', leadId);

                    return '<a href="' + viewUrl + '" class="lg-call-link" title="Call ' + (row.name || 'Lead') + '">' +
                               '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' +
                               '<span>Call</span>' +
                           '</a>';
                }
            }
        ],
        "order": [[0, "asc"]],
        "fnDrawCallback": function (settings) {
            if (settings.json) {
                var pageInfo = this.api().page.info();
                var totalRecs = pageInfo.recordsTotal || 0;
                var shownRecs = pageInfo.end - pageInfo.start;
                if (totalRecs > 0) {
                    $('#leads_count_info').text(shownRecs + ' shown of ' + Number(totalRecs).toLocaleString());
                } else {
                    $('#leads_count_info').text('0 shown of 0');
                }
            }
        }
    });

    $("#search-btn").on('click', function () {
        dataTable.draw();
    });

    $('#find_q').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            dataTable.draw();
        }
    });

    $('#find_campaign, #find_user, #filter_action').on('change', function () {
        dataTable.draw();
    });
});
</script>
@endpush
