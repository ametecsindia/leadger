@extends('dashboard.layouts.master')
@section('title', 'Create Segment')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe02e;</i> Add a new Segment</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Create Segment</a>
                </small>
            </div>
            <div class="box-tool sm-d-none">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("segments")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
            <div class="mini-container">
                {{Form::open(['route'=>['segmentsStore'],'method'=>'POST', 'files' => false ])}}

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">Segment Name
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('name','', array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4">Description
                    </label>
                    <div class="col-sm-8">
                        {!! Form::textarea('description','', array('placeholder' => '','class' => 'form-control','id'=>'description')) !!}
                    </div>
                </div>

                <div class="form-group row m-t-md">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary m-t"><i class="material-icons">
                                &#xe31b;</i> {!! __('backend.add') !!}</button>
                        <a href="{{route("segments")}}"
                           class="btn btn-default m-t"><i class="material-icons">
                                &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
                    </div>
                </div>

                {{Form::close()}}
</div>
            </div>
        </div>
    </div>
@endsection
