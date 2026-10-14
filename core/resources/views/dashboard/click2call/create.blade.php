@extends('dashboard.layouts.master')
@section('title', 'Create Click2Call')
@section('content')
    <div class="padding add-product-service-form">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe02e;</i> Add a new Click2Call</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Create Click2Call</a>
                </small>
            </div>
            <div class="box-tool sm-d-none">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("click2Call")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
            <div class="mini-container">
                {{Form::open(['route'=>['click2CallStore'],'method'=>'POST','id'=>'agent_form' ,'files' => false ])}}

                <div class="form-group row">
                    <label for="user"
                           class="col-sm-4">User</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <select name="user" id="user" required
                                    class="form-control c-select">
                                @foreach ($Users as $User)
                                    <option value="{{ $User->id }}">{{ $User->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="click_agent_number"
                           class="col-sm-4">Agent Number
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('click_agent_number','', array('placeholder' => '','class' => 'form-control','id'=>'click_agent_number')) !!}
                    </div>
                </div>

                <div class="form-group row m-t-md">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary m-t"><i class="material-icons">&#xe31b;</i> {!! __('backend.add') !!}</button>
                        <a href="{{route("click2Call")}}" class="btn btn-default m-t"><i class="material-icons"> &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
                    </div>
                </div>

                {{Form::close()}}
</div>
            </div>
        </div>
    </div>



@endsection

 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Ensure jQuery is included -->

<script>
    $(document).ready(function() {
        $(".add-product-service-form form").submit(function(event) {
            var agentNumber = $("#click_agent_number").val().trim();
            if (agentNumber === "") {
                alert("Please enter an agent number");
                event.preventDefault(); // Prevent form submission
            }
        });
    });
</script>

