@extends('dashboard.layouts.master')
@section('title', 'Transcript Report - LeadGER')

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
{{-- Same report design as Call Report (header, filter card, KPI cards, table card) --}}
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/lg-report-page.css') }}?v=4">
<style>
    .tr-types { display: flex; flex-wrap: wrap; gap: 4px; max-width: 230px; }
    /* nine columns in the width of a laptop screen: headings may wrap, tighter cells (this table only) */
    #trTable thead th { white-space: normal !important; line-height: 1.3; vertical-align: bottom; }
    #trTable thead th, #trTable tbody td { padding-left: 12px !important; padding-right: 12px !important; }
    #trTable tbody td:nth-child(2) { white-space: normal !important; max-width: 150px; }
    #trTable tbody td:nth-child(5) { min-width: 172px; }
    #trTable thead th:first-child, #trTable tbody td:first-child { padding-left: 16px !important; width: 1%; }
    #trTable thead th:last-child, #trTable tbody td:last-child { width: 1%; }
    .tr-type { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; white-space: nowrap; }
    .tr-status { display: inline-flex; align-items: center; gap: 5px; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .tr-status.s-completed { background: #DCFCE7; color: #166534; }
    .tr-status.s-processing { background: #DBEAFE; color: #1D4ED8; }
    .tr-status.s-pending { background: #FEF3C7; color: #92400E; }
    .tr-status.s-failed { background: #FEE2E2; color: #991B1B; }
    .tr-credit { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .tr-credit small { display: block; color: #94A3B8; font-size: 11px; }
    .tr-actions { display: flex; gap: 6px; flex-wrap: nowrap; }
    .tr-act { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #E2E8F0; background: #FFFFFF; color: #475569; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    #trTable .tr-act { width: 32px !important; min-width: 32px !important; height: 32px !important; padding: 0 !important; flex: 0 0 32px; }
    .tr-act:hover:not(:disabled) { border-color: #93C5FD; color: #1464F4; background: #EFF6FF; }
    .tr-act:disabled { opacity: .4; cursor: not-allowed; }
    .tr-filter-row { display: flex; flex-wrap: wrap; gap: 14px 16px; align-items: flex-end; }
    .tr-filter-row .cr-filter-group { min-width: 140px; }
    .tr-clear-btn { height: 40px; padding: 0 16px; border-radius: 8px; border: 1px solid #E2E8F0; background: #FFFFFF; color: #475569; font-weight: 600; font-size: 13.5px; cursor: pointer; }
    .tr-clear-btn:hover { background: #F8FAFC; }
    #trModal .modal-dialog { max-width: 860px; }
    #trModal .tr-rec { border: 1px solid #E2E8F0; border-radius: 10px; padding: 14px 16px; margin-bottom: 14px; }
    #trModal .tr-rec h4 { font-size: 14px; font-weight: 700; margin: 0 0 4px; color: #0F172A; }
    #trModal .tr-rec .tr-meta { font-size: 12px; color: #64748B; margin-bottom: 10px; }
    #trModal .tr-sec { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748B; margin: 10px 0 4px; }
    #trModal .tr-line { font-size: 13px; color: #334155; margin: 2px 0; }
    #trModal .tr-line b { color: #0F172A; }
    #trModal .tr-text { font-size: 13px; color: #334155; white-space: pre-wrap; background: #F8FAFC; border-radius: 8px; padding: 10px 12px; max-height: 220px; overflow-y: auto; }
</style>
@endpush

@section('content')
<div class="cr-canvas">

    {{-- PAGE HEADER --}}
    <div class="cr-header">
        <div class="cr-header-left">
            <div class="cr-header-icon">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <div>
                <h1 class="cr-header-title">Transcript Report</h1>
                <div class="cr-header-sub" id="trPeriod">&nbsp;</div>
            </div>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="cr-filter-card">
        <div class="tr-filter-row">
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="trFrom">From Date</label>
                <div class="cr-date-wrap"><input type="text" id="trFrom" class="cr-date-input" placeholder="YYYY-MM-DD" autocomplete="off" readonly></div>
            </div>
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="trTo">To Date</label>
                <div class="cr-date-wrap"><input type="text" id="trTo" class="cr-date-input" placeholder="YYYY-MM-DD" autocomplete="off" readonly></div>
            </div>
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="trUser">User / Agent</label>
                <select id="trUser" class="cr-select">
                    <option value="">All users</option>
                    @foreach($options['users'] as $u)
                        <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                    @endforeach
                    @if($options['auto'])<option value="auto">Automatic transcripts</option>@endif
                </select>
            </div>
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="trRole">Role</label>
                <select id="trRole" class="cr-select">
                    <option value="">All roles</option>
                    @foreach($options['roles'] as $r)
                        <option value="{{ $r['id'] }}">{{ $r['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="trType">Transcript Type</label>
                <select id="trType" class="cr-select">
                    <option value="">All types</option>
                    @foreach(\App\Services\TranscriptReportQuery::TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="trStatus">Transcript Status</label>
                <select id="trStatus" class="cr-select">
                    <option value="">All statuses</option>
                    @foreach(\App\Services\TranscriptReportQuery::STATUSES as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cr-filter-group">
                <span class="cr-filter-label">&nbsp;</span>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="cr-apply-btn" id="trSearchBtn"><i class="fa fa-magnifying-glass"></i> Search</button>
                    <button type="button" class="tr-clear-btn" id="trClearBtn">Clear</button>
                </div>
            </div>
        </div>
        <div id="trDateError" style="display:none; margin-top:12px; font-size:12.5px; color:#DC2626; font-weight:500;">'To Date' cannot be earlier than 'From Date'.</div>
    </div>

    {{-- SUMMARY CARDS (same filters as the table) --}}
    <div class="cr-cards-row">
        <div class="cr-stat-card" title="Distinct recordings with a completed transcript">
            <div class="cr-stat-card-icon icon-total"><i class="fa-solid fa-microphone-lines"></i></div>
            <div class="cr-stat-card-label">Total Transcribed Recordings</div>
            <div class="cr-stat-card-value" id="trKpiRecordings"><span class="cr-loading-spin"></span></div>
        </div>
        <div class="cr-stat-card cr-connected" title="Recording transcripts that completed">
            <div class="cr-stat-card-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="cr-stat-card-label">Completed Transcripts</div>
            <div class="cr-stat-card-value" id="trKpiCompleted"><span class="cr-loading-spin"></span></div>
        </div>
        <div class="cr-stat-card" title="Recording transcripts queued or being processed">
            <div class="cr-stat-card-icon icon-avg"><i class="fa-regular fa-clock"></i></div>
            <div class="cr-stat-card-label">Processing / Pending</div>
            <div class="cr-stat-card-value" id="trKpiWaiting"><span class="cr-loading-spin"></span></div>
        </div>
        <div class="cr-stat-card" title="Tracked usage of completed transcripts: real audio minutes × your configured rate (not Sarvam billing)">
            <div class="cr-stat-card-icon icon-talk"><i class="fa-solid fa-coins"></i></div>
            <div class="cr-stat-card-label">Total Credits Used</div>
            <div class="cr-stat-card-value" id="trKpiCredits"><span class="cr-loading-spin"></span></div>
            <span class="cr-stat-more" id="trKpiEstimated" style="cursor:default;"></span>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="cr-table-card">
        <div class="cr-table-head">
            <div>
                <div class="cr-table-title">Transcription Requests</div>
                <div class="cr-table-sub" id="trTableSub">Loading&hellip;</div>
            </div>
            <div class="cr-table-tools">
                <label class="cr-length" for="trLength">
                    <span>Show</span>
                    <span class="cr-select-wrap">
                        <select id="trLength" class="cr-select">
                            <option value="10">10</option><option value="25" selected>25</option><option value="50">50</option><option value="100">100</option>
                        </select>
                    </span>
                    <span>entries</span>
                </label>
            </div>
        </div>
        <table id="trTable" class="cr-table cr-dt" style="width: 100%">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Used By</th>
                    <th>Role</th>
                    <th>Call Recordings Count</th>
                    <th>Transcript Type</th>
                    <th>Transcript Generated</th>
                    <th>Credits Used</th>
                    <th>Date &amp; Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- View: the saved transcripts of a request (read from the database, never regenerated) --}}
<div class="modal fade" id="trModal" tabindex="-1" role="dialog" aria-labelledby="trModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="trModalTitle" style="font-weight:700;">Transcript</h5>
                    <div id="trModalMeta" style="font-size:12.5px; color:#64748B;"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="trModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="tr-clear-btn" id="trModalCopy"><i class="fa fa-copy"></i> Copy</button>
                <a class="cr-apply-btn" id="trModalDownload" href="#" style="text-decoration:none;"><i class="fa fa-download"></i> Download</a>
            </div>
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

    var DATA_URL = "{{ route('transcriptReportData') }}";
    var SHOW_URL = "{{ route('transcriptReportShow', ['batch' => '__B__']) }}";
    var DOWNLOAD_URL = "{{ route('transcriptReportDownload', ['batch' => '__B__']) }}";
    var DASH = '<span style="color:#94A3B8">-</span>';
    var table = null, rowsById = {};

    function pad(n) { return String(n).padStart(2, '0'); }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function esc(s) { return s === null || s === undefined ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function money(v, cur) {
        var sym = cur === 'INR' ? '₹' : (cur === 'USD' ? '$' : (cur ? cur + ' ' : ''));
        return sym + (Number(v) || 0).toFixed(Number(v) && Number(v) < 1 ? 4 : 2);
    }
    function defaults() { var t = new Date(), s = new Date(); s.setDate(s.getDate() - 29); return { from: fmt(s), to: fmt(t) }; }

    var d0 = defaults();
    var fromPicker = flatpickr('#trFrom', { dateFormat: 'Y-m-d', defaultDate: d0.from, maxDate: 'today' });
    var toPicker = flatpickr('#trTo', { dateFormat: 'Y-m-d', defaultDate: d0.to, maxDate: 'today' });

    function params() {
        return { from_date: $('#trFrom').val(), to_date: $('#trTo').val(), user: $('#trUser').val(), role: $('#trRole').val(),
            type: $('#trType').val(), status: $('#trStatus').val() };
    }

    var STATUS_ICON = { Completed: 'fa-circle-check', Processing: 'fa-spinner fa-spin', Pending: 'fa-clock', Failed: 'fa-circle-xmark' };
    var columns = [
        { data: null, name: 'sno', orderable: false, width: '44px', render: function (v, t, r, meta) { return meta.settings._iDisplayStart + meta.row + 1; } },
        { data: 'used_by', name: 'used_by', render: function (v, t, r) {
            return '<span style="font-weight:600">' + esc(v) + '</span>' + (r.batch_id ? '' : '');
        } },
        { data: 'role', name: 'role', render: function (v) { return v && v !== '-' ? esc(v) : DASH; } },
        { data: 'recordings', name: 'recordings', render: function (v, t, r) {
            var extra = r.recordings_requested > v ? ' <span style="color:#94A3B8;font-size:12px" title="Recordings in the request that are not (yet) transcribed">of ' + r.recordings_requested + '</span>' : '';
            return '<span style="font-weight:700;font-variant-numeric:tabular-nums">' + v + '</span>' + extra;
        } },
        { data: 'types', name: 'type', orderable: false, render: function (v) {
            return v && v.length ? '<div class="tr-types">' + v.map(function (x) { return '<span class="tr-type">' + esc(x) + '</span>'; }).join('') + '</div>' : DASH;
        } },
        { data: 'status', name: 'status', render: function (v, t, r) {
            var cls = 's-' + String(v).toLowerCase();
            var note = r.failed && v !== 'Failed' ? '<div style="font-size:11px;color:#991B1B;margin-top:3px">' + r.failed + ' failed</div>' : '';
            return '<span class="tr-status ' + cls + '"><i class="fa ' + (STATUS_ICON[v] || '') + '" style="font-size:10px"></i>' + esc(v) + '</span>' + note;
        } },
        { data: 'credits_confirmed', name: 'credits', render: function (v, t, r) {
            // Tracked = real audio minutes x the configured rate (LeadGER's record; Sarvam does not report charges per request)
            var html = '';
            if (Number(v) > 0) html += '<span title="Tracked usage: real audio minutes × your configured rate (not Sarvam billing)">' + money(v, r.currency) + '</span>';
            else if (r.recordings > 0) html += '<span style="color:#94A3B8" title="No usage amount was recorded for this request">Not available</span>';
            else if (!Number(r.credits_estimated)) html += money(0, r.currency);
            if (Number(r.credits_estimated) > 0) html += '<small title="Estimated from the configured rate; tracked when the transcript completes">Estimated ' + money(r.credits_estimated, r.currency) + '</small>';
            return '<div class="tr-credit">' + html + '</div>';
        } },
        { data: 'date', name: 'date', render: function (v, t, r) { return '<span style="white-space:nowrap">' + esc(v) + '</span><div style="color:#64748B;font-size:12px">' + esc(r.time) + '</div>'; } },
        { data: 'batch_id', name: 'actions', orderable: false, render: function (v, t, r) {
            var off = r.has_text ? '' : ' disabled title="' + (r.status === 'Failed' ? 'The transcript failed' : 'No transcript yet (' + esc(r.status).toLowerCase() + ')') + '"';
            return '<div class="tr-actions">'
                + '<button type="button" class="tr-act tr-view" data-batch="' + esc(v) + '"' + (off || ' title="View transcript"') + ' aria-label="View transcript"><i class="fa fa-eye"></i></button>'
                + '<button type="button" class="tr-act tr-download" data-lg-perm="reports.transcript_report.export" data-batch="' + esc(v) + '"' + (off || ' title="Download transcript"') + ' aria-label="Download transcript"><i class="fa fa-download"></i></button>'
                + '<button type="button" class="tr-act tr-copy" data-batch="' + esc(v) + '"' + (off || ' title="Copy transcript"') + ' aria-label="Copy transcript"><i class="fa fa-copy"></i></button>'
                + '</div>';
        } }
    ];

    function onData(json) {
        var k = json.kpis || {};
        $('#trKpiRecordings').text(k.recordings || 0);
        $('#trKpiCompleted').text(k.completed || 0);
        $('#trKpiWaiting').text(k.waiting || 0);
        $('#trKpiCredits').text(money(k.credits || 0, k.currency));
        $('#trKpiEstimated').text(Number(k.estimated) > 0 ? '+ ' + money(k.estimated, k.currency) + ' estimated (not completed yet)' : 'Tracked usage · selected period');
        var f = json.filters || {};
        $('#trPeriod').text(f.from === f.to ? f.from : (f.from + ' to ' + f.to));
        $('#trTableSub').text((json.recordsFiltered || 0) + ' request' + (json.recordsFiltered == 1 ? '' : 's') + ' · ' + (k.recordings || 0) + ' recording' + (k.recordings == 1 ? '' : 's') + ' transcribed');
        rowsById = {};
        (json.data || []).forEach(function (r) { rowsById[r.batch_id] = r; });
    }

    table = $('#trTable').DataTable({
        processing: true, serverSide: true, searching: false, lengthChange: false, autoWidth: false, pageLength: 25,
        order: [[7, 'desc']],
        ajax: {
            url: DATA_URL, type: 'POST',
            data: function (d) {
                d.columns = d.columns.map(function (c) { return { data: c.name }; });
                return $.extend(d, { _token: '{{ csrf_token() }}' }, params());
            },
            dataSrc: function (json) { onData(json); return json.data; },
            error: function () { $('#trTableSub').text('The report could not be loaded. Please refresh and try again.'); }
        },
        dom: '<"cr-table-wrap"t>r<"cr-table-footer"<"cr-footer-info"i><"cr-footer-pagination"p>>',
        language: {
            emptyTable: 'No transcription requests for this selection', zeroRecords: 'No transcription requests for this selection',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries', infoEmpty: 'Showing 0 to 0 of 0 entries',
            processing: '<span class="cr-loading-spin"></span> Loading...', paginate: { previous: 'Previous', next: 'Next' }
        },
        columns: columns
    });
    $.fn.dataTable.ext.errMode = 'none';

    $('#trSearchBtn').on('click', function () {
        if ($('#trFrom').val() && $('#trTo').val() && $('#trTo').val() < $('#trFrom').val()) { $('#trDateError').show(); return; }
        $('#trDateError').hide();
        table.ajax.reload();
    });
    $('#trClearBtn').on('click', function () {
        var d = defaults();
        fromPicker.setDate(d.from, false); toPicker.setDate(d.to, false);
        $('#trUser, #trRole, #trType, #trStatus').val('');
        $('#trDateError').hide();
        table.ajax.reload();
    });
    $('#trLength').on('change', function () { table.page.len(parseInt(this.value, 10)).draw(); });

    /* ---- actions: the saved transcript, never regenerated ---- */
    function fetchText(batch) {
        return $.ajax({ url: SHOW_URL.replace('__B__', encodeURIComponent(batch)), type: 'GET', dataType: 'json' });
    }
    // Clipboard API, else the older copy command (http sites, or a browser that refuses the API)
    function legacyCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        return ok ? Promise.resolve() : Promise.reject();
    }
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).catch(function () { return legacyCopy(text); });
        }
        return legacyCopy(text);
    }
    function copied() { if (window.toastr) toastr.success('Transcript copied'); }
    function copyFailed() { if (window.toastr) toastr.error('The transcript could not be copied. Use Download instead.'); }
    function failed(xhr) { if (window.toastr) toastr.error((xhr && xhr.responseJSON && xhr.responseJSON.message) || 'The transcript could not be loaded.'); }

    var modalText = '', modalBatch = '';
    $(document).on('click', '.tr-view', function () {
        var batch = $(this).data('batch');
        $('#trModalBody').html('<div style="padding:30px;text-align:center;color:#64748B"><span class="cr-loading-spin"></span> Loading…</div>');
        $('#trModal').modal('show');
        fetchText(batch).done(function (res) {
            var m = res.meta;
            modalText = res.text; modalBatch = batch;
            $('#trModalTitle').text('Transcript – ' + m.used_by);
            $('#trModalMeta').text(m.role + ' · ' + m.date + ' ' + m.time + ' · ' + m.status + ' · ' + m.recordings + ' recording' + (m.recordings == 1 ? '' : 's')
                + ' · ' + m.types.join(', ') + ' · ' + money(m.credits_confirmed, m.currency));
            $('#trModalDownload').attr('href', DOWNLOAD_URL.replace('__B__', encodeURIComponent(batch)));
            if (!res.items.length) { $('#trModalBody').html('<div style="padding:20px;color:#64748B">No completed transcript in this request yet.</div>'); return; }
            $('#trModalBody').html(res.items.map(function (t, i) {
                var html = '<div class="tr-rec"><h4>Recording ' + (i + 1) + ': ' + esc(t.recording) + '</h4><div class="tr-meta">Agent: ' + esc(t.agent) + ' · Duration: ' + esc(t.duration) + (t.language ? ' · Language: ' + esc(t.language) : '') + '</div>';
                if (t.summary) html += '<div class="tr-sec">Summary</div><div class="tr-text">' + esc(t.summary) + '</div>';
                if (t.conversation.length) html += '<div class="tr-sec">Agent &amp; Customer</div><div class="tr-text">' + t.conversation.map(function (c) { return '<div class="tr-line"><span style="color:#94A3B8">[' + esc(c.time) + ']</span> <b>' + esc(c.speaker) + ':</b> ' + esc(c.text) + '</div>'; }).join('') + '</div>';
                if (t.detailed) html += '<div class="tr-sec">Detailed Conversation</div><div class="tr-text">' + esc(t.detailed) + '</div>';
                return html + '</div>';
            }).join(''));
        }).fail(function (xhr) { $('#trModal').modal('hide'); failed(xhr); });
    });
    $('#trModalCopy').on('click', function () { if (modalText) copyText(modalText).then(copied, copyFailed); });
    $(document).on('click', '.tr-download', function () {
        window.location.href = DOWNLOAD_URL.replace('__B__', encodeURIComponent($(this).data('batch')));
    });
    $(document).on('click', '.tr-copy', function () {
        var btn = this;
        btn.disabled = true;
        fetchText($(btn).data('batch')).done(function (res) {
            copyText(res.text).then(copied, copyFailed);
        }).fail(failed).always(function () { btn.disabled = false; });
    });
})();
</script>
@endpush
