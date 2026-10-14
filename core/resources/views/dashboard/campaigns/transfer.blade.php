@extends('dashboard.layouts.master')
@section('title', 'Transfer Leads')
@push("after-styles")

<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">
    <style>
    #loadingSpinner {
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
    }
    #loadingSpinner.show {
        opacity: 1;
    }

        .dataTables_wrapper {
            padding-top: 0 !important;
        }
    </style>
@endpush

@section('content')
<div class="padding">
    <div class="box">
    <div class="box">
            <div class="box-header dker">
                <h3>Leads</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a>Leads</a>
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
                                            {!! Form::text('date',((@$_GET['date']!="")?Helper::formatDate(@$_GET['date']):""), array('placeholder' => __('backend.topicDate'),'class' => 'form-control','id'=>'find_date')) !!}
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
        
        <div class="box-body">
       
            <!-- Transfer Controls -->
            <div class="transfer-controls mb-3 p-3" style="background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
                <div class="form-group mb-3">
                    <label for="staff">Assign To</label>
                    <select name="staff" id="staff" class="form-control">
                        <option value="">Select User</option>
                        @foreach($Staff as $row)
                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label for="unassign_user">Un-Assign From</label>
                    <select name="unassign_user" id="unassign_user" class="form-control">
                        <option value="">Select User</option>
                        @foreach($Staff as $row)
                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label for="sales_person">Sales Person</label>
                    <select name="sales_person" id="sales_person" class="form-control">
                        <option value="">Un-Assign Sales Person</option>
                        @foreach($SalesPersons as $row)
                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                        @endforeach
                    </select>
                </div>

    
                    <button onclick="transferLeads()" class="btn btn-primary">
                        Transfer Selected
                    </button>
                    <button id="submit_show_msg" class="btn white" data-toggle="modal"
                                    style="display: none"
                                    data-target="#m-all" ui-toggle-class="bounce"
                                    ui-target="#animate">Transferred Successfully
                            </button>                
                    <button onclick="toggleUnassigned()" class="btn btn-secondary" style="background-color:#006666; color:white" id="unassigned_btn">
                        Show Unassigned
                    </button>
                    @if($isassigned>0)
                    <button onclick="unassignLeads()" class="btn btn-primary">
                        Unassign Selected
                    </button>
                    @endif
                    @if($isassigned==0)
                 
                    <button onclick="randomAllocation()" 
                            class="btn btn-danger" 
                            style="background-color:#990000; color:white" 
                            id="random_btn">
                        Random Allocation
                    </button>
                    @endif
                </div>
                <div id="transfer-message" class="alert alert-success mt-2" style="display: none;"></div>
            </div>

        <div id="transfer-message" class="alert alert-success mt-2" style="display: none;"></div>
</div>

        


                
         

            <!-- DataTable -->
            <div id="loadingSpinner" style="display: none; text-align: center; padding: 10px; font-size: 16px; color: #555;">
                <i class="fa fa-spinner fa-spin fa-2x"></i> Processing....
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="leads_table" style="width:100%">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width:20px;">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>Lead ID</th>
                            
                            <th>Name</th>
                        
                            <th>Designation</th>
                            <th>Phone Number</th>
                            <th>Phone Number 2</th>
                         
                            <th>Assigned To</th>
                     
                            <th>Company</th>
                            <th>Description</th>
                            <th>Country</th>
                            <th>Pin Code</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Website</th>
                            <th>Lead Value</th>
                            <th>Position</th>
                            <th>Tags</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push("after-scripts")
    <script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/dashboard/js/datatables/dataTables.fixedColumns.min.js') }}"></script>
    <script>
        let isUnassignedFilter = false;
        let dataTable;

        $(document).ready(function () {
            // Initialize DataTable with fixed columns
            dataTable = $('#leads_table').DataTable({
                processing: true, // ✅ This enables the "Processing..." indicator
                serverSide: true,
                scrollX: true,
                scrollY: 500,
                fixedColumns: {
                    leftColumns: 1
                },
                ajax: {
                    url: "{{ route('transferList', $id) }}",
                    type: "POST",
                    data: function (d) {
                        d._token = "{{ csrf_token() }}";
                        d.unassigned = isUnassignedFilter ? 1 : 0;
                        d.find_q = $('#find_q').val(); 
                        d.assigned_user = $('#unassign_user').val();
                    },
                    "beforeSend": function () {
                        $('#loadingSpinner').addClass('show').show(); 
                    },
                    "complete": function () {
                        $('#loadingSpinner').removeClass('show').hide();
                    }

                    
                },
                lengthMenu: [[10, 20, 30, 50, 75, 100, 200, -1], [10, 20, 30, 50, 75, 100, 200, "All"]], // ✅ Custom entries dropdown
                "pageLength": {{ env('BACKEND_PAGINATION') }},
                searching: true, 
                // ❌ Disables search box
                dom: "<'row'<'col-sm-6'><'col-sm-6 text-end'l>>" + // Moves "Show entries" to Top Right
             "t" + 
             "<'row'<'col-sm-6'i><'col-sm-6 d-flex justify-content-end'p>>",
                columns: [
                    { 
                        data: 'check', 
                        orderable: false,
                        className: 'dt-center'
                    },
                    { data: 'id' },
                     
                    { data: 'name' },
                          // ✅ Status
                    { data: 'designation' },        // ✅ Designation
                    { data: 'phonenumber' },        // ✅ Phone number
                    { data: 'phonenumber_2' },      // ✅ Phone number 2
                       // ✅ Sub Status
                    { data: 'assign' },             // ✅ Assigned To
                        // ✅ Call Back At
                    { data: 'company' },            // ✅ Company
                    { data: 'description' },        // ✅ Description
                    { data: 'country' },            // ✅ Country
                    { data: 'pincode' },            // ✅ Pin Code
                    { data: 'city' },               // ✅ City
                    { data: 'state' },              // ✅ State
                    { data: 'address' },            // ✅ Address
                    { data: 'email' },              // ✅ Email
                    { data: 'website' },            // ✅ Website
                    { data: 'lead_value' },         // ✅ Lead Value
                    { data: 'position' },           // ✅ Position
                    { data: 'tags' } 
                ],
                order: [[1, 'asc']],
                language: {!! json_encode(__("backend.dataTablesTranslation")) !!}
            });
            dataTable.on('page.dt', function () {
                $('html, body').animate({
                    scrollTop: $(".dataTables_wrapper").offset().top
                }, 'slow');
            });
            $("#search-btn").on('click', function () {
                dataTable.draw();
            });

           
            // Checkbox handling
            $('#checkAll').on('click', function() {
                $('.row-checkbox').prop('checked', this.checked);
            });
        });
        $("#filter_btn").click(function () {
            $("#filter_div").slideToggle();
        });
        function toggleUnassigned() {
            isUnassignedFilter = !isUnassignedFilter;
            $('#unassigned_btn').text(isUnassignedFilter ? 'Show All' : 'Show Unassigned');
            dataTable.ajax.reload();
        }
       

        $("#checkAll").click(function () {
            $('input:checkbox').not(this).prop('checked', this.checked).trigger("change");
        });
        $('#unassign_user').on('change', function(){
    dataTable.ajax.reload();   // reload with filter
});
 var leadsArray = [];
        function transferLeads()
        {
            if(leadsArray.length == 0)
            {
                alert("Please select leads");
                return false;
            }
            let staff = $("#staff").val();
            let sales_person = $("#sales_person").val();
            
            let url = "{{ route('leadsTransferAction') }}";
            $.ajax({
                type: "POST",
                url: url,
                data: {'leadsArray': leadsArray, staff: staff, sales_person: sales_person, '_token': "{!! csrf_token() !!}"},
                success: function(data){
                    $('#submit_show_msg').show();
                    setTimeout(window.location.reload(),5000);
                }
            });
        }
        function unassignLeads()
        {
            if(leadsArray.length == 0)
            {
                alert("Please select leads");
                return false;
            }
            let staff = $("#staff").val();
            let sales_person = $("#sales_person").val();
            
            let url = "{{ route('leadsUnassignAction') }}";
            $.ajax({
                type: "POST",
                url: url,
                data: {'leadsArray': leadsArray, staff: staff, sales_person: sales_person, '_token': "{!! csrf_token() !!}"},
                success: function(data){
                    toastr.success(data.message);
                    setTimeout(window.location.reload(),5000);
                },
                error: function(xhr){
                    toastr.error(xhr.responseJSON.message);
                }
            });
        }
        $('body').on('change', '#ids', function () {

            var k = $(this).prop('checked') ? 1 : 0;
            if (k) {
                leadsArray.push($(this).val());
            } else {
                leadsArray.splice(leadsArray.indexOf($(this).val()), 1);
            }
            console.log(leadsArray);

        });
        

$('#unassign_user').on('change', function () {

    let selectedUser = $(this).val();

    if (!selectedUser) {
        dataTable.column(6).search('').draw(); // column 6 = Assigned To
    } else {
        dataTable.column(6).search('^' + selectedUser + '$', true, false).draw();
    }

});
function randomAllocation()
{
    if(!confirm("Are you sure you want to randomly allocate all unassigned leads?"))
        return;

    $.ajax({
        type: "POST",
        url: "{{ route('randomAllocation', $id) }}",
        data: {
            _token: "{{ csrf_token() }}"
        },
        success: function(response){

            toastr.success(response.message);

            // Reload page after 2 seconds
            setTimeout(function(){
                location.reload();
            }, 2000);
        },
        error: function(xhr){

            toastr.error(xhr.responseJSON.message);

        }
    });
}
    </script>
@endpush