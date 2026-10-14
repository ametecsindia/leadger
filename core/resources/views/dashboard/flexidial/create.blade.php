@extends('dashboard.layouts.master')
@section('title', 'Create Flexidial')

@push("after-styles")
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/dashboard/js/datatables/fixedColumns.dataTables.min.css') }}">

@endpush

@section('content')

<div class="padding add-compaign-form">
<div class="box">

<div class="box-header dker">
<h3><i class="material-icons">phone_in_talk</i> Create Flexidial</h3>
<small>
<a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
Create Flexidial
</small>
</div>

<div class="box-body">
<div class="mini-container">

{{-- CHANNEL STATS --}}
@if($flexidial_channels > 0)
<div id="click2call_block" style="display:none;">
<div class="row text-center mb-3">

<div class="col-md-4">
<h6>Total Click2Call Flexidial</h6>
<strong>{{ $flexidial_channels }}</strong>
</div>

<div class="col-md-4">
<h6>Remaining</h6>
<strong>{{ $remaining_flexidial_channels }}</strong>
</div>

<div class="col-md-4">
<h6>In Use</h6>
<strong>{{ $usedChannels }}</strong>
</div>

</div>
</div>
@endif


@if($webrtc_channels > 0)
<div id="webrtc_block" style="display:none;">
<div class="row text-center mb-3">

<div class="col-md-4">
<h6>Total WebRTC Flexidial</h6>
<strong>{{ $webrtc_channels }}</strong>
</div>

<div class="col-md-4">
<h6>Remaining</h6>
<strong>{{ $remaining_webrtc_channels }}</strong>
</div>

<div class="col-md-4">
<h6>In Use</h6>
<strong>{{ $usedWebRTCChannels }}</strong>
</div>

</div>
</div>
@endif



{{-- FLEXI TYPE --}}
@if($flexidial_channels > 0 || $webrtc_channels > 0)
<br>
<div class="form-group row">
<label class="col-sm-4">Flexi Type</label>

<div class="col-sm-8">

<select id="flexi_type" class="form-control">

<option value="">Select Flexi Type</option>

@if($flexidial_channels > 0)
<option value="click2call">Click2Call</option>
@endif

@if($webrtc_channels > 0)
<option value="webrtc">WebRTC</option>
@endif

</select>

</div>
</div>

@endif



{{-- CAMPAIGN --}}
<div class="form-group row">

<label class="col-sm-4">Campaign</label>

<div class="col-sm-8">

<select name="campaign" id="campaign" class="form-control" onchange="getStatus()">

<option value="">Select Campaign</option>

@foreach($campaigns as $campaign)
<option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
@endforeach

</select>

</div>
</div>



{{-- CRITERIA --}}
<div class="form-group row">

<label class="col-sm-4">Criteria</label>

<div class="col-sm-8">

<select class="form-control" name="type" id="type" onchange="changeType()">
<option value="dp_code">DP Code</option>
<option value="c2c_status">Click2call Status</option>
</select>

</div>
</div>



{{-- DP CODE --}}
<div class="form-group row" id="dp_code_div">

<label class="col-sm-4">DP Codes</label>

<div class="col-sm-8">

<select multiple class="form-control" name="dp_code" id="dp_code">
</select>

</div>
</div>



{{-- C2C STATUS --}}
<div class="form-group row" id="c2c_status_div">

<label class="col-sm-4">Click2call Status</label>

<div class="col-sm-8">

<select multiple class="form-control" name="c2c_status" id="c2c_status">
</select>

</div>
</div>



{{-- MOBILE NUMBERS --}}
<div class="form-group row">

<label class="col-sm-4">Mobile Numbers</label>

<div class="col-sm-8">

<select class="form-control" name="mobile_numbers" id="mobile_numbers">
<option value="primary">Primary</option>
<option value="all">All</option>
</select>

</div>
</div>



{{-- PARALLEL USERS --}}
@if(!in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))

<div class="form-group row">

<label class="col-sm-4">Parallel Users</label>

<div class="col-sm-8">

<select class="form-control" name="flexidial_parallel_users" id="flexidial_parallel_users">

@for($i=1;$i<=10;$i++)
<option value="{{$i}}">{{$i}}</option>
@endfor

</select>

</div>
</div>

@else

<input type="hidden" name="flexidial_parallel_users" id="flexidial_parallel_users" value="1">

@endif



{{-- PARALLEL CALLS --}}
<div class="form-group row">

<label class="col-sm-4">Parallel Calls</label>

<div class="col-sm-8">

<select class="form-control" name="flexidial_call_rate" id="flexidial_call_rate">

@for($i=1;$i<=10;$i++)
<option value="{{$i}}">{{$i}}</option>
@endfor

</select>

</div>
</div>



{{-- SUBMIT --}}
@if($user->c2c_channel == 1 || $user->webrtc_channel == 1)

<div class="form-group row">

<div class="offset-sm-4 col-sm-8">

<button onclick="saveFlexidial()" class="btn btn-primary">

<i class="material-icons">save</i> Create

</button>

</div>
</div>

@else

<div class="form-group row">

<div class="offset-sm-4 col-sm-8">

<strong>Click2call/WebRTC channel not assigned</strong>

</div>
</div>

@endif

</div>
</div>
</div>
</div>

@endsection
@push("after-scripts")

<script>

    function changeType()
    {
        let type = $("#type").val();
        if(type == 'dp_code')
        {
            $("#dp_code_div").show();
            $("#c2c_status_div").hide();
        }
        else
        {
            $("#dp_code_div").hide();
            $("#c2c_status_div").show();
        }
    }
    changeType();
function getStatus() {

    let campaign = $('#campaign').val();

    if (campaign != '') {

        var url = '{{ route('admin.flexidial-options',':id') }}';
        url = url.replace(':id', campaign);

        $.ajax({
            url: url,
            type: "GET",
            success: function (response) {

                if (response.status != 'fail') {

                    $('#dp_code').html(response.statushtml);
                    $('#dp_code').trigger('change');

                    $('#c2c_status').html(response.click2callhtml);
                    $('#c2c_status').trigger('change');
                }

            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });

    } else {

        $('#dp_code').html('');
        $('#c2c_status').html('');

        $('#dp_code').trigger('change');
        $('#c2c_status').trigger('change');
    }
}
 function saveFlexidial()
{
    var campaign = $('#campaign').val();
    if(campaign == '')
    {
        alert("Select Campaign");
        return false;
    }

    let type = $('#type').val();
    let dp_code = $('#dp_code').val();
    let c2c_status = $('#c2c_status').val();
    let mobile_numbers = $("#mobile_numbers").val();

    if(type == 'dp_code' && dp_code == null)
    {
        alert("Select DP Code");
        return false;
    }
    else if(type == 'c2c_status' && c2c_status == null)
    {
        alert("Select Click2call Status");
        return false;
    }

    var flexidial_call_rate = $('#flexidial_call_rate').val();
    var flexidial_parallel_users = $('#flexidial_parallel_users').val();

    if(flexidial_call_rate == '' || flexidial_call_rate == null)
    {
        alert("Select Call Rate");
        return false;
    }

    let flexiType = $('#flexi_type').val();

    if (!flexiType) {
        alert("Select Flexi Type");
        return false;
    }

    console.log(flexiType);

    var url = '';
    var redirectUrl = '';

    if(flexiType == 'click2call'){
        url = '{{ route('admin.callmanager.save-flexidial') }}';
        redirectUrl = '{{ route('admin.callmanager.flexidial') }}';
    }else{
        url = '{{ route('admin.callmanager.save-webrtc-flexidial') }}';
        redirectUrl = '{{ route('admin.callmanager.webrtc-flexidial') }}';
    }

    $.ajax({
        url: url,
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            campaign: campaign,
            type: type,
            flexidial_call_rate: flexidial_call_rate,
            flexidial_parallel_users: flexidial_parallel_users,
            dp_code: dp_code,
            c2c_status: c2c_status,
            mobile_numbers: mobile_numbers
        },
        success: function (response) {

            if(response.status != 'fail'){
                window.location = redirectUrl;
            }else{
                alert(response.message ?? "Something went wrong");
            }

        },
        error: function(xhr){
            console.log(xhr.responseText);
            alert("Server error occurred");
        }
    });

}



        function viewLead(id) {
            
            url = url.replace(':id', id);
            $.ajaxModal('#addEditModal', url)
        }

document.addEventListener('DOMContentLoaded', function () {

    let flexiTypeSelect = $('#flexi_type');
    let hasClick2Call = {{ $flexidial_channels > 0 ? 'true' : 'false' }};
    let hasWebRTC = {{ $webrtc_channels > 0 ? 'true' : 'false' }};

function applyFlexiUI(type) {
    $('#click2call_block, #webrtc_block').hide();

    if (type === 'click2call') {
        $('#click2call_block').slideDown();
        @if(!in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))

        updateParallelUsers(remainingClick2Call);
        @endif
        updateParallelcalls('click2call');
    }

    if (type === 'webrtc') {
        $('#webrtc_block').slideDown();
        @if(!in_array(Auth::user()->permissionsGroup->name,['Telecaller','Sales Person']))


        updateParallelUsers(remainingWebRTC); 
        @endif
        updateParallelcalls('webrtc');
    }
}

    // ?? Auto-select if ONLY ONE option exists
    if (hasClick2Call && !hasWebRTC) {
        flexiTypeSelect.val('click2call');
        applyFlexiUI('click2call');
    }

    if (!hasClick2Call && hasWebRTC) {
        flexiTypeSelect.val('webrtc');
        applyFlexiUI('webrtc');
    }

    // ?? If both exist ? wait for user
    flexiTypeSelect.on('change', function () {
        applyFlexiUI($(this).val());
    });

});

let remainingClick2Call = {{ (int) $remaining_flexidial_channels }};
let remainingWebRTC     = {{ (int) $remaining_webrtc_channels }};

function updateParallelUsers(maxAllowed) {
    let $parallel = $('#flexidial_parallel_users');
    let currentVal = parseInt($parallel.val()) || 1;

    $parallel.empty();

    if (maxAllowed < 1) {
        $parallel.append('<option value="">No Channels Available</option>');
        return;
    }

    for (let i = 1; i <= maxAllowed; i++) {
        $parallel.append(`<option value="${i}">${i}</option>`);
    }

    if (currentVal <= maxAllowed) {
        $parallel.val(currentVal);
    } else {
        $parallel.val(1);
    }
}

function updateParallelcalls(type) {
    let $parallel = $('#flexidial_call_rate');
    let maxAllowed = 0;

    if (type === 'webrtc') {
        maxAllowed = Math.min(remainingWebRTC, 3);
    }
    else if (type === 'click2call') {
        maxAllowed = remainingClick2Call;
    }


    $parallel.empty();

    if (maxAllowed < 1) {
        $parallel.append('<option value="">No Channels Available</option>');
        return;
    }

    for (let i = 1; i <= maxAllowed; i++) {
        $parallel.append(`<option value="${i}">${i}</option>`);
    }

    $parallel.val(1);
}

    </script>


@endpush