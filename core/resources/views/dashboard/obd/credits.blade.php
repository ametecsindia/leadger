@extends('dashboard.layouts.master')
@section('title', 'OBD Credits')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>OBD Credits</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>
            <div class="card-box-shadow">
            <div class="manual-filters-section">
                <div class="row">
                    {{Form::open(['route'=>'obdAddCredits','method'=>'post'])}}
                    <div class="col-md-3 col-xs-6 m-b-5p">
                        <input placeholder="Voucher Code" class="form-control" id="voucher_code" autocomplete="off" name="voucher_code" type="text" required>
                    </div>
                    <div class="col-md-3 col-xs-6 m-b-5p">
                        <button class="btn btn-fw primary" id="search-btn" type="submit"><i class="fa fa-plus"></i> &nbsp; Add Credits</button>
                    </div>
                    <div class="col-md-3 col-xs-6 m-b-5p">
                    </div>
                    <div class="col-md-3 col-xs-6 m-b-5p">
                        <span>Credits: {!! $Setting[0]->obd_credits !!}</span>
                    </div>
                    {{Form::close()}}
                </div>
            </div>

            @if(count($CreditHistory) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Date</th>
                            <th>Voucher Code</th>
                            <th>Credits</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($CreditHistory as $Row)
                            <tr>
                                <td>{!! $Row->created_at !!}</td>
                                <td>{!! $Row->voucher_code !!}</td>
                                <td>{!! $Row->credits !!}</td>
                            </tr>
                        @endforeach

                        </tbody>
                    </table>

                </div>
                <footer class="dker p-a">
                    
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
