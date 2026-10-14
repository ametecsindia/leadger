@extends('dashboard.layouts.master')
@section('title', 'Create SMS Gateway')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>Add a new SMS Gateway</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Create SMS Gateway</a>
                </small>
            </div>
            <div class="box-tool">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("sms")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
            <div class="mini-container">
                {{Form::open(['route'=>['smsStore'],'method'=>'POST', 'files' => false ])}}

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">SMS Gateway Name
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('name','', array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="method"
                           class="col-sm-4">Method</label>
                    <div class="col-sm-8">
                        <select name="method" id="method" required class="form-control c-select">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4">SMS Gateway API URL
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('gateway_api_url','', array('placeholder' => '','class' => 'form-control','id'=>'gateway_api_url','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4">Mobile Number Parameter
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('mobile_number_param','', array('placeholder' => '','class' => 'form-control','id'=>'mobile_number_param','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4">SMS Content Parameter
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('content_param','', array('placeholder' => '','class' => 'form-control','id'=>'content_param','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="add_status1"
                           class="col-sm-4">Status</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <label class="ui-check ui-check-md" style="margin-bottom: 5px;">
                                {!! Form::radio('active_status','1',true, array('id' => 'active_status1','class'=>'has-value')) !!}
                                <i class="dark-white"></i>
                                {{ __('backend.active') }}
                            </label>
                            &nbsp; &nbsp;
                            <label class="ui-check ui-check-md" style="margin-bottom: 5px;">
                                {!! Form::radio('active_status','0',false, array('id' => 'active_status2','class'=>'has-value')) !!}
                                <i class="dark-white"></i>
                                {{ __('backend.notActive') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary"><i class="material-icons"> &#xe31b;</i> {!! __('backend.add') !!}</button>
                        <a href="{{route("sms")}}" class="btn btn-default"><i class="material-icons"> &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
                    </div>
                </div>

                {{Form::close()}}
</div>
            </div>
        </div>
    </div>
@endsection
