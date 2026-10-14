@extends('dashboard.layouts.master')
@section('title', 'Edit Email Setting')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>Edit Email Setting</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Email Setting</a>
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
                {{Form::open(['route'=>['emailUpdate',$EmailSetting->id],'method'=>'POST', 'files' => true, 'id'=> 'email-form'])}}

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4 form-control-label">Name
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('name',$EmailSetting->name, array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="driver"
                           class="col-sm-4 form-control-label">Mail Driver
                    </label>
                    <div class="col-sm-8">
                        <select name="driver" id="driver" required class="form-control c-select" onchange="driverChange()">
                            <option value="smtp" {{ $EmailSetting->driver == 'smtp' ? 'selected' : '' }}>SMTP (Recommended)</option>
                            <option value="mail" {{ $EmailSetting->driver == 'mail' ? 'selected' : '' }}>Mail</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row smtp">
                    <label for="host"
                           class="col-sm-4 form-control-label">Mail Host</label>
                    <div class="col-sm-8">
                        {!! Form::text('host', $EmailSetting->host, array('placeholder' => '','class' => 'form-control','id'=>'host')) !!}
                    </div>
                </div>

                <div class="form-group row smtp">
                    <label for="port"
                           class="col-sm-4 form-control-label">Mail Port</label>
                    <div class="col-sm-8">
                        {!! Form::text('port',$EmailSetting->port, array('placeholder' => '','class' => 'form-control','id'=>'port')) !!}
                    </div>
                </div>

                <div class="form-group row smtp">
                    <label for="username"
                           class="col-sm-4 form-control-label">Mail Username</label>
                    <div class="col-sm-8">
                        {!! Form::text('username',$EmailSetting->username, array('placeholder' => '','class' => 'form-control','id'=>'username')) !!}
                    </div>
                </div>

                <div class="form-group row smtp">
                    <label for="password"
                           class="col-sm-4 form-control-label">Mail Password</label>
                    <div class="col-sm-8">
                        {!! Form::text('password',$EmailSetting->password, array('placeholder' => '','class' => 'form-control','id'=>'password')) !!}
                    </div>
                </div>

                <div class="form-group row smtp">
                    <label for="encryption"
                           class="col-sm-4 form-control-label">Mail Encryption
                    </label>
                    <div class="col-sm-8">
                        <select name="encryption" id="encryption" required class="form-control c-select">
                            <option value="">None</option>
                            <option value="ssl" {{ $EmailSetting->encryption == 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="tls" {{ $EmailSetting->encryption == 'tls' ? 'selected' : '' }}>TLS</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="from_email"
                           class="col-sm-4 form-control-label">From Email
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('from_email',$EmailSetting->from_email, array('placeholder' => '','class' => 'form-control','id'=>'from_email','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="add_status1"
                           class="col-sm-4 form-control-label">Status</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <label class="ui-check ui-check-md" style="margin-bottom: 5px;">
                                {!! Form::radio('active_status','1',($EmailSetting->status==1) ? true : false, array('id' => 'active_status1','class'=>'has-value')) !!}
                                <i class="dark-white"></i>
                                {{ __('backend.active') }}
                            </label>
                            &nbsp; &nbsp;
                            <label class="ui-check ui-check-md" style="margin-bottom: 5px;">
                                {!! Form::radio('active_status','0',($EmailSetting->status==0) ? true : false, array('id' => 'active_status2','class'=>'has-value')) !!}
                                <i class="dark-white"></i>
                                {{ __('backend.notActive') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="button" class="btn btn-primary" onclick="beforeSubmit();return false;"><i class="material-icons">
                                &#xe31b;</i> {!! __('backend.update') !!}</button>
                        <a href="{{route("email")}}"
                           class="btn btn-default"><i class="material-icons">
                                &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
                    </div>
                </div>

                {{Form::close()}}
                </div>
            </div>
        </div>
    </div>
<script>
function driverChange()
{
    var driver = $("#driver").val();
    if(driver == 'smtp')
    {
        $(".smtp").show();
    }
    else
    {
        $(".smtp").hide();
        $("#host").val('');
        $("#port").val('');
        $("#username").val('');
        $("#password").val('');
        $("#encryption").val('');
    }
}
driverChange();
function beforeSubmit()
{
    var driver = $("#driver").val();
    if(driver == 'smtp')
    {
        if($("#host").val() == '')
        {
            alert("Please enter Mail Host");
            return false;
        }
        if($("#port").val() == '')
        {
            alert("Please enter Mail Port");
            return false;
        }
        if($("#username").val() == '')
        {
            alert("Please enter Username");
            return false;
        }
        if($("#password").val() == '')
        {
            alert("Please enter Password");
            return false;
        }
    }
    $("#email-form").submit();
}
</script>
@endsection
