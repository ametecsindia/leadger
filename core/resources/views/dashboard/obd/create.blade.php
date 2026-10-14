@extends('dashboard.layouts.master')
@section('title', 'Create OBD Campaign')
@section('content')
    <div class="padding add-new-status-form">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe02e;</i> Create OBD Campaign</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Create OBD Campaign</a>
                </small>
            </div>
            <div class="box-tool">
                <ul class="nav">
                    <li class="nav-item inline">
                        Credits: {!! $Setting[0]->obd_credits !!}
                    </li>
                    <li class="nav-item inline hide">
                        <button type="button" class="btn warn" id="print_btn" title="Print" onclick="print_as('print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </li>
                    <li class="nav-item inline hide">
                        <button type="button" class="btn success" id="excel_btn" title="Excel" onclick="print_as('excel')">
                            <i class="fa fa-file-excel-o"></i>
                        </button>
                    </li>
                </ul>
            </div>
            <div class="box-tool sm-d-none">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("obdCampaignCreate")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
            <div class="mini-container">
                {{Form::open(['route'=>['obdCampaignStore'],'method'=>'POST', 'files' => true ])}}

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">OBD Campaign Name
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('name','', array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="campaign_id"
                           class="col-sm-4">Campaign</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <select name="campaign_id[]" id="campaign_id" multiple onchange="getPhoneNumbers()"
                                    class="form-control c-select">
                                @foreach($Campaigns as $Campaign)
                                    <option value="{{ $Campaign->id }}">{{ $Campaign->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4">Mobile Numbers <br>(Comma , separated)
                    </label>
                    <div class="col-sm-8">
                        {!! Form::textarea('phone_numbers','', array('placeholder' => '','class' => 'form-control','id'=>'phone_numbers', 'onblur'=>'phoneNumberCount()' )) !!}
                        Number of Mobile Numbers: <span id='phone_numbers_count'>0</span>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="did"
                           class="col-sm-4">Did</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <select name="did" id="did" required
                                    class="form-control c-select">
                                @foreach($Did as $Row)
                                    <option value="{{ $Row->virtual_number }}">{{ $Row->virtual_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="schedule_start"
                           class="col-sm-4">Schedule Start
                    </label>
                    <div class="col-sm-8">
                        <div class="form-group">
                            <div class='input-group date' ui-jp="datetimepicker" ui-options="{
            format: '{{ 'YYYY-MM-DD' }}',
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
                                {!! Form::text('schedule_start',date("Y-m-d"), array('placeholder' => '','class' => 'form-control','id'=>'schedule_start','required'=>'')) !!}
                                <span class="input-group-addon">
              <span class="fa fa-calendar"></span>
          </span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="form-group row">
                    <label for="schedule_end"
                           class="col-sm-4">Schedule End
                    </label>
                    <div class="col-sm-8">
                        <div class="form-group">
                            <div class='input-group date' ui-jp="datetimepicker" ui-options="{
            format: '{{ 'YYYY-MM-DD' }}',
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
                                {!! Form::text('schedule_end',date("Y-m-d"), array('placeholder' => '','class' => 'form-control','id'=>'schedule_end','required'=>'')) !!}
                                <span class="input-group-addon">
              <span class="fa fa-calendar"></span>
          </span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4">Start Time
                    </label>
                    <div class="col-sm-8">
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
                                {!! Form::text('start_time','10:00 am', array('placeholder' => '','class' => 'form-control','id'=>'start_time','data-format'=>'hh:mm:ss')) !!}
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4">End Time
                    </label>
                    <div class="col-sm-8">
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
                                {!! Form::text('end_time','07:00 pm', array('placeholder' => '','class' => 'form-control','id'=>'end_time','data-format'=>'hh:mm:ss')) !!}
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="file"
                           class="col-sm-4">MP3 File</label>
                    <div class="col-sm-8">
                        {!! Form::hidden('credits','', array('placeholder' => '','class' => 'form-control','id'=>'credits','required'=>'')) !!}
                        {!! Form::file('file', array('class' => 'form-control','id'=>'file','accept'=>'mp3/*', 'required' => '')) !!}
                        <small>
                            <i class="material-icons">&#xe8fd;</i>
                            Extension: .mp3 
                        </small>Credits Used per call: <span id='credits_used'>0</span>
                    </div>
                </div>

                <div class="form-group row m-t-md">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary m-t"><i class="material-icons">&#xe31b;</i> {!! __('backend.add') !!}</button>
                        <a href="{{route("statuses")}}" class="btn btn-default m-t"><i class="material-icons">&#xe5cd;</i> {!! __('backend.cancel') !!}</a>
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