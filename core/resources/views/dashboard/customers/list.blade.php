@extends('dashboard.layouts.master')
@section('title', 'Deals')
@push("after-styles")
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">
@endpush
@section('content')

    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <?php
                $cf_title_var = "title_" . @Helper::currentLanguage()->code;
                $cf_title_var2 = "title_" . env('DEFAULT_LANGUAGE');
                ?>
                <h3>Deals</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a>Deals</a>
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
                    <table class="table display nowrap" style="width: 100%"
                           id="leads_table">
                        <thead class="dker">
                            <th>Lead ID</th>
                            <th>Campaign</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Designation</th>
                            <th>Phone number</th>
                            <th>Phone number 2</th>
                            <th>Sub Status</th>
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
            <div id="sms-popup" class="modal fade" data-backdrop="true">
                <div class="modal-dialog modal-sm" id="animate">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title pull-left">Send SMS</h5>
                        </div>
                        <div class="modal-body lead-popup-wrap p-lg">
                            <div class="row form-group">
                                <div class="col-md-12">
                                    <label>Phone Number</label>
                                    <select class="form-control select2" id="smsnumber"></select>
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-md-12">
                                    <label>SMS Content</label>
                                    <textarea class="form-control" maxlength="150" id="smstext"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer text-center">
                            <div class='row'>
                                <div class="col m12 s12">
                                    <span style='color:green' id='sms_success_msg'></span>
                                </div>
                            </div>
                            <div class='row'>
                                <div class="col m12 s12">
                                    <button type="button" id="" row-id="" onclick="send_sms()" class="btn btn-fw primary">Send</button>
                                    <button type="button" class="btn btn-secondary p-x-md" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.modal-content -->
                </div>
            </div>
            <div id="email-popup" class="modal fade" data-backdrop="true">
                <div class="modal-dialog modal-lg" id="animate">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title pull-left">Send Email</h5>
                        </div>
                        <div class="modal-body lead-popup-wrap p-lg">
                            <div class="row form-group">
                                <div class="col-md-12">
                                    <label>Email Address</label>
                                    <input type='text' class="form-control" id="email_address" />
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-md-12">
                                    <label>Email Subject</label>
                                    <input type='text' class="form-control" id="email_subject" />
                                </div>
                            </div>
                            <div class="row form-group">
                                <div class="col-md-12">
                                    <label>Email Content</label>
                                    <div class="box p-a-xs">
                                        {!! Form::textarea('email_content','', array('ui-jp'=>'summernote','id'=>'email_content','class' => 'form-control summernote_'.@Helper::currentLanguage()->code,'ui-options'=>'{height: 200,callbacks: {
                                        onImageUpload: function(files, editor, welEditable) {
                                            sendFile(files[0], editor, welEditable,"'.@Helper::currentLanguage()->code.'");
                                        }
                                    }}')) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer text-center">
                            <div class='row'>
                                <div class="col m12 s12">
                                    <span style='color:green' id='email_success_msg'></span>
                                </div>
                            </div>
                            <div class='row'>
                                <div class="col m12 s12">
                                    <button type="button" id="" row-id="" onclick="send_email()" class="btn btn-fw primary">Send</button>
                                    <button type="button" class="btn btn-secondary p-x-md" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.modal-content -->
                </div>
            </div>

            <div class="boat-deatils" style="display: none;">
                <div class="input-field">
                    <div class="select-wrapper ">
                        <label>Dialer Type</label>
                        <select id="dialerType">
                            <option value="phone">Mobile</option>
                            <option value="sip">SIP</option>
                            @if(Auth::user()->click_vmn != '')
                            <option value="click2call">Click2Call</option>
                            @endif
                        </select>
                    </div>
                </div>
                <a id="startautodial" onclick="autoDialerStart()" class="btn btn-sm primary modal-trigger">Start</a>
                <a id="stopautodial" onclick="autoDialerStop()" class="btn btn-sm warn" style="display:none">Stop</a>
            </div>

            <div class="boatcontainer hide-mob" data-position="top" data-tooltip="Auto Dialer">
                <span class="icon-phone trin-trin"></span>
                <h6>Auto Dialer</h6>
            </div>
            
@endsection
@push("after-scripts")

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
        var autoDialer = false;
        var dialerType = 'phone';
        function autoDialerStop()
        {
            autoDialer = false;
            dialerType = 'mobile';
            $('#startautodial').show()
            $('#stopautodial').hide()
            $(".boat-deatils").toggle(500);
        }
        function autoDialerStart()
        {
            autoDialer = true;
            dialerType = $('#dialerType').val();
            var leads = $('#leads_table').DataTable().rows().data().each( function(e){
                return e;
            });
            $('#startautodial').hide()
            $('#stopautodial').show()
            $(".boat-deatils").toggle(500);
            if(leads.length > 0)
            {
                let first_col = leads[0].id;
                lead = first_col.substring(first_col.indexOf(">") + 1,first_col.lastIndexOf("</"));
                var url = "{{ route('leadDetail', [':id']) }}";
                url = url.replace(':id', lead);
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
        }        
        var dataTable = '';
        $(document).ready(function () {

            $(".boatcontainer").click(function(){
                $(".boat-deatils").toggle(500);
            });

            var table_name = "#leads_table";
            dataTable = $(table_name).DataTable({
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
                    "url": "{{ route('leadsList',$id) }}",
                    "dataType": "json",
                    "type": "POST",
                    "data": function (data) {
                        data._token = "{{csrf_token()}}";
                        data.find_q = $('#find_q').val();
                        data.find_date = $('#find_date').val();
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
                        "data": "name", "orderable": true
                    },
                    {
                        "data": "status_name", "orderable": true
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
                        "data": "substatus_name", "orderable": true
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
                        "data": "pin_code"
                    },
                    {
                        "data": "city", "orderable": true
                    },
                    {
                        "data": "state", "orderable": true
                    },
                    {
                        "data": "address", "orderable": true
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
