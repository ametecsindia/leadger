@extends('dashboard.layouts.master')
@section('title', 'Campaigns')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe3c9;</i> Edit Campaign</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Campaigns</a>
                </small>
            </div>
            <div class="box-tool">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("campaigns")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
            <div class="mini-container">
                {{Form::open(['route'=>['campaignsUpdate',$Campaigns->id],'method'=>'POST', 'files' => true])}}

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">{!!  __('backend.fullName') !!}
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('name',$Campaigns->name, array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="teams"
                           class="col-sm-4">Source</label>
                    <div class="col-sm-8">
                        <select name="source_id" id="source_id" required class="form-control c-select">
                            @foreach($Sources as $Source)
                            <option value="{!! $Source->id !!}" {{$Campaigns->source_id == $Source->id ? 'selected' : '' }}>{!! $Source->name !!}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="segment_id"
                           class="col-sm-4">Segment</label>
                    <div class="col-sm-8">
                        <select name="segment_id" id="segment_id" required class="form-control c-select">
                            @foreach($Segments as $Segment)
                            <option value="{!! $Segment->id !!}" {!! $Campaigns->segment_id == $Segment->id ? 'selected' : '' !!}>{!! $Segment->name !!}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row hide">
                    <label for="team_id"
                           class="col-sm-4">Team</label>
                    <div class="col-sm-8">
                        <select name="team_id" id="team_id" required class="form-control c-select">
                            @foreach($Teams as $Team)
                            <option value="{!! $Team->id !!}" {!! $Campaigns->team_id == $Team->id ? 'selected' : '' !!}>{!! $Team->name !!}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="calling_type"
                           class="col-sm-4">Calling Type</label>
                    <div class="col-sm-8">
                        @php
                            $curType = strtolower(trim((string)($Campaigns->calling_type ?? 'click2call')));
                        @endphp
                        <select name="calling_type" id="calling_type" required class="form-control c-select">
                            <option value="click2call" {{ $curType === 'click2call' ? 'selected' : '' }}>Click2Call</option>
                            <option value="webrtc" {{ $curType === 'webrtc' ? 'selected' : '' }}>WebRTC</option>
                            <option value="both" {{ $curType === 'both' ? 'selected' : '' }}>Both (Click2Call & WebRTC)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="photo"
                           class="col-sm-4">File</label>
                    <div class="col-sm-8">
                        {!! Form::file('file', array('class' => 'form-control','id'=>'file','accept'=>'csv/*')) !!}
                        <small>
                            <i class="material-icons">&#xe8fd;</i>
                            Extension: .csv <a href="{!! URL::to('/uploads/sample_campaign.csv') !!}">Download Sample File</a>
                        </small>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="date"
                           class="col-sm-4">Expiry
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
                                {!! Form::text('date',$Campaigns->expiry_date, array('placeholder' => '','class' => 'form-control','id'=>'date','required'=>'')) !!}
                                <span class="input-group-addon">
              <span class="fa fa-calendar"></span>
          </span>
                            </div>
                        </div>

                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="faq"
                           class="col-sm-4">Lead info edit
                    </label>
                    <div class="col-sm-8">
                        {!! Form::checkbox('lead_info_edit','1',$Campaigns->lead_info_edit ? true : false, array('id' => 'lead_info_edit')) !!}
                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="number_masking"
                           class="col-sm-4">Number Masking
                    </label>
                    <div class="col-sm-8">
                        {!! Form::checkbox('number_masking','1',$Campaigns->number_masking ? true : false, array('id' => 'number_masking')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="faq"
                           class="col-sm-4">FAQ
                    </label>
                    <div class="col-sm-8">
                        {!! Form::textarea('faq',$Campaigns->faq, array('placeholder' => '','class' => 'form-control','id'=>'faq')) !!}
                    </div>
                </div>

                <div class="form-group row m-t-md">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary m-t"><i class="material-icons"> &#xe31b;</i> {!! __('backend.update') !!}</button>
                        <a href="{{route("campaigns")}}" class="btn btn-default m-t"><i class="material-icons"> &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
                    </div>
                </div>

                {{Form::close()}}
        </div>
            </div>
        </div>
    </div>
@endsection
