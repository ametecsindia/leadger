@extends('dashboard.layouts.master')
@section('title', 'Notifications')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>Notifications</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>
        <div class="card-box-shadow">
            @if(count($Notifications) == 0)
                <div class="row">
                    <div class="col-sm-12">
                        <div class="text-center ">
                            {{ __('backend.noData') }}
                        </div>
                    </div>
                </div>
            @endif

            @if(count($Notifications) > 0)
                <div class="table-responsive">
                    <table class="table m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Id</th>
                            <th>Lead Id's</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($Notifications as $Notification)
                            <tr>
                                <td>
                                    <a href='{!! route('notificationView',$Notification->id) !!}' target="_blank" class='text-primary'>{!! $Notification->id !!}</a>
                                </td>

                                <td>
                                    {!! collect(explode(',', (string)$Notification->lead_ids))->map(fn ($l) => Helper::leadIdLink($l))->filter(fn ($l) => $l !== '')->implode(', ') !!}
                                </td>
                                <td>
                                    {!! $Notification->created_at   !!}
                                </td>
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
