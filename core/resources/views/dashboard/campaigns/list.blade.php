@extends('dashboard.layouts.master')
@section('title', 'Campaigns - LeadGER')

@push("after-styles")
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
<style>
/* ==========================================================================
   LeadGER Design System - Campaigns List Page (Matching Visual Reference)
   ========================================================================== */

.lg-campaigns-dashboard {
    padding: 24px 30px 40px 30px;
    background-color: #F8FAFC;
    min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #0F172A;
}

/* Page Header */
.lg-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-header-left {
    display: flex;
    flex-direction: column;
}

.lg-breadcrumb {
    font-size: 13px;
    font-weight: 600;
    color: var(--lg-accent);
    margin-bottom: 4px;
    text-transform: none;
    letter-spacing: normal;
}

.lg-header-title-row {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-header-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lg-accent);
    flex-shrink: 0;
}

.lg-page-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 22px;
    line-height: 1.25;
    letter-spacing: -0.02em;
}

.lg-page-subtitle {
    font-size: 13px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 400;
}

.lg-page-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* Buttons */
.lg-btn {
    height: 40px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}

.lg-btn-primary {
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border-color: var(--lg-accent);
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.22);
}

.lg-btn-primary:hover {
    background: var(--lg-accent-hover);
    border-color: var(--lg-accent-hover);
    color: #FFFFFF !important;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
    transform: translateY(-1px);
}

.lg-btn-secondary {
    background: #FFFFFF;
    color: #334155 !important;
    border: 1px solid #CBD5E1;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.lg-btn-secondary:hover {
    background: #F8FAFC;
    color: #0F172A !important;
    border-color: #94A3B8;
}

/* Workspace Table Card */
.lg-table-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03), 0 4px 12px rgba(15, 23, 42, 0.02);
    overflow: hidden;
}

/* Table Controls Toolbar */
.lg-table-toolbar {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #F1F5F9;
    flex-wrap: wrap;
    gap: 12px;
    background: #FFFFFF;
}

.lg-dt-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748B;
    font-size: 13.5px;
    font-weight: 500;
}

.lg-dt-select {
    height: 38px;
    padding: 0 32px 0 12px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    font-size: 13px;
    font-weight: 600;
    color: #0F172A;
    background-color: #FFFFFF !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: calc(100% - 10px) center !important;
    background-size: 12px 12px !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    outline: none;
    cursor: pointer;
    transition: border-color 0.15s ease;
}
.lg-dt-select::-ms-expand {
    display: none !important;
}

.lg-dt-select:focus {
    border-color: var(--lg-accent);
}

.lg-search-box {
    position: relative;
    min-width: 250px;
    display: flex;
    align-items: center;
}

.lg-search-box input {
    width: 100%;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    padding: 0 14px 0 38px;
    font-size: 13.5px;
    color: #0F172A;
    outline: none;
    background: #FFFFFF;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lg-search-box input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lg-search-box input::placeholder {
    color: #94A3B8;
    font-weight: 400;
}

.lg-search-icon {
    position: absolute;
    left: 12px;
    color: #94A3B8;
    pointer-events: none;
}

/* Master Table Design */
.lg-campaigns-table {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    margin: 0 !important;
    background-color: #FFFFFF !important;
}

.lg-campaigns-table thead th {
    background-color: #F8FAFC !important;
    color: #475569 !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 13px 18px !important;
    border-bottom: 1px solid #E2E8F0 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
}

.lg-campaigns-table tbody td {
    padding: 16px 18px !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #F1F5F9 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    color: #334155 !important;
    font-size: 13.5px !important;
    background-color: #FFFFFF;
    transition: background-color 0.1s ease;
}

.lg-campaigns-table tbody tr:hover td {
    background-color: #F8FAFC !important;
}

.lg-campaigns-table tbody tr:last-child td {
    border-bottom: none !important;
}

/* Campaign Name & Subtitle */
.lg-camp-cell {
    display: flex;
    flex-direction: column;
    gap: 3px;
    justify-content: center;
}

.lg-camp-name {
    font-size: 15px !important;
    font-weight: 700 !important;
    color: #0F172A !important;
    text-decoration: none !important;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 320px;
    transition: color 0.15s ease;
}

.lg-camp-name:hover {
    color: var(--lg-accent) !important;
}

.lg-camp-sub {
    font-size: 12.5px !important;
    color: #64748B !important;
    font-weight: 400 !important;
    line-height: 1.25;
}

/* Numeric Values */
.lg-num-cell {
    font-size: 14px !important;
    font-weight: 500 !important;
    color: #0F172A !important;
    display: inline-block;
    white-space: nowrap;
}

.lg-conv-cell {
    font-weight: 700 !important;
}

/* Progress Bar */
.lg-progress-cell {
    width: 105px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.lg-progress-track {
    width: 100%;
    height: 7px;
    border-radius: 999px;
    background: #E2E8F0;
    overflow: hidden;
}

.lg-progress-fill {
    height: 100%;
    border-radius: 999px;
    background: var(--lg-accent);
    transition: width 0.3s ease;
}

.lg-progress-label {
    font-size: 12px !important;
    font-weight: 400 !important;
    color: #64748B !important;
    white-space: nowrap;
}

/* Status Badges */
.lg-status-pill {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 4px 12px !important;
    border-radius: 9999px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
    line-height: 1.2 !important;
}

.lg-status-pill .lg-dot {
    width: 6px !important;
    height: 6px !important;
    border-radius: 50% !important;
    display: inline-block !important;
    flex-shrink: 0 !important;
}

.lg-status-pill.status-active {
    background: #ECFDF5 !important;
    color: #059669 !important;
    border: 1px solid #A7F3D0 !important;
}
.lg-status-pill.status-active .lg-dot {
    background: #10B981 !important;
}

.lg-status-pill.status-paused {
    background: #FFFBEB !important;
    color: #D97706 !important;
    border: 1px solid #FDE68A !important;
}
.lg-status-pill.status-paused .lg-dot {
    background: #F59E0B !important;
}

.lg-status-pill.status-completed {
    background: #EFF6FF !important;
    color: var(--lg-accent) !important;
    border: 1px solid #BFDBFE !important;
}
.lg-status-pill.status-completed .lg-dot {
    background: var(--lg-accent) !important;
}

/* Bottom Bar */
.lg-table-bottom-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-top: 1px solid #F1F5F9;
    flex-wrap: wrap;
    gap: 12px;
}

.lg-entries-info {
    font-size: 13px !important;
    color: #64748B !important;
    font-weight: 500 !important;
}

.lg-pagination a,
.lg-pagination .paginate_button {
    border-radius: 6px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    padding: 6px 12px !important;
    border: 1px solid #E2E8F0 !important;
    background: #FFFFFF !important;
    color: #475569 !important;
    margin: 0 2px !important;
    transition: all 0.15s ease !important;
    display: inline-block !important;
    text-decoration: none !important;
    cursor: pointer !important;
}

.lg-pagination .paginate_button:hover {
    background: #F8FAFC !important;
    color: #0F172A !important;
    border-color: #CBD5E1 !important;
}

.lg-pagination .paginate_button.current,
.lg-pagination .paginate_button.current:hover {
    background: var(--lg-accent) !important;
    color: #FFFFFF !important;
    border-color: var(--lg-accent) !important;
}

/* Actions column (Download / Delete) */
.lg-actions-cell { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
.lg-row-act {
    width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid #E2E8F0; background: #FFFFFF; color: var(--lg-accent); cursor: pointer; padding: 0;
    transition: background .15s ease, border-color .15s ease, color .15s ease;
}
.lg-row-act:hover { background: #EFF6FF; border-color: #BFDBFE; }
.lg-row-act.is-delete { color: #DC2626; }
.lg-row-act.is-delete:hover { background: #FEF2F2; border-color: #FECACA; }
.lg-row-act:disabled { opacity: .55; cursor: wait; }
#leads_table th.lg-actions-th, #leads_table td.lg-actions-td { text-align: center; }
</style>
@endpush

@section('content')
<div class="lg-campaigns-dashboard">

    <!-- PAGE HEADER -->
    <div class="lg-page-header">
        <div class="lg-header-left">
            <div class="lg-breadcrumb">Campaigns</div>
            <div class="lg-header-title-row">
                <div class="lg-header-icon-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="lg-page-title">Campaigns</h1>
                    <div class="lg-page-subtitle">Manage your sales pipeline from one workspace.</div>
                </div>
            </div>
        </div>

        <div class="lg-page-header-right">
            <a class="lg-btn lg-btn-secondary" href="{{ route('campaignsDuplicate') }}" title="Duplicate Campaign">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span>Duplicate</span>
            </a>
            <a class="lg-btn lg-btn-secondary" href="{{ route('leadsCreate') }}" title="Import Leads">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Import Leads</span>
            </a>
            <a class="lg-btn lg-btn-primary" href="{{ route('campaignsCreate') }}" title="Create New Campaign">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>New Campaign</span>
            </a>
        </div>
    </div>

    <!-- CAMPAIGN WORKSPACE TABLE CONTAINER -->
    <div class="lg-table-card">
        <!-- Top Toolbar -->
        <div class="lg-table-toolbar">
            <div class="lg-dt-controls">
                <span>Show</span>
                <select id="dtLengthSelect" class="lg-dt-select" onchange="changePageLength(this.value)">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="30" selected>30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>

            <div class="lg-search-box">
                <svg class="lg-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="dtSearchInput" placeholder="Search campaign..." onkeyup="handleSearchInput(this.value)" autocomplete="off">
            </div>
        </div>

        <!-- Responsive Table -->
        <div class="table-responsive">
            <table class="table lg-campaigns-table mb-0" id="leads_table">
                <thead>
                    <tr>
                        <th style="min-width: 240px;">Campaign</th>
                        <th style="min-width: 90px;">Leads</th>
                        <th style="min-width: 90px;">Called</th>
                        <th style="min-width: 100px;">Connected</th>
                        <th style="min-width: 100px;">Follow-ups</th>
                        <th style="min-width: 100px;">Interested</th>
                        <th style="min-width: 100px;">Converted</th>
                        <th style="min-width: 130px;">Progress</th>
                        <th style="min-width: 110px;">Status</th>
                        <th style="min-width: 96px;" class="lg-actions-th">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

@if(\App\Helpers\Helper::canDelete('campaigns.campaign_list'))
<!-- Delete Campaign Confirmation -->
<div id="lg-camp-delete-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); color: #FFFFFF; padding: 16px 22px; border: none;">
                <h5 class="modal-title" style="font-size: 16px; font-weight: 700; margin: 0; color: #FFFFFF;">Delete campaign?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #FFFFFF; opacity: .9; background: transparent; border: none; font-size: 22px;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 18px 22px; font-size: 13.5px; color: #334155; line-height: 1.55;">
                <p style="margin: 0 0 6px;">Campaign <strong id="lg-camp-del-name"></strong> will be deleted.</p>
                <p style="margin: 0 0 6px;" id="lg-camp-del-leads"></p>
                <p style="margin: 0; font-weight: 600; color: #B91C1C;">This cannot be undone. Download the leads first if you need them.</p>
            </div>
            <div class="modal-footer" style="background: #F8FAFC; border-top: 1px solid #E2E8F0; padding: 12px 22px; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn" data-dismiss="modal" style="background: #FFFFFF; border: 1px solid #CBD5E1; color: #334155; font-weight: 600; border-radius: 8px; padding: 7px 18px; font-size: 13px;">Cancel</button>
                <button type="button" class="btn" id="lg-camp-del-confirm" onclick="lgRunCampaignDelete()" style="background: #EF4444; border: none; color: #FFFFFF; font-weight: 600; border-radius: 8px; padding: 7px 18px; font-size: 13px;">Delete</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push("after-scripts")
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script type="text/javascript">
function changePageLength(val) {
    if (window.agentDataTable) {
        window.agentDataTable.page.len(parseInt(val, 10)).draw();
    }
}

function handleSearchInput(val) {
    if (window.agentDataTable) {
        window.agentDataTable.search(val).draw();
    }
}

function lgCampToast(type, msg) {
    if (typeof toastr !== 'undefined' && toastr[type]) { toastr[type](msg); } else { alert(msg); }
}

/* Download: check first, so an empty campaign shows a message instead of an empty file */
function lgDownloadCampaign(btn) {
    var url = $(btn).data('url');
    $(btn).prop('disabled', true);
    $.getJSON(url, { check: 1 }).done(function (res) {
        if (res.success && res.count > 0) {
            window.location.href = url;   // file download; the page stays open
            lgCampToast('success', 'Downloading ' + res.count + ' lead(s)...');
        } else {
            lgCampToast('info', res.message || 'This campaign has no lead data to download.');
        }
    }).fail(function (xhr) {
        lgCampToast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'The download could not be prepared.');
    }).always(function () { $(btn).prop('disabled', false); });
}

/* Delete: confirmation dialog, then AJAX delete and table refresh */
var lgPendingCampaignDelete = null;
function lgConfirmDeleteCampaign(btn) {
    lgPendingCampaignDelete = btn;
    var leads = parseInt($(btn).data('leads'), 10) || 0;
    $('#lg-camp-del-name').text($(btn).data('name'));
    $('#lg-camp-del-leads').text(leads > 0
        ? 'Its ' + leads + ' lead(s) will also be deleted (leads marked "Deal" are kept).'
        : 'This campaign has no leads.');
    $('#lg-camp-del-confirm').prop('disabled', false).html('Delete');
    if ($('#lg-camp-delete-modal').parent().is(':not(body)')) { $('#lg-camp-delete-modal').appendTo('body'); }
    $('#lg-camp-delete-modal').modal('show');
}
function lgRunCampaignDelete() {
    var btn = lgPendingCampaignDelete;
    if (!btn) return;
    $('#lg-camp-del-confirm').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Deleting...');
    $.ajax({ url: $(btn).data('url'), type: 'POST', data: { _token: "{{ csrf_token() }}" }, dataType: 'json' })
        .done(function (res) {
            $('#lg-camp-delete-modal').modal('hide');
            lgCampToast(res.success ? 'success' : 'error', res.message || 'Campaign deleted.');
            if (window.agentDataTable) window.agentDataTable.ajax.reload(null, false);
        })
        .fail(function (xhr) {
            $('#lg-camp-delete-modal').modal('hide');
            lgCampToast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'The campaign could not be deleted.');
        })
        .always(function () { lgPendingCampaignDelete = null; });
}

$(document).ready(function () {
    var table_name = "#leads_table";
    window.agentDataTable = $(table_name).DataTable({
        "processing": true,
        "serverSide": true,
        "searching": true,
        "responsive": false,
        "autoWidth": false,
        "scrollX": false,
        "pageLength": 30,
        "lengthMenu": [[10, 25, 30, 50, 75, 100, 200], [10, 25, 30, 50, 75, 100, 200]],
        "ajax": {
            "url": "{{ route('campaignsData') }}",
            "dataType": "json",
            "type": "POST",
            "data": function (data) {
                data._token = "{{csrf_token()}}";
                data.find_q = $('#dtSearchInput').val();
            }
        },
        "dom": 't<"lg-table-bottom-bar"<"lg-entries-info"i><"lg-pagination"p>>',
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
        "fnDrawCallback": function (settings) {
            if ($(table_name + '_paginate .paginate_button').length > 3) {
                $(table_name + '_paginate').show();
            } else {
                $(table_name + '_paginate').hide();
            }
            if ($.fn.tooltip) {
                $('[data-toggle="tooltip"]').tooltip({html: true});
            }
        },
        "columns": [
            /* 1. CAMPAIGN */
            {
                "data": "name",
                "width": "26%",
                "orderable": true,
                "render": function(data, type, row) {
                    var name = row.raw_name || 'Campaign';
                    var segment = row.segment_name || 'TeleCallers';
                    var startDate = row.start_date_formatted || '';
                    var endDate = row.end_date_formatted || '';
                    var viewUrl = row.view_url || '#';
                    var dateInfo = (startDate && endDate && startDate !== endDate) ? (startDate + ' – ' + endDate) : (startDate || 'Active');

                    return '<div class="lg-camp-cell">' +
                           '<a href="' + viewUrl + '" class="lg-camp-name" title="' + name + '">' + name + '</a>' +
                           '<div class="lg-camp-sub">' + segment + ' · ' + dateInfo + '</div>' +
                           '</div>';
                }
            },
            /* 2. LEADS */
            {
                "data": "lead_count",
                "width": "9%",
                "orderable": true,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? Number(data).toLocaleString() : (row.lead_count ? Number(row.lead_count).toLocaleString() : '0');
                    return '<span class="lg-num-cell">' + val + '</span>';
                }
            },
            /* 3. CALLED */
            {
                "data": "actioned",
                "width": "9%",
                "orderable": true,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? Number(data).toLocaleString() : (row.called_count ? Number(row.called_count).toLocaleString() : '0');
                    return '<span class="lg-num-cell">' + val + '</span>';
                }
            },
            /* 4. CONNECTED */
            {
                "data": "connected_count",
                "width": "10%",
                "orderable": false,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? Number(data).toLocaleString() : '0';
                    return '<span class="lg-num-cell">' + val + '</span>';
                }
            },
            /* 5. FOLLOW-UPS */
            {
                "data": "followup_count",
                "width": "10%",
                "orderable": false,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? Number(data).toLocaleString() : '0';
                    return '<span class="lg-num-cell">' + val + '</span>';
                }
            },
            /* 6. INTERESTED */
            {
                "data": "interested_count",
                "width": "10%",
                "orderable": false,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? Number(data).toLocaleString() : '0';
                    return '<span class="lg-num-cell">' + val + '</span>';
                }
            },
            /* 7. CONVERTED */
            {
                "data": "converted_count",
                "width": "9%",
                "orderable": false,
                "render": function(data, type, row) {
                    var val = (data !== undefined && data !== null) ? Number(data).toLocaleString() : '0';
                    return '<span class="lg-num-cell lg-conv-cell">' + val + '</span>';
                }
            },
            /* 8. PROGRESS */
            {
                "data": "progress_pct",
                "width": "17%",
                "orderable": true,
                "render": function(data, type, row) {
                    var pct = parseInt(data !== undefined ? data : 0, 10);
                    if (isNaN(pct)) pct = 0;
                    if (pct > 100) pct = 100;

                    return '<div class="lg-progress-cell">' +
                           '<div class="lg-progress-track">' +
                           '<div class="lg-progress-fill" style="width: ' + pct + '%;"></div>' +
                           '</div>' +
                           '<div class="lg-progress-label">' + pct + '% called</div>' +
                           '</div>';
                }
            },
            /* 9. STATUS */
            {
                "data": "status_label",
                "width": "10%",
                "orderable": true,
                "render": function(data, type, row) {
                    var label = data || 'Active';
                    var lower = label.toLowerCase();
                    var cls = 'status-active';
                    if (lower === 'paused') {
                        cls = 'status-paused';
                    } else if (lower === 'completed') {
                        cls = 'status-completed';
                    }
                    return '<span class="lg-status-pill ' + cls + '"><span class="lg-dot"></span>' + label + '</span>';
                }
            },
            /* 10. ACTIONS */
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "lg-actions-td",
                "render": function(data, type, row) {
                    var name = $('<div>').text(row.raw_name || 'Campaign').html();
                    var html = '<div class="lg-actions-cell">' +
                        '<button type="button" class="lg-row-act" data-lg-perm="campaigns.campaign_list.export" title="Download leads (CSV / Excel)" data-toggle="tooltip" ' +
                        'onclick="lgDownloadCampaign(this)" data-url="' + row.export_url + '" data-name="' + name + '">' +
                        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' +
                        '</button>';
                    if (row.delete_url) {
                        html += '<button type="button" class="lg-row-act is-delete" title="Delete campaign" data-toggle="tooltip" ' +
                            'onclick="lgConfirmDeleteCampaign(this)" data-url="' + row.delete_url + '" data-name="' + name + '" data-leads="' + (row.lead_count || 0) + '">' +
                            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>' +
                            '</button>';
                    }
                    return html + '</div>';
                }
            }
        ],
        "order": [[0, "asc"]]
    });

    $.fn.dataTable.ext.errMode = 'none';
});
</script>
@endpush
