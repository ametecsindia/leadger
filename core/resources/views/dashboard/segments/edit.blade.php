@extends('dashboard.layouts.master')
@section('title', 'Edit Segment')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe3c9;</i> Edit Segment</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Segment</a>
                </small>
            </div>
            <div class="box-tool">
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
                {{Form::open(['route'=>['segmentsUpdate',$Segment->id],'method'=>'POST', 'files' => true])}}

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">Name
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('name',$Segment->name, array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4">Description
                    </label>
                    <div class="col-sm-8">
                        {!! Form::textarea('description',$Segment->description, array('placeholder' => '','class' => 'form-control','id'=>'description')) !!}
                    </div>
                </div>

                <div class="form-group row m-t-md">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary m-t"><i class="material-icons"> &#xe31b;</i> {!! __('backend.update') !!}</button>
                        <a href="{{route("segments")}}" class="btn btn-default m-t"><i class="material-icons"> &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
                    </div>
                </div>

                {{Form::close()}}
</div>
            </div>
        </div>
    </div>
@endsection
