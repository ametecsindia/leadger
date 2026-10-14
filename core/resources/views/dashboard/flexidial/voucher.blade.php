@extends('dashboard.layouts.master')
@section('title', 'Flexidial Voucher')
@section('content')
    <div class="padding">
        <div class="box">

            <div class="box-header dker">
                <h3>Flexidial Voucher</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a>
                </small>
            </div>
            <div class="card-box-shadow">
            <div class="manual-filters-section">
             
                <div class="row">
                    {{Form::open(['route'=>'admin.flexidialer.flexi_dialer_voucher_add','method'=>'post'])}}
                    <div class="col-md-3 col-xs-6 m-b-5p">
                        <input placeholder="Voucher Code" class="form-control" id="voucher_code" autocomplete="off" name="voucher_code" type="text" required>
                    </div>
                    <div class="col-md-3 col-xs-6 m-b-5p">
                        <button class="btn btn-fw primary" id="search-btn" type="submit"><i class="fa fa-plus"></i> &nbsp; Add Flexi Voucher</button>
                    </div>
                    {{Form::close()}}
                </div>
            </div>
            
        
            @if(count($flexiDialerVouchers) > 0)
                <div class="table-responsive">
                    <table class="table m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>Voucher</th>
                                        <th>Type</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Click2call Channels</th>
                                        <th>WebRTC Channels</th>
                                        <th>Added By</th>
                        </tr>
                        </thead>
                        <tbody>
                         <?php 
                                    foreach($flexiDialerVouchers as $row) { 
                                        ?>
                                    <tr>
                                        <td>{{$row->voucher_code}}</td>
                                        <td>{{$row->type}}</td>
                                        <td>{{$row->expiry}}</td>
                                        <td>{{$row->status}}</td>
                                        <td>{{$row->channels}}</td> 
                                        <td>{{$row->webrtc_channels}}</td>                                       
                                        <td>
                                            {{ $row->user_name}} 
                                        </td>
                                        <!-- <td> -->
                                            <!-- <a href="javascript:void(0);" onclick="editModal('{{ $row->id }}')" class="btn-flat modal-trigger tooltipped" data-position="bottom" data-tooltip="View" data-target="addEditModal"><i class="material-icons">visibility</i></a> -->
                                            <!-- <a href="javascript:void(0);" onclick="editModal('{{ $row->id }}')" class="btn-flat modal-trigger tooltipped" data-position="bottom" data-tooltip="Edit" data-target="addEditModal"><i class="material-icons">edit</i></a> -->
                                            <!--<button onclick="deleteModal({{ $row->id }})" class="btn-flat tooltipped" data-position="bottom" data-tooltip="Delete"><i class="material-icons" aria-hidden="true">delete</i></button>-->
                                        <!-- </td> -->
                                    </tr>
                                    <?php } ?>

                        </tbody>
                    </table>
                </div>
                
                @endif



@endsection
@push("after-scripts")
<script type="text/javascript">
   

</script>
@endpush

