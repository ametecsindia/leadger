@extends('dashboard.layouts.master')
@section('title', 'Payout Report')

@push("after-styles")
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">



<style>

#payout_table td {
    font-family: var(--lg-font-sans);
}
#rupee,#summary-amount{
     font-family: var(--lg-font-sans);
}
.cardbg-4 {
    background-color: #006666;
    border-radius: 10px;
    padding: 10px 20px;
    display: block;
    width: 100%;
    min-width: unset;
}

.box-header {
    width: 100%;
}

.cardbg-4 .card-body {
    padding: 0.75rem 1rem !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.cardbg-4 .text-white {
    font-size: 14px;
}

.cardbg-4 .h4 {
    font-size: 18px;
    margin-top: 5px;
}

.cardbg-4 .h5 {
    font-size: 15px;
    margin-bottom: 2px;
}

.cardbg-4 .h6 {
    font-size: 13px;
    font-weight: 400;
}
</style>





@endpush

@section('content')
<div class="padding">
    <div class="box">
        <div class="box-header dker">
            <h3>Payout Report</h3>
            <small>
                <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                <a>Payout Report</a>
            </small>






        <div class="box-tool">
            <ul class="nav">
                <button type="button" class="btn btn-info" id="filter_btn">
                    <i class="fa fa-search"></i>
                </button>
                @if(in_array(Auth::user()->permissionsGroup->name,['Webmaster','Admin','Manager']))
                <button type="button" class="btn btn-success" id="excel_btn" title="Excel" onclick="print_as('excel')">
                    <i class="fa fa-file-excel-o"></i>
                </button>
                @endif
            </ul>
        </div>

        <div class="card-box-shadow">

            <!-- Filter -->
            <div class="dker b-b displayNone" id="filter_div">
                <div class="p-a">
                    <input type="hidden" name="stat" id="search_submit_stat" value="">
                    <div class="filter_div">
                        <div class="row">
                            <div class="col-md-3 col-xs-6 m-b-5p">
                                <div class='input-group date' ui-jp="datetimepicker" ui-options="{
                                    format: '{{ Helper::jsDateFormat() }}',
                                    icons: {
                                        time: 'fa fa-clock-o',
                                        date: 'fa fa-calendar',
                                        up: 'fa fa-chevron-up',
                                        down: 'fa fa-chevron-down',
                                        previous: 'fa fa-chevron-left',
                                        next: 'fa fa-chevron-right',
                                        today: 'fa fa-screenshot',
                                        clear: 'fa fa-trash',
                                        close: 'fa fa-remove'
                                    }
                                }">
                                    {!! Form::text('from_date',date('Y-m-d'), array('placeholder' => __('backend.topicDate'),'class' => 'form-control','id'=>'from_date')) !!}
                                    <span class="input-group-addon">
                                        <span class="fa fa-calendar"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3 col-xs-6 m-b-5p">
                                <div class='input-group date' ui-jp="datetimepicker" ui-options="{
                                    format: '{{ Helper::jsDateFormat() }}',
                                    icons: {
                                        time: 'fa fa-clock-o',
                                        date: 'fa fa-calendar',
                                        up: 'fa fa-chevron-up',
                                        down: 'fa fa-chevron-down',
                                        previous: 'fa fa-chevron-left',
                                        next: 'fa fa-chevron-right',
                                        today: 'fa fa-screenshot',
                                        clear: 'fa fa-trash',
                                        close: 'fa fa-remove'
                                    }
                                }">
                                    {!! Form::text('to_date',date('Y-m-d'), array('placeholder' => __('backend.topicDate'),'class' => 'form-control','id'=>'to_date')) !!}
                                    <span class="input-group-addon">
                                        <span class="fa fa-calendar"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-1 col-xs-6 m-b-5p">
                                <button class="btn white w-full" id="search-btn" type="button"><i class="fa fa-search"></i></button>
                            </div>
                            <div class="col-md-1 col-xs-6 m-b-5p">
                             </div>

                            <div class="col-md-5 col-xs-6 m-b-5p">
                                <div class="w-100">
                                    <div class="card cardbg-4 w-100">
                                        <div class="card-body d-flex justify-content-between align-items-center w-100 flex-wrap">
                                            
                                            <!-- Left Side: Labels -->
                                            <div class="text-white">
                                                <div class="h5 mb-1" style="margin-bottom: 7px;">Total Payout from</div>
                                                <div class="h6 mb-1" id="summary-dates">-- to --</div>
                                            </div>

                                            <!-- Right Side: Amount and Icon -->
                                            <div class="d-flex align-items-center text-white ms-auto text-end">
                                                <div class="me-2">
                                                    <div class="h4 fw-bold mb-0" id="summary-amount">₹0.00</div>
                                                </div>
                                                <div id="rupee">
                                                    <i class="fas fa-rupee-sign fa-lg"></i>
                                                </div>
                                            </div>

                                        </div>
                                    </div>


                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </div>

            <!-- Table -->
            <table class="table display nowrap" id="payout_table" style="width: 100%">
                <thead class="dker">
                    <tr>
                        <th>Name</th>
                        <th>Account Number</th>
                        <th>IFSC Code</th>
                        <th>Branch Name</th>
                        <th>UPI ID</th>
                        <th>Call Log Calls</th>
                        <th>Connected call logs</th>
                        <th>Not Connected Call logs</th>
                        <th>Connected call log Duration</th>
                        <th>Manual Dial Calls</th>
                        <th>Connected manual calls</th>
                        <th>Not Connected manual calls</th>
                        <th>Connected manual call Duration</th>
                        <th>Total Connected calls</th>
                        <th>Total Not connected calls </th>
                        <th>Total Connected Duration</th>
                        <th>Total Pay for calls</th>
                        <th>total Pay for duration</th>
                        <th>Total Payout</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push("after-scripts")
<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<!-- Add this after including datatables.min.css -->
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css" />

<!-- Add this after datatables.min.js -->
<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>


<script>
    $(document).ready(function () {
        var table = $("#payout_table").DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            fixedColumns: {
                leftColumns: 1
            },
            scrollX: true,
            "dom": '<"row"<"col-sm-12 col-md-6"f><"col-sm-12 col-md-6 text-end"l>>' + 
       '<"row"tr>' + 
       '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ajax: {
                url: "{{ route('payoutData') }}",
                type: "POST",
                data: function (data) {
                    data._token = "{{ csrf_token() }}";
                    data.from_date = $('#from_date').val();
                    data.to_date = $('#to_date').val();
                },
                dataSrc: function (json) {
                    if (json.status === false) {
                        alert(json.message); // Show the custom error message
                        return []; // Return empty data to the table
                    }
                    $('#summary-amount').text('₹ ' + parseFloat(json.total_payout).toFixed(2));
                    $('#summary-dates').text($('#from_date').val() + ' to ' + $('#to_date').val());

                    return json.data;
                },
                error: function (xhr, error, thrown) {
                    console.error("AJAX error:", error, thrown);
                }
            },
            columns: [
                { data: "name", orderable: true },
                { data: "account_number", orderable: true },
                { data: "ifsc_code", orderable: true },
                { data: "branch_name", orderable: true },
                { data: "upi_id", orderable: true },
                { data: "call_log_total", orderable: true },
                { data: "call_log_connected", orderable: true },
                { data: "call_log_not_connected", orderable: true },
                { data: "call_log_duration", orderable: true },
                { data: "dialer_log_total", orderable: true },
                { data: "dialer_log_connected", orderable: true },
                { data: "dialer_log_not_connected", orderable: true },
                { data: "dialer_log_duration", orderable: true },
                { data: "total_connected_calls", orderable: true },
                { data: "total_not_connected_calls", orderable: true },
                { data: "total_connected_duration", orderable: true },
                { data: "pay_for_calls", orderable: true, render: function (data) {
                    return "₹" + data;
                }},
                { data: "pay_for_duration", orderable: true, render: function (data) {
                    return "₹" + data;
                }},
                { data: "total_payout", orderable: true, render: function (data) {
                    return "₹" + data;
                }}
            ],

            order: [[0, "asc"]]
        });

        $("#filter_btn").click(function () {
            $("#filter_div").slideToggle();
        });

        $("#search-btn").on('click', function () {
            table.draw();
        });

        function exportExcel() {
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.table_to_sheet(document.getElementById('payout_table'));
            XLSX.utils.book_append_sheet(wb, ws, "Payouts");
            XLSX.writeFile(wb, "payout_report.xlsx");
        }

        window.print_as = function(type) {
            if (type === 'excel') exportExcel();
        }
    });
</script>
@endpush
