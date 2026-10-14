@extends('dashboard.layouts.master')
@section('title', 'Report')
@push("after-styles")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
@endpush
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>Report</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a>Report</a>
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
                        {{Form::open(['route'=>['attendanceReport'],'method'=>'GET','id'=>'filter_form','target'=>''])}}
                        <input type="hidden" name="stat" id="search_submit_stat" value="">
                        <div class="filter_div">
                            <div class="row">
                                <div class="col-md-3 col-xs-6 m-b-5p">
                                    <select name="my" id="my" class="form-control c-select">
                                        <?php $my = "2023-03";
                                        while(strtotime($my)<=strtotime(date('Y-m-d')))
                                        { ?>
                                            <option value="{{date('Y-m',strtotime($my))}}" {{$my == $ym ? "selected" : ""}}>{{date('F-Y',strtotime($my))}}</option>
                                        <?php    $my = date('Y-m',strtotime($my.' +1 month'));
                                        }
                                         ?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-xs-6 m-b-5p">
                                    <select name="staff_id" id="staff_id" class="form-control c-select">
                                        <option value="">- - All Users - -</option>
                                        @foreach($users as $user)
                                        <option value="{!! $user->id !!}" {!! $staff_id == $user->id ? 'selected': '' !!}>{!! $user->name !!}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1 col-xs-6 m-b-5p">
                                    <button class="btn white w-full" id="search-btn" type="submit"><i
                                            class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{Form::close()}}
                    </div>
                </div>
                    <table class="table table-bordered display nowrap" id="leads_table" style="width: 100%">
                        <thead class="dker">
                            <th>Staff</th>
                            <th>Role</th>
                            <th>Shift</th>
                            <?php for($i=1;$i<=$days;$i++) { ?>
                                <th>{{$ym.'-'.$i}}</th>
                            <?php } ?>

                        </thead>
                        <body>
                        @foreach($res as $log)
                            <tr>
                                <td>{{$log->name}}</td>
                                <td>{{$log->role}}</td>
                                <td>{{$log->start_time}} - {{$log->end_time}}</td>
                                <?php for($i=1;$i<=$days;$i++) { ?>
                                <?php 
                                $diff = strtotime(date('H:i',strtotime(substr($log->{"day$i"},0,8))))-strtotime($log->start_time);
                                $color = empty($log->{"lat$i"}) ? 'blue' : ($diff <= 0 ? 'green' : ($diff <= 900 ? 'yellow' : 'red'));
                                ?>
                                <td><span style="color:{{$color}}">{{$log->{"day$i"} }}</span></td>
                                <?php } ?>
                            </tr>
                        @endforeach
                        </body>
                    </table>
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
        $(document).ready(function () {
            var table_name = "#leads_table";
            var dataTable = $(table_name).DataTable({
                "processing": true,
                "serverSide": false,
                "searching": false,
                "responsive": false,
                scrollX: true,
                "pageLength": {{ env('BACKEND_PAGINATION') }},
                "lengthMenu": [[10, 20, 30, 50, 75, 100, 200, -1], [10, 20, 30, 50, 75, 100, 200, "All"]],
            });
            dataTable.on('page.dt', function () {
                $('html, body').animate({
                    scrollTop: $(".dataTables_wrapper").offset().top
                }, 'slow');
            });
            $.fn.dataTable.ext.errMode = 'none';

//            $("#search-btn").on('click', function () {
//                dataTable.draw();
//            });
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
