@extends('dashboard.layouts.master')
@section('title', 'Email to Lead Setting')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>Email to Lead Setting</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Email to ticket Setting</a>
                </small>
            </div>
            <div class="box-tool">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("imap")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
                <div class="mini-container">
                {{Form::open(['route'=>['imapUpdate', $ImapSetting->id],'method'=>'POST', 'files' => true, 'id'=> 'imap_form'])}}

                <div class="form-group row">
                    <label for="encryption"
                           class="col-sm-4 form-control-label">User</label>
                    <div class="col-sm-8">
                        <select name="user_id" id="user_id" class="form-control c-select">
                            @foreach($Users as $User)
                            <option value="{!! $User->id !!}" {!! $ImapSetting->user_id == $User->id ? 'selected' : '' !!}>{!! $User->name !!}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4 form-control-label">IMAP Username
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('username',$ImapSetting->username, array('placeholder' => '','class' => 'form-control','id'=>'username','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4 form-control-label">IMAP Host
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('host',$ImapSetting->host, array('placeholder' => '','class' => 'form-control','id'=>'host','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4 form-control-label">IMAP Port
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('port',$ImapSetting->port, array('placeholder' => '','class' => 'form-control','id'=>'port','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4 form-control-label">Password
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('password',$ImapSetting->password, array('placeholder' => '','class' => 'form-control','id'=>'password','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="encryption"
                           class="col-sm-4 form-control-label">Encryption</label>
                    <div class="col-sm-8">
                        <select name="encryption" id="encryption" class="form-control c-select">
                            <option value="">No Encryption</option>
                            <option value="tls" {{ $ImapSetting->encryption == 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ $ImapSetting->encryption == 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="folder" class="col-sm-4 form-control-label">Folder</label>
                    <div class="col-sm-8">
                        <select name="folder" id="folder" required class="form-control c-select">
                            <?php echo $folderOptions; ?>
                        </select>
                        <a class="btn btn-fw primary m-t-1" style="color:#fff;" onclick="fetchFolders()">Fetch Folders</a>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4 form-control-label">Whitelist From Emails
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('whitelist_emails',$ImapSetting->whitelist_emails, array('placeholder' => 'Enter , separated emails','class' => 'form-control','id'=>'password','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="add_status1"
                           class="col-sm-4 form-control-label">Status</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <label class="ui-check ui-check-md" style="margin-bottom: 5px;">
                                
                                {!! Form::radio('active_status','1',(empty($ImapSetting->status) || $ImapSetting->status==1) ? true : false, array('id' => 'active_status1','class'=>'has-value')) !!}
                                <i class="dark-white"></i>
                                {{ __('backend.active') }}
                            </label>
                            &nbsp; &nbsp;
                            <label class="ui-check ui-check-md" style="margin-bottom: 5px;">
                                {!! Form::radio('active_status','0',!(empty($ImapSetting->status) || $ImapSetting->status==1) ? true : false, array('id' => 'active_status2','class'=>'has-value')) !!}
                                <i class="dark-white"></i>
                                {{ __('backend.notActive') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary"><i class="material-icons"> &#xe31b;</i> {!! __('backend.update') !!}</button>
                        <a href="{{ route('imap') }}" class="btn btn-default">{!! __('backend.cancel') !!}</a>
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
    function fetchFolders()
    {
        if($("#username").val() == '')
        {
            alert("Please enter Username");
            return false;
        }
        if($("#host").val() == '')
        {
            alert("Please enter Host");
            return false;
        }
        if($("#password").val() == '')
        {
            alert("Please enter Password");
            return false;
        }
        var url = "{{ route('imapFetchFolders') }}";
        $.ajax({
            type: "POST",
            url: url,
            data: $("#imap_form").serialize(),
            dataType: "html",
            success: function(html){
                if(html == "")
                    alert("Couldn't connect Server");
                else
                    $('#folder').html(html);
            }
        });
    }
</script>
@endpush