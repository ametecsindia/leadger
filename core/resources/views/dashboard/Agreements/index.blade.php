@extends('dashboard.layouts.master')
@section('title', 'Agreements')
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>Agreements</h3>
                <small>
                    <a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> /
                    <a href="#">{{ __('Agreements') }}</a>
                </small>
            </div>

            @if($agreements->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered m-a-0">
                        <thead class="dker">
                        <tr>
                            <th>S.No</th>
                            <th>Advertiser Name</th>
                            <th>Calls per Week</th>
                            <th>Calls per Month</th>
                            <th>Preferred Calling Hours</th>
                            <th>Support Person Name</th>
                            <th>Contact Details</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Script File</th>
                            <th>Uploaded File</th>
                            <th>Agreement File</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($agreements as $agreement)
                            <tr>
                                <td>{{ $loop->iteration }}</td>  
                                <td>{{ $agreement->advertiser_name }}</td>
                                <td>{{ $agreement->calls_per_week }}</td>
                                <td>{{ $agreement->calls_per_month }}</td>
                                <td>{{ $agreement->preferred_calling_hours }}</td>
                                <td>{{ $agreement->support_person_name }}</td>
                                <td>{{ $agreement->contact_details }}</td>
                                <td>{{ $agreement->start_date }}</td>
                                <td>{{ $agreement->end_date }}</td>

                                <!-- Script File -->
                                <td>
                                    @if($agreement->script_file)
                                        <a href="{{ route('file.view', ['path' => $agreement->script_file]) }}" target="_blank">View</a> /
                                        <a href="{{ route('file.download', ['path' => $agreement->script_file]) }}">Download</a>
                                    @endif
                                </td>

                                <!-- Uploaded File -->
                                <td>
                                    @if($agreement->uploaded_file)
                                    
                                    <a href="{{ route('file.download', ['path' => $agreement->uploaded_file]) }}">Download</a>
                                    @endif
                                </td>

                                <!-- PDF File -->
                                <td>
                                    @if($agreement->pdf_path)
                                    <a href="{{ route('file.view', ['path' => $agreement->pdf_path]) }}" target="_blank">View</a> /
                                    <a href="{{ route('file.download', ['path' => $agreement->pdf_path]) }}">Download</a>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td>
                                    <a href="{{ route('agreements.duplicate', $agreement->id) }}" class="btn btn-sm btn-warning">Duplicate</a>

                                    @if($userRole == 'Admin')
                                        <a href="{{ route('agreements.edit', $agreement->id) }}" class="btn btn-sm btn-info">Edit</a>
                                        <form action="{{ route('agreements.destroy', $agreement->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this agreement?')">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-a text-center">
                    {{ __('backend.noData') }}
                </div>
            @endif
        </div>
    </div>
@endsection
