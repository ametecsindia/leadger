@extends('dashboard.layouts.master')
@section('title', 'Announcement')
@section('content')
    <div class="padding add-product-service-form">
        <div class="box">
            <div class="box-header dker">
                <h3>Announcement</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Announcement</a>
                </small>
            </div>
            <div class="box-tool sm-d-none">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{route("createAnnouncement")}}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="box-body">
                <div class="mini-container">
                {{Form::open(['route'=>['sendAnnouncement'],'method'=>'POST', 'files' => false ])}}

                <div class="form-group row">
                    <label for="seq_order" class="col-sm-4 form-control-label">Team</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <select name="team_id[]" id="team_id" required multiple onchange='getTeamMembers()'
                                    class="form-control c-select">
                                @foreach ($Teams as $Team)
                                    <option value="{{ $Team->id }}">{{ $Team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="seq_order"
                           class="col-sm-4 form-control-label">Team Members</label>
                    <div class="col-sm-8">
                        <div class="radio">
                            <select name="team_members[]" id="team_members" required multiple
                                    class="form-control c-select">
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                           class="col-sm-4 form-control-label">Announcement
                    </label>
                    <div class="col-sm-8">
                        {!! Form::textarea('announcement','', array('placeholder' => '','class' => 'form-control','id'=>'announcement', 'required')) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <div class="offset-sm-4 col-sm-8">
                        <button type="submit" class="btn btn-primary"><i class="material-icons">
                                &#xe31b;</i> Send Announcement</button>
                        <a href="{{route("createAnnouncement")}}"
                           class="btn btn-default"><i class="material-icons">
                                &#xe5cd;</i> {!! __('backend.cancel') !!}</a>
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
    function getTeamMembers()
    {
        var team = $("#team_id").val();
        $("#team_members").html('');
        $("#team_members").select2();
        if(team.length == 0)
        {
            alert('Select Team');
            return false;
        }
        var url = "{{ route('notificationsGetTeamMembers') }}";
        $.ajax({
            type: "POST",
            url: url,
            data: {_token:"{{csrf_token()}}", team: team},
            dataType: "html",
            success: function(html){
                $("#team_members").html(html);
                $("#team_members").select2();
            }
        });
        
    }
</script>
@endpush
