@extends('dashboard.layouts.master')
@section('title', 'Work Locations')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>Work Locations</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>
            <div class="card-box-shadow">
            @if($WorkLocations->total() >0)
                @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                    <div class="row pull-right">
                        <div class="col-sm-12">
                            <a class="btn btn-fw primary m-b-1" href="{{route("workLocationsCreate")}}">
                                <i class="material-icons">&#xe7fe;</i>
                                &nbsp; Add a new Work Location
                            </a>
                        </div>
                    </div>
                @endif
            @endif
            @if($WorkLocations->total() == 0)
                <div class="row">
                    <div class="col-sm-12">
                        <div class="text-center ">
                            {{ __('backend.noData') }}
                            <br>
                            @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                                <br>
                                <a class="btn btn-fw primary m-b-1" href="{{route("workLocationsCreate")}}">
                                    <i class="material-icons">&#xe7fe;</i>
                                    &nbsp; Add a new Work Location
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($WorkLocations->total() > 0)
                {{Form::open(['route'=>'usersUpdateAll','method'=>'post'])}}
                <div class="table-responsive">
                    <table class="table m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Work Location</th>
                            <th>Lat : Long</th>
                            <th>Radius (in Meters)</th>
                            @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                            <th class="text-center" style="width:200px;">{{ __('backend.options') }}</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($WorkLocations as $WorkLocation)
                            <tr>
                                <td>
                                    {!! $WorkLocation->name   !!}
                                </td>

                                <td>{!! $WorkLocation->lat !!} : {!! $WorkLocation->long !!}</td>
                                <td>{!! $WorkLocation->radius   !!}</td>
                                @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                                <td class="text-center">
                                    @if($WorkLocation->id != '1')
                                    <div class="action-items">
                                        <a href="{{ route("workLocationsEdit",["id"=>$WorkLocation->id]) }}">
                                            <small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Edit">&#xe3c9;</i> <!-- {{ __('backend.edit') }} -->
                                            </small>
                                        </a>
                                    </div>
                                    @endif
                                </td>
                                @endif
                            </tr>
                        @endforeach

                        </tbody>
                    </table>

                </div>
                <footer class="dker p-a">
                    <div class="row">
                        <div class="col-sm-3 hidden-xs">
                        </div>

                        <div class="col-sm-3 text-center">
                            <small class="text-muted inline m-t-sm m-b-sm">{{ __('backend.showing') }} {{ $WorkLocations->firstItem() }}
                                -{{ $WorkLocations->lastItem() }} {{ __('backend.of') }}
                                <strong>{{ $WorkLocations->total()  }}</strong> {{ __('backend.records') }}</small>
                        </div>
                        <div class="col-sm-6 text-right text-center-xs">
                            {!! $WorkLocations->links() !!}
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
