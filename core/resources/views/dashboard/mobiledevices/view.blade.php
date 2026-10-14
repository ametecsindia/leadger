@extends('dashboard.layouts.master')
@section('title', 'Leads')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>Leads</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>

            @if($Leads->total() >0)
                @if(@Auth::user()->permissionsGroup->webmaster_status)
                    <div class="row p-a pull-right" style="margin-top: -70px;">
                        <div class="col-sm-12">
                            <a class="btn btn-fw primary" href="{{route("leadsCreate",["id"=>$id])}}">
                                <i class="material-icons">&#xe7fe;</i>
                                &nbsp; Add a new Lead
                            </a>
                        </div>
                    </div>
                @endif
            @endif
            @if($Leads->total() == 0)
                <div class="row p-a">
                    <div class="col-sm-12">
                        <div class=" p-a text-center ">
                            {{ __('backend.noData') }}
                            <br>
                            @if(@Auth::user()->permissionsGroup->webmaster_status)
                                <br>
                                <a class="btn btn-fw primary" href="{{route("leadsCreate",["id"=>$id])}}">
                                    <i class="material-icons">&#xe7fe;</i>
                                    &nbsp; Add a new Lead
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($Leads->total() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Lead ID</th>
                            <th>{{ __('backend.fullName') }}</th>
                            <th>Designation</th>
                            <th>Phone number</th>
                            <th>Phone number 2</th>
                            <th>Company</th>
                            <th>Description</th>
                            <th>Country</th>
                            <th>Pin Code</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Website</th>
                            <th>Lead value</th>
                            <th>Position</th>
                            <th>Tags</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($Leads as $Lead)
                            <tr>
                                <td><a onclick="leadDetail('{!! $Lead->id !!}')" class='text-primary'>{!! $Lead->id !!}</a></td>
                                <td class="h6">
                                    {!! $Lead->name   !!}
                                </td>
                                <td>{!! $Lead->designation !!}</td>
                                <td>{!! $Lead->phonenumber !!}</td>
                                <td>{!! $Lead->phonenumber_2 !!}</td>
                                <td>{!! $Lead->company !!}</td>
                                <td>{!! $Lead->description !!}</td>
                                <td>{!! $Lead->country !!}</td>
                                <td>{!! $Lead->pin_code !!}</td>
                                <td>{!! $Lead->city !!}</td>
                                <td>{!! $Lead->state !!}</td>
                                <td>{!! $Lead->address !!}</td>
                                <td>{!! $Lead->email !!}</td>
                                <td>{!! $Lead->website !!}</td>
                                <td>{!! $Lead->lead_value !!}</td>
                                <td>{!! $Lead->position !!}</td>
                                <td>{!! $Lead->tags !!}</td>

                            </tr>
                        @endforeach

                        </tbody>
                    </table>

                </div>
            @endif
            <!-- .modal -->
            <div id="lead-detail" class="modal fade" data-backdrop="true">
                <div class="modal-dialog modal-lg" id="animate">
                    <div class="modal-content">
                    </div><!-- /.modal-content -->
                </div>
            </div>
        </div>
    </div>
@endsection
@push("after-scripts")
    <script type="text/javascript">
        function leadDetail(id) {
            var url = "{{ route('leadDetail', [':id']) }}";
            url = url.replace(':id', id);
            $('.modal-content').html('');
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function(html){
                    $('.modal-content').html(html);
                    $("#lead-detail").modal("show");
                }
            });
        }
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
