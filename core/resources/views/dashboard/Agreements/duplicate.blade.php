@extends('dashboard.layouts.master')
@section('title', 'Duplicate Agreement')
@section('content')

<div class="padding">
    <div class="box">
        <div class="box-header dker">
            <h3>{{ __('Duplicate Agreement') }}</h3>
            <small>
                <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                <a href="#">{{ __('Agreements') }}</a> /
                <a href="#">{{ __('Duplicate Agreement') }}</a>
            </small>
        </div>

        <div class="card-box-shadow">
            <form action="{{ route('agreements.duplicate.store', $agreement->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Advertiser Name</label>
                        <input type="text" name="advertiser_name" class="form-control" value="{{ $agreement->advertiser_name }}" >
                    </div>

                    <div class="form-group col-md-6">
                        <label>Calls Per Week</label>
                        <input type="number" name="calls_per_week" class="form-control" value="{{ $agreement->calls_per_week }}" >
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Calls Per Month</label>
                        <input type="number" name="calls_per_month" class="form-control" value="{{ $agreement->calls_per_month }}" >
                    </div>

                    <div class="form-group col-md-6">
                        <label>Preferred Calling Hours</label>
                        <input type="text" name="preferred_calling_hours" class="form-control" value="{{ $agreement->preferred_calling_hours }}" >
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Support Person Name</label>
                        <input type="text" name="support_person_name" class="form-control" value="{{ $agreement->support_person_name }}" >
                    </div>

                    <div class="form-group col-md-6">
                        <label>Contact Details</label>
                        <input type="text" name="contact_details" class="form-control" value="{{ $agreement->contact_details }}" >
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>

                    <div class="form-group col-md-6">
                        <label>End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Script File (Doc, txt, pdf) <span class="text-danger">*</span></label>
                        <input type="file" name="script_file" class="form-control" >
                    </div>

                    <div class="form-group col-md-6">
                        <label>Uploaded File (Csv, Excel) <span class="text-danger">*</span></label>
                        <input type="file" name="uploaded_file" class="form-control">
                    </div>
                </div>
                
                <div class="form-group col-md-6">
                    <input type="checkbox" name="agree_terms" class="form-check-input" id="agreeTerms"  required>
                    <label class="form-check-label" for="agreeTerms">I agree to the terms</label>
                </div>
                <div class="row">
                <button type="submit" class="btn btn-primary">Duplicate Agreement</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
