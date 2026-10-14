@extends('dashboard.layouts.master')
@section('title', 'Call Logs - LeadGER')

@push('after-styles')
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
<style>
/* LeadGER Corporate Call History */
.lg-ch-wrapper {
    background-color: #F8FAFC;
    min-height: calc(100vh - 72px);
    padding: 24px 30px;
    font-family: var(--lg-font-sans);
    color: #0F172A;
    box-sizing: border-box;
}

/* 1. Page Header */
.lg-ch-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-ch-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lg-ch-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.08);
    flex-shrink: 0;
}

.lg-ch-text-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lg-ch-breadcrumb {
    font-size: 13px;
    font-weight: 500;
    color: var(--lg-accent);
}

.lg-ch-title {
    font-size: 22px;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}

.lg-ch-subtitle {
    font-size: 13px;
    color: #64748B;
    margin-top: 2px;
}

/* Header Buttons */
.lg-ch-header-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.lg-btn-header-secondary {
    height: 40px;
    padding: 0 18px;
    background: #FFFFFF;
    color: #0F172A !important;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.15s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.lg-btn-header-secondary:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A !important;
}

/* Filter Card */
.filter-card {
    background: #FFFFFF;
    border-radius: 14px;
    border: 1px solid #E2E8F0;
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.filter-grid {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 14px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
    min-width: 140px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-input {
    height: 38px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    padding: 0 12px;
    font-size: 13px;
    color: #0F172A;
    background-color: #FFFFFF;
    outline: none;
    transition: all 0.15s ease;
    width: 100%;
}

.filter-input:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.1);
}

.btn-apply-filter {
    height: 38px;
    padding: 0 18px;
    border-radius: 8px;
    background: var(--lg-accent);
    color: #FFFFFF;
    border: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background 0.15s ease;
    white-space: nowrap;
}

.btn-apply-filter:hover {
    background: #0D4FC4;
    color: #FFFFFF;
}

.btn-reset-filter {
    height: 38px;
    padding: 0 14px;
    border-radius: 8px;
    background: #F1F5F9;
    color: #475569;
    border: 1px solid #E2E8F0;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.btn-reset-filter:hover {
    background: #E2E8F0;
    color: #0F172A;
}

/* 2. Main Table Card Container */
.lg-ch-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    padding: 24px;
    overflow: hidden;
}

.lg-ch-table-wrapper {
    overflow-x: auto;
    width: 100%;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
}

/* Table Styling */
.lg-ch-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    background: #FFFFFF;
}

.lg-ch-table th {
    background: #F8FAFC;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 16px;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}

.lg-ch-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    color: #0F172A;
    font-size: 13px;
    white-space: nowrap;
}

.lg-ch-table tbody tr:hover td {
    background-color: #F8FAFC;
}

.lg-lead-name {
    font-weight: 700;
    color: #0F172A;
}

.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.badge-connected {
    background: #ECFDF5;
    color: #059669;
}

.badge-missed {
    background: #FEF2F2;
    color: #EF4444;
}

.badge-failed {
    background: #FFF7ED;
    color: #C2410C;
}

.badge-busy {
    background: #FEF3C7;
    color: #92400E;
}

.type-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
}

.type-pill.click2call {
    background: #EFF6FF;
    color: var(--lg-accent-hover);
}

.type-pill.webrtc {
    background: #F5F3FF;
    color: #6D28D9;
}

.lg-play-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    background: #EFF6FF;
    color: var(--lg-accent);
    font-size: 12px;
    font-weight: 600;
    text-decoration: none !important;
    border: none;
    cursor: pointer;
}

.lg-play-btn:hover {
    background: var(--lg-accent);
    color: #FFFFFF;
}

.lg-action-btn-circle {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    color: #64748B;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none !important;
}

.lg-action-btn-circle:hover {
    background: var(--lg-accent);
    color: #FFFFFF;
    border-color: var(--lg-accent);
}

.export-btn-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-export {
    height: 34px;
    padding: 0 14px;
    border-radius: 7px;
    background: #FFFFFF;
    color: #475569;
    border: 1px solid #CBD5E1;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-export:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}
</style>
@endpush

@section('content')
<div class="lg-ch-wrapper">

    <!-- 1. PAGE HEADER -->
    <div class="lg-ch-header">
        <div class="lg-ch-header-left">
            <div class="lg-ch-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </div>
            <div class="lg-ch-text-group">
                <h1 class="lg-ch-title">Call Logs</h1>
                <div class="lg-ch-subtitle">Detailed log of campaign queue calls, outcomes, durations, and audio recordings.</div>
            </div>
        </div>

        <div class="lg-ch-header-right">
            <a href="{{ route('dialer') }}" class="lg-btn-header-secondary">
                <i class="fa fa-phone"></i> Open Dialer
            </a>
            <a href="{{ route('callReport') }}" class="lg-btn-header-secondary">
                <i class="fa fa-chart-bar"></i> Call Report
            </a>
        </div>
    </div>

    <!-- 2. FILTER BAR CARD -->
    <div class="filter-card">
        {{ Form::open(['route' => ['callLogsData'], 'method' => 'POST', 'id' => 'filter_form']) }}
        <div class="filter-grid">

            <!-- From Date -->
            <div class="filter-group">
                <label for="from_date">From Date</label>
                <input type="date" id="from_date" name="from_date" class="filter-input" value="{{ date('Y-m-01') }}">
            </div>

            <!-- To Date -->
            <div class="filter-group">
                <label for="to_date">To Date</label>
                <input type="date" id="to_date" name="to_date" class="filter-input" value="{{ date('Y-m-d') }}">
            </div>

            <!-- Campaign -->
            <div class="filter-group">
                <label for="find_campaign">Campaign</label>
                <select name="find_campaign" id="find_campaign" class="filter-input">
                    <option value="">All Campaigns</option>
                    @foreach($Campaigns as $Campaign)
                        <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Telecaller / Agent -->
            <div class="filter-group">
                <label for="telecaller_id">User / Agent</label>
                <select name="telecaller_id" id="telecaller_id" class="filter-input">
                    <option value="">All Users</option>
                    @foreach($Telecallers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search Input -->
            <div class="filter-group">
                <label for="find_q">Search Query</label>
                {!! Form::text('find_q', @$_GET['find_q'], ['placeholder' => 'Search by lead, phone, agent...', 'class' => 'filter-input', 'id' => 'find_q', "autocomplete" => "off"]) !!}
            </div>

            <!-- Filter Buttons -->
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-apply-filter" id="search-btn">
                    <i class="fa fa-search"></i> Apply Filters
                </button>

                <button type="button" class="btn-reset-filter" onclick="$('#find_q,#find_campaign,#telecaller_id').val(''); $('#search-btn').click();">
                    <i class="fa fa-undo"></i> Reset
                </button>
            </div>

        </div>
        {{ Form::close() }}
    </div>

    <!-- 3. MAIN CALL HISTORY TABLE CONTAINER -->
    <div class="lg-ch-card">
        <div class="lg-ch-table-wrapper">
            <table class="lg-ch-table table display nowrap" id="call_logs_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Campaign</th>
                        <th>Lead Name</th>
                        <th>Number</th>
                        <th>Agent</th>
                        <th>Call Type</th>
                        <th>Status</th>
                        <th>Substatus</th>
                        <th>Duration</th>
                        <th>Recording</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>

        <!-- Bottom Action Export Buttons -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #E2E8F0;">
            <div class="export-btn-group">
                <button type="button" class="btn-export" onclick="toastr.info('Table content copied to clipboard')">
                    <i class="fa fa-copy"></i> Copy
                </button>

                @if(in_array(Auth::user()->permissionsGroup->name, ['Webmaster', 'Admin', 'Manager']))
                <button type="button" class="btn-export" id="excel_btn" onclick="print_as('excel')">
                    <i class="fa fa-file-excel"></i> Excel
                </button>
                @endif

                <button type="button" class="btn-export" onclick="print_as('excel')">
                    <i class="fa fa-file-csv"></i> CSV
                </button>

                <button type="button" class="btn-export" onclick="toastr.info('Exporting PDF report...')">
                    <i class="fa fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Lead Detail Modal -->
<div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" id="animate">
        <div class="modal-content lead-content">
        </div>
    </div>
</div>

@endsection

@push("after-scripts")
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/dashboard/js/datatables/dataTables.fixedColumns.min.js') }}"></script>

<script type="text/javascript">
    function leadDetail(id) {
        if (!id) return;
        var url = window.location.origin + "{{ '/' . ltrim(parse_url(route('leadDetail', [':id']), PHP_URL_PATH), '/') }}";
        url = url.replace(':id', id);
        $('.modal-content.lead-content').html('<div class="p-5 text-center"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i></div>');
        $("#lead-detail").modal("show");
        $.ajax({
            type: "GET",
            url: url,
            dataType: "html",
            success: function(html){
                $('.modal-content.lead-content').html(html);
                if ($.fn.select2) {
                    $("select").select2();
                }
            }
        });
    }

    let currentCallAudio = null;
    let currentCallBtn = null;
    function playCallLogAudio(url, btn) {
        if (currentCallAudio && currentCallBtn === btn) {
            if (currentCallAudio.paused) {
                currentCallAudio.play();
                $(btn).find('i').removeClass('fa-play').addClass('fa-pause');
            } else {
                currentCallAudio.pause();
                $(btn).find('i').removeClass('fa-pause').addClass('fa-play');
            }
            return;
        }

        if (currentCallAudio) {
            currentCallAudio.pause();
            if (currentCallBtn) {
                $(currentCallBtn).find('i').removeClass('fa-pause').addClass('fa-play');
            }
        }

        currentCallBtn = btn;
        $(btn).find('i').removeClass('fa-play').addClass('fa-spinner fa-spin');
        currentCallAudio = new Audio(url);
        currentCallAudio.play().then(function() {
            $(btn).find('i').removeClass('fa-spinner fa-spin').addClass('fa-pause');
        }).catch(function() {
            $(btn).find('i').removeClass('fa-spinner fa-spin').addClass('fa-play');
            if (typeof toastr !== 'undefined') {
                toastr.error('Unable to play recording');
            }
        });

        currentCallAudio.onended = function() {
            $(btn).find('i').removeClass('fa-pause fa-spinner fa-spin').addClass('fa-play');
            currentCallAudio = null;
            currentCallBtn = null;
        };
    }

    $(document).ready(function () {
        var table_name = "#call_logs_table";
        var dataTable = $(table_name).DataTable({
            "processing": true,
            "serverSide": true,
            "searching": true,
            "responsive": false,
            scrollX: true,
            "pageLength": {{ env('BACKEND_PAGINATION', 10) }},
            "lengthMenu": [[10, 20, 30, 50, 75, 100, 200, -1], [10, 20, 30, 50, 75, 100, 200, "All"]],
            "ajax": {
                "url": "{{ route('callLogsData') }}",
                "dataType": "json",
                "type": "POST",
                "data": function (data) {
                    data._token = "{{ csrf_token() }}";
                    data.find_q = $('#find_q').val();
                    data.from_date = $('#from_date').val();
                    data.to_date = $('#to_date').val();
                    data.find_campaign = $('#find_campaign').val();
                    data.telecaller_id = $('#telecaller_id').val();
                }
            },
            "dom": '<"row margin-bottom-md"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 text-end"f>>' + 
                   '<"row"tr>' + 
                   '<"row margin-top-md"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            "fnDrawCallback": function () {
                if ($(table_name + '_paginate .paginate_button').length > 3) {
                    $(table_name + '_paginate')[0].style.display = "block";
                } else {
                    $(table_name + '_paginate')[0].style.display = "none";
                }
                $('[data-toggle="tooltip"]').tooltip({html: true});
            },
            "columns": [
                { "data": "date_time", "orderable": true },
                { "data": "campaign", "orderable": true },
                { 
                    "data": "lead_name", 
                    "orderable": true,
                    "render": function(data, type, row) {
                        return '<span class="lg-lead-name">' + (data || '—') + '</span>';
                    }
                },
                { "data": "number", "orderable": true },
                { "data": "agent", "orderable": true },
                { 
                    "data": "call_type", 
                    "orderable": true,
                    "render": function(data, type, row) {
                        let ct = (data || 'Click2Call').toLowerCase();
                        if (ct.indexOf('webrtc') !== -1) {
                            return '<span class="type-pill webrtc">🌐 WebRTC</span>';
                        }
                        return '<span class="type-pill click2call">📞 Click2Call</span>';
                    }
                },
                { 
                    "data": "status", 
                    "orderable": true,
                    "render": function(data, type, row) {
                        if (!data) return '<span class="badge-status badge-connected">● Completed</span>';
                        let st = data.toLowerCase();
                        if (st.indexOf('connect') !== -1 || st.indexOf('won') !== -1 || st.indexOf('interest') !== -1) {
                            return '<span class="badge-status badge-connected">● ' + data + '</span>';
                        } else if (st.indexOf('miss') !== -1 || st.indexOf('no answer') !== -1) {
                            return '<span class="badge-status badge-missed">● ' + data + '</span>';
                        } else if (st.indexOf('fail') !== -1 || st.indexOf('cancel') !== -1) {
                            return '<span class="badge-status badge-failed">● ' + data + '</span>';
                        } else if (st.indexOf('busy') !== -1) {
                            return '<span class="badge-status badge-busy">● ' + data + '</span>';
                        }
                        return '<span class="badge-status badge-connected">● ' + data + '</span>';
                    }
                },
                { "data": "substatus", "orderable": true },
                { 
                    "data": "duration", 
                    "orderable": true,
                    "render": function(data, type, row) {
                        return '<span style="font-weight: 600;">' + (data || '00:00') + '</span>';
                    }
                },
                { "data": "recording", "orderable": false },
                { 
                    "data": "action", 
                    "orderable": false,
                    "className": "text-end"
                }
            ],
            "order": [[0, "desc"]]
        });

        $("#search-btn").on('click', function () {
            dataTable.draw();
        });

        dataTable.on('page.dt', function () {
            $('html, body').animate({
                scrollTop: $(".lg-ch-card").offset().top - 20
            }, 'slow');
        });
        
        $.fn.dataTable.ext.errMode = 'none';

        // Auto-refresh call logs every 30 seconds when idle
        setInterval(function() {
            if (typeof dataTable !== 'undefined' && dataTable && (!currentCallAudio || currentCallAudio.paused)) {
                dataTable.ajax.reload(null, false);
            }
        }, 30000);
    });

    function print_as(stat) {
        if (stat === 'excel') {
            var table = $('#call_logs_table').DataTable();
            var data = table.rows({ search: 'applied' }).data().toArray();

            let exportData = [];
            let headers = [
                "Date/Time", "Campaign", "Lead Name", "Number", "Agent",
                "Call Type", "Status", "Substatus", "Duration", "Recording URL"
            ];

            exportData.push(headers);

            data.forEach(function (row) {
                let rowData = [
                    row.date_time,
                    row.campaign,
                    row.lead_name,
                    row.number,
                    row.agent,
                    row.call_type,
                    row.status,
                    row.substatus,
                    row.duration,
                    row.click_recording_url || "-"
                ];
                exportData.push(rowData);
            });

            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet(exportData);

            let colWidths = headers.map(() => ({ wch: 22 }));
            ws['!cols'] = colWidths;

            XLSX.utils.book_append_sheet(wb, ws, "Call Logs");
            XLSX.writeFile(wb, "campaign_call_logs.xlsx");
        }
    }
</script>
@endpush
