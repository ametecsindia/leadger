@extends('dashboard.layouts.master')
@section('title', 'Responsed Calls')
@push("after-styles")
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">

@endpush
@section('content')
<!-- for Datetimepicker use this div-->
<div class='input-group date hide' ui-jp="datetimepicker" ui-options="{
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
                                            {!! Form::text('date',((@$_GET['date']!="")?Helper::formatDate(@$_GET['date']):""), array('placeholder' => __('backend.topicDate'),'class' => 'form-control','id'=>'find_date')) !!}
                                            <span class="input-group-addon">
              <span class="fa fa-calendar"></span>
          </span>
</div>
<div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>Responsed Calls</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a>Responsed Calls</a>
                </small>
            </div>
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
            <div class="dker b-b displayNone" id="filter_div">
    <div class="p-a">
        {{ Form::open(['route' => ['recordingsData'], 'method' => 'GET', 'id' => 'filter_form', 'target' => '']) }}
        <input type="hidden" name="stat" id="search_submit_stat" value="">

        <div class="row align-items-center">
            <!-- Campaign Dropdown -->
            <div class="col-md-2 col-sm-4 mb-2">
                <select name="find_campaign" id="find_campaign" class="form-control">
                    <option value="">All Campaigns</option>
                    @foreach($Campaigns as $Campaign)
                        <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Telecaller Dropdown -->
            <div class="col-md-2 col-sm-4 mb-2">
                <select name="telecaller_id" id="telecaller_id" class="form-control">
                    <option value="">All Telecallers</option>
                    @foreach($Telecallers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search Box -->
            <div class="col-md-2 col-sm-4 mb-2">
                {!! Form::text('find_q', @$_GET['find_q'], ['placeholder' => __('backend.searchFor'), 'class' => 'form-control', 'id' => 'find_q', "autocomplete" => "off"]) !!}
            </div>

            <!-- From Date -->
            <div class="col-md-2 col-sm-4 mb-2">
                <div class="input-group date" ui-jp="datetimepicker" ui-options="{
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
                    }}">
                    {!! Form::text('from_date', null, ['placeholder' => 'From date', 'class' => 'form-control', 'id' => 'from_date']) !!}
                    <span class="input-group-addon">
                        <span class="fa fa-calendar"></span>
                    </span>
                </div>
            </div>

            <!-- To Date -->
            <div class="col-md-2 col-sm-4 mb-2">
                <div class="input-group date" ui-jp="datetimepicker" ui-options="{
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
                    }}">
                    {!! Form::text('to_date', null, ['placeholder' => 'To date', 'class' => 'form-control', 'id' => 'to_date']) !!}
                    <span class="input-group-addon">
                        <span class="fa fa-calendar"></span>
                    </span>
                </div>
            </div>
<<<<<<< .mine
            <!-- Results Dropdown -->
            <div class="col-md-2 col-sm-4 mb-2">
                <select name="results" id="results" class="form-control">
                    <option value="">Call Results</option>
                    <option value="User not responding">User not responding</option>
                    <option value="Timeout">Timeout</option>
                    <option value="Connected">Connected</option>
                </select>
            </div>
||||||| .r498
=======
            <!-- Results Dropdown -->
            <div class="col-md-2 col-sm-4 mb-2">
                <select name="results" id="results" class="form-control">
                    <option value="">Call Status</option>
                     <option value="Connected">Connected</option>
                    <option value="User Not Responding">User not responding</option>
                    <option value="Timeout">Timeout</option>
                     <option value="Busy">Busy</option>
                   
                </select>
            </div>
>>>>>>> .r509

            <!-- Search Button -->
            <div class="col-md-2 col-sm-4 mb-2">
                <button class="btn btn-primary w-full" id="search-btn" type="button">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </div>
        {{ Form::close() }}
    </div>
</div>


                <table class="table display nowrap" id="leads_table" style="width: 100%">
                    <thead class="dker">
                        <th>Lead ID</th>
                        <th>Campaign Name</th>
                        <th>Phone Number</th>
                        <th>Customer Name</th>
                        <th>Call Type</th>
                        
                        <th>sesssion duration</th>
                        <th>Call Duration	</th>
                        <th>Conversation Duration</th>
                        <th>Agent Status</th>
                        <th>Customer status</th>
                        @if(in_array(Auth::user()->permissionsGroup->name,['Webmaster','Admin','Manager']))
                        <th>Play</th>
                        @endif
                        <th>Called By</th>
                        <th>Called At</th>
                    </thead>
                </table>
                <footer class="p-x p-b light dk">
                </footer>
            </div>
        </div>
    </div>
            <!-- .modal -->
            <div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-lg" id="animate">
                    <div class="modal-content lead-content">
                    </div><!-- /.modal-content -->
                </div>
            </div>
            <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script> -->

@endsection
@push("after-scripts")
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script src="{{ URL::asset('assets/frontend/js/Chart.min.js') }}"></script>
    <script>
            $(document).on('show.bs.modal', '.modal', function (event) {
            var zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);
            setTimeout(function() {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);
        });
        var dynamicColors = function () {
            var r = Math.floor(Math.random() * 255);
            var g = Math.floor(Math.random() * 255);
            var b = Math.floor(Math.random() * 255);
            return "rgb(" + r + "," + g + "," + b + ")";
        };

        var randomScalingFactor = function () {
            return Math.round(Math.random() * 100);
        };

        window.onload = function () {

        };
    </script>


<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/dashboard/js/datatables/dataTables.fixedColumns.min.js') }}"></script>
    <script type="text/javascript">
        function leadDetail(id) {
            var url = "{{ route('leadDetail', [':id']) }}";
            url = url.replace(':id', id);
            $('.lead-content').html('');
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function(html){
                    $('.lead-content').html(html);
                    $("#lead-detail").modal("show");
                }
            });
        }
        
        $(document).ready(function () {
            var table_name = "#leads_table";
            var dataTable = $(table_name).DataTable({
                "processing": true,
                "serverSide": true,
                "searching": false,
                "responsive": false,
                scrollX: true,
                scrollY: 350,
                fixedColumns: {
                    start: 1
                },
                "pageLength": {{ env('BACKEND_PAGINATION') }},
                "lengthMenu": [[10, 20, 30, 50, 75, 100, 200, -1], [10, 20, 30, 50, 75, 100, 200, "All"]],
                "ajax": {
                    "url": "{{ route('responsedData') }}",
                    "dataType": "json",
                    "type": "POST",
                    "data": function (data) {
                        
                        data._token = "{{csrf_token()}}";
                        data.find_q = $('#find_q').val();
                        data.from_date = $('#from_date').val();
                        data.to_date = $('#to_date').val();
                        data.find_campaign = $('#find_campaign').val();
                        data.telecaller_id = $('#telecaller_id').val();
                        data.results = $('#results').val();
                    }
                },
"dom": '<"row"<"col-sm-12 col-md-6"f><"col-sm-12 col-md-6 text-end"l>>' + 
       '<"row"tr>' + 
       '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',


                "fnDrawCallback": function () {
                    if ($(table_name + '_paginate .paginate_button').size() > 3) {
                        $(table_name + '_paginate')[0].style.display = "block";
                    } else {
                        $(table_name + '_paginate')[0].style.display = "none";
                    }
                    $('[data-toggle="tooltip"]').tooltip({html: true});
                },
                "language": {!! json_encode(__("backend.dataTablesTranslation")) !!}
                ,
                "columns": [
                    {
                        "data": "id", "orderable": true
                    },
                    {
                        "data": "campaign_name", "orderable": true
                    },
                    {
                        "data": "phonenumber", "orderable": true
                    },
                    {
                        "data": "customer_name", "orderable": true
                    },
                    {
                        "data": "call_type", "orderable": true
                    },
                    
                                        {
                        "data": "duration", "orderable": true
                    },

                                        {
                        "data": "call_call", "orderable": true
                    },
                    {
                        "data": "actual_call_time", "orderable": true
                    },
                    {
                        "data": "agent_status", "orderable": true
                    },
                    {
                        "data": "customer_status", "orderable": true
                    },
                    @if(in_array(Auth::user()->permissionsGroup->name,['Webmaster','Admin','Manager']))
                    {
                        "data": "play", "orderable": true
                    },
                    @endif
                    {
                        "data": "called_by", "orderable": true
                    },
                    {
                        "data": "created_at", "orderable": true
                    }
                ]
                , "order": [[{{in_array(Auth::user()->permissionsGroup->name,['Webmaster','Admin','Manager']) ? 12 : 11 }}, "desc"]]

            });
            $(document).on('click', '.dataTables_length select', function (e) {
                var dropdown = $(this);
                setTimeout(function () {
                dropdown.focus();
            }, 100);
            });
            $("#search-btn").on('click', function () {
                dataTable.draw();
            });
            dataTable.on('page.dt', function () {
                $('html, body').animate({
                    scrollTop: $(".dataTables_wrapper").offset().top
                }, 'slow');
            });
            $.fn.dataTable.ext.errMode = 'none';

          
        });

        $("#filter_btn").click(function () {
            $("#filter_div").slideToggle();
        });

    function print_as(stat) {
    if (stat === 'excel') {
        var table = document.getElementById('leads_table');
        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.table_to_sheet(table);

        // Convert specific columns to text format (Phone Number, Called At Date)
        Object.keys(ws).forEach(cell => {
            if (ws[cell].v) {
                let value = ws[cell].v.toString(); // Convert to string
                
                // Ensure phone numbers display correctly (prevent scientific notation)
                if (value.match(/^\d{10,}$/)) { // Matches numbers with 10+ digits
                    ws[cell].t = 's'; // Set type as string
                }

                // Ensure "Called At" date displays properly
                if (value.match(/^\d{4}-\d{2}-\d{2}/)) { // Matches YYYY-MM-DD format
                    ws[cell].t = 's'; // Set type as string
                }
            }
        });

        // Auto-adjust column widths
        let columnWidths = [];
        let range = XLSX.utils.decode_range(ws['!ref']);
        for (let C = range.s.c; C <= range.e.c; ++C) {
            columnWidths.push({ wch: 20 }); // Set column width to 20
        }
        ws['!cols'] = columnWidths;

        XLSX.utils.book_append_sheet(wb, ws, "Leads");
        XLSX.writeFile(wb, "callLog_data.xlsx");
    }
}



    </script>
@endpush
