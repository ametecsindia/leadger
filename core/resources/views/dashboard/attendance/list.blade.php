@extends('dashboard.layouts.master')
@section('title', 'Punch')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>Punch</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>

            @if(count($res) == 0)
                <div class="row p-a">
                    <div class="col-sm-12">
                        <div class=" p-a text-center ">
                            {{ __('backend.noData') }}
                        </div>
                    </div>
                </div>
            @endif

            @if(count($res) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Date</th>
                            <th>Login</th>
                            <th>Logout</th>
                            <th>Login Address</th>
                            <th>Logout Address</th>
                            <th>Login LatLong</th>
                            <th>Logout LatLong</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($res as $row)
                            <tr>
                                <td class="h6">
                                    {!! date('Y-m-d',strtotime($row->created_at))   !!}
                                </td>

                                <td>{!! date('h:i:s a',strtotime($row->start_at)) !!}</td>
                                <td>{!! $row->end_at != '' ? date('h:i:s a',strtotime($row->end_at)) : '' !!}</td>
                                <td>{!! $row->address !!}</td>
                                <td>{!! $row->logout_address !!}</td>
                                <td>{!! $row->lat !!} : {!! $row->long !!}</td>
                                <td>{!! $row->logout_lat !!} : {!! $row->logout_long !!}</td>
                            </tr>
                        @endforeach

                        </tbody>
                    </table>

                </div>
                <footer class="dker p-a">
                    <div class="row">
                        <div class="col-sm-3 hidden-xs">
                        </div>
                    </div>
                </footer>
            @endif
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
