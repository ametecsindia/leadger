@extends('dashboard.layouts.master')
@section('title', 'Agreements')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-file-contract"></i> {{ __('Agreements') }}</h1>
        <ol class="breadcrumb">
            <li><a href="{{ route('adminHome') }}"><i class="fas fa-tachometer-alt"></i> {{ __('Home') }}</a></li>
            <li class="active"><i class="fa fa-file-contract"></i> {{ __('Agreements') }}</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3>Add New Agreement</h3>
                    </div>
                    <div class="box-body">
                        <form action="{{ route('agreements.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Advertiser Name</label>
                                <input type="text" name="advertiser_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Calls Per Week</label>
                                <input type="number" name="calls_per_week" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Calls Per Month</label>
                                <input type="number" name="calls_per_month" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Support Person</label>
                                <input type="text" name="support_person_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Contact Details</label>
                                <input type="text" name="contact_details" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Agreement Document (PDF)</label>
                                <input type="file" name="document" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Script File</label>
                                <input type="file" name="script_file" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Uploaded File</label>
                                <input type="file" name="uploaded_file" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Agreement</button>
                        </form>
                    </div>
                </div>

                <div class="box">
                    <div class="box-header">
                        <h3>Agreements List</h3>
                    </div>
                    <div class="box-body">
                        @if ($agreements->isNotEmpty())
                        <table class="table table-bordered table-striped" id="agreements_table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Advertiser Name</th>
                                    <th>Calls Per Week</th>
                                    <th>Calls Per Month</th>
                                    <th>Support Person</th>
                                    <th>Contact Details</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Agreement Document</th>
                                    <th>Script File</th>
                                    <th>Uploaded File</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($agreements as $key => $agreement)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $agreement->advertiser_name }}</td>
                                    <td>{{ $agreement->calls_per_week }}</td>
                                    <td>{{ $agreement->calls_per_month }}</td>
                                    <td>{{ $agreement->support_person_name }}</td>
                                    <td>{{ $agreement->contact_details }}</td>
                                    <td>{{ date('d-m-Y', strtotime($agreement->start_date)) }}</td>
                                    <td>{{ date('d-m-Y', strtotime($agreement->end_date)) }}</td>
                                    <td>
                                        @if ($agreement->document)
                                            <a href="{{ asset('storage/agreements/' . $agreement->document) }}" target="_blank">View Document</a>
                                        @else
                                            No Document
                                        @endif
                                    </td>
                                    <td>
                                        @if ($agreement->script_file)
                                            <a href="{{ asset('storage/agreements/' . $agreement->script_file) }}" target="_blank">View Script</a>
                                        @else
                                            No Script
                                        @endif
                                    </td>
                                    <td>
                                        @if ($agreement->uploaded_file)
                                            <a href="{{ asset('storage/agreements/' . $agreement->uploaded_file) }}" target="_blank">Download File</a>
                                        @else
                                            No File
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <p>No agreements found.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('page-scripts')
<script>
    $(document).ready(function() {
        $('#agreements_table').DataTable();
    });
</script>
@endsection
