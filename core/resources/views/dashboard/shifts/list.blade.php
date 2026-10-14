@extends('dashboard.layouts.master')
@section('title', 'Shifts')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>Shifts</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>
            <div class="card-box-shadow">
            @if($Shifts->total() >0)
                @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                    <div class="row pull-right">
                        <div class="col-sm-12">
                            <a class="btn btn-fw primary m-b-1" href="{{route("shiftsCreate")}}">
                                <i class="material-icons">&#xe7fe;</i>
                                &nbsp; Add a new Shift
                            </a>
                        </div>
                    </div>
                @endif
            @endif
            @if($Shifts->total() == 0)
                <div class="row">
                    <div class="col-sm-12">
                        <div class="text-center ">
                            {{ __('backend.noData') }}
                            <br>
                            @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                                <br>
                                <a class="btn btn-fw primary m-b-1" href="{{route("shiftsCreate")}}">
                                    <i class="material-icons">&#xe7fe;</i>
                                    &nbsp; Add a new Shift
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($Shifts->total() > 0)
                <div class="table-responsive">
                    <table class="table m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Shifts Name</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                            <th class="text-center" style="width:200px;">{{ __('backend.options') }}</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($Shifts as $Shift)
                            <tr>
                                <td class="h6">
                                    {!! $Shift->name   !!}
                                </td>

                                <td>{!! $Shift->start_time !!}</td>
                                <td>{!! $Shift->end_time !!}</td>
                                @if(in_array(@Auth::user()->permissionsGroup->name,['Admin','Manager']))
                                <td class="text-center">
                                    <div class="action-items">
                                        <a href="{{ route("shiftsEdit",["id"=>$Shift->id]) }}"><small><i class="material-icons" data-toggle="tooltip" data-placement="bottom" title="Edit">&#xe3c9;</i> <!-- {{ __('backend.edit') }}--></small>
                                        </a>
                                    </div>
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
                            <!-- .modal -->
                            <div id="m-all" class="modal fade" data-backdrop="true">
                                <div class="modal-dialog" id="animate">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('backend.confirmation') }}</h5>
                                        </div>
                                        <div class="modal-body text-center p-lg">
                                            <p>
                                                {{ __('backend.confirmationDeleteMsg') }}
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn dark-white p-x-md"
                                                    data-dismiss="modal">{{ __('backend.no') }}</button>
                                            <button type="submit"
                                                    class="btn danger p-x-md">{{ __('backend.yes') }}</button>
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div>
                            </div>
                            <!-- / .modal -->
                        </div>

                        <div class="col-sm-3 text-center">
                            <small class="text-muted inline m-t-sm m-b-sm">{{ __('backend.showing') }} {{ $Shifts->firstItem() }}
                                -{{ $Shifts->lastItem() }} {{ __('backend.of') }}
                                <strong>{{ $Shifts->total()  }}</strong> {{ __('backend.records') }}</small>
                        </div>
                        <div class="col-sm-6 text-right text-center-xs">
                            {!! $Shifts->links() !!}
                        </div>
                    </div>
                </footer>
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
