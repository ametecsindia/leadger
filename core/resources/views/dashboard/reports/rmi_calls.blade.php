@extends('dashboard.layouts.master')
@section('title', 'RMI Analysis Records - LeadGER')

@push("after-styles")
<style>
/* RMI Analysis records (opened from an RMI summary card); same look as the RMI Analysis page */
.lg-rmi-calls { padding: 24px 30px 40px 30px; background-color: #F8FAFC; min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #0F172A; }
.lg-rc-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.lg-rc-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--lg-accent); text-decoration: none; margin-bottom: 6px; }
.lg-rc-back:hover { text-decoration: underline; }
.lg-rc-title { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.02em; }
.lg-rc-sub { font-size: 13.5px; color: #64748B; margin-top: 3px; }
.lg-rc-card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 18px 20px; margin-bottom: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.015); }
.lg-rc-filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
.lg-rc-field { display: flex; flex-direction: column; gap: 6px; min-width: 150px; flex: 1 1 150px; }
.lg-rc-field label { font-size: 12px; font-weight: 700; color: #475569; margin: 0; }
.lg-rc-input { height: 40px; border: 1px solid #CBD5E1; border-radius: 8px; padding: 0 12px; font-size: 13.5px; background: #FFFFFF; color: #0F172A; }
.lg-rc-input:focus { outline: none; border-color: var(--lg-accent); box-shadow: 0 0 0 3px rgba(23,105,255,0.12); }
.lg-rc-btn { height: 40px; padding: 0 18px; border: none; border-radius: 8px; background: var(--lg-accent); color: #FFFFFF; font-weight: 600; font-size: 13.5px; cursor: pointer; }
.lg-rc-btn:hover { background: #0F52CC; }
.lg-rc-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
.lg-rc-count { font-size: 14px; font-weight: 700; }
.lg-rc-count .chip { display: inline-block; margin-left: 8px; padding: 3px 10px; border-radius: 999px; font-size: 12px; background: #EFF6FF; color: var(--lg-accent); border: 1px solid #BFDBFE; }
.lg-rc-table-wrap { overflow-x: auto; }
.lg-rc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.lg-rc-table th { text-align: left; font-size: 11.5px; text-transform: uppercase; letter-spacing: .03em; color: #64748B; font-weight: 700; padding: 10px 12px; border-bottom: 1px solid #E2E8F0; white-space: nowrap; background: #F8FAFC; }
.lg-rc-table td { padding: 11px 12px; border-bottom: 1px solid #F1F5F9; vertical-align: middle; white-space: nowrap; }
.lg-rc-table tr:hover td { background: #FAFCFF; }
.lg-rc-empty td { text-align: center; color: #94A3B8; padding: 32px 12px !important; white-space: normal; }
.lg-rc-badge { display: inline-block; padding: 3px 9px; border-radius: 6px; font-weight: 700; font-size: 11.5px; background: #F1F5F9; color: #475569; }
.lg-rc-badge.angry { background: #FEF2F2; color: #DC2626; }
.lg-rc-badge.frustrated { background: #FFEDD5; color: #EA580C; }
.lg-rc-badge.confident { background: #EFF6FF; color: #1464F4; }
.lg-rc-badge.calm { background: #ECFDF5; color: #059669; }
.lg-rc-badge.done { background: #ECFDF5; color: #047857; }
.lg-rc-badge.busy { background: #FFFBEB; color: #B45309; }
.lg-rc-badge.fail { background: #FEF2F2; color: #DC2626; }
.lg-rc-act { display: inline-flex; align-items: center; justify-content: center; gap: 5px; height: 30px; padding: 0 10px; border-radius: 7px; border: 1px solid #CBD5E1; background: #FFFFFF; color: #334155; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none !important; }
.lg-rc-act:hover { border-color: var(--lg-accent); color: var(--lg-accent); }
.lg-rc-act:disabled { opacity: .45; cursor: not-allowed; }
.lg-rc-pager { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 14px; font-size: 13px; color: #64748B; }
.lg-rc-pager button { height: 32px; min-width: 32px; padding: 0 10px; border: 1px solid #CBD5E1; background: #FFFFFF; border-radius: 7px; cursor: pointer; font-weight: 600; color: #334155; }
.lg-rc-pager button.active { background: var(--lg-accent); border-color: var(--lg-accent); color: #FFFFFF; }
.lg-rc-pager button:disabled { opacity: .45; cursor: not-allowed; }
.lg-rc-export { display: flex; align-items: center; gap: 10px; }
.lg-rc-pager .lg-rc-xbtn { height: 38px; padding: 0 16px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 7px; cursor: pointer; transition: background .15s, border-color .15s, box-shadow .15s; }
.lg-rc-pager .lg-rc-xbtn.excel { background: #16A34A; border: 1px solid #16A34A; color: #FFFFFF; box-shadow: 0 2px 8px rgba(22,163,74,0.20); }
.lg-rc-pager .lg-rc-xbtn.excel:hover:not(:disabled) { background: #15803D; border-color: #15803D; }
.lg-rc-pager .lg-rc-xbtn.csv { background: #FFFFFF; border: 1px solid #CBD5E1; color: #334155; }
.lg-rc-pager .lg-rc-xbtn.csv:hover:not(:disabled) { border-color: var(--lg-accent); color: var(--lg-accent); }
.lg-rc-pager .lg-rc-xbtn:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; }
.lg-rc-lead { font-family: monospace; font-weight: 700; color: var(--lg-accent); text-decoration: none; }
.lg-rc-lead:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="lg-rmi-calls">
    <div class="lg-rc-header">
        <div>
            <a href="{{ route('rmiAnalysis') }}" class="lg-rc-back" id="rcBackLink"><i class="fas fa-arrow-left"></i> RMI Analysis</a>
            <h1 class="lg-rc-title" id="rcTitle">{{ $categories[$category] }} &ndash; RMI Analysis Records</h1>
            <div class="lg-rc-sub">Calls behind the RMI Analysis summary card, with the same filters and counting rules.</div>
        </div>
    </div>

    <div class="lg-rc-card">
        <div class="lg-rc-filters">
            <div class="lg-rc-field"><label for="rcFrom">From Date</label><input type="date" id="rcFrom" class="lg-rc-input" value="{{ date('Y-m-01') }}"></div>
            <div class="lg-rc-field"><label for="rcTo">To Date</label><input type="date" id="rcTo" class="lg-rc-input" value="{{ date('Y-m-d') }}"></div>
            <div class="lg-rc-field"><label for="rcGroup">Groups</label>
                <select id="rcGroup" class="lg-rc-input"><option value="">All Groups</option>
                    @foreach($Teams as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                </select></div>
            <div class="lg-rc-field"><label for="rcAgent">Agent</label>
                <select id="rcAgent" class="lg-rc-input"><option value="">All Agents</option>
                    @foreach($Users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                </select></div>
            <div class="lg-rc-field"><label for="rcCategory">Records</label>
                <select id="rcCategory" class="lg-rc-input">
                    @foreach($categories as $key => $label)<option value="{{ $key }}" {{ $key === $category ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                </select></div>
            <button type="button" class="lg-rc-btn" id="rcApply"><i class="fas fa-search"></i> Search</button>
        </div>
    </div>

    <div class="lg-rc-card">
        <div class="lg-rc-toolbar">
            <div class="lg-rc-count"><span id="rcCount">0</span> record(s) <span class="chip" id="rcChip">{{ $categories[$category] }}</span></div>
            <input type="search" id="rcSearch" class="lg-rc-input" placeholder="Search agent, customer, number, lead ID..." style="min-width: 260px;">
        </div>
        <div class="lg-rc-table-wrap">
            <table class="lg-rc-table">
                <thead><tr>
                    <th>Lead ID</th><th>Agent</th><th>Customer Name</th><th>Mobile Number</th><th>Call Date &amp; Time</th><th>Duration</th>
                    <th>Recording</th><th>RMI Emotion</th><th>Analysis Status</th><th>View Details</th>
                </tr></thead>
                <tbody id="rcBody"><tr class="lg-rc-empty"><td colspan="10">Loading records...</td></tr></tbody>
            </table>
        </div>
        {{-- Export (left) | record info + pagination (right) --}}
        <div class="lg-rc-pager">
            <div class="lg-rc-export">
                <button type="button" class="lg-rc-xbtn excel" id="rcExportXlsx" data-lg-perm="recordings.rmi_analysis.export" data-format="xlsx" title="Download all matching records as Excel" disabled>
                    <i class="fas fa-file-excel"></i> <span>Excel</span>
                </button>
                <button type="button" class="lg-rc-xbtn csv" id="rcExportCsv" data-lg-perm="recordings.rmi_analysis.export" data-format="csv" title="Download all matching records as CSV" disabled>
                    <i class="fas fa-file-csv"></i> <span>CSV</span>
                </button>
            </div>
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <div id="rcInfo"></div>
                <div id="rcPages" style="display: flex; gap: 6px; flex-wrap: wrap;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push("after-scripts")
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    var labels = @json($categories);
    var page = 1, size = 10, searchTimer = null, audio = null, audioBtn = null;

    // Filters come from the RMI card link (?from_date=&to_date=&group_id=&agent_id=&category=)
    var p = new URLSearchParams(window.location.search);
    var setIf = function (sel, val) { if (val !== null && $(sel).find('option[value="' + String(val).replace(/[^0-9a-z_]/gi, '') + '"]').length) $(sel).val(val); };
    if (/^\d{4}-\d{2}-\d{2}$/.test(p.get('from_date') || '')) $('#rcFrom').val(p.get('from_date'));
    if (/^\d{4}-\d{2}-\d{2}$/.test(p.get('to_date') || '')) $('#rcTo').val(p.get('to_date'));
    setIf('#rcGroup', p.get('group_id'));
    setIf('#rcAgent', p.get('agent_id'));
    setIf('#rcCategory', p.get('category'));

    function esc(s) { return s === null || s === undefined || s === '' ? '—' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function filters() {
        return { from_date: $('#rcFrom').val(), to_date: $('#rcTo').val(), group_id: $('#rcGroup').val(), agent_id: $('#rcAgent').val(), category: $('#rcCategory').val() };
    }
    function syncUrl() {
        var f = filters();
        history.replaceState(null, '', window.location.pathname + '?' + new URLSearchParams(f).toString());
        // "RMI Analysis" link returns to the RMI page with the same filters
        var back = new URLSearchParams({ from_date: f.from_date, to_date: f.to_date, group_id: f.group_id, agent_id: f.agent_id });
        $('#rcBackLink').attr('href', "{{ route('rmiAnalysis') }}?" + back.toString());
        $('#rcTitle').text(labels[f.category] + ' – RMI Analysis Records');
        $('#rcChip').text(labels[f.category]);
    }

    function statusBadge(st) {
        if (st === 'completed') return '<span class="lg-rc-badge done">Completed</span>';
        if (st === 'processing') return '<span class="lg-rc-badge busy">Transcribing</span>';
        if (st === 'pending' || st === 'queued') return '<span class="lg-rc-badge busy">Pending</span>';
        if (st === 'failed') return '<span class="lg-rc-badge fail">Failed</span>';
        return '<span class="lg-rc-badge">Not analysed</span>';
    }

    function load() {
        var f = filters();
        if (f.from_date && f.to_date && f.to_date < f.from_date) {
            if (typeof toastr !== 'undefined') toastr.error('To Date cannot be earlier than From Date.');
            return;
        }
        syncUrl();
        $('#rcBody').html('<tr class="lg-rc-empty"><td colspan="10">Loading records...</td></tr>');
        $.ajax({
            url: "{{ route('rmiAnalysisCallsData') }}",
            type: 'POST',
            data: $.extend({ _token: "{{ csrf_token() }}", start: (page - 1) * size, length: size, search: $('#rcSearch').val(), draw: page }, f),
            success: function (res) {
                $('#rcCount').text(res.recordsFiltered);
                lastTotal = res.recordsFiltered;
                setExportEnabled(!exporting);
                var rows = res.data || [];
                if (!rows.length) {
                    $('#rcBody').html('<tr class="lg-rc-empty"><td colspan="10"><i class="fas fa-inbox" style="font-size: 18px; display: block; margin-bottom: 6px;"></i>No matching records found.</td></tr>');
                } else {
                    $('#rcBody').html(rows.map(function (r) {
                        var emo = r.emotion ? '<span class="lg-rc-badge ' + r.emotion.toLowerCase() + '">' + esc(r.emotion) + '</span>' : '<span style="color:#94A3B8">—</span>';
                        var rec = r.recording_url
                            ? '<button type="button" class="lg-rc-act rc-play" data-src="' + esc(r.recording_url) + '" title="Play recording"><i class="fas fa-play"></i> Play</button>'
                            : '<span style="color:#94A3B8">' + (r.rec_status === 'awaiting' || r.rec_status === 'pending' || r.rec_status === 'downloading' ? 'Waiting for recording' : 'No recording') + '</span>';
                        var detail = r.detail_url
                            ? '<a class="lg-rc-act" href="' + esc(r.detail_url) + '" title="Analysis results and transcript"><i class="fas fa-file-alt"></i> View Details</a>'
                            : '<span style="color:#94A3B8">—</span>';
                        // Lead ID: the blue link to the lead; "-" when the call has no lead
                        var lead = r.lead_id && r.lead_url ? '<a class="lg-rc-lead" href="' + esc(r.lead_url) + '" title="Open lead ' + esc(r.lead_id) + '">' + esc(r.lead_id) + '</a>' : '-';
                        return '<tr>'
                            + '<td>' + lead + '</td>'
                            + '<td style="font-weight:600">' + esc(r.agent_name) + '</td>'
                            + '<td>' + (r.customer_name ? esc(r.customer_name) : '-') + '</td>'
                            + '<td style="font-family: monospace">' + esc(r.phonenumber) + '</td>'
                            + '<td>' + esc(r.date) + '</td>'
                            + '<td style="font-variant-numeric: tabular-nums">' + esc(r.duration) + '</td>'
                            + '<td>' + rec + '</td>'
                            + '<td>' + emo + '</td>'
                            + '<td>' + statusBadge(r.ai_status) + '</td>'
                            + '<td>' + detail + '</td>'
                            + '</tr>';
                    }).join(''));
                }
                renderPager(res.recordsFiltered);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to load records.';
                $('#rcBody').html('<tr class="lg-rc-empty"><td colspan="10" style="color:#EF4444">' + esc(msg) + '</td></tr>');
                lastTotal = 0;
                setExportEnabled(false);
            }
        });
    }

    function renderPager(total) {
        var pages = Math.max(1, Math.ceil(total / size));
        if (page > pages) page = pages;
        var from = total ? (page - 1) * size + 1 : 0, to = Math.min(total, page * size);
        $('#rcInfo').text('Showing ' + from + ' to ' + to + ' of ' + total + ' records');
        var html = '<button type="button" data-p="' + (page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '>&lsaquo;</button>';
        for (var i = 1; i <= pages; i++) {
            if (pages > 7 && i > 2 && i < pages - 1 && Math.abs(i - page) > 1) { if (i === 3 || i === pages - 2) html += '<span>…</span>'; continue; }
            html += '<button type="button" data-p="' + i + '"' + (i === page ? ' class="active"' : '') + '>' + i + '</button>';
        }
        html += '<button type="button" data-p="' + (page + 1) + '"' + (page >= pages ? ' disabled' : '') + '>&rsaquo;</button>';
        $('#rcPages').html(html);
    }

    // ---- Excel / CSV: every record matching the filters + search (built on the server, same rules as the table) ----
    var lastTotal = 0, exporting = false;
    function setExportEnabled(on) {
        $('#rcExportXlsx, #rcExportCsv').prop('disabled', !on || lastTotal === 0)
            .attr('title', lastTotal === 0 ? 'No records to export' : null);
    }
    $('.lg-rc-xbtn').on('click', function () {
        if (exporting) return;
        if (!lastTotal) { if (typeof toastr !== 'undefined') toastr.info('No records to export.'); return; }
        var $btn = $(this), format = $btn.data('format'), original = $btn.html();
        var q = new URLSearchParams($.extend(filters(), { search: $('#rcSearch').val() || '' }));
        exporting = true;
        setExportEnabled(false);
        $btn.html('<i class="fas fa-spinner fa-spin"></i> <span>Preparing...</span>');
        fetch("{{ url('/admin/reports/rmi-analysis/calls/export') }}/" + format + '?' + q.toString(), { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) {
                    return res.json().catch(function () { return {}; }).then(function (j) { throw new Error(j.message || ('Export failed (error ' + res.status + ').')); });
                }
                var cd = res.headers.get('Content-Disposition') || '';
                var m = /filename="?([^";]+)"?/i.exec(cd);
                return res.blob().then(function (blob) { return { blob: blob, name: m ? m[1] : 'rmi-analysis.' + format }; });
            })
            .then(function (f) {
                var a = document.createElement('a');
                a.href = URL.createObjectURL(f.blob);
                a.download = f.name;
                document.body.appendChild(a);
                a.click();
                setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
            })
            .catch(function (err) {
                if (typeof toastr !== 'undefined') toastr.error(err.message || 'The export failed. Please try again.');
            })
            .finally(function () {
                exporting = false;
                $btn.html(original);
                setExportEnabled(true);
            });
    });

    $('#rcPages').on('click', 'button[data-p]', function () { page = parseInt($(this).data('p'), 10); load(); });
    $('#rcApply').on('click', function () { page = 1; load(); });
    $('#rcCategory').on('change', function () { page = 1; load(); });
    $('#rcSearch').on('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(function () { page = 1; load(); }, 300); });

    // Recording playback (the protected downloadRecordings route, as on Call Recordings)
    $('#rcBody').on('click', '.rc-play', function () {
        var btn = this;
        if (audio && audioBtn === btn) {
            if (audio.paused) { audio.play(); btn.innerHTML = '<i class="fas fa-pause"></i> Pause'; } else { audio.pause(); btn.innerHTML = '<i class="fas fa-play"></i> Play'; }
            return;
        }
        if (audio) { audio.pause(); if (audioBtn) audioBtn.innerHTML = '<i class="fas fa-play"></i> Play'; }
        audio = new Audio(btn.getAttribute('data-src'));
        audioBtn = btn;
        audio.onended = function () { btn.innerHTML = '<i class="fas fa-play"></i> Play'; };
        audio.onerror = function () { btn.innerHTML = '<i class="fas fa-triangle-exclamation"></i> Unavailable'; btn.disabled = true; };
        audio.play();
        btn.innerHTML = '<i class="fas fa-pause"></i> Pause';
    });

    load();
});
</script>
@endpush
