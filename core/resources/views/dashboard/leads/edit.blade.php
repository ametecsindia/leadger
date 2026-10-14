@extends('dashboard.layouts.master')
@section('title', 'Leads')
@section('content')
    <div class="padding add-new-lead-form">
        <div class="box">
            <div class="box-header dker">
                <h3>Edit Lead</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="">{{ __('backend.settings') }}</a> /
                    <a href="">Leads</a>
                </small>
            </div>
            <div class="box-tool sm-d-none">
                <ul class="nav">
                    <li class="nav-item inline">
                        <a class="nav-link" href="{{ !empty($CampaignId) ? route("campaignsView", ['id'=>$CampaignId]) : route('leads') }}">
                            <i class="material-icons md-18">×</i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-box-shadow">
                {{Form::open(['route'=>['leadsUpdate', $Lead->id],'method'=>'POST', 'files' => true ])}}
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="campaign_id" class="form-control-label">Campaign</label>
                        <select name="campaign_id" id="campaign_id" required class="form-control select2">
                            <option value="">- - Select Campaign - -</option>
                            @foreach($Campaigns as $singleCampaign)
                            <option value="{!! $singleCampaign->id !!}" {{ $singleCampaign->id == $CampaignId ? 'selected' : '' }}>{!! $singleCampaign->name !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="product_service" class="form-control-label">Products & Services</label>
                        <select name="product_service[]" id="product_service" required multiple  class="form-control c-select">
                            <?php $LeadProducts = !empty($Lead->product_service) ? explode(',',$Lead->product_service) : []; ?>
                            
                            @foreach($ProductServices as $ProductService)
                            <option value="{!! $ProductService->id !!}" {!! in_array($ProductService->id,$LeadProducts) ? 'selected':'' !!}>{!! $ProductService->name !!}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="forms-heading-inner">Customer Details</h4>
                            </div>
                        </div>
                        <div class="form-group row">                            
                            <label for="name" class="col-md-3 form-control-label">Customer Name</label>
                            <div class="col-md-9">
                                {!! Form::text('name',$Lead->name, array('placeholder' => '','class' => 'form-control','id'=>'name','required'=>'')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="designation" class="col-md-3 form-control-label">Designation</label>
                            <div class="col-md-9">
                                {!! Form::text('designation',$Lead->designation, array('placeholder' => '','class' => 'form-control','id'=>'designation')) !!}
                            </div>
                        </div>
                        <div class="form-group row">                            
                            <label for="phonenumber" class="col-md-3 form-control-label">Phone Number</label>
                            <div class="col-md-9">
                                {!! Form::number('phonenumber',$Lead->phonenumber, array('placeholder' => '','class' => 'form-control','id'=>'phonenumber','required'=>'', 'min' => 6000000000)) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="phonenumber_2" class="col-md-3 form-control-label">Phone Number 2</label>
                            <div class="col-md-9">
                                {!! Form::number('phonenumber_2',$Lead->phonenumber_2, array('placeholder' => '','class' => 'form-control','id'=>'phonenumber_2')) !!}
                            </div>
                        </div>
                        <div class="form-group row">                        
                            <label for="email" class="col-md-3 form-control-label">Email</label>
                            <div class="col-md-9">
                                {!! Form::email('email',$Lead->email, array('placeholder' => '','class' => 'form-control','id'=>'email')) !!}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="forms-heading-inner">Lead Notes</h4>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="lead_value" class="col-md-3 form-control-label">Business Value</label>
                            <div class="col-md-9">
                                {!! Form::number('lead_value',$Lead->lead_value, array('placeholder' => '','class' => 'form-control','id'=>'website')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="position" class="col-md-3 form-control-label">Position</label>
                            <div class="col-md-9">
                                {!! Form::text('position',$Lead->position, array('placeholder' => '','class' => 'form-control','id'=>'position')) !!}
                            </div>
                        </div>
                        <div class="form-group row">                            
                            <label for="description" class="col-md-3 form-control-label">Description</label>
                            <div class="col-md-9">
                                {!! Form::textarea('description',$Lead->description, array('placeholder' => '','class' => 'form-control','id'=>'description', 'rows' => 3)) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="forms-heading-inner">Company Details</h4>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="company" class="col-md-3 form-control-label">Company Name</label>
                            <div class="col-md-9">
                                {!! Form::text('company',$Lead->company, array('placeholder' => '','class' => 'form-control','id'=>'company')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="address" class="col-md-3 form-control-label">Address</label>
                            <div class="col-md-9">
                                {!! Form::textarea('address',$Lead->address, array('placeholder' => '','class' => 'form-control','id'=>'address', 'rows'=>2)) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="city" class="col-md-3 form-control-label">City</label>
                            <div class="col-md-9">
                                {!! Form::text('city',$Lead->city, array('placeholder' => '','class' => 'form-control','id'=>'city')) !!}
                            </div>
                        </div>
                        <div class="form-group row">                            
                            <label for="state" class="col-md-3 form-control-label">State</label>
                            <div class="col-md-9">
                                {!! Form::text('state',$Lead->state, array('placeholder' => '','class' => 'form-control','id'=>'state')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="country" class="col-md-3 form-control-label">Country</label>
                            <div class="col-md-9">
                                {!! Form::text('country',$Lead->country, array('placeholder' => '','class' => 'form-control','id'=>'country')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="pin_code" class="col-md-3 form-control-label">Pin Code</label>
                            <div class="col-md-9">
                                {!! Form::number('pin_code',$Lead->pin_code, array('placeholder' => '','class' => 'form-control','id'=>'pin_code')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="website" class="col-md-3 form-control-label">Website</label>
                            <div class="col-md-9">
                                {!! Form::text('website',$Lead->website, array('placeholder' => '','class' => 'form-control','id'=>'website')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="latitude" class="col-md-3 form-control-label">Latitude</label>
                            <div class="col-md-9">
                                {!! Form::text('latitude',$Lead->latitude, array('placeholder' => '','class' => 'form-control','id'=>'latitude')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="longitude" class="col-md-3 form-control-label">Longitude</label>
                            <div class="col-md-9">
                                {!! Form::text('longitude',$Lead->longitude, array('placeholder' => '','class' => 'form-control','id'=>'longitude')) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="faq" class="col-md-3 form-control-label">Public Leads</label>
                            <div class="col-md-9">
                                {!! Form::checkbox('is_public','1',$Lead->is_public ? true : false, array('id' => 'is_public')) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-sm-12 text-center">
                        <div class="m-t">
                            <button type="submit" class="btn btn-primary">{!! __('backend.update') !!}</button>
                            <a href="{{ !empty($CampaignId) ? route("campaignsView", ['id'=>$CampaignId]) : route('leads') }}" class="btn btn-default">{!! __('backend.cancel') !!}</a>
                        </div>
                    </div>
                </div>
                {{Form::close()}}
            </div>
        </div>
    </div>
@endsection
