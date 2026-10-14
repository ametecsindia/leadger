@extends('dashboard.layouts.master')
@section('title', 'VMN: '.$ObdDid->virtual_number)
@section('content')
    <div class="padding add-new-status-form">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe02e;</i> VMN: {!! $ObdDid->virtual_number !!}</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">VMN: {!! $ObdDid->virtual_number !!}</a>
                </small>
            </div>
            <div class="box-tool sm-d-none">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("obdDid")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
            <div class="mini-container">
                {{Form::open(['route'=>['obdDidUpdate'],'method'=>'POST', 'files' => true ])}}
                <input type='hidden' id='id' name='id' value='{{ $ObdDid->id }}'>

                <div class="form-group row">
                    <label for="campaign_id"
                           class="col-sm-4">Team</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <select name="team_id[]" id="team_id" multiple
                                    class="form-control c-select">
                                @foreach($teams as $row)
                                <option value="{{ $row->id }}" {{ in_array($row->id, explode(',',$ObdDid->team_id)) ? 'selected' : '' }}>{{ $row->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group row m-t-md">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary m-t"><i class="material-icons">&#xe31b;</i> Update</button>
                        <a href="{{route("obdDid")}}" class="btn btn-default m-t"><i class="material-icons">&#xe5cd;</i> {!! __('backend.cancel') !!}</a>
                    </div>
                </div>
                {{Form::close()}}
            </div>
            </div>
        </div>
    </div>
@endsection
@push("after-scripts")
<script>
    window.onload = (event) => {
        $('#start_time').datetimepicker({
            format:'hh:mm a'
        });
        $('#end_time').datetimepicker({
            format:'hh:mm a'
        });
        $('#start_time').data('DateTimePicker').date('10:00:00');
        $('#end_time').data('DateTimePicker').date('19:00:00');
    };
</script>
<script>
  $(document).ready(function() {
    $('#file').on('change', function (event) {
      const file = event.target.files[0];
      if (!file) return;

      // Create an HTML5 audio element
      const audioElement = new Audio();

      // Set the URL of the audio element to the selected MP3 file
      audioElement.src = URL.createObjectURL(file);

      // Wait for the audio metadata to load
      audioElement.addEventListener('loadedmetadata', function() {
        // Get the duration in seconds
        let durationInSeconds = audioElement.duration;
        console.log('Duration (seconds):', Math.ceil(durationInSeconds));
        let credits = Math.ceil(Math.ceil(durationInSeconds)/30);
        $("#credits").val(credits);
        $("#credits_used").html(credits);
        
      });
    });
  });
function phoneNumberCount()
{
    $("#phone_numbers_count").html($("#phone_numbers").val().split(',').length);
}
function getPhoneNumbers()
{
    var campaign_id = $("#campaign_id").val();
    $("#phone_numbers").val('');
    if(campaign_id == null)
        return true;
    $.ajax({
        type: "POST",
        url: "{!! route('obdPhoneNumbers') !!}",
        data: {
            _token:"{!! csrf_token() !!}",
            campaigns : campaign_id
        },
        dataType: 'json',
        success: function(result){
            if(result.success == true)
            {
                $("#phone_numbers").val(result.numbers);
                phoneNumberCount();
            }
            else
            {
                $("#phone_numbers").val('');
                phoneNumberCount();
            }
        }
    });

}

</script>
@endpush