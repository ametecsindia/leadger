@extends('dashboard.layouts.master')
@section('title', $Campaign->name)
@push("after-styles")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
@endpush
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>{!! $Campaign->name !!}</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a>OBD Campaigns</a>  / {!! $Campaign->name !!}
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
                        <a type="button" class="btn success" id="excel_btn" title="Excel" href='{{route('obdDownloadCampaignDetail', $Campaign->id)}}'>
                            <i class="fa fa-file-excel-o"></i>
                        </a>
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
            <div>
                <div class="view-port-header">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="col-form-label">
                                <span class="keytxt">Calls Submitted:</span>
                                <span class="valuetxt" id="total_pos">{!! $details[0]->calls_count !!}</span>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="col-form-label">
                                <span class="keytxt">Credits Used:</span>
                                <span class="valuetxt" id="total_pos">{!! $Campaign->credits !!}</span>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="col-form-label">
                                <span class="keytxt">Connected:</span>
                                <span class="valuetxt" id="total_pos">{!! $details[0]->connected !!}</span>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="col-form-label">
                                <span class="keytxt">Busy:</span>
                                <span class="valuetxt" id="total_pos">{!! $details[0]->busy !!}</span>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="col-form-label">
                                <span class="keytxt">Time Out:</span>
                                <span class="valuetxt" id="total_pos">{!! $details[0]->timeout !!}</span>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="col-form-label">
                                <span class="keytxt">No Ring:</span>
                                <span class="valuetxt" id="total_pos">{!! $details[0]->no_ring !!}</span>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="col-form-label">
                                <span class="keytxt">No Answer:</span>
                                <span class="valuetxt" id="total_pos">{!! $details[0]->no_ring !!}</span>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="col-form-label">
                                <span class="keytxt">No Dial Tone:</span>
                                <span class="valuetxt" id="total_pos">{!! $details[0]->no_dialtone !!}</span>
                            </label>
                        </div>
                    </div>
                </div>
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
                    <table class="table table-bordered display nowrap" id="leads_table" style="width: 100%" >
                        <thead class="dker">
                            <th>Phone Number</th>
                            <th>Call Initiated</th>
                            <th>Call Answered</th>
                            <th>Call End</th>
                            <th>Duration</th>
                            <th>Hangup Cause</th>
                            <th>Disconnected By</th>
                            <th>Status</th>
                            <th>No of calls made</th>
                        </thead>
                    </table>
                <footer class="p-x p-b light dk">
                </footer>
            </div>
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
                    "url": "{{ route('obdViewCampaignDetailData', $Campaign->id) }}",
                    "dataType": "json",
                    "type": "POST",
                    "data": function (data) {
                        data._token = "{{csrf_token()}}";
                        data.find_q = $('#find_q').val();
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
                        "data": "mobile_number", "orderable": true
                    },
                    {
                        "data": "call_initiated_time", "orderable": true
                    },
                    {
                        "data": "call_answered_time", "orderable": true
                    },
                    {
                        "data": "call_end_time", "orderable": true
                    },
                    {
                        "data": "call_duration", "orderable": true
                    },
                    {
                        "data": "hangup_cause", "orderable": true
                    },
                    {
                        "data": "disconnected_by", "orderable": true
                    },
                    {
                        "data": "status", "orderable": true
                    },
                    {
                        "data": "no_of_calls", "orderable": true
                    }
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
