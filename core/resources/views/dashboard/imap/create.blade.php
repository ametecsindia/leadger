@extends('dashboard.layouts.master')
@section('title', 'Email to Lead Setting')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3><i class="material-icons">&#xe3c9;</i> Email to Lead Setting</h3>
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
                {{Form::open(['route'=>['imapStore'],'method'=>'POST', 'files' => true, 'id'=> 'imap_form'])}}

                <div class="form-group row">
                    <label for="encryption"
                           class="col-sm-4">User</label>
                    <div class="col-sm-8">
                        <select name="user_id" id="user_id" class="form-control c-select">
                            <option value="">-- Select User --</option>
                            @foreach($Users as $User)
                            <option value="{!! $User->id !!}">{!! $User->name !!}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">IMAP Username
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('username','', array('placeholder' => '','class' => 'form-control','id'=>'username','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">IMAP Host
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('host','', array('placeholder' => '','class' => 'form-control','id'=>'host','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">IMAP Port
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('port','', array('placeholder' => '','class' => 'form-control','id'=>'port','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">Password
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('password','', array('placeholder' => '','class' => 'form-control','id'=>'password','required'=>'')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="encryption"
                           class="col-sm-4">Encryption</label>
                    <div class="col-sm-8">
                        <select name="encryption" id="encryption" class="form-control c-select">
                            <option value="">No Encryption</option>
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="folder" class="col-sm-4">Folder</label>
                    <div class="col-sm-8">
                        <select name="folder" id="folder" required class="form-control c-select">
                            <?php echo $folderOptions; ?>
                        </select>
                        <a class="btn btn-fw primary btn-sm" onclick="fetchFolders()" style="color:#fff;margin-top: 10px;margin-bottom: 10px;font-weight:300;">Fetch Folders</a>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name"
                           class="col-sm-4">Whitelist From Emails
                    </label>
                    <div class="col-sm-8">
                        {!! Form::text('whitelist_emails','', array('placeholder' => 'Enter , separated emails','class' => 'form-control','id'=>'password','required'=>'')) !!}
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
                        <button type="submit" class="btn btn-primary"><i class="material-icons"> &#xe31b;</i> {!! __('backend.update') !!}</button>
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