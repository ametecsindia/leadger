@extends('dashboard.layouts.master')
@section('title', 'Campaign Status')

@push("after-styles")
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
{{-- Same page design as the Call Report (header, filter card, KPI cards, table card) --}}
<link rel="stylesheet" href="{{ asset('assets/dashboard/css/lg-report-page.css') }}?v=4">
@endpush

@section('content')
@php
    $statusNewLead = $Statuses->first(function($s) { return strtolower(trim($s->name)) === 'new lead'; });
    $statusFollowUp = $Statuses->first(function($s) { return strtolower(trim($s->name)) === 'follow up'; });
    $statusNotInRecovery = $Statuses->first(function($s) { return strtolower(trim($s->name)) === 'not in recovery'; });

    $newLeadCol = $statusNewLead ? 'status'.$statusNewLead->id : 'status1';
    $followUpCol = $statusFollowUp ? 'status'.$statusFollowUp->id : 'status2';
    $notInRecoveryCol = $statusNotInRecovery ? 'status'.$statusNotInRecovery->id : 'status3';
@endphp
<div class="cr-canvas">

    {{-- PAGE HEADER --}}
    <div class="cr-header">
        <div class="cr-header-left">
            <div class="cr-header-icon">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <h1 class="cr-header-title">Campaign Status</h1>
                <div class="cr-header-sub" id="csPeriodLabel">All campaigns &middot; as of {{ date('d M Y') }}</div>
            </div>
        </div>
        <div class="cr-export-wrap" data-lg-perm="reports.campaigns.export">
            <button type="button" class="cr-export-btn" id="csExportBtn" aria-haspopup="true" aria-expanded="false" aria-controls="csExportMenu">
                <i class="fa fa-download"></i> Export
            </button>
            <div class="cr-export-menu" id="csExportMenu" role="menu" hidden>
                <button type="button" class="cr-export-item" role="menuitem" data-stat="print"><i class="fa fa-print"></i> Print</button>
                <button type="button" class="cr-export-item" role="menuitem" data-stat="excel"><i class="fa fa-file-pdf"></i> PDF</button>
            </div>
        </div>
    </div>

    {{-- FILTER CARD (no date range: most leads have no created date, so a date filter could not be applied correctly) --}}
    <div class="cr-filter-card">
        <div class="cr-filter-row">
            <div class="cr-filter-group">
                <label class="cr-filter-label" for="csCampaignFilter">Campaign</label>
                <select id="csCampaignFilter" class="cr-select">
                    <option value="">All campaigns</option>
                    @foreach($Campaigns as $Campaign)
                        <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cr-filter-group">
                <span class="cr-filter-label">&nbsp;</span>
                <button type="button" class="cr-apply-btn" id="csApplyBtn">
                    <i class="fa fa-rotate-right"></i> Apply
                </button>
            </div>
        </div>
    </div>

    {{-- KPI CARDS: totals of the same rows as the table (every page) --}}
    <div class="cr-cards-row">
        <div class="cr-stat-card cr-accent">
            <div class="cr-stat-card-icon icon-total"><i class="fa-solid fa-bullhorn"></i></div>
            <div class="cr-stat-card-label">Total Campaigns</div>
            <div class="cr-stat-card-value" id="csStatCampaigns"><span class="cr-loading-spin"></span></div>
        </div>
        <div class="cr-stat-card">
            <div class="cr-stat-card-icon icon-talk"><i class="fa-solid fa-users"></i></div>
            <div class="cr-stat-card-label">Total Leads</div>
            <div class="cr-stat-card-value" id="csStatLeads"><span class="cr-loading-spin"></span></div>
        </div>
        <div class="cr-stat-card">
            <div class="cr-stat-card-icon icon-avg"><i class="fa-solid fa-phone-volume"></i></div>
            <div class="cr-stat-card-label">Total Calls</div>
            <div class="cr-stat-card-value" id="csStatCalls"><span class="cr-loading-spin"></span></div>
        </div>
        <div class="cr-stat-card">
            <div class="cr-stat-card-icon icon-notconn"><i class="fa-solid fa-handshake"></i></div>
            <div class="cr-stat-card-label">Deals</div>
            <div class="cr-stat-card-value" id="csStatDeals"><span class="cr-loading-spin"></span></div>
        </div>
        <div class="cr-stat-card">
            <div class="cr-stat-card-icon icon-teal"><i class="fa-solid fa-user-plus"></i></div>
            <div class="cr-stat-card-label">New Leads</div>
            <div class="cr-stat-card-value" id="csStatNewLeads"><span class="cr-loading-spin"></span></div>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="cr-table-card">
        <div class="cr-table-head">
            <div>
                <div class="cr-table-title">Campaign Performance Summary</div>
                <div class="cr-table-sub" id="csTableSub">Loading&hellip;</div>
            </div>
            <div class="cr-table-tools">
                <label class="cr-length" for="csLength">
                    <span>Show</span>
                    <span class="cr-select-wrap">
                        <select id="csLength" class="cr-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="30" selected>30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </span>
                    <span>entries</span>
                </label>
                <div class="cr-search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="csSearch" placeholder="Search campaign..." autocomplete="off" aria-label="Search campaigns">
                </div>
            </div>
        </div>

        <table class="cr-table cr-dt" id="leads_table" style="width: 100%">
            <thead>
                <tr>
                    <th style="min-width: 220px;">Campaign Name</th>
                    <th class="text-center">Count</th>
                    <th class="text-center">Call Count</th>
                    <th class="text-center">Leads</th>
                    <th class="text-center">Deals</th>
                    <th class="text-center">New Lead</th>
                    <th class="text-center">Follow Up</th>
                    <th class="text-center">Not In Recovery</th>
                </tr>
            </thead>
            {{-- Total of every row for this filter (pinned: not sorted or paged) --}}
            <tfoot id="csTotalRow" hidden>
                <tr>
                    <td>Total</td>
                    <td class="text-center" data-total="total"></td>
                    <td class="text-center" data-total="call_count"></td>
                    <td class="text-center" data-total="leads"></td>
                    <td class="text-center" data-total="deal"></td>
                    <td class="text-center" data-total="{{ $newLeadCol }}"></td>
                    <td class="text-center" data-total="{{ $followUpCol }}"></td>
                    <td class="text-center" data-total="{{ $notInRecoveryCol }}"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Print / PDF: the same request as before (this page in a new tab, with the campaign and search) --}}
    <form id="filter_form" method="GET" action="" style="display: none;">
        <input type="hidden" name="stat" id="search_submit_stat" value="">
        <input type="hidden" name="find_campaign" id="find_campaign" value="">
        <input type="hidden" name="find_q" id="find_q" value="">
    </form>

</div>

<!-- Modal -->
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content lead-content"></div>
    </div>
</div>
@endsection

@push("after-scripts")
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script type="text/javascript">
var csState = { campaign: '', search: '' };

function csEsc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// "3(37.5%)" -> number with a percent pill (grey for 0%, light blue above 0%)
function csNumPill(count, pct) {
    var pos = parseFloat(pct) > 0;
    return '<span class="cr-num-pill"><span class="cr-num">' + csEsc(count) + '</span>' +
           '<span class="cr-pct ' + (pos ? 'has-val' : 'is-zero') + '">' + csEsc(pct) + '</span></span>';
}
function csPctCell(data) {
    var m = String(data || '').trim().match(/^(\d+)\s*\(([\d.]+%)\)$/);
    if (m) return csNumPill(m[1], m[2]);
    return data ? '<span class="cr-num">' + csEsc(data) + '</span>' : csNumPill(0, '0%');
}
function csNum(data) {
    return '<span class="cr-num">' + csEsc(data || 0) + '</span>';
}
function csPct(part, total) {
    return (total > 0 ? Math.round(part / total * 100 * 100) / 100 : 0) + '%';
}

function csCampaignLabel() {
    var sel = document.getElementById('csCampaignFilter');
    return csState.campaign ? sel.options[sel.selectedIndex].text : 'All campaigns';
}

// Cards, Total row and subtitles from the server totals (all rows for this filter and search, not only this page)
function csRenderTotals(json) {
    var t = (json && json.statics && json.statics.totals) || {};
    var n = t.campaigns || 0;
    var fmt = function (v) { return (parseInt(v, 10) || 0).toLocaleString(); };
    $('#csStatCampaigns').text(fmt(n));
    $('#csStatLeads').text(fmt(t.total));
    $('#csStatCalls').text(fmt(t.call_count));
    $('#csStatDeals').text(fmt(t.deal));
    $('#csStatNewLeads').text(fmt(t['{{ $newLeadCol }}']));

    $('#csTotalRow td[data-total]').each(function () {
        var key = this.getAttribute('data-total');
        var v = parseInt(t[key], 10) || 0;
        this.innerHTML = (key === 'total' || key === 'call_count') ? csNum(v) : csNumPill(v, csPct(v, t.total || 0));
    });
    document.getElementById('csTotalRow').hidden = n === 0;

    var sub = n + ' campaign' + (n === 1 ? '' : 's') + ' · All time';
    if (csState.campaign) sub += ' · ' + csCampaignLabel();
    if (csState.search) sub += ' · matching “' + csState.search + '”';
    $('#csTableSub').text(sub);
    $('#csPeriodLabel').text(csCampaignLabel() + ' · as of {{ date('d M Y') }}');
}

$(document).ready(function () {
    var translation = {!! json_encode(__("backend.dataTablesTranslation")) !!};
    var language = $.extend({}, typeof translation === 'object' ? translation : {}, {
        emptyTable: 'No campaigns found for this selection', sEmptyTable: 'No campaigns found for this selection',
        zeroRecords: 'No campaigns found for this selection', sZeroRecords: 'No campaigns found for this selection'
    });

    window.statusDataTable = $('#leads_table').DataTable({
        "processing": true,
        "serverSide": true,
        "searching": true,
        "responsive": false,
        "scrollX": false,
        "autoWidth": false,
        "pageLength": 30,
        "lengthMenu": [[10, 25, 30, 50, 100], [10, 25, 30, 50, 100]],
        "ajax": {
            "url": "{{ route('campaignStatusData') }}",
            "dataType": "json",
            "type": "POST",
            "data": function (data) {
                data._token = "{{ csrf_token() }}";
                data.find_q = csState.search;
                data.find_campaign = csState.campaign;
            }
        },
        "dom": '<"cr-table-wrap"t>r<"cr-table-footer"<"cr-footer-info"i><"cr-footer-pagination"p>>',
        "fnDrawCallback": function () {
            var api = this.api();
            csRenderTotals(api.ajax.json());
            var $pg = $('#leads_table_paginate');
            $pg.toggle($pg.find('.paginate_button').length > 3);
        },
        "language": language,
        "columns": [
            { "data": "campaign_name", "orderable": true, "className": "cr-campaign-cell" },
            { "data": "total", "orderable": true, "className": "text-center", "render": csNum },
            { "data": "call_count", "orderable": true, "className": "text-center", "render": csNum },
            { "data": "leads", "orderable": true, "className": "text-center", "render": csPctCell },
            { "data": "deal", "orderable": true, "className": "text-center", "render": csPctCell },
            { "data": "{{ $newLeadCol }}", "orderable": true, "className": "text-center", "render": csPctCell },
            { "data": "{{ $followUpCol }}", "orderable": true, "className": "text-center", "render": csPctCell },
            { "data": "{{ $notInRecoveryCol }}", "orderable": true, "className": "text-center", "render": csPctCell }
        ],
        "order": [[0, "asc"]]
    });
    $.fn.dataTable.ext.errMode = 'none';

    // Filter card: Campaign + Apply
    $('#csApplyBtn').on('click', function () {
        csState.campaign = $('#csCampaignFilter').val() || '';
        window.statusDataTable.draw();
    });

    // Table card: entries + search
    $('#csLength').on('change', function () {
        window.statusDataTable.page.len(parseInt(this.value, 10)).draw();
    });
    var searchTimer = null;
    $('#csSearch').on('input', function () {
        var val = $.trim(this.value);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if (val === csState.search) return;
            csState.search = val;
            window.statusDataTable.draw();
        }, 300);
    });

    // Export dropdown: Print / PDF
    var $btn = $('#csExportBtn'), menu = document.getElementById('csExportMenu');
    function closeMenu(focusBtn) {
        if (menu.hidden) return;
        menu.hidden = true;
        $btn.attr('aria-expanded', 'false');
        if (focusBtn) $btn.focus();
    }
    $btn.on('click', function (e) {
        e.stopPropagation();
        if (menu.hidden) {
            menu.hidden = false;
            $btn.attr('aria-expanded', 'true');
            $(menu).find('.cr-export-item').first().focus();
        } else {
            closeMenu(false);
        }
    });
    $(menu).on('click', '.cr-export-item', function () {
        closeMenu(true);
        print_as($(this).data('stat'));
    }).on('keydown', function (e) {
        var items = $(menu).find('.cr-export-item').get(), i = items.indexOf(document.activeElement);
        if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
        else if (e.key === 'Tab') { closeMenu(false); }
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.cr-export-wrap').length) closeMenu(false);
    }).on('keydown', function (e) {
        if (e.key === 'Escape') closeMenu(true);
    });
});

function print_as(stat){
    $("#find_campaign").val(csState.campaign);
    $("#find_q").val(csState.search);
    $("#search_submit_stat").val(stat);
    $("#filter_form").attr('action', '');
    $("#filter_form").attr('target', '_blank');
    $("#filter_form").submit();
    $("#filter_form").attr('action', '');
    $("#search_submit_stat").val("");
    $("#filter_form").attr('target', '');
}
</script>
@endpush
