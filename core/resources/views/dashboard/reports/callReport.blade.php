@extends('dashboard.layouts.master')
@section('title', 'Call Report - LeadGER')

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
{{-- Report page design (header, filter card, KPI cards, table card), shared with Campaign Status --}}
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/lg-report-page.css') }}?v=4">
@endpush

@section('content')
<div class="cr-canvas">

    {{-- PAGE HEADER --}}
    <div class="cr-header">
        <div class="cr-header-left">
            <div class="cr-header-icon">
                <i class="fa-solid fa-chart-column"></i>
            </div>
            <div>
                <h1 class="cr-header-title">Call Report</h1>
                <div class="cr-header-sub" id="crPeriodLabel">Today &mdash; {{ date('d M Y') }}</div>
            </div>
        </div>
        <div class="cr-export-wrap" data-lg-perm="reports.call_report.export">
            <button type="button" class="cr-export-btn" id="crExportBtn" aria-haspopup="true" aria-expanded="false" aria-controls="crExportMenu">
                <i class="fa fa-download"></i> Export
            </button>
            <div class="cr-export-menu" id="crExportMenu" role="menu" hidden>
                <button type="button" class="cr-export-item" role="menuitem" data-format="xlsx"><i class="fa fa-file-excel"></i> Excel (.xlsx)</button>
                <button type="button" class="cr-export-item" role="menuitem" data-format="csv"><i class="fa fa-file-csv"></i> CSV</button>
            </div>
        </div>
    </div>

    {{-- FILTER CARD --}}
    <div class="cr-filter-card">
        <div class="cr-filter-row">
            {{-- Date Range Preset Buttons --}}
            <div class="cr-filter-group">
                <span class="cr-filter-label">Date Range</span>
                <div class="cr-date-pills">
                    <button type="button" class="cr-pill active" data-range="today">Today</button>
                    <button type="button" class="cr-pill" data-range="yesterday">Yesterday</button>
                    <button type="button" class="cr-pill" data-range="last7">Last 7 days</button>
                    <button type="button" class="cr-pill" data-range="thismonth">This month</button>
                </div>
            </div>

            {{-- From Date Calendar Picker --}}
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="crFromDate">From Date</label>
                <div class="cr-date-wrap">
                    <input type="text" id="crFromDate" class="cr-date-input" placeholder="YYYY-MM-DD" autocomplete="off" readonly>
                    <span class="cr-date-icon" id="crFromDateIcon" title="Open Calendar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                </div>
            </div>

            {{-- To Date Calendar Picker --}}
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="crToDate">To Date</label>
                <div class="cr-date-wrap">
                    <input type="text" id="crToDate" class="cr-date-input" placeholder="YYYY-MM-DD" autocomplete="off" readonly>
                    <span class="cr-date-icon" id="crToDateIcon" title="Open Calendar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                </div>
            </div>

            {{-- Campaign Filter ("Manual Dial" = manual dial calls) --}}
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="crCampaignFilter">Campaign</label>
                <select id="crCampaignFilter" class="cr-select">
                    <option value="">All campaigns</option>
                    <option value="manual">Manual Dial</option>
                    @foreach($Campaigns as $c)
                        <option value="{{ $c->id }}">{{ strcasecmp(trim($c->name), 'Manual Dial') === 0 ? 'Manual Dial (campaign)' : $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Agent Filter --}}
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="crAgentFilter">Agent</label>
                <select id="crAgentFilter" class="cr-select">
                    <option value="">All agents</option>
                    @foreach($Telecallers as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Call Source Filter --}}
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="crSourceFilter">Call Source</label>
                <select id="crSourceFilter" class="cr-select">
                    <option value="all">All sources</option>
                    <option value="campaign">Campaign</option>
                    <option value="manual">Manual</option>
                </select>
            </div>

            {{-- Apply Button --}}
            <div class="cr-filter-group">
                <span class="cr-filter-label">&nbsp;</span>
                <button type="button" class="cr-apply-btn" id="crApplyBtn">
                    <i class="fa fa-rotate-right"></i> Apply
                </button>
            </div>
        </div>

        {{-- Validation Error Alert --}}
        <div id="crDateRangeError" style="display: none; margin-top: 12px; font-size: 12.5px; color: #DC2626; font-weight: 500; align-items: center; gap: 6px;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span id="crDateRangeErrorText">'To Date' cannot be earlier than 'From Date'.</span>
        </div>
    </div>

    {{-- SUMMARY CARDS: campaign + manual calls for the filters; each card shows its calls in the table --}}
    <div class="cr-cards-row">
        <div class="cr-stat-card cr-stat-link" data-card="all" role="button" tabindex="0" aria-pressed="false" aria-label="Total calls: show all calls">
            <div class="cr-stat-card-icon icon-total"><i class="fa-solid fa-phone-volume"></i></div>
            <div class="cr-stat-card-label">Total Calls</div>
            <div class="cr-stat-card-value" id="crStatTotal"><span class="cr-loading-spin"></span></div>
            <span class="cr-stat-more">View details &rarr;</span>
        </div>
        <div class="cr-stat-card cr-connected cr-stat-link" data-card="connected" role="button" tabindex="0" aria-pressed="false" aria-label="Connected calls: show them">
            <div class="cr-stat-card-icon"><i class="fa-solid fa-phone-flip"></i></div>
            <div class="cr-stat-card-label">Connected</div>
            <div class="cr-stat-card-value" id="crStatConnected"><span class="cr-loading-spin"></span></div>
            <span class="cr-stat-more">View details &rarr;</span>
        </div>
        <div class="cr-stat-card cr-stat-link" data-card="not_connected" role="button" tabindex="0" aria-pressed="false" aria-label="Not connected calls: show them">
            <div class="cr-stat-card-icon icon-notconn"><i class="fa-solid fa-phone-slash"></i></div>
            <div class="cr-stat-card-label">Not Connected</div>
            <div class="cr-stat-card-value" id="crStatNotConn"><span class="cr-loading-spin"></span></div>
            <span class="cr-stat-more">View details &rarr;</span>
        </div>
        <div class="cr-stat-card cr-stat-link" data-card="avg" role="button" tabindex="0" aria-pressed="false" aria-label="Average call duration: show the calls with talk time">
            <div class="cr-stat-card-icon icon-avg"><i class="fa-regular fa-clock"></i></div>
            <div class="cr-stat-card-label">Avg Call Duration</div>
            <div class="cr-stat-card-value" id="crStatAvg"><span class="cr-loading-spin"></span></div>
            <span class="cr-stat-more">View details &rarr;</span>
        </div>
        <div class="cr-stat-card cr-stat-link" data-card="talk" role="button" tabindex="0" aria-pressed="false" aria-label="Total talk time: show the calls with talk time">
            <div class="cr-stat-card-icon icon-talk"><i class="fa-solid fa-headset"></i></div>
            <div class="cr-stat-card-label">Total Talk Time</div>
            <div class="cr-stat-card-value" id="crStatTalk"><span class="cr-loading-spin"></span></div>
            <span class="cr-stat-more">View details &rarr;</span>
        </div>
    </div>

    {{-- CALLS TABLE --}}
    <div class="cr-table-card" id="crTableCard" style="scroll-margin-top: 24px;">
        <div class="cr-table-head">
            <div>
                <div class="cr-table-title">All Calls</div>
                <div class="cr-table-sub" id="crTableSub">Loading&hellip;</div>
            </div>
            <div class="cr-table-tools">
                <label class="cr-length" for="crLength">
                    <span>Show</span>
                    <span class="cr-select-wrap">
                        <select id="crLength" class="cr-select">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </span>
                    <span>entries</span>
                </label>
                <div class="cr-search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="crSearch" placeholder="Lead ID, name, phone or agent" autocomplete="off" aria-label="Search calls">
                </div>
                <a href="{{ route('callLogs') }}" style="font-size:13px;color:var(--lg-accent);font-weight:600;text-decoration:none;white-space:nowrap;">
                    <i class="fa fa-table-list" style="margin-right:5px;"></i>View Full Call Logs
                </a>
            </div>
        </div>
        <div id="crFilterChipContainer" style="display: none; padding: 0 20px 14px 20px;"></div>
        {{-- Selected KPI card: "Showing: Connected [x]" and, for the time cards, the summary line --}}
        <div class="cr-view-bar" id="crViewBar">
            <span class="cr-view-chip"><span id="crViewChipText"></span><button type="button" id="crViewClear" title="Show all calls" aria-label="Clear the card selection">&times;</button></span>
            <span class="cr-view-summary" id="crViewSummary"></span>
        </div>
        <table class="cr-table cr-dt" id="crCallsTable" style="width: 100%">
            <thead>
                <tr>
                    <th>Lead ID</th>
                    <th>Agent</th>
                    <th>Source</th>
                    <th>Campaign</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Duration</th>
                    <th>Call Result</th>
                    <th>Status</th>
                    <th>Substatus</th>
                    <th>Call Type</th>
                    <th>Feedback</th>
                    <th>Recording</th>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
            </thead>
        </table>
        {{-- Export the calls for the current filters, search and sort (every matching row, not only this page) --}}
        <div class="cr-export-row">
            <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#475569;cursor:pointer;" title="Add a column with the link of each call recording">
                <input type="checkbox" id="crWithUrl"> Include recording URL
            </label>
            <button type="button" class="cr-dl-btn cr-dl-excel" id="crExportXlsx" data-lg-perm="reports.call_report.export" title="Download these calls as an Excel file">
                <i class="fa fa-file-excel"></i> Excel
            </button>
            <button type="button" class="cr-dl-btn cr-dl-csv" id="crExportCsv" data-lg-perm="reports.call_report.export" title="Download these calls as a CSV file">
                <i class="fa fa-file-csv"></i> CSV
            </button>
        </div>
    </div>

</div>
@endsection

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script>
(function () {
    'use strict';

    // scope 'agents' = opened from an Agent Performance card (that card's calls and counting rules)
    var state = { range: 'today', from_date: '', to_date: '', campaign: '', agent: '', source: 'all', status: '', scope: '', metric: '', card: 'all', search: '' };
    // KPI card -> server view (avg / talk both list the calls with talk time) and the chip label
    var CARD_VIEW = { all: 'all', connected: 'connected', not_connected: 'not_connected', avg: 'with_talk_time', talk: 'with_talk_time' };
    var CARD_LABEL = { connected: 'Connected', not_connected: 'Not Connected', avg: 'Avg Call Duration', talk: 'Total Talk Time' };
    var lastData = null;
    var table = null;

    function pad(n) { return String(n).padStart(2, '0'); }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }

    function getDates(range) {
        var today = new Date();
        if (range === 'today')     return { from: fmt(today), to: fmt(today) };
        if (range === 'yesterday') { var y=new Date(today); y.setDate(y.getDate()-1); return { from: fmt(y), to: fmt(y) }; }
        if (range === 'last7')     { var s=new Date(today); s.setDate(s.getDate()-6); return { from: fmt(s), to: fmt(today) }; }
        if (range === 'thismonth') { var b=new Date(today.getFullYear(), today.getMonth(), 1); return { from: fmt(b), to: fmt(today) }; }
        return { from: fmt(today), to: fmt(today) };
    }

    function formatDisplayDate(dateStr) {
        if (!dateStr) return '';
        var parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function labelFor(range, fromDate, toDate) {
        if (range === 'today')     return 'Today — ' + formatDisplayDate(fromDate);
        if (range === 'yesterday') return 'Yesterday — ' + formatDisplayDate(fromDate);
        if (range === 'last7')     return 'Last 7 Days — ' + formatDisplayDate(fromDate) + ' to ' + formatDisplayDate(toDate);
        if (range === 'thismonth') return 'This Month — ' + formatDisplayDate(fromDate) + ' to ' + formatDisplayDate(toDate);
        if (fromDate && toDate) {
            if (fromDate === toDate) return formatDisplayDate(fromDate);
            return formatDisplayDate(fromDate) + ' — ' + formatDisplayDate(toDate);
        }
        return '';
    }

    function esc(s) {
        if (s === null || s === undefined) return '-';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    var DASH = '<span style="color:#94A3B8">-</span>';

    function renderCallFilterChip() {
        var chipContainer = document.getElementById('crFilterChipContainer');
        if (!chipContainer) return;
        if (state.scope === 'agents') {
            var what = state.metric === 'conversion_rate' ? 'Conversion rate' : (state.metric === 'avg_talk' ? 'Avg talk time'
                : (state.status === 'connected' ? 'Connected calls' : (state.status === 'converted' ? 'Conversions (Deal)' : 'Total calls')));
            var range = state.from_date === state.to_date ? formatDisplayDate(state.from_date) : formatDisplayDate(state.from_date) + ' – ' + formatDisplayDate(state.to_date);
            var note = '';
            if (lastData && state.metric === 'avg_talk') {
                note = 'Avg talk time = total talk time ' + esc(lastData.total_talk) + ' ÷ ' + lastData.connected + ' connected call' + (lastData.connected == 1 ? '' : 's') + ' = ' + esc(lastData.avg_duration);
            } else if (lastData && state.metric === 'conversion_rate' && lastData.conversion) {
                var cv = lastData.conversion;
                note = 'Conversion rate = ' + cv.deals + ' Deal call' + (cv.deals == 1 ? '' : 's') + ' ÷ ' + cv.calls + ' total call' + (cv.calls == 1 ? '' : 's') + ' = ' + cv.rate + '%';
            }
            chipContainer.innerHTML =
                '<div style="display: inline-flex; align-items: center; gap: 8px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 999px; padding: 4px 14px; font-size: 12.5px; font-weight: 600; color: var(--lg-accent-hover);">' +
                '<i class="fa fa-chart-line" style="font-size: 11px;"></i>' +
                '<span>From Agent Performance · ' + what + ' · ' + esc(range) + ' · campaign + manual calls</span>' +
                '<button type="button" onclick="window.clearCallFilter()" style="border: none; background: transparent; cursor: pointer; color: var(--lg-accent-hover); font-weight: 700; font-size: 15px; padding: 0; margin-left: 4px; line-height: 1;" title="Back to the normal Call Report">&times;</button>' +
                '</div>' +
                (note ? '<div style="margin-top: 8px; font-size: 12.5px; color: #334155; font-weight: 600;"><i class="fa fa-calculator" style="color: var(--lg-accent); margin-right: 6px;"></i>' + note + '</div>' : '');
            chipContainer.style.display = 'block';
        } else {
            chipContainer.style.display = 'none';
            chipContainer.innerHTML = '';
        }
    }

    window.clearCallFilter = function () {
        state.status = '';
        state.scope = '';
        state.metric = '';
        renderCallFilterChip();
        loadReport();
    };

    function showDateError(msg) {
        var el = document.getElementById('crDateRangeError');
        var txt = document.getElementById('crDateRangeErrorText');
        if (el && txt) { txt.textContent = msg; el.style.display = 'flex'; }
    }
    function hideDateError() {
        var el = document.getElementById('crDateRangeError');
        if (el) el.style.display = 'none';
    }

    function setStat(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = (val !== null && val !== undefined) ? val : '-';
    }

    var crAudio = null, crAudioBtn = null;
    window.crPlayRecording = function (btn) {
        if (crAudio && crAudioBtn === btn) {
            if (crAudio.paused) { crAudio.play(); btn.innerHTML = '<i class="fa fa-pause"></i>'; }
            else { crAudio.pause(); btn.innerHTML = '<i class="fa fa-play"></i>'; }
            return;
        }
        if (crAudio) { crAudio.pause(); if (crAudioBtn) crAudioBtn.innerHTML = '<i class="fa fa-play"></i>'; }
        crAudio = new Audio(btn.getAttribute('data-src'));
        crAudioBtn = btn;
        crAudio.onended = function () { btn.innerHTML = '<i class="fa fa-play"></i>'; };
        crAudio.onerror = function () { btn.innerHTML = '<i class="fa fa-triangle-exclamation"></i>'; btn.title = 'Recording could not be played'; };
        crAudio.play();
        btn.innerHTML = '<i class="fa fa-pause"></i>';
    };

    /* ---- the table (server side: one query for rows, cards and export) ---- */
    function requestParams() {
        if (!state.from_date || !state.to_date) {
            var d = getDates(state.range || 'today');
            state.from_date = d.from; state.to_date = d.to;
        }
        return {
            from_date: state.from_date, to_date: state.to_date,
            find_campaign: state.campaign || '', telecaller_id: state.agent || '', source: state.source || 'all',
            q: state.search || '', status: state.status || '', scope: state.scope || '',
            view: CARD_VIEW[state.card] || 'all'
        };
    }

    function showSpinners() {
        ['crStatTotal','crStatConnected','crStatNotConn','crStatAvg','crStatTalk'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="cr-loading-spin"></span>';
        });
    }

    function loadReport(resetPage) {
        var pl = document.getElementById('crPeriodLabel');
        requestParams();
        if (pl) pl.textContent = labelFor(state.range, state.from_date, state.to_date);
        syncUrl(false);
        showSpinners();
        if (table) table.ajax.reload(null, resetPage !== false);
    }

    function onData(d) {
        lastData = d;
        setStat('crStatTotal',     d.total_calls);
        setStat('crStatConnected', d.connected);
        setStat('crStatNotConn',   d.not_connected);
        setStat('crStatAvg',       d.avg_duration);
        setStat('crStatTalk',      d.total_talk);
        var n = d.view_count;
        // a preset's name ("Today", "Last 7 Days"), else the dates
        var label = labelFor(state.range, state.from_date, state.to_date);
        var period = ['today', 'yesterday', 'last7', 'thismonth'].indexOf(state.range) !== -1 ? label.split(' — ')[0] : label;
        var sub = n + ' call' + (n == 1 ? '' : 's') + (state.card !== 'all' && !state.scope ? ' · ' + CARD_LABEL[state.card] : '') + ' · ' + period
            + ' · Campaign ' + d.campaign_calls + ' · Manual ' + d.manual_calls
            + ' · Feedback saved ' + d.with_feedback + (d.without_feedback ? ' (' + d.without_feedback + ' without feedback)' : '');
        if (state.scope === 'agents') sub += ' · counted by Agent Performance';
        $('#crTableSub').text(sub);
        renderViewBar(d);
        renderCallFilterChip();
    }

    var columns = [
        { data: 'lead_id', name: 'lead_id', render: function (v) {
            return /^\d+$/.test(String(v || ''))
                ? '<a href="javascript:void(0)" class="lg-lead-id-pill lg-lead-id-link" data-lead-id="' + v + '" title="View lead ' + v + '">' + v + '</a>' : DASH;
        } },
        { data: 'telecaller', name: 'telecaller', render: function (v) { return '<span style="font-weight:600">' + esc(v) + '</span>'; } },
        { data: 'source', name: 'source', render: function (v) {
            return '<span class="cr-src ' + (v === 'Manual' ? 'is-manual' : 'is-campaign') + '">' + esc(v) + '</span>';
        } },
        { data: 'campaign', name: 'campaign', render: esc },
        { data: 'lead', name: 'customer', render: function (v) { return v && v !== '-' ? '<span style="font-weight:700">' + esc(v) + '</span>' : DASH; } },
        { data: 'mobile', name: 'mobile', render: function (v) { return '<span style="font-family:monospace">' + esc(v) + '</span>'; } },
        { data: 'duration', name: 'duration', render: function (v, t, r) {
            return '<span style="font-variant-numeric:tabular-nums;white-space:nowrap" title="' + esc('Start ' + r.start_time + ' · End ' + r.end_time) + '">' + esc(v)
                + ' <span style="color:#94A3B8">(' + (parseInt(r.seconds, 10) || 0) + 's)</span></span>';
        } },
        // Connected / not connected (busy, no answer, failed...) as the provider or the call reported it
        { data: 'call_result', name: 'call_result', orderable: false, render: function (v) {
            if (v === 'Connected') return '<span class="cr-badge" style="background:#DCFCE7;color:#166534;">Connected</span>';
            if (v === 'In Progress') return '<span class="cr-badge" style="background:#F1F5F9;color:#475569;">In progress</span>';
            return '<span class="cr-badge" style="background:#FEE2E2;color:#991B1B;">Not connected' + (v && v !== 'Not Connected' && v !== '-' ? ' · ' + esc(v) : '') + '</span>';
        } },
        // Status / Substatus = the Call Outcome the agent saved for this call, "-" when none was saved
        { data: 'status', name: 'status', render: function (v) { return v && v !== '-' ? '<span class="cr-badge cr-badge-outcome">' + esc(v) + '</span>' : DASH; } },
        { data: 'substatus', name: 'substatus', render: function (v) { return v && v !== '-' ? '<span style="color:#475569">' + esc(v) + '</span>' : DASH; } },
        { data: 'call_type', name: 'call_type', render: function (v) {
            if (!v || v === '-') return DASH;
            return '<span class="cr-ctype ' + (v === 'WebRTC' ? 'is-webrtc' : 'is-c2c') + '"><i aria-hidden="true"></i>' + esc(v) + '</span>';
        } },
        { data: 'feedback', name: 'feedback', render: function (v, t, r) {
            var hasFb = v && v !== '-';
            if (!hasFb && !r.comments) return DASH;
            var tip = (hasFb ? v : '') + (r.comments ? (hasFb ? '\n\nComments: ' : 'Comments: ') + r.comments : '');
            return '<span class="cr-feedback" title="' + esc(tip) + '">' + esc(hasFb ? v : r.comments) + '</span>';
        } },
        { data: 'recording', name: 'recording', orderable: false, render: function (rec) {
            rec = rec || {};
            if (rec.state === 'available') return '<button type="button" class="cr-rec-play" onclick="crPlayRecording(this)" data-src="' + esc(rec.url) + '" title="Play recording"><i class="fa fa-play"></i></button>';
            if (rec.state === 'processing') return '<span class="cr-badge" style="background:#FEF3C7;color:#92400E;"><i class="fa fa-spinner fa-spin"></i> Processing</span>';
            if (rec.state === 'unavailable') return '<span class="cr-badge" style="background:#F1F5F9;color:#64748B;" title="The call provider did not deliver a recording">Not available</span>';
            return DASH;
        } },
        { data: 'date', name: 'date', render: function (v) { return '<span style="white-space:nowrap">' + esc(v) + '</span>'; } },
        { data: 'time', name: 'time', render: function (v) { return '<span style="color:#64748B;white-space:nowrap">' + esc(v) + '</span>'; } }
    ];
    // a column's position by its name (sorting does not depend on the column order)
    function colIndex(name) { return columns.findIndex(function (c) { return c.name === name; }); }

    function initTable() {
        table = $('#crCallsTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            autoWidth: false,
            pageLength: 25,
            order: CARD_VIEW[state.card] === 'with_talk_time' ? [[colIndex('duration'), 'desc']] : [[colIndex('time'), 'desc']],   // newest first (talk-time cards: longest first)
            ajax: {
                url: '{{ route("callReportData") }}',
                type: 'POST',
                data: function (d) {
                    // DataTables' own column / order / paging parameters + the page's filters
                    d.columns = d.columns.map(function (c) { return { data: c.name }; });
                    return $.extend(d, { _token: '{{ csrf_token() }}' }, requestParams());
                },
                dataSrc: function (json) { onData(json); return json.data; },
                error: function () {
                    ['crStatTotal','crStatConnected','crStatNotConn','crStatAvg','crStatTalk'].forEach(function (id) { setStat(id, '-'); });
                    $('#crTableSub').text('The calls could not be loaded. Please refresh and try again.');
                }
            },
            dom: '<"cr-table-wrap"t>r<"cr-table-footer"<"cr-footer-info"i><"cr-footer-pagination"p>>',
            language: {
                emptyTable: 'No calls found for this selection', zeroRecords: 'No calls found for this selection',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries', infoEmpty: 'Showing 0 to 0 of 0 entries',
                processing: '<span class="cr-loading-spin"></span> Loading...',
                paginate: { previous: 'Previous', next: 'Next' }
            },
            columns: columns,
            drawCallback: function () {
                var $pg = $('#crCallsTable_paginate');
                $pg.toggle($pg.find('.paginate_button').length > 3);
            }
        });
        $.fn.dataTable.ext.errMode = 'none';
    }

    /* ---- filters ---- */
    var fromPicker = flatpickr('#crFromDate', {
        dateFormat: 'Y-m-d', allowInput: false, clickOpens: true,
        onChange: function (selectedDates, dateStr) {
            if (!dateStr) return;
            hideDateError();
            document.querySelectorAll('.cr-pill').forEach(function (p) { p.classList.remove('active'); });
            state.range = 'custom';
            toPicker.set('minDate', dateStr);
            var curTo = document.getElementById('crToDate').value;
            if (curTo && curTo < dateStr) { toPicker.setDate(dateStr, false); state.to_date = dateStr; }
            state.from_date = dateStr;
        }
    });
    var toPicker = flatpickr('#crToDate', {
        dateFormat: 'Y-m-d', allowInput: false, clickOpens: true,
        onChange: function (selectedDates, dateStr) {
            if (!dateStr) return;
            var curFrom = document.getElementById('crFromDate').value;
            if (curFrom && dateStr < curFrom) { showDateError("'To Date' cannot be earlier than 'From Date'."); return; }
            hideDateError();
            document.querySelectorAll('.cr-pill').forEach(function (p) { p.classList.remove('active'); });
            state.range = 'custom';
            state.to_date = dateStr;
        }
    });
    document.getElementById('crFromDateIcon').addEventListener('click', function () { fromPicker.open(); });
    document.getElementById('crToDateIcon').addEventListener('click', function () { toPicker.open(); });

    function readSelects() {
        state.campaign = document.getElementById('crCampaignFilter').value;
        state.agent = document.getElementById('crAgentFilter').value;
        state.source = document.getElementById('crSourceFilter').value || 'all';
    }

    document.querySelectorAll('.cr-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            document.querySelectorAll('.cr-pill').forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            state.range = pill.dataset.range;
            var dates = getDates(state.range);
            hideDateError();
            state.from_date = dates.from;
            state.to_date = dates.to;
            fromPicker.setDate(dates.from, false);
            toPicker.setDate(dates.to, false);
            toPicker.set('minDate', dates.from);
            readSelects();
            loadReport();
        });
    });

    document.getElementById('crApplyBtn').addEventListener('click', function () {
        var fromVal = document.getElementById('crFromDate').value;
        var toVal = document.getElementById('crToDate').value;
        if (!fromVal || !toVal) { showDateError('Please select both From Date and To Date.'); return; }
        if (toVal < fromVal) { showDateError("'To Date' cannot be earlier than 'From Date'."); return; }
        hideDateError();
        state.from_date = fromVal;
        state.to_date = toVal;
        var matchedRange = 'custom';
        ['today', 'yesterday', 'last7', 'thismonth'].forEach(function (rng) {
            var d = getDates(rng);
            if (d.from === fromVal && d.to === toVal) matchedRange = rng;
        });
        state.range = matchedRange;
        document.querySelectorAll('.cr-pill').forEach(function (p) { p.classList.toggle('active', p.dataset.range === matchedRange); });
        readSelects();
        loadReport();
    });

    // table search (lead ID, customer name, phone, agent) and page size
    var searchTimer = null;
    document.getElementById('crSearch').addEventListener('input', function () {
        var val = this.value.trim();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if (val === state.search) return;
            state.search = val;
            loadReport();
        }, 350);
    });
    document.getElementById('crLength').addEventListener('change', function () {
        if (table) table.page.len(parseInt(this.value, 10)).draw();
    });

    /* ---- export: same filters, search, card view and sort as the table ---- */
    function exportCalls(format, btn) {
        var q = new URLSearchParams(requestParams());
        if (document.getElementById('crWithUrl').checked) q.set('with_url', '1');
        if (table) {
            var ord = table.order()[0];
            if (ord) { q.set('sort', columns[ord[0]].name); q.set('dir', ord[1]); }
        }
        if (btn) { btn.disabled = true; setTimeout(function () { btn.disabled = false; }, 1500); }
        window.location.href = "{{ route('callReportExport', ['format' => 'csv']) }}".replace(/csv$/, format) + '?' + q.toString();
    }
    document.getElementById('crExportXlsx').addEventListener('click', function () { exportCalls('xlsx', this); });
    document.getElementById('crExportCsv').addEventListener('click', function () { exportCalls('csv', this); });

    var exportBtn = document.getElementById('crExportBtn'), exportMenu = document.getElementById('crExportMenu');
    function closeExportMenu(focusBtn) {
        if (exportMenu.hidden) return;
        exportMenu.hidden = true;
        exportBtn.setAttribute('aria-expanded', 'false');
        if (focusBtn) exportBtn.focus();
    }
    exportBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (exportMenu.hidden) {
            exportMenu.hidden = false;
            exportBtn.setAttribute('aria-expanded', 'true');
            exportMenu.querySelector('.cr-export-item').focus();
        } else {
            closeExportMenu(false);
        }
    });
    exportMenu.addEventListener('click', function (e) {
        var item = e.target.closest('.cr-export-item');
        if (!item) return;
        closeExportMenu(true);
        exportCalls(item.getAttribute('data-format'));
    });
    exportMenu.addEventListener('keydown', function (e) {
        var items = Array.prototype.slice.call(exportMenu.querySelectorAll('.cr-export-item')), i = items.indexOf(document.activeElement);
        if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
        else if (e.key === 'Tab') { closeExportMenu(false); }
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.cr-export-wrap')) closeExportMenu(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeExportMenu(true); });

    /* ---- KPI cards: show the calls behind a card in the table below ---- */
    function renderCardStates() {
        document.querySelectorAll('.cr-stat-link').forEach(function (c) {
            var on = !state.scope && c.dataset.card === state.card && state.card !== 'all';
            c.classList.toggle('is-active', on);
            c.setAttribute('aria-pressed', on ? 'true' : 'false');
            // From Agent Performance the table already shows that card's calls: the KPI cards are not clickable there
            c.style.cursor = state.scope ? 'default' : '';
            c.tabIndex = state.scope ? -1 : 0;
        });
    }
    function renderViewBar(d) {
        renderCardStates();
        var bar = document.getElementById('crViewBar');
        if (state.scope || state.card === 'all') { bar.style.display = 'none'; return; }
        document.getElementById('crViewChipText').textContent = 'Showing: ' + CARD_LABEL[state.card];
        var summary = '';
        if (state.card === 'avg') summary = 'Average: ' + d.avg_duration + ' across ' + d.connected + ' call' + (d.connected == 1 ? '' : 's');
        if (state.card === 'talk') summary = 'Total talk time: ' + d.total_talk + ' across ' + d.connected + ' call' + (d.connected == 1 ? '' : 's');
        document.getElementById('crViewSummary').textContent = summary;
        bar.style.display = 'flex';
    }
    // The selected card and source in the address bar (refresh, Back and a shared link restore the view)
    function syncUrl(push) {
        var q = new URLSearchParams(window.location.search);
        var setOrDel = function (k, v) { if (v) q.set(k, v); else q.delete(k); };
        setOrDel('view', state.card !== 'all' ? CARD_VIEW[state.card] : '');
        setOrDel('card', state.card === 'avg' || state.card === 'talk' ? state.card : '');
        setOrDel('source', state.source && state.source !== 'all' ? state.source : '');
        var url = window.location.pathname + (q.toString() ? '?' + q.toString() : '');
        if (url === window.location.pathname + window.location.search) return;
        if (push) history.pushState({ card: state.card }, '', url); else history.replaceState({ card: state.card }, '', url);
    }
    function cardFromUrl() {
        var p = new URLSearchParams(window.location.search);
        var v = p.get('view'), c = p.get('card');
        if (v === 'with_talk_time') return c === 'talk' ? 'talk' : 'avg';
        return v === 'connected' || v === 'not_connected' ? v : 'all';
    }
    function scrollToTable() {
        var el = document.getElementById('crTableCard');
        if (!el) return;
        window.scrollTo({ top: el.getBoundingClientRect().top + window.pageYOffset - 90, behavior: 'smooth' });
    }
    function selectCard(card) {
        if (state.scope) return;
        state.card = (card === state.card || card === 'all') ? 'all' : card;   // the selected card again (or Total Calls) clears the view
        // the talk-time cards list the longest calls first; the others newest first
        if (table) table.order(CARD_VIEW[state.card] === 'with_talk_time' ? [[colIndex('duration'), 'desc']] : [[colIndex('time'), 'desc']]);
        renderCardStates();
        syncUrl(true);
        loadReport();
        scrollToTable();
    }
    document.querySelectorAll('.cr-stat-link').forEach(function (c) {
        c.addEventListener('click', function () { selectCard(c.dataset.card); });
        c.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectCard(c.dataset.card); }
        });
    });
    document.getElementById('crViewClear').addEventListener('click', function () { selectCard('all'); });
    window.addEventListener('popstate', function () {
        var card = cardFromUrl();
        if (card !== state.card) { state.card = card; renderCardStates(); loadReport(); }
    });

    /* ---- initial state from the address bar (Agent Performance cards, dashboard links, ?source=manual) ---- */
    var urlParams = new URLSearchParams(window.location.search);
    var paramFrom = urlParams.get('from_date');
    var paramTo = urlParams.get('to_date');
    if (urlParams.get('status')) state.status = urlParams.get('status');
    if (urlParams.get('scope') === 'agents') {
        state.scope = 'agents';
        state.metric = urlParams.get('metric') || '';
    } else {
        state.card = cardFromUrl();
        // dashboard "Connected" card (?status=Connected): the Connected KPI card selected
        if (String(state.status).toLowerCase() === 'connected') { state.card = 'connected'; state.status = ''; }
    }
    var paramSource = urlParams.get('source');
    if (paramSource === 'campaign' || paramSource === 'manual') {
        state.source = paramSource;
        document.getElementById('crSourceFilter').value = paramSource;
    }
    var paramAgent = (urlParams.get('telecaller_id') || '').replace(/[^0-9]/g, '');
    var paramCampaign = urlParams.get('find_campaign') === 'manual' ? 'manual' : (urlParams.get('find_campaign') || '').replace(/[^0-9]/g, '');
    if (paramAgent) {
        var agentSel = document.getElementById('crAgentFilter');
        if (!agentSel.querySelector('option[value="' + paramAgent + '"]')) {
            // Agent Performance lists every staff role; the Call Report dropdown only Telecallers
            var opt = document.createElement('option');
            opt.value = paramAgent;
            opt.textContent = urlParams.get('agent_name') || ('Agent #' + paramAgent);
            agentSel.appendChild(opt);
        }
        agentSel.value = paramAgent;
        state.agent = paramAgent;
    }
    if (paramCampaign) {
        var campSel = document.getElementById('crCampaignFilter');
        if (campSel.querySelector('option[value="' + paramCampaign + '"]')) campSel.value = paramCampaign;
        state.campaign = paramCampaign;
    }
    var initDates = getDates('today');
    state.from_date = paramFrom ? (paramFrom === 'today' ? initDates.from : paramFrom) : initDates.from;
    state.to_date = paramTo ? (paramTo === 'today' ? initDates.to : paramTo) : initDates.to;
    fromPicker.setDate(state.from_date, false);
    toPicker.setDate(state.to_date, false);
    toPicker.set('minDate', state.from_date);
    if (paramFrom || paramTo) {
        state.range = 'custom';
        ['today', 'yesterday', 'last7', 'thismonth'].forEach(function (rng) {
            var d = getDates(rng);
            if (d.from === state.from_date && d.to === state.to_date) state.range = rng;
        });
        document.querySelectorAll('.cr-pill').forEach(function (p) { p.classList.toggle('active', p.dataset.range === state.range); });
    }
    document.getElementById('crPeriodLabel').textContent = labelFor(state.range, state.from_date, state.to_date);
    renderCallFilterChip();
    renderCardStates();
    initTable();

    if (urlParams.get('status') || paramFrom) {
        setTimeout(scrollToTable, 350);
    }
})();
</script>
@endpush
