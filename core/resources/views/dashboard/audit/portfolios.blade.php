@extends('dashboard.layouts.master')
@section('title', 'Portfolios')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>Portfolios</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>
        <div class="card-box-shadow">
            @if(count($Portfolios) > 0)
                <div class="table-responsive">
                    <table class="table m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Teams</th>
                            <th>Source</th>
                            <th>Segment</th>
                            <th>Accounts</th>
                            <th>Actioned</th>
                            <th>Audited</th>
                            <th>Auditors</th>
                            <th>Last Actioned On</th>
                            <th>View</th>
                            @if(@Auth::user()->permissionsGroup->name == 'Admin')
                            <th>Auditors</th>
                            <th>Action</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($Portfolios as $Portfolio)
                            <tr>
                                <td>{{$Portfolio->name}} ({{$Portfolio->campaign_id}})</td>
                                <td>{{date('Y-m-d',strtotime($Portfolio->created_at))}}</td>
                                <td>{{$Portfolio->expiry_date}}</td>
                                <td>{{ App\Models\Team::find($Portfolio->team_id)->name }}</td>
                                <td>{{App\Models\Source::find($Portfolio->source_id)->name}}</td>
                                <td>{{App\Models\Segment::find($Portfolio->segment_id)->name}}</td>
                                <td>{{$Portfolio->total_leads}}</td>
                                <td>{{$Portfolio->actioned}}</td>
                                <td>{{$Portfolio->audited}}</td>
                                <td>{{!empty($Portfolio->auditor_ids) ? count(explode(',',$Portfolio->auditor_ids)) : 0}}</td>
                                <td>{{$Portfolio->last_actioned}}</td>
                                <td>
                                    <a href="{{route('campaignsView',$Portfolio->id)}}" class="btn btn-primary waves-effect">Start</a>
                                    <a href="{{route('auditReport').'?campaign_id='.$Portfolio->campaign_id}}" class="btn btn-primary waves-effect">View</a>
                                </td>
                                @if(@Auth::user()->permissionsGroup->name == 'Admin')
                                <td>
                                    <select class="select2 browser-default" multiple name="auditor_{{$Portfolio->campaign_id}}[]" id="auditor_{{$Portfolio->campaign_id}}">
                                        <?php $assignedAuditors = explode(',',$Portfolio->auditor_ids); ?>
                                        @foreach($Auditors as $auditor)
                                        <option value="{{$auditor->id}}" {{in_array($auditor->id, $assignedAuditors) ? 'selected' : ''}}>{{$auditor->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <button class="waves-effect btn btn-primary" onclick="assignAuditors('{{$Portfolio->campaign_id}}')">Assign</button>
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
                            <div id="viewMembers" class="modal fade" data-backdrop="true">
                                <div class="modal-dialog" id="animate">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">View Team Members</h5>
                                        </div>
                                        <div class="modal-body text-center p-lg">
                                            
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn dark-white p-x-md"
                                                    data-dismiss="modal">Close</button>
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div>
                            </div>
                            <!-- / .modal -->
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
function assignAuditors(id)
{
    if($("#auditor_"+id).val().length == 0)
    {
        alert("Please select Auditors");
        return false;
    }
    
    var url = '{{ route('auditAssignAuditors') }}';
    var token = "{{ csrf_token() }}";
    $.ajax({
        url: url,
        type: "POST",
        data: {'_token': token,'campaign_id':id,auditors:$("#auditor_"+id).val()},
        success: function (response) {
            alert(response.message);
            location.reload();
        }
    });
}
        
        function getTeamMembers(id)
        {
            var url = "{{ route('getTeamMembers', [':id']) }}";
            url = url.replace(':id', id);
            $('.modal-body').html('');
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function(html){
                    $('.modal-body').html(html);
                }
            });
        }
    </script>
@endpush
