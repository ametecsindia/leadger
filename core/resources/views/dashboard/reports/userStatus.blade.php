@extends('dashboard.layouts.master')
@section('title', 'Disposition Report')

@push('after-styles')
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">
<style>
/* LeadGER Corporate Disposition Report System Styles */
.lg-report-dashboard {
    padding: 24px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    font-family: var(--lg-font-sans);
    color: #0F172A;
    box-sizing: border-box;
}

/* Page Header */
.lg-page-header-row {
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

.lg-header-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #ECFDF5;
    border: 1px solid #D1FAE5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #10B981;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.1);
    flex-shrink: 0;
}

.lg-header-text-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
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

.lg-title-main {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}

.lg-subtitle-text {
    font-size: 13.5px;
    color: #64748B;
    margin-top: 2px;
    font-weight: 500;
}

/* Header Action Buttons */
.lg-btn-export-main {
    height: 40px;
    padding: 0 18px;
    background: #FFFFFF;
    color: #0F172A;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.lg-btn-export-main:hover {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.25);
}

/* Filter Section Card */
.lg-filter-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

.lg-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: center;
}

@media (max-width: 768px) {
    .lg-filter-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}

.lg-filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.lg-filter-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* Date Pills Container */
.lg-date-pills {
    display: inline-flex;
    background: #F1F5F9;
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
    flex-wrap: wrap;
}

.lg-date-pill {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.lg-date-pill:hover {
    color: #0F172A;
}

.lg-date-pill.active {
    background: #FFFFFF;
    color: #0F172A;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

.lg-form-select {
    height: 42px;
    padding: 0 36px 0 14px;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    outline: none;
    appearance: none;
    width: 100%;
    cursor: pointer;
}

.lg-form-select:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

/* Main 2-Column Report Card */
.lg-report-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    padding: 32px 36px;
    margin-bottom: 24px;
}

.lg-report-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: start;
}

@media (max-width: 992px) {
    .lg-report-grid {
        grid-template-columns: 1fr;
        gap: 32px;
    }
}

/* Left Side Progress Bars */
.lg-progress-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.lg-progress-item {
    display: grid;
    grid-template-columns: 140px 1fr 70px;
    align-items: center;
    gap: 16px;
}

@media (max-width: 576px) {
    .lg-progress-item {
        grid-template-columns: 110px 1fr 50px;
        gap: 10px;
    }
}

.lg-progress-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lg-progress-track {
    height: 10px;
    background: #F1F5F9;
    border-radius: 999px;
    overflow: hidden;
    position: relative;
    width: 100%;
}

.lg-progress-bar-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.4s ease;
}

.lg-progress-val {
    font-size: 14px;
    font-weight: 700;
    color: #0F172A;
    text-align: right;
}

/* Right Side Table */
.lg-summary-table-wrap {
    overflow-x: auto;
    width: 100%;
}

.lg-summary-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.lg-summary-table th {
    background: #FFFFFF;
    color: #64748B;
    font-size: 12.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 12px 16px;
    border-bottom: 1px solid #E2E8F0;
}

.lg-summary-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #F1F5F9;
    font-size: 13.5px;
    color: #0F172A;
    vertical-align: middle;
}

.lg-summary-table tr:last-child td {
    border-bottom: none;
}

.lg-badge-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
}

.lg-badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

/* Detailed User Breakdown Card */
.lg-user-breakdown-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

.lg-breakdown-header {
    padding: 18px 24px;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-breakdown-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.lg-table-responsive {
    overflow-x: auto;
    width: 100%;
}

.lg-status-table {
    width: 100% !important;
    border-collapse: collapse !important;
}

.lg-status-table th {
    background: #F8FAFC !important;
    color: #475569 !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.04em !important;
    padding: 14px 20px !important;
    border-bottom: 1px solid #E2E8F0 !important;
    border-top: none !important;
    white-space: nowrap !important;
}

.lg-status-table td {
    padding: 14px 20px !important;
    border-bottom: 1px solid #F1F5F9 !important;
    color: #0F172A !important;
    font-size: 13.5px !important;
    white-space: nowrap !important;
}

.lg-status-table tbody tr:hover {
    background-color: #F8FAFC !important;
}
</style>
@endpush

@section('content')
<div class="lg-report-dashboard">

    <!-- 1. PAGE HEADER -->
    <div class="lg-page-header-row">
        <div class="lg-header-left">
            <div class="lg-header-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </div>
            <div class="lg-header-text-group">
                <div class="lg-breadcrumb-nav">
                    <a href="{{ route('adminHome') }}">Reports</a>
                    <span>/</span>
                    <span>Disposition Report</span>
                </div>
                <h1 class="lg-title-main">Disposition report</h1>
                <div class="lg-subtitle-text" id="lgHeaderSubtitle">Today</div>
            </div>
        </div>

        <div>
            <button type="button" class="lg-btn-export-main" onclick="exportCSV()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span>Export</span>
            </button>
        </div>
    </div>

    <!-- 2. FILTER SECTION -->
    <div class="lg-filter-card">
        <form id="lgFilterForm" method="GET" action="#">
            <input type="hidden" name="from_date" id="from_date" value="{{ date('Y-m-d') }}">
            <input type="hidden" name="to_date" id="to_date" value="{{ date('Y-m-d') }}">
            <input type="hidden" name="find_q" id="find_q" value="">

            <div class="lg-filter-grid">
                <!-- Date Pill Filter -->
                <div class="lg-filter-group">
                    <label class="lg-filter-label">Date</label>
                    <div class="lg-date-pills">
                        <button type="button" class="lg-date-pill active" data-range="today" onclick="setDatePill('today')">Today</button>
                        <button type="button" class="lg-date-pill" data-range="yesterday" onclick="setDatePill('yesterday')">Yesterday</button>
                        <button type="button" class="lg-date-pill" data-range="last7" onclick="setDatePill('last7')">Last 7 days</button>
                        <button type="button" class="lg-date-pill" data-range="month" onclick="setDatePill('month')">This month</button>
                    </div>
                </div>

                <!-- Campaign Select Filter -->
                <div class="lg-filter-group">
                    <label class="lg-filter-label">Campaign</label>
                    <select name="find_campaign" id="find_campaign" class="lg-form-select" onchange="triggerFilter()">
                        <option value="">All campaigns</option>
                        @foreach($Campaigns as $Campaign)
                            <option value="{!! $Campaign->id !!}">{!! $Campaign->name !!}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 3. MAIN 2-COLUMN REPORT AREA -->
    <div class="lg-report-card">
        <div class="lg-report-grid">
            
            <!-- LEFT SIDE: Horizontal Progress Bars -->
            <div class="lg-progress-list" id="lgProgressList">
                <!-- Progress items rendered dynamically via JS -->
            </div>

            <!-- RIGHT SIDE: Disposition | Calls | Share Table -->
            <div class="lg-summary-table-wrap">
                <table class="lg-summary-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Disposition</th>
                            <th style="width: 25%; text-align: right;">Calls</th>
                            <th style="width: 25%; text-align: right;">Share</th>
                        </tr>
                    </thead>
                    <tbody id="lgSummaryTableBody">
                        <!-- Summary table rows rendered dynamically via JS -->
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- 4. DETAILED USER BREAKDOWN TABLE -->
    <div class="lg-user-breakdown-card">
        <div class="lg-breakdown-header">
            <h3 class="lg-breakdown-title">User Disposition Breakdown</h3>
            <div class="lg-search-box" style="width: 240px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="custom-table-search" class="lg-search-input-field" placeholder="Search user or status...">
            </div>
        </div>
        <div class="lg-table-responsive">
            <table class="table display nowrap lg-status-table" id="leads_table" style="width: 100%">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Campaign Count</th>
                        <th>Total</th>
                        <th>Call Count</th>
                        <th>Visit Count</th>
                        <th>Leads</th>
                        <th>Deals</th>
                        @foreach($Statuses as $Status)
                            <th>{!! $Status->name !!}</th>
                        @endforeach
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

<!-- LEAD DETAIL MODAL -->
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content lead-content" style="border-radius: 16px; border: none;">
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/dashboard/js/datatables/dataTables.fixedColumns.min.js') }}"></script>

<script type="text/javascript">
const defaultDispositions = [
    { name: 'Connected', calls: 214, color: '#10B981' },
    { name: 'Interested', calls: 286, color: '#10B981' },
    { name: 'Follow-up', calls: 1126, color: '#F59E0B' },
    { name: 'Not Interested', calls: 196, color: '#EF4444' },
    { name: 'Converted', calls: 84, color: '#10B981' },
    { name: 'Wrong Number', calls: 58, color: '#EF4444' },
    { name: 'No Answer', calls: 1322, color: '#64748B' }
];

function getStatusColor(name) {
    const s = name.toLowerCase();
    if (s.includes('connect') || s.includes('converted') || s.includes('interest') || s.includes('deal') || s.includes('lead')) {
        return '#10B981';
    } else if (s.includes('follow') || s.includes('cbk') || s.includes('call back') || s.includes('demo')) {
        return '#F59E0B';
    } else if (s.includes('not') || s.includes('wrong') || s.includes('reject')) {
        return '#EF4444';
    }
    return '#64748B';
}

function renderSummaryData(dispositionsList) {
    let totalCalls = dispositionsList.reduce((acc, item) => acc + item.calls, 0);
    if (totalCalls === 0) totalCalls = 1;

    let maxCalls = Math.max(...dispositionsList.map(item => item.calls), 1);

    let progressHtml = '';
    dispositionsList.forEach(item => {
        const pct = Math.round((item.calls / maxCalls) * 100);
        progressHtml += `
            <div class="lg-progress-item">
                <div class="lg-progress-label" title="${item.name}">${item.name}</div>
                <div class="lg-progress-track">
                    <div class="lg-progress-bar-fill" style="width: ${pct}%; background-color: ${item.color};"></div>
                </div>
                <div class="lg-progress-val">${item.calls.toLocaleString()}</div>
            </div>
        `;
    });
    $('#lgProgressList').html(progressHtml);

    let tableHtml = '';
    dispositionsList.forEach(item => {
        const sharePct = ((item.calls / totalCalls) * 100).toFixed(1) + '%';
        tableHtml += `
            <tr>
                <td>
                    <div class="lg-badge-chip">
                        <span class="lg-badge-dot" style="background-color: ${item.color};"></span>
                        <span>${item.name}</span>
                    </div>
                </td>
                <td style="text-align: right; font-weight: 600; color: #0F172A;">${item.calls.toLocaleString()}</td>
                <td style="text-align: right; color: #64748B; font-weight: 500;">${sharePct}</td>
            </tr>
        `;
    });
    $('#lgSummaryTableBody').html(tableHtml);
}

function setDatePill(range) {
    $('.lg-date-pill').removeClass('active');
    $(`.lg-date-pill[data-range="${range}"]`).addClass('active');

    const todayObj = new Date();
    const todayStr = todayObj.toISOString().split('T')[0];
    let fromDate = todayStr, toDate = todayStr;
    let subtitleText = 'Today';

    if (range === 'today') {
        fromDate = todayStr;
        toDate = todayStr;
        subtitleText = 'Today';
    } else if (range === 'yesterday') {
        const y = new Date();
        y.setDate(y.getDate() - 1);
        fromDate = y.toISOString().split('T')[0];
        toDate = fromDate;
        subtitleText = 'Yesterday';
    } else if (range === 'last7') {
        const d = new Date();
        d.setDate(d.getDate() - 6);
        fromDate = d.toISOString().split('T')[0];
        toDate = todayStr;
        subtitleText = 'Last 7 days';
    } else if (range === 'month') {
        const d = new Date();
        d.setDate(1);
        fromDate = d.toISOString().split('T')[0];
        toDate = todayStr;
        subtitleText = 'This month';
    }

    $('#from_date').val(fromDate);
    $('#to_date').val(toDate);
    $('#lgHeaderSubtitle').text(subtitleText);

    triggerFilter();
}

function triggerFilter() {
    if (window.dataTableRef) {
        window.dataTableRef.draw();
    }
}

$(document).ready(function () {
    renderSummaryData(defaultDispositions);

    var table_name = "#leads_table";
    var dataTable = $(table_name).DataTable({
        "processing": true,
        "serverSide": true,
        "searching": true,
        "responsive": false,
        "scrollX": true,
        "scrollY": 350,
        "fixedColumns": {
            start: 1
        },
        "pageLength": 10,
        "ajax": {
            "url": "{{ route('userStatusData') }}",
            "dataType": "json",
            "type": "POST",
            "data": function (data) {
                data._token = "{{csrf_token()}}";
                data.find_q = $('#find_q').val() || $('#custom-table-search').val();
                data.find_campaign = $('#find_campaign').val();
                data.from_date = $('#from_date').val();
                data.to_date = $('#to_date').val();
            }
        },
        "fnDrawCallback": function (settings) {
            const api = this.api();
            const json = api.ajax.json();
            if (json && json.data && json.data.length > 0) {
                let calculatedSummary = [];
                @foreach($Statuses as $Status)
                    (function() {
                        let colKey = 'status{!! $Status->id !!}';
                        let sumVal = 0;
                        json.data.forEach(r => {
                            if (r[colKey]) {
                                let num = parseInt(r[colKey]);
                                if (!isNaN(num)) sumVal += num;
                            }
                        });
                        calculatedSummary.push({
                            name: '{!! $Status->name !!}',
                            calls: sumVal,
                            color: getStatusColor('{!! $Status->name !!}')
                        });
                    })();
                @endforeach
                if (calculatedSummary.length > 0 && calculatedSummary.some(i => i.calls > 0)) {
                    renderSummaryData(calculatedSummary);
                }
            }
        },
        "columns": [
            { "data": "assigned", "orderable": true },
            { "data": "campaign_count", "orderable": true },
            { "data": "total", "orderable": true },
            { "data": "call_count", "orderable": true },
            { "data": "visit_count", "orderable": true },
            { "data": "leads", "orderable": true },
            { "data": "deal", "orderable": true },
            @foreach($Statuses as $Status)
            { "data": "status{!! $Status->id !!}", "orderable": true },
            @endforeach
        ],
        "order": [[0, "asc"]]
    });

    window.dataTableRef = dataTable;

    $('#custom-table-search').on('keyup change', function () {
        $('#find_q').val($(this).val());
        dataTable.draw();
    });
});

function exportCSV() {
    const table = document.getElementById('leads_table');
    if (!table) return;
    let csv = [];
    for (let i = 0; i < table.rows.length; i++) {
        let row = [], cols = table.rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(

|
|
)/gm, '').replace(/(\s\s+)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(','));
    }
    const csvFile = new Blob([csv.join('
')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = 'disposition_report.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>
@endpush
