@extends('dashboard.layouts.master')
@section('title', 'WebRTC Flexidial')

@push("after-styles")
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">
<style>
.select2-container {
    width: 100% !important;
}
.campaign-filter-bar{
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 5;
    padding: 10px;
    border-bottom: 1px solid #eee;
}
.select2-container--default .select2-selection--single {
    height: 38px;
    padding: 4px;
}
.select2-container {
    z-index: 999999 !important;
}


.select2-dropdown {
    z-index: 999999 !important;
}

.dropdown-content {
    z-index: 999999 !important;
}
.cardbg-4{
background:#006666;
border-radius:10px;
padding:10px 20px;
}

.cardbg-4 .card-body{
display:flex;
justify-content:space-between;
align-items:center;
}

.cardbg-4 .h4{
font-size:20px;
margin-top:5px;
}

.cardbg-4 .h5{
font-size:14px;
}

.viewChannelsUsed{
margin-top:15px;
}

#users-table td,
#live-table td{
font-family: var(--lg-font-sans);
}
</style>
@endpush


@section('content')

<div class="padding">

<div class="box">

<div class="box-header dker">

<h3>WebRTC Flexidial</h3>

<small>
<a href="{{ route('adminHome') }}">Home</a> /
<a>WebRTC Flexidial</a>
</small>

<div class="box-tool">

<button class="btn btn-success start hide" onclick="flexidial(1)">
<i class="fa fa-play"></i> Start
</button>

<button class="btn btn-warning stop hide" onclick="flexidial(0)">
<i class="fa fa-stop"></i> Stop
</button>

<button class="btn btn-danger" onclick="flexidialClear()">
<i class="fa fa-trash"></i> Clear
</button>

<button class="btn btn-info restart" onclick="restartFlexidial()">
<i class="fa fa-refresh"></i> Restart Leads
</button>

</div>

</div>



<div class="card-box-shadow">

{{-- FLEXIDIAL STATS --}}
<div class="row p-a">

<div class="col-md-3 col-xs-6">
<div class="card cardbg-4">
<div class="card-body text-white">

<div>
<div class="h5">Total Channels</div>
<div class="h4">{{$total_ch}}</div>
</div>

<i class="fa fa-phone fa-lg"></i>

</div>
</div>
</div>


<div class="col-md-3 col-xs-6">
<div class="card cardbg-4">
<div class="card-body text-white">

<div>
<div class="h5">Channels In Use</div>
<div class="h4">{{$usedChannels[0]->used ?? 0}}</div>
</div>

<i class="fa fa-signal fa-lg"></i>

</div>
</div>
</div>


@if(!in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))

<div class="col-md-3 col-xs-6">
<div class="card cardbg-4">
<div class="card-body text-white">

<div>
<div class="h5">Parallel Users</div>
<div class="h4">{{$user->webrtc_flexidial_parallel_users}}</div>
</div>

<i class="fa fa-users fa-lg"></i>

</div>
</div>
</div>

@endif


<div class="col-md-3 col-xs-6">
<div class="card cardbg-4">
<div class="card-body text-white">

<div>
<div class="h5">Parallel Calls</div>
<div class="h4">{{$user->flexidial_call_rate}}</div>
</div>

<i class="fa fa-phone-square fa-lg"></i>

</div>
</div>
</div>

</div>



{{-- CHANNELS USED --}}
@if(!in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))

<div class="p-a">

<button class="btn btn-info" id="showChannelsUsed">
<i class="fa fa-eye"></i> Show Channels Used
</button>

</div>

<div class="viewChannelsUsed displayNone p-a">

<table id="active-table" class="table display nowrap" width="100%">

<thead class="dker">
<tr>
<th>Telecaller</th>
<th>Channels Used</th>
<th>Leads Count</th>
</tr>
</thead>

</table>

</div>

@endif



{{-- RUNNING CALLS --}}
@if($user->flexidial_status == '1')

<div class="p-a">

<h4>Accounts Dialed</h4>

<table id="live-table" class="table display nowrap">

<thead class="dker">
<tr>
<th>Lead Id</th>
<th>Customer Name</th>
<th>Campaign</th>
<th>Mobile</th>
<th>DID</th>
<th>Dial Status</th>
<th>C2C Status</th>
</tr>
</thead>

<tbody id="sse_data">

@foreach($running as $row)

<tr>

<td>
    <a href="javascript:void(0);" style="color:#007bff; text-decoration:underline;"
       onclick="leadDetail1('{{ $row->lead_id }}')">
        {{ $row->lead_id }}
    </a>
</td>

<td>{{$row->customer_name}}</td>

<td>{{$row->campaign_name}}</td>

<td>{{$row->mobile_number}}</td>

<td>{{$row->agent_did}}</td>

<td>{{$row->dial_status}}</td>

<td>{{$row->c2c_status}}</td>

</tr>

@endforeach

</tbody>

</table>

</div>

@endif



{{-- ACCOUNT LIST --}}
<div class="p-a campaign-filter-bar">

<div class="row">

<div class="col-md-4">

<select id="campaignFilterUsers" class="form-control">

<option value="">Select Campaign</option>

@foreach($campaigns as $campaign)

<option value="{{$campaign->id}}">
{{$campaign->name}}
</option>

@endforeach

</select>

</div>

</div>

</div>



<div class="p-a">

<table id="users-table" class="table display nowrap" width="100%">

<thead class="dker">

<tr>
<th>Lead Id</th>
<th>Customer Name</th>
<th>Campaign</th>
<th>Mobile</th>
<th>Dial Status</th>
<th>Customer Status</th>
<th>Agent Call Status</th>
<th>DID</th>
</tr>

</thead>

</table>

</div>

</div>

</div>

</div>
            <div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-lg" id="animate">
                    <div class="modal-content lead-content">
                    </div><!-- /.modal-content -->
                </div>
            </div>
@endsection


@push("after-scripts")

<script src="{{ asset('assets/dashboard/js/datatables/datatables.min.js') }}"></script>

<script>

var table='';
var table1='';
var flexidial_status='{{$user->webrtc_flexidial_status}}';


$("#showChannelsUsed").click(function(){

$(".viewChannelsUsed").toggle()

if($(this).text()=="Show Channels Used")
$(this).text("Hide Channels Used")
else
$(this).text("Show Channels Used")

});


if(flexidial_status=='1')
$(".stop").removeClass('hide')
else
$(".start").removeClass('hide')



function activeUsers(){

var url="{{route('admin.callmanager.webrtc-active-flexidial')}}"

if(table1!='')
table1.destroy()

table1=$('#active-table').DataTable({

dom:'Bfrtip',
scrollX:true,
scrollY:450,
ordering:false,
processing:true,
serverSide:true,
searching:false,

columns:[
{data:'name'},
{data:'channels_used'},
{data:'pending_calls'}
],



ajax:{
url:url,
type:"POST",
headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}
}

})

}



function doFormSubmit(){

var url="{{route('admin.callmanager.webrtc-flexidial-data')}}"

if(table!='')
table.destroy()

table=$('#users-table').DataTable({

dom:'lBfrtip',
scrollX:true,
scrollY:450,
ordering:false,
processing:true,
serverSide:true,

lengthMenu:[[10,50,100,500,1000],[10,50,100,500,1000]],

columns:getColumns(),


ajax:{
url:url,
type:"POST",
headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
data:function(d){
d.campaign_id=$('#campaignFilterUsers').val()
}
}

})

}



function getColumns(){

var cols=@json($colsarr)

columnsarr=[]

for(var i=0;i<cols.length;i++){

    if(cols[i] == 'lead_id'){
        columnsarr.push({
            data:'lead_id',
            render:function(data,type,row){
                return `<a href="javascript:void(0)" 
                        style="color:#007bff"
                        onclick="leadDetail('${data}')">
                        ${data}
                        </a>`;
            }
        })
    }else{
        columnsarr.push({data:cols[i]})
    }

}

return columnsarr

}



$('#campaignFilterUsers').on('change',function(){

if(table){
table.ajax.reload()
}

})



$(document).ready(function(){



doFormSubmit()

$('select').formSelect()

@if(!in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))

activeUsers()

setInterval(function(){
activeUsers()
},60000)

@endif

})



function leadDetail(id, element) {

    var url = "{{ route('leadDetail', [':id']) }}";
    url = url.replace(':id', id);

    $('.lead-content').html('');

    let call_type = 'webrtc';
    let callLogHash = $(element).data('calllog');

    $.ajax({
        type: "GET",
        url: url,
        dataType: "html",
        success: function(html){

            $('.lead-content').html(html);
            $("#lead-detail").modal("show");

            $("select").select2();

            // ✅ Now elements exist in DOM
            $('#save_button').attr('disabled', false);
            $('#call_type').val(call_type);
            $('#call_log_id').val(callLogHash);

        }
    });
}
function leadDetail1(id) {

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

            $("select").select2();

            // ✅ Now elements exist in DOM
          

        }
    });
}
function flexidial(status){

var url='{{ route('admin.callmanager.webrtc-flexidial-status') }}';

$.ajax({
    url:url,
    type:"POST",
    data:{
        _token:"{{ csrf_token() }}",
        status:status
    },
    success:function(response){
        if(response.status!='fail'){
            location.reload();
        }
    }
});

}



function flexidialClear(){

var url='{{ route('admin.callmanager.webrtc-flexidial-clear') }}';

$.ajax({
    url:url,
    type:"POST",
    data:{
        _token:"{{ csrf_token() }}"
    },
    success:function(response){
        if(response.status!='fail'){
            location.reload();
        }
    }
});

}



function restartFlexidial(){

var url='{{ route('admin.callmanager.webrtc-refresh-flexidial') }}';

$.ajax({
    url:url,
    type:"POST",
    data:{
        _token:"{{ csrf_token() }}"
    },
    success:function(response){
        if(response.status!='fail'){
            location.reload();
        }
    }
});

}
if(flexidial_status=='1'){

const sseSource=new EventSource('/admin/webrtc_flexidial_running')

sseSource.onmessage=function(event){

const data=JSON.parse(event.data)

let html=''

data.forEach(row=>{

if(row.lead_id=='Insufficient virtual numbers'){

html+=`
<tr>
<td colspan="9" style="color:red">Insufficient virtual numbers</td>
</tr>`

}
else{

html+=`
<tr>
<td>
<a href="javascript:void(0)"  data-calllog="${row.call_log_hash}" class="text-primary" onclick='leadDetail("${row.lead_id}", this)'>
${row.lead_id}
</a>
<td>${row.customer_name}</td>

<td>${row.campaign_name}</td>
<td>${row.mobile_number}</td>
<td>${row.agent_did}</td>
<td>${row.dial_status}</td>
<td>${row.c2c_status}</td>
</tr>`

}

})

$('#sse_data').html(html)

}

}

</script>

@endpush