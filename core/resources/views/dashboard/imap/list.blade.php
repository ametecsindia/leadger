@extends('dashboard.layouts.master')
@section('title', 'Email Settings')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>Email Settings</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>
        <div class="card-box-shadow">
            @if($ImapSettings->total() >0)
                @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                    <div class="row pull-right">
                        <div class="col-sm-12">
                            <a class="btn btn-fw m-b-1 primary" href="{{route("imapCreate")}}">
                                <i class="material-icons">&#xe7fe;</i>
                                &nbsp; Add a new Email Setting
                            </a>
                        </div>
                    </div>
                @endif
            @endif
            @if($ImapSettings->total() == 0)
                <div class="row">
                    <div class="col-sm-12">
                        <div class=" p-a text-center ">
                            {{ __('backend.noData') }}
                            <br>
                            @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                                <br>
                                <a class="btn btn-fw primary" href="{{route("imapCreate")}}">
                                    <i class="material-icons">&#xe7fe;</i>
                                    &nbsp; Add a new Email Setting
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($ImapSettings->total() > 0)
                {{Form::open(['route'=>'usersUpdateAll','method'=>'post'])}}
                <div class="table-responsive">
                    <table class="table m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>User</th>
                            <th>Mail Host</th>
                            <th>Port</th>
                            <th>From Email</th>
                            <th>Status</th>
                            @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                            <th class="text-center" style="width:200px;">{{ __('backend.options') }}</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($ImapSettings as $ImapSetting)
                            <tr>
                                <td class="h6">
                                    {!! $ImapSetting->user_name   !!}
                                </td>

                                <td>{!! $ImapSetting->host !!}</td>
                                <td>{!! $ImapSetting->port !!}</td>
                                <td>{!! $ImapSetting->username !!}</td>
                                <td class="text-center">
                                    <i class="fa {{ ($ImapSetting->status==1) ? "fa-check text-success":"fa-times text-danger" }} inline"></i>
                                </td>
                                @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                                <td class="text-center">
                                    <div class="action-items">
                                        <a href="{{ route("imapEdit",["id"=>$ImapSetting->id]) }}"><small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Edit">&#xe3c9;</i> <!-- {{ __('backend.edit') }}--></small>
                                    </a>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @endforeach

                        </tbody>
                    </table>

                </div>
                <footer class="dker">
                    <div class="row">
                        <div class="col-sm-3 hidden-xs">
                        </div>

                        <div class="col-sm-3 text-center">
                            <small class="text-muted inline m-t-sm m-b-sm">{{ __('backend.showing') }} {{ $ImapSettings->firstItem() }}
                                -{{ $ImapSettings->lastItem() }} {{ __('backend.of') }}
                                <strong>{{ $ImapSettings->total()  }}</strong> {{ __('backend.records') }}</small>
                        </div>
                        <div class="col-sm-6 text-right text-center-xs">
                            {!! $ImapSettings->links() !!}
                        </div>
                    </div>
                </footer>
                {{Form::close()}}
            @endif
            </div>
        </div>
    </div>
@endsection
@push("after-scripts")
    <script type="text/javascript">
        $("#checkAll").click(function () {
            $('input:checkbox').not(this).prop('checked', this.checked);
        });
        $("#action").change(function () {
            if (this.value == "delete") {
                $("#submit_all").css("display", "none");
                $("#submit_show_msg").css("display", "inline-block");
            } else {
                $("#submit_all").css("display", "inline-block");
                $("#submit_show_msg").css("display", "none");
            }
        });
    </script>
@endpush
