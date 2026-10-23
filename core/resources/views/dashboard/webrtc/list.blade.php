@extends('dashboard.layouts.master')
@section('title', 'WebRTC - LeadGER')

@push("after-styles")
    <style>
    /* LeadGER Corporate WebRTC Redesign - Visual Design Match with Click2Call */
    .lg-webrtc-wrapper {
        font-family: var(--lg-font-sans);
        color: #0F172A;
        background: #F8FAFC;
        padding: 24px 30px;
        box-sizing: border-box;
        min-height: calc(100vh - 72px);
    }

    /* 1. Page Header with Title, Breadcrumbs & Top Actions */
    .lg-webrtc-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
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

    .lg-title-main {
        font-size: 22px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
        line-height: 1.25;
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

    .lg-header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .lg-btn-action-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 40px;
        padding: 0 20px;
        background: var(--lg-accent);
        color: #FFFFFF !important;
        font-size: 13.5px;
        font-weight: 600;
        border-radius: 9px;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(20, 100, 244, 0.25);
        transition: all 0.15s ease;
        text-decoration: none !important;
    }

    .lg-btn-action-primary:hover {
        background: #0F52CC;
        box-shadow: 0 4px 10px rgba(20, 100, 244, 0.35);
    }

    .lg-btn-action-secondary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 40px;
        padding: 0 20px;
        background: #FFFFFF;
        color: var(--lg-accent) !important;
        font-size: 13.5px;
        font-weight: 600;
        border-radius: 9px;
        border: 1px solid #CBD5E1;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none !important;
    }

    .lg-btn-action-secondary:hover {
        border-color: var(--lg-accent);
        background: #F1F5F9;
    }

    /* 2. Channel Summary Cards Grid - 4 Cards Evenly Aligned */
    .lg-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 24px;
        width: 100%;
    }

    @media (max-width: 992px) {
        .lg-summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .lg-summary-grid {
            grid-template-columns: 1fr;
        }
    }

    .lg-summary-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 20px 22px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .lg-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .lg-stat-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .lg-stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .lg-stat-value {
        font-size: 24px;
        font-weight: 800;
        color: #0F172A;
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

    .lg-stat-icon-wrapper.blue { background: #EFF6FF; color: var(--lg-accent); }
    .lg-stat-icon-wrapper.emerald { background: #ECFDF5; color: #10B981; }
    .lg-stat-icon-wrapper.amber { background: #FFFBEB; color: #F59E0B; }
    .lg-stat-icon-wrapper.purple { background: #F5F3FF; color: #8B5CF6; }

    /* 3. Main Settings Card & Table */
    .lg-settings-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 24px 28px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    }

    /* Toolbar Controls */
    .lg-table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .lg-entries-group {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        color: #64748B;
        font-weight: 500;
    }

    .lg-entries-select {
        height: 38px;
        padding: 0 28px 0 12px;
        background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        font-size: 13.5px;
        color: #0F172A;
        outline: none;
        appearance: none;
        cursor: pointer;
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

    .lg-search-input-field {
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
    }

    .lg-search-input-field:focus {
        border-color: var(--lg-accent);
        box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
    }

    /* Table Styling */
    .lg-table-wrapper {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
    }

    .lg-webrtc-table {
        width: 100% !important;
        border-collapse: collapse !important;
        text-align: left;
        font-size: 13.5px;
        margin: 0 !important;
    }

    .lg-webrtc-table thead th {
        background: #F8FAFC !important;
        color: #475569 !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: 14px 18px !important;
        border-bottom: 1px solid #E2E8F0 !important;
        white-space: nowrap !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }

    .lg-webrtc-table tbody td {
        padding: 14px 18px !important;
        border-bottom: 1px solid #F1F5F9 !important;
        color: #0F172A !important;
        vertical-align: middle !important;
    }

    .lg-webrtc-table tbody tr:hover td {
        background: #F8FAFC !important;
    }

    .lg-user-flex {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .lg-user-icon-badge {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #EFF6FF;
        color: var(--lg-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .lg-action-btn-group {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    .lg-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #E2E8F0;
        background: #FFFFFF;
        color: #475569;
        cursor: pointer;
        text-decoration: none !important;
        transition: all 0.15s ease;
    }

    .lg-action-btn.edit:hover { background: var(--lg-accent); color: #FFFFFF; border-color: var(--lg-accent); }
    .lg-action-btn.delete:hover { background: #EF4444; color: #FFFFFF; border-color: #EF4444; }

    /* Modals */
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
        background: none; border: none; cursor: pointer; color: #64748B;
    }

    .lg-modal-body { padding: 24px; }
    .lg-modal-form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .lg-modal-label { font-size: 13px; font-weight: 600; color: #334155; }
    .lg-modal-control { height: 40px; padding: 0 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; outline: none; width: 100%; box-sizing: border-box; }
    .lg-modal-footer-bar { display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 16px 24px; border-top: 1px solid #E2E8F0; background: #F8FAFC; }
    .lg-btn-modal-cancel { height: 38px; padding: 0 18px; border: 1px solid #CBD5E1; background: #FFFFFF; border-radius: 8px; font-weight: 600; color: #475569; cursor: pointer; }
    .lg-btn-modal-submit { height: 38px; padding: 0 22px; border: none; background: var(--lg-accent); border-radius: 8px; font-weight: 600; color: #FFFFFF; cursor: pointer; }
    </style>
@endpush

@section('content')
<div class="lg-webrtc-wrapper">

    <!-- 1. PAGE HEADER ROW -->
    <div class="lg-webrtc-header-row">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </div>
            <div class="lg-header-text-group">
                <h1 class="lg-title-main">WebRTC</h1>
                <div class="lg-breadcrumb-nav">
                    <a href="{{ route('adminHome') }}">Home</a>
                    <span>/</span>
                    <span style="color: #0F172A; font-weight: 600;">WebRTC</span>
                </div>
            </div>
        </div>

        <!-- TOP ACTIONS: + ADD & + BULK -->
        <div class="lg-header-actions">
            <button type="button" class="lg-btn-action-primary" id="lgBtnAddWebRTC" aria-haspopup="dialog" onclick="lgOpenAddChannelModal(this)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add
            </button>
            <button type="button" class="lg-btn-action-secondary" onclick="lgOpenBulkWebRTCModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                Bulk
            </button>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('doneMessage'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px; margin-bottom: 20px;">
            {{ session('doneMessage') }}
        </div>
    @endif
    @if(session('errorMessage'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px; margin-bottom: 20px;">
            {{ session('errorMessage') }}
        </div>
    @endif

    <!-- 2. CHANNEL SUMMARY CARDS (REUSABLE PARTIAL) -->
    @include('dashboard.layouts.channel_stats_cards', [
        'totalChannels' => $licensewebrtcChannels ?? 20,
        'usedChannels' => $webrtcChannels ?? 0,
        'availableChannels' => max(0, ($licensewebrtcChannels ?? 20) - ($webrtcChannels ?? 0)),
        'expiryDate' => $channelExpiry ?? '2026/09/30',
    ])

    <!-- 3. MAIN WORKSPACE CARD & WEBRTC TABLE -->
    <div class="lg-settings-card">
        
        <!-- TABLE CONTROLS TOOLBAR -->
        <div class="lg-table-toolbar">
            <div class="lg-entries-group">
                <span>Show</span>
                <select id="lgWebRTCEntries" class="lg-entries-select">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>

            <div class="lg-search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="lgWebRTCSearch" class="lg-search-input-field" placeholder="Search user…">
            </div>
        </div>

        <form id="filter_form" method="post" style="display: none;">
            @csrf
            <input type="hidden" name="stat" id="search_submit_stat" value="">
            <input type="hidden" name="find_q" id="find_q" value="">
        </form>

        <!-- WEBRTC USERS TABLE -->
        <div class="lg-table-wrapper">
            <table class="lg-webrtc-table" id="leads_table">
                <thead>
                    <tr>
                        <th style="width: 40%;">User</th>
                        <th style="width: 24%;">Role</th>
                        <th style="width: 22%;">Channel</th>
                        <th style="width: 14%; text-align: right;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

</div>

<!-- BULK WEBRTC MODAL -->
<div id="lgBulkWebRTCModal" class="lg-modal-overlay">
    <div class="lg-modal-wrapper">
        <div class="lg-modal-header-bar">
            <h3 class="lg-modal-header-title">Bulk Assign WebRTC Channels</h3>
            <button type="button" class="lg-modal-close-btn" onclick="lgCloseBulkWebRTCModal()" title="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('webrtcStore') }}" method="POST">
            @csrf
            <div class="lg-modal-body">
                <div class="lg-modal-form-group">
                    <label class="lg-modal-label">Upload CSV or Enter Numbers</label>
                    <input type="text" name="click_agent_number" class="lg-modal-control" placeholder="Comma separated agent numbers" required>
                </div>
                {{-- Bulk: each number is matched to an existing user, who gets WebRTC turned on --}}
                <input type="hidden" name="user" value="1">
                <input type="hidden" name="bulk" value="1">
            </div>
            <div class="lg-modal-footer-bar">
                <button type="button" class="lg-btn-modal-cancel" onclick="lgCloseBulkWebRTCModal()">Cancel</button>
                <button type="submit" class="lg-btn-modal-submit">Bulk Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- REUSABLE CHANNEL MODAL: Edit (pencil) and "+ Add" (add mode) -->
@include('dashboard.layouts.edit_channel_modal')

@push('after-scripts')
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function () {
        var table_name = "#leads_table";
        var dataTable = $(table_name).DataTable({
            "processing": true,
            "serverSide": true,
            "searching": false,
            "responsive": false,
            "scrollX": false,   // .lg-table-wrapper scrolls sideways; DataTables scrolling drew an empty second header row
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
            "pagingType": "simple_numbers",
            "ajax": {
                "url": "{{ route('webrtcData') }}",
                "dataType": "json",
                "type": "POST",
                "data": function (data) {
                    data._token = "{{csrf_token()}}";
                    data.find_q = $('#lgWebRTCSearch').val();
                }
            },
            "dom": '<"row t-wrap"<"col-12"t>><"row lg-dt-bottom-row mt-3 align-items-center"<"col-sm-6"i><"col-sm-6 text-right"p>>',
            "language": {
                "emptyTable": "No data available in table",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "paginate": {
                    "previous": "Previous",
                    "next": "Next"
                }
            },
            "columns": [
                { "data": "name", "orderable": true },
                { "data": "role", "orderable": false },
                { "data": "channel", "orderable": false },
                { "data": "options", "orderable": false }
            ],
            "order": [[0, "desc"]]
        });

        $("#lgWebRTCSearch").on('keyup change', function () {
            $('#find_q').val(this.value);
            dataTable.draw();
        });

        $('#lgWebRTCEntries').on('change', function() {
            dataTable.page.len(this.value).draw();
        });

        $.fn.dataTable.ext.errMode = 'none';
    });

    function lgOpenBulkWebRTCModal() {
        const modal = document.getElementById('lgBulkWebRTCModal');
        if (modal) modal.classList.add('active');
    }

    function lgCloseBulkWebRTCModal() {
        const modal = document.getElementById('lgBulkWebRTCModal');
        if (modal) modal.classList.remove('active');
    }
</script>
@endpush
@endsection
