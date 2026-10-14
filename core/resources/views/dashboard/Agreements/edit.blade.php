@extends('dashboard.layouts.master')
@section('title', 'Edit Agreement')
@section('content')

<div class="padding">
    <div class="box">
        <div class="box-header dker">
            <h3>{{ __('Edit Agreement') }}</h3>
            <small>
                <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                <a href="#">{{ __('Agreements') }}</a> /
                <a href="#">{{ __('Edit Agreement') }}</a>
            </small>
        </div>
        <div class="box-tool">
            <ul class="nav">
                <li class="nav-item inline">
                    <a class="nav-link" href="{{route("users")}}">
                        <i class="material-icons md-18">×</i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-box-shadow">
        <form action="{{ route('agreements.update', $agreement->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="advertiser_name" class="form-label">Advertiser Name</label>
                        <input type="text" name="advertiser_name" id="advertiser_name" class="form-control" value="{{ old('advertiser_name', $agreement->advertiser_name) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="support_person_name" class="form-label">Support Person Name</label>
                        <input type="text" name="support_person_name" id="support_person_name" class="form-control" value="{{ old('support_person_name', $agreement->support_person_name) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="calls_per_week" class="form-label">Calls Per Week</label>
                        <input type="number" name="calls_per_week" id="calls_per_week" class="form-control" value="{{ old('calls_per_week', $agreement->calls_per_week) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="calls_per_month" class="form-label">Calls Per Month</label>
                        <input type="number" name="calls_per_month" id="calls_per_month" class="form-control" value="{{ old('calls_per_month', $agreement->calls_per_month) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="preferred_calling_hours" class="form-label">Preferred Calling Hours</label>
                        <input type="text" name="preferred_calling_hours" id="preferred_calling_hours" class="form-control" value="{{ old('preferred_calling_hours', $agreement->preferred_calling_hours) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="contact_details" class="form-label">Contact Details</label>
                        <input type="text" name="contact_details" id="contact_details" class="form-control" value="{{ old('contact_details', $agreement->contact_details) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', $agreement->start_date) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', $agreement->end_date) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="script_file" class="form-label">Script File (Doc, txt, pdf) </label>
                        <input type="file" name="script_file" id="script_file" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="uploaded_file" class="form-label">Uploaded File (Csv, Excel)</label>
                        <input type="file" name="uploaded_file" id="uploaded_file" class="form-control">
                    </div>
                </div>
                <div class="form-group row m-t-md">
                <div class="offset-sm-2 col-sm-10">
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success">Update Agreement</button>
                </div>
</div>
</div>
            </div>
        </form>
    </div>
    </div>
</div>

@endsection
