@extends('dashboard.layouts.master')
@section('title', 'Leads')
@push("after-styles")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
@endpush
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <?php
                $cf_title_var = "title_" . @Helper::currentLanguage()->code;
                $cf_title_var2 = "title_" . env('DEFAULT_LANGUAGE');
                ?>
                <h3>Leads</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a>Leads</a>
                </small>
            </div>
            <div class="box-tool">
                <ul class="nav">
                    @if(@Auth::user()->permissionsGroup->add_status)
                        <li class="nav-item inline">
                            <a class="btn btn-fw primary" href="{{route("leadsCreate",["id"=>$id])}}">
                                <i class="material-icons">&#xe02e;</i>
                                &nbsp; {{ __('backend.topicNew') }}  Lead
                            </a>
                        </li>
                    @endif
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
            <div>

                <div class="dker b-b displayNone" id="filter_div">
                    <div class="p-a">
                        {{Form::open(['route'=>['topics',$id],'method'=>'GET','id'=>'filter_form','target'=>''])}}
                        <input type="hidden" name="stat" id="search_submit_stat" value="">
                        <div class="filter_div">
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="col-md-3 col-xs-6 m-b-5p">
                                    {!! Form::text('find_q',@$_GET['find_q'], array('placeholder' =>  __('backend.searchFor'),'class' => 'form-control','id'=>'find_q', "autocomplete"=>"off")) !!}
                                </div>
                                <div class="col-md-3 col-xs-6 m-b-5p">
                                    <div class="form-group m-b-0">
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
                                            {!! Form::text('find_date',((@$_GET['find_date']!="")?Helper::formatDate(@$_GET['find_date']):""), array('placeholder' => __('backend.topicDate'),'class' => 'form-control','id'=>'find_date')) !!}
                                            <span class="input-group-addon">
              <span class="fa fa-calendar"></span>
          </span>
                                        </div>
                                    </div>
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
                <div class="table-responsive">
                    <table class="table table-bordered" style="width: 100%"
                           id="leads_table">
                        <thead class="dker">
                            <th>Lead ID</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Phone number</th>
                            <th>Phone number 2</th>
                            <th>Assigned To</th>
                            <th>Sales Person</th>
                            <th>Last Actioned By</th>
                            <th>Last Actioned At</th>
                            <th>Call Back At</th>
                            <th>Company</th>
                            <th>Description</th>
                            <th>Country</th>
                            <th>Pin Code</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Website</th>
                            <th>Lead value</th>
                            <th>Position</th>
                            <th>Tags</th>
                        </thead>
                    </table>
                </div>
                <footer class="p-x p-b light dk">
                </footer>
            </div>
        </div>
    </div>
            <!-- .modal -->
            <div id="lead-detail" class="modal fade" data-backdrop="true">
                <div class="modal-dialog modal-lg" id="animate">
                    <div class="modal-content">
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
                }
            });
        }
        $(document).ready(function () {
            var table_name = "#leads_table";
            var dataTable = $(table_name).DataTable({
                "processing": true,
                "serverSide": true,
                "searching": false,
                "responsive": true,
                "pageLength": {{ env('BACKEND_PAGINATION') }},
                "lengthMenu": [[10, 20, 30, 50, 75, 100, 200, -1], [10, 20, 30, 50, 75, 100, 200, "All"]],
                "ajax": {
                    "url": "{{ route('leadsList',$id) }}",
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
                        "data": "id", "orderable": true
                    },
                    {
                        "data": "name", "orderable": true
                    },
                    {
                        "data": "designation", "orderable": true
                    },
                    {
                        "data": "phonenumber", "orderable": true
                    },
                    {
                        "data": "phonenumber_2", "orderable": true
                    },
                    {
                        "data": "assign", "orderable": true
                    },
                    {
                        "data": "sales_person", "orderable": true
                    },
                    {
                        "data": "last_action", "orderable": true
                    },
                    {
                        "data": "last_actioned_date", "orderable": true
                    },
                    {
                        "data": "follow_up_on", "orderable": true
                    },
                    {
                        "data": "company", "orderable": true
                    },
                    {
                        "data": "description", "orderable": true
                    },
                    {
                        "data": "country", "orderable": true
                    },
                    {
                        "data": "pincode"
                    },
                    {
                        "data": "city", "orderable": true
                    },
                    {
                        "data": "state", "orderable": true
                    },
                    {
                        "data": "email", "orderable": true
                    },
                    {
                        "data": "website", "orderable": true
                    },
                    {
                        "data": "lead_value", "orderable": true
                    },
                    {
                        "data": "position", "orderable": true
                    },
                    {
                        "data": "tags", "orderable": true
                    }
                ]
                , "order": [[0, "asc"]]

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
            $("#filter_form").attr('action', '{{ route("topicsPrint",$id) }}');
            $("#filter_form").attr('target', '_blank');
            $("#filter_form").submit();
            $("#filter_form").attr('action', '{{ route("topics",$id) }}');
            $("#search_submit_stat").val("");
            $("#filter_form").attr('target', '');
        }
    </script>
@endpush
