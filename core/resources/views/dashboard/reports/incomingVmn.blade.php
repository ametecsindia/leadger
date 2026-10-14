@extends('dashboard.layouts.master')
@section('title', 'Incoming Vmn')
@push("after-styles")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
    <style>
        .cli-link {
    color: #007bff;
    text-decoration: underline;
}

    </style>
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
                <h3>Incoming VMN</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a>Incoming VMN</a>
                </small>
            </div>
            <div class="box-tool">
                <ul class="nav">
                    <li class="nav-item inline">
                        <button type="button" class="btn info" id="filter_btn">
                            <i class="fa fa-search"></i>
                        </button>
                    </li>
                    <li class="nav-item inline">
                        <button type="button" class="btn warn" id="print_btn" title="Print" onclick="print_as('print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </li>
                    <li class="nav-item inline">
                        <button type="button" class="btn success" id="excel_btn" title="Excel" onclick="print_as('excel')">
                            <i class="fa fa-file-excel-o"></i>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-box-shadow">
                <div class="dker b-b displayNone" id="filter_div">
                    <div class="p-a">
                        {{Form::open(['route'=>['callLogsData'],'method'=>'GET','id'=>'filter_form','target'=>''])}}
                        <input type="hidden" name="stat" id="search_submit_stat" value="">
                        <div class="filter_div">
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="col-md-3 col-xs-6 m-b-5p">
                                    {!! Form::text('find_q',@$_GET['find_q'], array('placeholder' =>  __('backend.searchFor'),'class' => 'form-control','id'=>'find_q', "autocomplete"=>"off")) !!}
                                </div>
                                <div class="col-md-2 col-xs-6 m-b-5p">
                                    {!! Form::date('from_date', request('from_date') ?? \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control', 'id' => 'from_date']) !!}
                                </div>
                                <div class="col-md-2 col-xs-6 m-b-5p">
                                    {!! Form::date('to_date', request('to_date') ?? \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control', 'id' => 'to_date']) !!}
                                </div>

                                <div class="col-md-1 col-xs-6 m-b-5p">
                                    <button class="btn white w-full" id="search-btn" type="button"><i
                                            class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{Form::close()}}
                    </div>
                </div>
                <table class="table display nowrap" id="leads_table" style="width: 100%">
                    <thead class="dker">
                        <th>Call ID</th>
                        <th>Customer Mobile</th>
                        <th>VMN</th>
                        <th>Agent Number</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>Name</th>
                        @if(in_array(Auth::user()->permissionsGroup->name,['Webmaster','Admin','Manager']))
                        <th>Recording</th>
                        @endif
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
@endsection
@push("after-scripts")

    <script src="{{ URL::asset('assets/frontend/js/Chart.min.js') }}"></script>
    <script>
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
    <script type="text/javascript">
        function leadDetail(id) {
            var url = "{{ route('leadDetail', [':id']) }}";
            url = url.replace(':id', id);
            $('.modal-content').html('');
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function(html){
                    $('.modal-content').html(html);
                    $("#lead-detail").modal("show");
                    $("select").select2();
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
                "pageLength": {{ env('BACKEND_PAGINATION') }},
                "lengthMenu": [[10, 20, 30, 50, 75, 100, 200, -1], [10, 20, 30, 50, 75, 100, 200, "All"]],
                "ajax": {
                    "url": "{{ route('incomingVmnData') }}",
                    "dataType": "json",
                    "type": "POST",
                    "data": function (data) {
                        data._token = "{{csrf_token()}}";
                        data.find_q = $('#find_q').val();
                        data.from_date = $('#from_date').val();
                        data.to_date = $('#to_date').val();
                    }
                },
                "dom": '<"dataTables_wrapper"<"col-sm-12 col-md-9"i><"col-sm-12 col-md-3"l><"col-sm-12 col-md-12"r><"row"t><"row b-t p-x p-t light dk"<"col-sm-12"p>>>',
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
                        "data": "call_id", "orderable": true
                    },
                    {
                        "data": "cli", "orderable": true,
                        "render": function (data, type, row, meta) {
                            let url = "{{ route('callLogs') }}?find_q=" + encodeURIComponent(data);
                            return '<a href="' + url + '" target="_blank" style="color:#007bff;">' + data + '</a>';
                        }
                    },


                    {
                        "data": "dni", "orderable": true
                    },
                    {
                        "data": "a_party_no", "orderable": true
                    },
                    {
                        "data": "a_start_time", "orderable": true
                    },
                    {
                        "data": "a_dial_status", "orderable": true
                    },
                    {
                        "data": "duration", "orderable": true
                    },
                    {
                        "data": "name", "orderable": true
                    },
                    @if(in_array(Auth::user()->permissionsGroup->name,['Webmaster','Admin','Manager']))
                    {
                        "data": "file_path", "orderable": false
                    },
                    @endif
                ]
                , "order": [[0, "desc"]]

            });
            dataTable.on('page.dt', function () {
                $('html, body').animate({
                    scrollTop: $(".dataTables_wrapper").offset().top
                }, 'slow');
            });
            $.fn.dataTable.ext.errMode = 'none';

            $("#search-btn").on('click', function () {
                dataTable.draw();
            });
        });

        $("#filter_btn").click(function () {
            $("#filter_div").slideToggle();
        });

        function print_as(stat){
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
