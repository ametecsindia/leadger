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
            @if(count($Notification) == 0)
                <div class="row">
                    <div class="col-sm-12">
                        <div class="text-center ">
                            {{ __('backend.noData') }}
                        </div>
                    </div>
                </div>
            @endif

            @if(count($Notification) > 0)
                <div class="table-responsive">
                    <table class="table m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Lead Id</th>
                            <th>Name</th>
                            <th>Follow Up On</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($Notification as $Notification)
                            <tr>
                                <td>
                                    <a onclick="leadDetail('{!! $Notification->id !!}')" class='text-primary'>{!! $Notification->id !!}</a>
                                </td>

                                <td>
                                    {!! $Notification->name   !!}
                                </td>
                                <td>
                                    {!! $Notification->follow_up_on   !!}
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
            <div id="lead-detail" class="modal fade lead-details-modal" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-lg" id="animate">
                    <div class="modal-content lead-content">
                    </div><!-- /.modal-content -->
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
        function leadDetail(id) {
            var url = "{{ route('leadDetail', [':id']) }}";
            url = url.replace(':id', id);
            $('.lead-content').html('');
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function(html){
                    $('.lead-content').html(html);
                    $("#lead-detail").modal("show");
                }
            });
        }
    </script>
@endpush
