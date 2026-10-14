@extends('dashboard.layouts.master')
@section('title', 'Edit Shift')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe3c9;</i> Edit Shift</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Shift</a>
                </small>
            </div>
            <div class="box-tool">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("shifts")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
            <div class="mini-container">
                {{Form::open(['route'=>['shiftsUpdate',$Shift->id],'method'=>'POST', 'files' => true])}}

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">Name
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('name',$Shift->name, array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
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
                                {!! Form::text('start_time',date('h:i a',strtotime($Shift->start_time)), array('placeholder' => '','class' => 'form-control','id'=>'start_time')) !!}
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
                                {!! Form::text('end_time',date('h:i a',strtotime($Shift->end_time)), array('placeholder' => '','class' => 'form-control','id'=>'end_time')) !!}
                        </div>
                    </div>
                </div>

                <div class="form-group row m-t-md">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary m-t"><i class="material-icons"> &#xe31b;</i> {!! __('backend.update') !!}</button>
                        <a href="{{route("shifts")}}" class="btn btn-default m-t"><i class="material-icons"> &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
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
        $("#start_time").val('{{date('h:i a',strtotime($Shift->start_time))}}');
        $("#end_time").val('{{date('h:i a',strtotime($Shift->end_time))}}');
    };
</script>
@endpush