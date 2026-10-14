@extends('dashboard.layouts.master')
@section('title', 'Create Client Type')
@section('content')
    <div class="padding add-product-service-form">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe02e;</i> Add a new Client Type</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Create Client Type</a>
                </small>
            </div>
            <div class="box-tool sm-d-none">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("clientTypes")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
            <div class="mini-container">
                {{Form::open(['route'=>['clientTypesStore'],'method'=>'POST', 'files' => false ])}}

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">Client Type
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('name','', array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
                    </div>
                </div>

                <!-- <div class="form-group row">
                    <label for="seq_order"
                           class="col-sm-4">Order</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <select name="seq_order" id="seq_order" required
                                    class="form-control c-select">
                                <option value="">- - Select Order - -</option>
                                @for ($i=1;$i<=$Count || $i == 1;$i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div> -->

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
                        <button type="submit" class="btn btn-primary m-t"><i class="material-icons"> &#xe31b;</i> {!! __('backend.add') !!}</button>
                        <a href="{{route("clientTypes")}}" class="btn btn-default m-t"><i class="material-icons"> &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
                    </div>
                </div>

                {{Form::close()}}
</div>
            </div>
        </div>
    </div>
@endsection
