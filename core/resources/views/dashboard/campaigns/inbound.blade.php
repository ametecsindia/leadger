@extends('dashboard.layouts.master')
@section('title', 'Campaigns')
@push("after-styles")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
@endpush
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>Campaigns</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a>Campaigns</a>
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
            <div class="manual-filters-section">
                <div class="row">
                    {{Form::open(['route'=>'inboundCampaignsStore','method'=>'post'])}}
                    <div class="col-md-2 col-xs-6 m-b-5p">
                        <select name="campaign_id" id="campaign_id" required>
                            <option value="">Select Campaign</option>
                            @foreach($campaigns as $campaign)
                            <option value="{{$campaign->id}}">{{$campaign->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-xs-6 m-b-5p">
                        <select name="agent_id" id="agent_id" required>
                            <option value="">Select Agent</option>
                            @foreach($agents as $agent)
                            <option value="{{$agent->id}}">{{$agent->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-xs-6 m-b-5p">
                        <input placeholder="Source" class="form-control" id="source" autocomplete="off" name="source" type="text" required>
                    </div>
                    <div class="col-md-1 col-xs-6 m-b-5p">
                        <button class="btn btn-fw primary" id="search-btn" type="submit"><i class="fa fa-plus"></i> &nbsp; Add</button>
                    </div>
                    {{Form::close()}}
                </div>
            </div>
                    <table class="table table-bordered display nowrap" id="leads_table" style="width: 100%" >
                        <thead class="dker">
                            <th>Campaign Name</th>
                            <th>Agent</th>
                            <th>Source</th>
                            <th>Created Date</th>
                            <th class="text-center" style="width:200px;">{{ __('backend.options') }}</th>
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
        function copy(textToCopy)
        {
            var tempTextarea = $('<textarea>');
            $('body').append(tempTextarea);
            tempTextarea.val(textToCopy).select();
            document.execCommand('copy');
            tempTextarea.remove();
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
                    "url": "{{ route('inboundData') }}",
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
                        "data": "campaign_name", "orderable": true
                    },
                    {
                        "data": "agent_name", "orderable": true
                    },
                    {
                        "data": "source", "orderable": true
                    },
                    {
                        "data": "created_at", "orderable": true
                    },
                    {
                        "data": "options"
                    }
                ]
                , "order": [[3, "desc"]]

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
