@extends('dashboard.layouts.master')
@section('title', __('Create Agreement'))
@section('content')



<div class="padding">
    <div class="box">
        <div class="box-header dker">
            <h3>{{ __('Create a new Agreement') }}</h3>
            <small>
                <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                <a href="#">{{ __('Agreements') }}</a> /
                <a href="#">{{ __('Create Agreement') }}</a>
            </small>
        </div>
        <div class="box-tool">
            <ul class="nav">
                <li class="nav-item inline">
                    <a class="nav-link" href="{{route("users")}}">
                        <i class="material-icons md-18">×</i>
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-box-shadow">
            {{ Form::open(['route'=>['agreements.store'],'method'=>'POST', 'files' => true]) }}
           

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="advertiser_name">Advertiser Name<span class="text-danger">*</span></label>
                        {!! Form::text('advertiser_name', '', ['class' => 'form-control', 'id' => 'advertiser_name', 'required']) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="calls_per_week">Calls Per Week<span class="text-danger">*</span></label>
                        {!! Form::number('calls_per_week', '', ['class' => 'form-control', 'id' => 'calls_per_week','required']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="calls_per_month">Calls Per Month<span class="text-danger">*</span></label>
                        {!! Form::number('calls_per_month', '', ['class' => 'form-control', 'id' => 'calls_per_month','required']) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="preferred_calling_hours">Preferred Calling Hours<span class="text-danger">*</span></label>
                        {!! Form::text('preferred_calling_hours', '', ['class' => 'form-control', 'id' => 'preferred_calling_hours','required']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
               
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="support_person_name">Support Person Name<span class="text-danger">*</span></label>
                        {!! Form::text('support_person_name', '', ['class' => 'form-control', 'id' => 'support_person_name','required']) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="contact_details">Contact Details<span class="text-danger">*</span></label>
                        {!! Form::text('contact_details', '', ['class' => 'form-control', 'id' => 'contact_details','required']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="start_date">Expected Start Date<span class="text-danger">*</span></label>
                        {!! Form::date('start_date', '', ['class' => 'form-control', 'id' => 'start_date','required']) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_date">Expected End Date<span class="text-danger">*</span></label>
                        {!! Form::date('end_date', '', ['class' => 'form-control', 'id' => 'end_date','required']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
            <div class="col-md-6">
                    <div class="form-group">
                        <label for="script_file">Script File (Doc, txt, pdf)<span class="text-danger">*</span></label>
                        {!! Form::file('script_file', ['class' => 'form-control', 'id' => 'script_file','required']) !!}
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="uploaded_file">Data File (Csv, Excel)<span class="text-danger">*</span></label>
                        {!! Form::file('uploaded_file', ['class' => 'form-control', 'id' => 'uploaded_file','required']) !!}
                    </div>
                </div>
            </div>

            


            <div class="row">
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-group">
                        <label for="agree_terms">
                            {!! Form::checkbox('agree_terms', '1', false, ['id' => 'agree_terms','required']) !!} {{ __('Agree to Terms and Conditions') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group row m-t-md">
                <div class="offset-sm-2 col-sm-10">
                    <button type="submit" class="btn btn-primary m-t">Add</button>
                    <a href="{{route("users")}}" class="btn btn-default m-t">
                        <i class="material-icons">&#xe5cd;</i> {{ __('backend.cancel') }}
                    </a>
                </div>
            </div>

            {{ Form::close() }}
        </div>
    </div>
</div>
@endsection


