@extends('dashboard.layouts.master')
@section('title', 'Allocations History')

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* =============================================
           LEADGER — ALLOCATIONS HISTORY PAGE
        ============================================= */
        .la-page {
            padding: 24px 28px;
            background: #F8FAFC;
            min-height: calc(100vh - 60px);
            font-family: var(--lg-font-sans);
            box-sizing: border-box;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* ---- Page Header ---- */
        .la-header-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(15,23,42,0.04);
            width: 100%;
            box-sizing: border-box;
        }
        .la-header-left { display: flex; align-items: center; gap: 16px; }
        .la-header-icon-box {
            width: 48px; height: 48px;
            border-radius: 12px;
            background: #EFF6FF;
            border: 1px solid #DBEAFE;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: var(--lg-accent);
        }
        .la-page-title {
            font-size: 22px; font-weight: 700;
            color: #0F172A; margin: 0 0 3px 0; line-height: 1.2;
        }
        .la-page-subtitle {
            font-size: 13.5px; color: #64748B;
            font-weight: 400; margin: 0;
        }

        /* ---- Filter Card ---- */
        .la-filters-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(15,23,42,0.04);
            width: 100%;
            box-sizing: border-box;
        }
        .la-filters-row {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            width: 100%;
            flex-wrap: wrap;
        }
        .la-filter-col { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
        .la-col-search   { flex: 1.5 1 150px; min-width: 120px; }
        .la-col-campaign { flex: 1.2 1 130px; min-width: 110px; }
        .la-col-utype    { flex: 1 1 100px; min-width: 90px; }
        .la-col-date     { flex: 1 1 105px; min-width: 95px; }
        .la-col-actions  { flex: 0 0 auto; display: flex; flex-direction: row; align-items: flex-end; gap: 6px; margin-left: auto; }
        .la-filter-label {
            font-size: 12px; font-weight: 600; color: #334155;
            margin: 0; white-space: nowrap; letter-spacing: 0.1px;
        }

        /* Search */
        .la-search-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .la-search-icon { position: absolute; left: 11px; display: flex; align-items: center; pointer-events: none; color: #94A3B8; }
        .la-search-input {
            width: 100%; height: 40px;
            padding: 0 32px 0 32px;
            border: 1px solid #CBD5E1; border-radius: 9px;
            font-size: 13px; color: #0F172A;
            background: #FFFFFF; outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }
        .la-search-input:focus { border-color: var(--lg-accent); box-shadow: 0 0 0 3px rgba(23,105,255,0.10); }
        .la-search-input::placeholder { color: #94A3B8; font-size: 12.5px; }
        .la-search-clear {
            position: absolute; right: 10px; background: none; border: none;
            color: #94A3B8; font-size: 16px; cursor: pointer;
            display: none; line-height: 1; padding: 0;
        }

        /* Select */
        .la-select-wrap { position: relative; width: 100%; }
        .la-select-input {
            width: 100%; height: 40px;
            padding: 0 28px 0 10px;
            border: 1px solid #CBD5E1; border-radius: 9px;
            font-size: 13px; color: #0F172A;
            background: #FFFFFF; appearance: none; outline: none;
            cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }
        .la-select-input:focus { border-color: var(--lg-accent); box-shadow: 0 0 0 3px rgba(23,105,255,0.10); }
        .la-select-arrow { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #94A3B8; pointer-events: none; }

        /* Date */
        .la-date-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .la-date-input {
            width: 100%; height: 40px;
            padding: 0 30px 0 10px;
            border: 1px solid #CBD5E1; border-radius: 9px;
            font-size: 12.5px; color: #0F172A;
            background: #FFFFFF; outline: none; cursor: pointer;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }
        .la-date-input:focus { border-color: var(--lg-accent); box-shadow: 0 0 0 3px rgba(23,105,255,0.10); }
        .la-date-input::placeholder { color: #94A3B8; font-size: 12.5px; }
        .la-date-icon { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #94A3B8; cursor: pointer; display: flex; align-items: center; }

        /* Buttons */
        .la-btn-filter {
            height: 40px; padding: 0 16px; border-radius: 9px;
            background: var(--lg-accent); border: 1px solid var(--lg-accent); color: #FFFFFF;
            font-size: 13px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            white-space: nowrap; transition: all 0.15s ease;
        }
        .la-btn-filter:hover { background: #1255D4; border-color: #1255D4; }
        .la-btn-reset {
            height: 40px; width: 40px; border-radius: 9px;
            background: #FFFFFF; border: 1px solid #CBD5E1; color: #475569;
            font-size: 14px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            transition: all 0.15s ease; flex-shrink: 0;
        }
        .la-btn-reset:hover { background: #F8FAFC; border-color: #94A3B8; color: var(--lg-accent); }

        /* ---- Table Card ---- */
        .la-table-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(15,23,42,0.04);
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }
        .la-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Top bar */
        .la-table-top-bar {
            display: flex; justify-content: flex-end;
            align-items: center; padding: 12px 20px;
            border-bottom: 1px solid #F1F5F9;
            background: #FFFFFF;
        }
        .la-table-top-bar .dataTables_length { margin: 0; }
        .la-table-top-bar .dataTables_length label {
            font-size: 13px; color: #64748B; font-weight: 500;
            display: flex; align-items: center; gap: 8px; margin: 0;
        }
        .la-table-top-bar .dataTables_length select {
            height: 34px; padding: 0 10px;
            border: 1px solid #CBD5E1; border-radius: 7px;
            background: #FFFFFF; font-size: 13px; color: #0F172A;
            outline: none; cursor: pointer; appearance: auto;
        }

        /* Table */
        .la-table-card .table { width: 100% !important; margin: 0 !important; border-collapse: collapse; }
        .la-table-card .table thead th {
            background: #F8FAFC; color: #475569;
            font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.6px;
            padding: 14px 18px; border-bottom: 1px solid #E2E8F0;
            border-top: none; white-space: nowrap; vertical-align: middle;
        }
        .la-table-card .table thead th:first-child { padding-left: 24px; }
        .la-table-card .table thead th:last-child  { padding-right: 24px; }
        .la-table-card .table tbody td {
            padding: 14px 18px; font-size: 13.5px; color: #334155;
            border-bottom: 1px solid #F1F5F9; vertical-align: middle;
            white-space: nowrap;
        }
        .la-table-card .table tbody td:first-child { padding-left: 24px; }
        .la-table-card .table tbody td:last-child  { padding-right: 24px; }
        .la-table-card .table tbody tr:last-child td { border-bottom: none; }
        .la-table-card .table tbody tr:hover td { background: #F8FAFC; }

        /* Lead ID */
        .la-lead-id-link { color: var(--lg-accent) !important; font-weight: 600; text-decoration: none; cursor: pointer; }
        .la-lead-id-link:hover { text-decoration: underline; }

        /* Dash */
        .la-dash { color: #94A3B8; }

        /* Badges */
        .la-badge { display: inline-flex; align-items: center; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .la-badge-blue   { background: #EFF6FF; color: var(--lg-accent); border: 1px solid #DBEAFE; }
        .la-badge-green  { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .la-badge-purple { background: #F5F3FF; color: #7C3AED; border: 1px solid #DDD6FE; }
        .la-badge-gray   { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }

        /* Date text */
        .la-date-text { color: #64748B; font-size: 13px; }

        /* ---- Pagination Footer ---- */
        .la-table-bottom-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 24px; border-top: 1px solid #F1F5F9;
            gap: 12px; flex-wrap: wrap; box-sizing: border-box; width: 100%; overflow: hidden;
            background: #FFFFFF;
        }
        .la-entries-info { font-size: 13px; color: #64748B; font-weight: 500; white-space: nowrap; }
        .la-pagination { flex-shrink: 0; }
        .la-pagination ul.pagination, .la-pagination .pagination {
            display: flex !important; align-items: center !important;
            gap: 5px !important; margin: 0 !important; padding: 0 !important;
            list-style: none !important; flex-wrap: nowrap;
        }
        .la-pagination .pagination li.paginate_button,
        .la-pagination .paginate_button {
            display: flex !important; align-items: center !important; justify-content: center !important;
            padding: 0 !important; margin: 0 !important; border: none !important;
            background: transparent !important; list-style: none !important;
            box-shadow: none !important; border-radius: 0 !important;
        }
        .la-pagination .pagination li.paginate_button > a,
        .la-pagination .pagination li.paginate_button > span,
        .la-pagination .paginate_button > a,
        .la-pagination .paginate_button > span {
            display: inline-flex !important; align-items: center !important; justify-content: center !important;
            min-width: 36px !important; height: 36px !important; padding: 0 8px !important;
            border-radius: 8px !important; border: 1px solid #E2E8F0 !important;
            background: #FFFFFF !important; color: #334155 !important;
            font-size: 13px !important; font-weight: 500 !important;
            cursor: pointer !important; transition: all 0.15s ease !important;
            text-decoration: none !important; white-space: nowrap !important; box-sizing: border-box !important;
        }
        .la-pagination .pagination li.paginate_button:not(.active):not(.disabled) > a:hover,
        .la-pagination .paginate_button:not(.current):not(.disabled) > a:hover {
            background: #EFF6FF !important; border-color: #BFDBFE !important; color: var(--lg-accent) !important;
        }
        .la-pagination .pagination li.paginate_button.active > a,
        .la-pagination .paginate_button.current > a,
        .la-pagination .paginate_button.current {
            background: var(--lg-accent) !important; border-color: var(--lg-accent) !important;
            color: #FFFFFF !important; font-weight: 700 !important;
            box-shadow: 0 2px 6px rgba(23,105,255,0.28) !important;
        }
        .la-pagination .pagination li.paginate_button.disabled > a,
        .la-pagination .paginate_button.disabled > a,
        .la-pagination .paginate_button.disabled {
            background: #F8FAFC !important; border-color: #E2E8F0 !important;
            color: #CBD5E1 !important; cursor: not-allowed !important;
            pointer-events: none !important; box-shadow: none !important;
        }

        /* Lead Details Modal Fixes */
        body.modal-open {
            padding-right: 0 !important;
            overflow: hidden !important;
        }
        .modal-backdrop {
            background-color: rgba(15, 23, 42, 0.5) !important;
            backdrop-filter: blur(3px) !important;
        }
        .lead-details-modal {
            z-index: 1060 !important;
            padding-right: 0 !important;
        }
        .lead-details-modal .modal-dialog {
            max-width: 920px;
            margin: 20px auto;
            width: 94%;
            max-height: calc(100vh - 40px);
            display: flex;
            align-items: center;
        }
        .lead-details-modal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            background: #FFFFFF;
            width: 100%;
            max-height: calc(100vh - 40px);
            display: flex;
            flex-direction: column;
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .la-col-search   { flex: 1 1 100%; }
            .la-col-campaign { flex: 1 1 calc(50% - 6px); }
            .la-col-utype    { flex: 1 1 calc(50% - 6px); }
            .la-col-date     { flex: 1 1 calc(50% - 6px); }
            .la-col-actions  { margin-left: 0; width: 100%; justify-content: flex-end; }
        }
        @media (max-width: 768px) {
            .la-page { padding: 14px; }
            .la-filters-row { flex-wrap: wrap; gap: 10px; }
            .la-col-search, .la-col-campaign, .la-col-utype, .la-col-date, .la-col-actions { flex: 1 1 100%; }
            .la-col-actions { flex-direction: row; }
            .la-table-bottom-bar { flex-direction: column; align-items: flex-start; }
            .la-pagination ul.pagination, .la-pagination .pagination { flex-wrap: wrap !important; gap: 4px !important; }
        }
    </style>
@endpush

@section('content')
<div class="la-page">

    {{-- PAGE HEADER --}}
    <div class="la-header-card">
        <div class="la-header-left">
            <div class="la-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 1 21 5 17 9"/>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                    <polyline points="7 23 3 19 7 15"/>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                </svg>
            </div>
            <div>
                <h1 class="la-page-title">Allocations History</h1>
                <p class="la-page-subtitle">View and track lead allocations across campaigns and users.</p>
            </div>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="la-filters-card">
        <div class="la-filters-row">

            {{-- 1. Search --}}
            <div class="la-filter-col la-col-search">
                <label class="la-filter-label" for="find_q">Search</label>
                <div class="la-search-wrap">
                    <span class="la-search-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </span>
                    <input type="text" id="find_q" class="la-search-input" placeholder="Search by Lead ID, campaign, user..." autocomplete="off">
                    <button type="button" class="la-search-clear" id="la_search_clear_btn">&times;</button>
                </div>
            </div>

            {{-- 2. Campaign Name --}}
            <div class="la-filter-col la-col-campaign">
                <label class="la-filter-label" for="find_campaign">Campaign Name</label>
                <div class="la-select-wrap">
                    <select id="find_campaign" class="la-select-input">
                        <option value="">All Campaigns</option>
                        @foreach($Campaigns as $Campaign)
                            <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                        @endforeach
                    </select>
                    <svg class="la-select-arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>

            {{-- 3. User Type --}}
            <div class="la-filter-col la-col-utype">
                <label class="la-filter-label" for="find_user_type">User Type</label>
                <div class="la-select-wrap">
                    <select id="find_user_type" class="la-select-input">
                        <option value="">All Types</option>
                        <option value="Staff">Staff</option>
                        <option value="Sales Person">Sales Person</option>
                    </select>
                    <svg class="la-select-arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>

            {{-- 4. From Date --}}
            <div class="la-filter-col la-col-date">
                <label class="la-filter-label" for="from_date">From Date</label>
                <div class="la-date-wrap">
                    <input type="text" id="from_date" class="la-date-input" placeholder="From Date" autocomplete="off" readonly>
                    <span class="la-date-icon" id="from_date_icon" title="Open Calendar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                </div>
            </div>

            {{-- 5. To Date --}}
            <div class="la-filter-col la-col-date">
                <label class="la-filter-label" for="to_date">To Date</label>
                <div class="la-date-wrap">
                    <input type="text" id="to_date" class="la-date-input" placeholder="To Date" autocomplete="off" readonly>
                    <span class="la-date-icon" id="to_date_icon" title="Open Calendar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                </div>
            </div>

            {{-- 6 & 7. Filter + Reset --}}
            <div class="la-filter-col la-col-actions">
                <button type="button" class="la-btn-filter" id="search-btn" title="Apply Filters">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filter
                </button>
                <button type="button" class="la-btn-reset" id="reset-btn" title="Reset Filters">
                    <i class="fas fa-rotate-left"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="la-table-card">
        <div class="la-table-wrapper">
            <table class="table display" id="leads_table" style="width:100%">
                <thead>
                    <tr>
                        <th style="min-width: 90px;">Lead ID</th>
                        <th style="min-width: 170px;">Campaign Name</th>
                        <th style="min-width: 100px;">User Type</th>
                        <th style="min-width: 150px;">From</th>
                        <th style="min-width: 150px;">To</th>
                        <th style="min-width: 140px;">Allocation Type</th>
                        <th style="min-width: 150px;">Allocated By</th>
                        <th style="min-width: 155px;">Allocated On</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

{{-- Lead Detail Modal --}}
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content lead-content"></div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script type="text/javascript">

    function leadDetail(id) {
        var url = "{{ route('leadDetail', [':id']) }}";
        url = url.replace(':id', id);
        $('.modal-content').html('');
        $.ajax({
            type: "GET", url: url, dataType: "html",
            success: function(html) {
                $('.modal-content').html(html);
                $("#lead-detail").modal("show");
                $("select").select2();
            }
        });
    }

    $(document).ready(function () {

        var dataTable = $("#leads_table").DataTable({
            "processing": true,
            "serverSide": true,
            "searching": false,
            "responsive": false,
            "scrollX": false,
            "pageLength": {{ env('BACKEND_PAGINATION', 30) }},
            "lengthMenu": [[10, 20, 30, 50, 75, 100], [10, 20, 30, 50, 75, 100]],
            "ajax": {
                "url": "{{ route('allocationsData') }}",
                "dataType": "json",
                "type": "POST",
                "data": function (data) {
                    data._token          = "{{ csrf_token() }}";
                    data.find_q          = $('#find_q').val();
                    data.find_campaign   = $('#find_campaign').val();
                    data.find_user_type  = $('#find_user_type').val();
                    data.from_date       = $('#from_date').val();
                    data.to_date         = $('#to_date').val();
                }
            },
            "dom": '<"la-table-top-bar"<"dataTables_length"l>>t<"la-table-bottom-bar"<"la-entries-info"i><"la-pagination"p>>',
            "fnDrawCallback": function () {
                $('[data-toggle="tooltip"]').tooltip({ html: true });
            },
            "language": {
                "info":         "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty":    "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "lengthMenu":   "Show _MENU_ entries",
                "processing":   "Loading...",
                "zeroRecords":  "No matching records found",
                "paginate": { "previous": "&#8249;", "next": "&#8250;" }
            },
            "columns": [
                { "data": "lead_id",        "orderable": true,  "width": "90px"  },
                { "data": "campaign_name",  "orderable": true,  "width": "18%"   },
                { "data": "user_type",      "orderable": true,  "width": "90px"  },
                { "data": "from_user",      "orderable": true,  "width": "14%"   },
                { "data": "to_user",        "orderable": true,  "width": "14%"   },
                { "data": "allocation_type","orderable": true,  "width": "130px" },
                { "data": "added_by",       "orderable": true,  "width": "15%"   },
                { "data": "created_at",     "orderable": true,  "width": "145px",
                    "render": function(data) {
                        if (!data) return '<span class="la-dash">—</span>';
                        var d = new Date(data);
                        if (isNaN(d)) return '<span class="la-date-text">' + data + '</span>';
                        var opts = { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true };
                        return '<span class="la-date-text">' + d.toLocaleString('en-IN', opts) + '</span>';
                    }
                }
            ],
            "order": [[0, "asc"]]
        });

        // Flatpickr
        var fromPicker = flatpickr("#from_date", {
            dateFormat: "Y-m-d", allowInput: false, clickOpens: true,
            onChange: function(sel, str) {
                if (str) toPicker.set('minDate', str);
                dataTable.draw();
            }
        });
        var toPicker = flatpickr("#to_date", {
            dateFormat: "Y-m-d", allowInput: false, clickOpens: true,
            onChange: function(sel, str) {
                if (str) fromPicker.set('maxDate', str);
                dataTable.draw();
            }
        });
        $('#from_date_icon').on('click', function () { fromPicker.open(); });
        $('#to_date_icon').on('click',   function () { toPicker.open();   });

        // Search clear
        $('#find_q').on('input', function () {
            $('#la_search_clear_btn').css('display', $(this).val() ? 'flex' : 'none');
        });
        $('#la_search_clear_btn').on('click', function () {
            $('#find_q').val(''); $(this).hide(); dataTable.draw();
        });

        // Filter
        $('#search-btn').on('click', function () { dataTable.draw(); });
        $('#find_q').on('keyup', function(e) { if (e.which === 13) dataTable.draw(); });

        // Dropdown auto-apply
        $('#find_campaign, #find_user_type').on('change', function () { dataTable.draw(); });

        // Reset
        $('#reset-btn').on('click', function () {
            $('#find_q').val(''); $('#la_search_clear_btn').hide();
            $('#find_campaign').val(''); $('#find_user_type').val('');
            fromPicker.clear(); toPicker.clear();
            fromPicker.set('maxDate', null); toPicker.set('minDate', null);
            dataTable.draw();
        });

        dataTable.on('page.dt', function () {
            $('html, body').animate({ scrollTop: $(".la-table-card").offset().top - 20 }, 300);
        });

        $.fn.dataTable.ext.errMode = 'none';
    });

</script>
@endpush

