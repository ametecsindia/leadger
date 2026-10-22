@extends('dashboard.layouts.master')
@section('title', 'Inbound Calls - LeadGER')

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
{{-- Report page design (header, filter card, table card), shared with Call Report --}}
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/lg-report-page.css') }}?v=4">
<style>
.ic-page .cr-header-icon { width:44px; height:44px; border-radius:12px; font-size:18px; }
.ic-page .cr-date-input { cursor:text; }
.ic-page .cr-date-wrap.has-error .cr-date-input { border-color:#DC2626; }
.ic-field-error { display:none; margin-top:5px; font-size:12px; font-weight:500; color:#DC2626; max-width:220px; }
.ic-field-error.show { display:block; }
/* sticky header inside the scrolling table */
.ic-page table.cr-table.cr-dt thead th { position:sticky; top:0; z-index:2; }
.ic-page .cr-table-wrap { max-height:calc(100vh - 250px); }
.ic-mono { font-family:monospace; }
.ic-muted { color:#64748B; }
.ic-dash { color:#94A3B8; }
.ic-state { padding:44px 16px !important; text-align:center; white-space:normal !important; color:#64748B; font-size:13px; }
.ic-state i.ic-state-icon { display:block; font-size:26px; margin-bottom:10px; color:#94A3B8; }
.ic-state.is-error i.ic-state-icon { color:#EF4444; }
.ic-retry { margin-top:12px; height:34px; padding:0 16px; border-radius:8px; border:1px solid var(--lg-accent); background:#fff; color:var(--lg-accent); font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:7px; }
.ic-retry:hover { background:#EFF6FF; }
/* Excel / CSV export: same design as Agent Performance (green Excel, outline CSV) */
.ic-export-btn { height:38px; padding:0 16px; border-radius:8px; font-family:inherit; font-size:14px; font-weight:600; line-height:1; white-space:nowrap; display:inline-flex; align-items:center; gap:8px; cursor:pointer; transition:all 0.15s ease; box-sizing:border-box; }
.ic-export-btn.excel { background:#12A66A; border:1px solid #12A66A; color:#FFFFFF; }
.ic-export-btn.excel:hover:not(:disabled) { background:#0E8C59; border-color:#0E8C59; box-shadow:0 3px 8px rgba(18,166,106,0.25); }
.ic-export-btn.csv { background:#FFFFFF; border:1px solid #CBD5E1; color:#0F172A; }
.ic-export-btn.csv:hover:not(:disabled) { background:#F8FAFC; border-color:#94A3B8; }
.ic-export-btn:focus-visible { outline:2px solid var(--lg-accent); outline-offset:2px; }
.ic-export-btn:disabled { opacity:0.55; cursor:not-allowed; }
.ic-export-btn .ic-spinner { display:none; width:14px; height:14px; border:2px solid currentColor; border-right-color:transparent; border-radius:50%; animation:icSpin 0.7s linear infinite; }
.ic-export-btn.is-loading .ic-spinner { display:inline-block; }
.ic-export-btn.is-loading svg { display:none; }
@keyframes icSpin { to { transform:rotate(360deg); } }
/* Customer mobile: link to call the customer back */
.ic-call-link { display:inline-flex; align-items:center; gap:6px; color:var(--lg-accent) !important; font-weight:600; text-decoration:none; border-radius:4px; }
.ic-call-link:hover { text-decoration:underline; color:var(--lg-accent-hover, #0F52CC) !important; }
.ic-call-link:focus-visible { outline:2px solid var(--lg-accent); outline-offset:2px; }
.ic-call-link svg { flex-shrink:0; }
/* Several campaigns: the agent picks where to call */
.ic-chooser-backdrop { position:fixed; inset:0; background:rgba(15,23,42,0.45); z-index:1060; display:flex; align-items:center; justify-content:center; padding:16px; }
.ic-chooser { background:#fff; border-radius:12px; box-shadow:0 20px 50px rgba(15,23,42,0.25); width:100%; max-width:360px; padding:18px; }
.ic-chooser-title { font-size:15px; font-weight:700; color:#0F172A; margin:0 0 4px; }
.ic-chooser-sub { font-size:12.5px; color:#64748B; margin:0 0 14px; }
.ic-chooser-opt { display:flex; align-items:center; gap:8px; width:100%; padding:10px 12px; margin-bottom:8px; border:1px solid #E2E8F0; border-radius:8px; color:#0F172A !important; font-size:13.5px; font-weight:600; text-decoration:none !important; }
.ic-chooser-opt:hover, .ic-chooser-opt:focus-visible { border-color:var(--lg-accent); background:#EFF6FF; color:var(--lg-accent) !important; outline:none; }
/* Admins: pipeline status line below the table */
.ic-pipeline { margin:10px 4px 0; font-size:12px; color:#64748B; display:flex; flex-wrap:wrap; align-items:center; gap:4px 8px; }
.ic-pipeline code { font-size:11.5px; color:#475569; background:#F1F5F9; padding:1px 6px; border-radius:4px; word-break:break-all; }
.ic-pipeline-sep { color:#CBD5E1; }
.ic-pipeline-err { color:#B45309; }
.ic-copy-btn { height:24px; padding:0 8px; border:1px solid #CBD5E1; border-radius:6px; background:#fff; color:#475569; font-size:11.5px; font-weight:600; display:inline-flex; align-items:center; gap:4px; cursor:pointer; }
.ic-copy-btn:hover { border-color:var(--lg-accent); color:var(--lg-accent); }
.ic-copy-btn:focus-visible { outline:2px solid var(--lg-accent); outline-offset:2px; }
.ic-chooser-close { margin-top:4px; width:100%; height:34px; border:none; background:transparent; color:#64748B; font-size:13px; font-weight:600; cursor:pointer; }
</style>
@endpush

@section('content')
<div class="cr-canvas ic-page">

    {{-- PAGE HEADER --}}
    <div class="cr-header">
        <div class="cr-header-left">
            <div class="cr-header-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 2 16 8 22 8"/><line x1="22" y1="2" x2="16" y2="8"/><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
                <h1 class="cr-header-title">Inbound Calls</h1>
                <div class="cr-header-sub" id="icPeriodLabel">Today &mdash; {{ date('d M Y') }}</div>
            </div>
        </div>
    </div>

    {{-- FILTER CARD --}}
    <div class="cr-filter-card">
        <div class="cr-filter-row">
            <div class="cr-filter-group">
                <span class="cr-filter-label">Date Range</span>
                <div class="cr-date-pills">
                    <button type="button" class="cr-pill active" data-range="today">Today</button>
                    <button type="button" class="cr-pill" data-range="yesterday">Yesterday</button>
                    <button type="button" class="cr-pill" data-range="last7">Last 7 days</button>
                    <button type="button" class="cr-pill" data-range="thismonth">This month</button>
                </div>
            </div>

            <div class="cr-filter-group">
                <label class="cr-filter-label" for="icFromDate">From Date</label>
                <div class="cr-date-wrap" id="icFromWrap">
                    <input type="text" id="icFromDate" class="cr-date-input" placeholder="YYYY-MM-DD" autocomplete="off" inputmode="numeric" aria-describedby="icFromError">
                    <span class="cr-date-icon" id="icFromDateIcon" title="Open Calendar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                </div>
                <div class="ic-field-error" id="icFromError" role="alert"></div>
            </div>

            <div class="cr-filter-group">
                <label class="cr-filter-label" for="icToDate">To Date</label>
                <div class="cr-date-wrap" id="icToWrap">
                    <input type="text" id="icToDate" class="cr-date-input" placeholder="YYYY-MM-DD" autocomplete="off" inputmode="numeric" aria-describedby="icToError">
                    <span class="cr-date-icon" id="icToDateIcon" title="Open Calendar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                </div>
                <div class="ic-field-error" id="icToError" role="alert"></div>
            </div>

            <div class="cr-filter-group">
                <span class="cr-filter-label">&nbsp;</span>
                <button type="button" class="cr-apply-btn" id="icSearchBtn">
                    <i class="fa fa-magnifying-glass"></i> Search
                </button>
            </div>
        </div>
    </div>

    {{-- CALLS TABLE --}}
    <div class="cr-table-card" id="icTableCard">
        <div class="cr-table-head">
            <div>
                <div class="cr-table-title">Inbound Calls</div>
                <div class="cr-table-sub" id="icTableSub">Loading&hellip;</div>
            </div>
            <div class="cr-table-tools">
                <label class="cr-length" for="icLength">
                    <span>Show</span>
                    <span class="cr-select-wrap">
                        <select id="icLength" class="cr-select">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </span>
                    <span>entries</span>
                </label>
                <div class="cr-search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="icSearch" placeholder="Lead ID, call ID, mobile, VMN or agent" autocomplete="off" aria-label="Search inbound calls" maxlength="100">
                </div>
            </div>
        </div>
        <table class="cr-table cr-dt" id="icCallsTable" style="width: 100%">
            <thead>
                <tr>
                    <th>Lead ID</th>
                    <th>Call ID</th>
                    <th>Customer Mobile</th>
                    <th>VMN</th>
                    <th>Start Time</th>
                    <th>Agent Number</th>
                    <th>Agent Name</th>
                </tr>
            </thead>
        </table>
        {{-- Every call of the current dates and search (not only this page), in the table's order --}}
        <div class="cr-export-row" role="group" aria-label="Export inbound calls">
            <button type="button" class="ic-export-btn excel" data-format="xlsx" title="Download as Excel (.xlsx)" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="9" x2="9" y2="21"/></svg>
                <span class="ic-spinner" aria-hidden="true"></span>
                <span>Excel</span>
            </button>
            <button type="button" class="ic-export-btn csv" data-format="csv" title="Download as CSV" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                <span class="ic-spinner" aria-hidden="true"></span>
                <span>CSV</span>
            </button>
        </div>
    </div>

    @if($pipeline)
    {{-- Admins: are inbound events arriving? (webhook requests and the provider pull) --}}
    <div class="ic-pipeline" id="icPipeline">
        <span>{{ $pipeline['last_event'] ? 'Last inbound event received: ' . $pipeline['last_event'] : 'No inbound events received yet' }}</span>
        <span class="ic-pipeline-sep">&middot;</span>
        <span>Provider sync: {{ $pipeline['last_pull'] ?? 'never' }}@if($pipeline['pull_error']) <span class="ic-pipeline-err">({{ \Illuminate\Support\Str::limit($pipeline['pull_error'], 80) }})</span>@endif</span>
        <span class="ic-pipeline-sep">&middot;</span>
        <span>Webhook URL: <code id="icWebhookUrl">{{ $pipeline['webhook_url'] }}</code></span>
        <button type="button" class="ic-copy-btn" id="icCopyWebhook" title="Copy webhook URL" aria-label="Copy webhook URL">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <span>Copy</span>
        </button>
    </div>
    @endif

</div>
@endsection

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script>
(function () {
    'use strict';

    var MAX_DAYS = {{ \App\Http\Controllers\Dashboard\InboundCallsController::MAX_DAYS }};
    var state = { range: 'today', from_date: '', to_date: '', search: '' };
    var table = null, total = null, exportBusy = false, lastError = false;
    var DASH = '<span class="ic-dash">-</span>';
    var PHONE_ICON = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';

    function pad(n) { return String(n).padStart(2, '0'); }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function getDates(range) {
        var today = new Date();
        if (range === 'yesterday') { var y = new Date(today); y.setDate(y.getDate() - 1); return { from: fmt(y), to: fmt(y) }; }
        if (range === 'last7')     { var s = new Date(today); s.setDate(s.getDate() - 6); return { from: fmt(s), to: fmt(today) }; }
        if (range === 'thismonth') { return { from: fmt(new Date(today.getFullYear(), today.getMonth(), 1)), to: fmt(today) }; }
        return { from: fmt(today), to: fmt(today) };
    }
    function parseDate(s) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(s || '')) return null;
        var p = s.split('-'), d = new Date(+p[0], +p[1] - 1, +p[2]);
        return d.getMonth() === +p[1] - 1 && d.getDate() === +p[2] ? d : null;
    }
    function displayDate(s) {
        var d = parseDate(s);
        return d ? d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : s;
    }
    var RANGE_NAME = { today: 'Today', yesterday: 'Yesterday', last7: 'Last 7 days', thismonth: 'This month' };
    function periodLabel() {
        var dates = state.from_date === state.to_date ? displayDate(state.from_date) : displayDate(state.from_date) + ' to ' + displayDate(state.to_date);
        return RANGE_NAME[state.range] ? RANGE_NAME[state.range] + ' — ' + dates : dates;
    }
    function esc(s) {
        return String(s === null || s === undefined ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function cell(v, cls) { return v && v !== '-' ? '<span' + (cls ? ' class="' + cls + '"' : '') + '>' + esc(v) + '</span>' : DASH; }

    /* ---- inline date errors ---- */
    function fieldError(which, msg) {
        var el = document.getElementById(which === 'from' ? 'icFromError' : 'icToError');
        el.textContent = msg || '';
        el.classList.toggle('show', !!msg);
        document.getElementById(which === 'from' ? 'icFromWrap' : 'icToWrap').classList.toggle('has-error', !!msg);
    }
    function clearErrors() { fieldError('from', ''); fieldError('to', ''); }
    function validDates(from, to) {
        clearErrors();
        var f = parseDate(from), t = parseDate(to), ok = true;
        if (!f) { fieldError('from', from ? 'Enter a valid date (YYYY-MM-DD).' : 'Choose a From Date.'); ok = false; }
        if (!t) { fieldError('to', to ? 'Enter a valid date (YYYY-MM-DD).' : 'Choose a To Date.'); ok = false; }
        if (!ok) return false;
        if (t < f) { fieldError('to', "'To Date' cannot be earlier than 'From Date'."); return false; }
        if (Math.round((t - f) / 86400000) + 1 > MAX_DAYS) { fieldError('to', 'Choose a range of at most ' + MAX_DAYS + ' days.'); return false; }
        return true;
    }

    /* ---- table ---- */
    var columns = [
        { data: 'lead_id', name: 'lead_id', render: function (v) {
            return /^\d+$/.test(String(v || ''))
                ? '<a href="javascript:void(0)" class="lg-lead-id-pill lg-lead-id-link" data-lead-id="' + v + '" title="View lead ' + v + '">' + v + '</a>' : DASH;
        } },
        { data: 'call_id', name: 'call_id', render: function (v) { return cell(v, 'ic-mono ic-muted'); } },
        // a real link (Ctrl / middle click: new tab): the server picks the campaign lead calling page or Manual Dial
        { data: 'customer_mobile', name: 'customer_mobile', render: function (v, t, r) {
            if (!v || v === '-' || !r.call_url) return cell(v, 'ic-mono');
            return '<a href="' + esc(r.call_url) + '" class="ic-call-link" data-kind="' + esc(r.call_kind) + '" title="' + esc(r.call_title) + '">' + PHONE_ICON + '<span class="ic-mono">' + esc(v) + '</span></a>';
        } },
        { data: 'vmn', name: 'vmn', render: function (v) { return cell(v, 'ic-mono'); } },
        { data: 'start_time', name: 'start_time', render: function (v) { return cell(v, 'ic-muted'); } },
        { data: 'agent_number', name: 'agent_number', render: function (v) { return cell(v, 'ic-mono'); } },
        { data: 'agent_name', name: 'agent_name', render: function (v) { return v && v !== '-' ? '<span style="font-weight:600">' + esc(v) + '</span>' : DASH; } }
    ];

    function params() {
        return { from_date: state.from_date, to_date: state.to_date, search: state.search };
    }
    function sortParams() {
        var ord = table ? table.order()[0] : null;
        return ord ? { sort: columns[ord[0]].name, dir: ord[1] === 'asc' ? 'asc' : 'desc' } : { sort: 'start_time', dir: 'desc' };
    }

    function syncExport() {
        document.querySelectorAll('.ic-export-btn').forEach(function (b) {
            b.disabled = exportBusy || !total;
            if (!b.dataset.title) b.dataset.title = b.title;
            b.title = !total ? 'No data to export' : b.dataset.title;
        });
    }

    function stateRow(html, isError) {
        $('#icCallsTable tbody').html('<tr><td colspan="7" class="ic-state' + (isError ? ' is-error' : '') + '">' + html + '</td></tr>');
    }
    function showError(msg) {
        lastError = true;
        total = 0; syncExport();
        $('#icTableSub').text('The inbound calls could not be loaded.');
        stateRow('<i class="fa-solid fa-triangle-exclamation ic-state-icon"></i>' + esc(msg || 'The inbound calls could not be loaded.') +
            '<br><button type="button" class="ic-retry" id="icRetry"><i class="fa fa-rotate-right"></i> Try again</button>', true);
        $('#icCallsTable_info').text('');
    }

    function initTable() {
        table = $('#icCallsTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            autoWidth: false,
            pageLength: 10,
            order: [[4, 'desc']],   // newest first
            ajax: function (d, callback) {
                lastError = false;
                $.ajax({
                    url: '{{ route("inboundCallsData") }}',
                    type: 'POST',
                    headers: { Accept: 'application/json' },
                    data: $.extend({ _token: '{{ csrf_token() }}', draw: d.draw, start: d.start, length: d.length }, params(), sortParams()),
                    success: function (json) {
                        total = json.recordsTotal;
                        var period = RANGE_NAME[state.range] || periodLabel();
                        $('#icTableSub').text(json.recordsTotal.toLocaleString('en-IN') + ' call' + (json.recordsTotal === 1 ? '' : 's') + ' · ' + period);
                        syncExport();
                        callback(json);
                    },
                    error: function (xhr) {
                        var errs = xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors;
                        if (errs) {
                            if (errs.from_date) fieldError('from', errs.from_date[0]);
                            if (errs.to_date) fieldError('to', errs.to_date[0]);
                        }
                        callback({ draw: d.draw, data: [], recordsTotal: 0, recordsFiltered: 0 });
                        showError(errs ? 'Please correct the dates above and search again.'
                            : (xhr.status === 403 ? 'You do not have permission to view inbound calls.' : null));
                    }
                });
            },
            dom: '<"cr-table-wrap"t>r<"cr-table-footer"<"cr-footer-info"i><"cr-footer-pagination"p>>',
            language: {
                emptyTable: '<div class="ic-state" style="padding:24px 0 !important"><i class="fa-solid fa-phone-slash ic-state-icon"></i>No inbound calls found for this period</div>',
                zeroRecords: '<div class="ic-state" style="padding:24px 0 !important"><i class="fa-solid fa-phone-slash ic-state-icon"></i>No inbound calls found for this period</div>',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries', infoEmpty: 'Showing 0 to 0 of 0 entries',
                processing: '<span class="cr-loading-spin"></span> Loading...',
                paginate: { previous: 'Previous', next: 'Next' }
            },
            columns: columns,
            drawCallback: function () {
                var $pg = $('#icCallsTable_paginate');
                $pg.toggle($pg.find('.paginate_button').length > 3);
            }
        });
        $.fn.dataTable.ext.errMode = 'none';
    }

    function load() {
        document.getElementById('icPeriodLabel').textContent = periodLabel();
        if (table) table.ajax.reload(null, true);
    }

    $(document).on('click', '#icRetry', function () { clearErrors(); load(); });

    /* ---- filters ---- */
    function pillsFor(range) {
        document.querySelectorAll('.cr-pill').forEach(function (p) { p.classList.toggle('active', p.dataset.range === range); });
    }
    var fpOpts = { dateFormat: 'Y-m-d', allowInput: true, clickOpens: true, maxDate: 'today' };
    var fromPicker = flatpickr('#icFromDate', fpOpts);
    var toPicker = flatpickr('#icToDate', fpOpts);
    document.getElementById('icFromDateIcon').addEventListener('click', function () { fromPicker.open(); });
    document.getElementById('icToDateIcon').addEventListener('click', function () { toPicker.open(); });

    function search() {
        var from = document.getElementById('icFromDate').value.trim(), to = document.getElementById('icToDate').value.trim();
        if (!validDates(from, to)) return;
        state.from_date = from; state.to_date = to; state.range = 'custom';
        ['today', 'yesterday', 'last7', 'thismonth'].forEach(function (r) { var d = getDates(r); if (d.from === from && d.to === to) state.range = r; });
        pillsFor(state.range);
        load();
    }
    document.getElementById('icSearchBtn').addEventListener('click', search);
    ['icFromDate', 'icToDate'].forEach(function (id) {
        document.getElementById(id).addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); fromPicker.close(); toPicker.close(); search(); }
        });
    });

    document.querySelectorAll('.cr-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            var d = getDates(pill.dataset.range);
            clearErrors();
            state.range = pill.dataset.range; state.from_date = d.from; state.to_date = d.to;
            fromPicker.setDate(d.from, false); toPicker.setDate(d.to, false);
            pillsFor(state.range);
            load();
        });
    });

    var searchTimer = null;
    document.getElementById('icSearch').addEventListener('input', function () {
        var val = this.value.trim();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if (val === state.search) return;
            state.search = val;
            load();
        }, 350);
    });
    document.getElementById('icLength').addEventListener('change', function () {
        if (table) table.page.len(parseInt(this.value, 10)).draw();
    });

    /* ---- export: every row of the dates + search in the table's order ---- */
    function exportCalls(format, btn) {
        if (exportBusy || !total) return;
        exportBusy = true;
        btn.classList.add('is-loading');
        btn.setAttribute('aria-busy', 'true');
        syncExport();
        var q = new URLSearchParams($.extend(params(), sortParams()));
        var url = "{{ route('inboundCallsExport', ['format' => 'csv']) }}".replace(/csv$/, format) + '?' + q.toString();
        var name = 'inbound-calls-' + state.from_date + (state.to_date !== state.from_date ? '-to-' + state.to_date : '') + '.' + format;
        fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) return r.json().catch(function () { return {}; }).then(function (j) { throw new Error(j.message || 'The file could not be prepared.'); });
                return r.blob();
            })
            .then(function (blob) {
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = name;
                document.body.appendChild(a);
                a.click();
                setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
            })
            .catch(function (e) { if (typeof toastr !== 'undefined') toastr.error(e.message); })
            .finally(function () {
                exportBusy = false;
                btn.classList.remove('is-loading');
                btn.removeAttribute('aria-busy');
                syncExport();
            });
    }
    document.querySelectorAll('.ic-export-btn').forEach(function (b) {
        b.addEventListener('click', function () { exportCalls(b.dataset.format, b); });
    });

    /* ---- customer mobile link: several campaigns -> the agent picks one (or Manual Dial) ---- */
    function closeChooser() {
        var el = document.getElementById('icChooser');
        if (el) el.remove();
        document.removeEventListener('keydown', chooserKeys);
    }
    function chooserKeys(e) { if (e.key === 'Escape') closeChooser(); }
    function openChooser(d) {
        closeChooser();
        var opts = (d.options || []).map(function (o) {
            return '<a class="ic-chooser-opt" href="' + esc(o.url) + '">' + PHONE_ICON + '<span>' + esc(o.label) + '</span></a>';
        }).join('');
        var wrap = document.createElement('div');
        wrap.id = 'icChooser';
        wrap.className = 'ic-chooser-backdrop';
        wrap.innerHTML = '<div class="ic-chooser" role="dialog" aria-modal="true" aria-labelledby="icChooserTitle">' +
            '<p class="ic-chooser-title" id="icChooserTitle">Call ' + esc(d.number) + '</p>' +
            '<p class="ic-chooser-sub">This number is a lead in more than one campaign.</p>' + opts +
            '<button type="button" class="ic-chooser-close">Cancel</button></div>';
        wrap.addEventListener('click', function (e) { if (e.target === wrap || e.target.closest('.ic-chooser-close')) closeChooser(); });
        document.body.appendChild(wrap);
        document.addEventListener('keydown', chooserKeys);
        var first = wrap.querySelector('.ic-chooser-opt');
        if (first) first.focus();
    }
    function resolveAndGo(url) {
        fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status === 403 ? 'You cannot open this call.' : 'This call could not be opened.'); return r.json(); })
            .then(function (d) { if (d.type === 'choose') openChooser(d); else window.location.href = d.url; })
            .catch(function (e) { if (typeof toastr !== 'undefined') toastr.error(e.message); });
    }
    // plain click on a several-campaigns number: ask here; Ctrl / Cmd / Shift / middle click keep the browser default
    $(document).on('click', '.ic-call-link[data-kind="choose"]', function (e) {
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        resolveAndGo(this.href);
    });

    /* ---- start: today in both fields ---- */
    var chooseId = (new URLSearchParams(window.location.search).get('choose') || '').replace(/\D/g, '');
    if (new URLSearchParams(window.location.search).has('choose')) {
        var cu = new URL(window.location.href);
        cu.searchParams.delete('choose');
        history.replaceState(history.state, '', cu.pathname + cu.search + cu.hash);
    }
    if (chooseId) resolveAndGo("{{ route('inboundCallOpen', ['inboundCall' => 0]) }}".replace(/\/0\/call$/, '/' + chooseId + '/call'));
    var init = getDates('today');
    state.from_date = init.from; state.to_date = init.to;
    fromPicker.setDate(init.from, false);
    toPicker.setDate(init.to, false);
    document.getElementById('icPeriodLabel').textContent = periodLabel();
    initTable();
})();
</script>
@if($pipeline)
<script>
(function () {
    'use strict';
    var btn = document.getElementById('icCopyWebhook');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var text = document.getElementById('icWebhookUrl').textContent.trim(), label = btn.querySelector('span');
        function done(ok) { label.textContent = ok ? 'Copied' : 'Copy failed'; setTimeout(function () { label.textContent = 'Copy'; }, 1600); }
        function fallback() {
            var t = document.createElement('textarea');
            t.value = text; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
            document.body.appendChild(t); t.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(t);
            done(ok);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () { done(true); }, fallback);
        } else {
            fallback();
        }
    });
})();
</script>
@endif
@endpush
