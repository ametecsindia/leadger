@extends('dashboard.layouts.master')
@section('title', 'Payouts List')
<style>
#payout_table td {
    font-family: var(--lg-font-sans);
}

</style>
@section('content')

<div class="padding">
    <div class="box">
        <div class="box-header dker">
            <h3>Payouts</h3>
            <small>
                <a href="{{ route('adminHome') }}">Home</a> /
                <a href="#">Finance</a> /
                <a href="#">Payouts</a>
            </small>
        </div>

        <div class="card-box-shadow">
            <a class="btn btn-primary m-b-2" href="{{ route('payout.create') }}">Create New Payout</a>

            @if(count($Payouts) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="payout_table">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Pay Type</th>
                                <th>Call Slab 1</th>
                                <th>Call Pay 1</th>
                                <th>Call Slab 2</th>
                                <th>Call Pay 2</th>
                                <th>Call Slab 3</th>
                                <th>Call Pay 3</th>
                                <th>Call Slab 4</th>
                                <th>Call Pay 4</th>
                                <th>Duration Slab 1</th>
                                <th>Duration Pay 1</th>
                                <th>Duration Slab 2</th>
                                <th>Duration Pay 2</th>
                                <th>Duration Slab 3</th>
                                <th>Duration Pay 3</th>
                                <th>Duration Slab 4</th>
                                <th>Duration Pay 4</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($Payouts as $Payout)
                                <tr>
                                    <td>{{ $Payout->role_name }}</td>
                                    <td>{{ ucfirst($Payout->payout_type) }}</td>

                                    {{-- Call Slab 1 --}}
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybycall', 'both']))
                                            {{ $Payout->first_range_min_call }} - {{ $Payout->first_range_max_call }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybycall', 'both']))
                                            ₹{{ $Payout->first_range_pay_call }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                     {{-- Call Slab 2 --}}
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybycall', 'both']))
                                            {{ $Payout->second_range_min_call }} - {{ $Payout->second_range_max_call }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybycall', 'both']))
                                            ₹{{ $Payout->second_range_pay_call }}
                                        @else
                                            -
                                        @endif
                                    </td>
                              {{-- Call Slab 3 --}}
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybycall', 'both']))
                                            {{ $Payout->third_range_min_call }} - {{ $Payout->third_range_max_call }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybycall', 'both']))
                                            ₹{{ $Payout->third_range_pay_call }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                     {{-- Call Slab 4 --}}
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybycall', 'both']))
                                            {{ $Payout->fourth_range_min_call }} - {{ $Payout->fourth_range_max_call }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybycall', 'both']))
                                            ₹{{ $Payout->fourth_range_pay_call }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    {{-- Duration Slab 1 --}}
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybyduration', 'both']))
                                            {{ $Payout->first_range_min_duration }} - {{ $Payout->first_range_max_duration }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybyduration', 'both']))
                                            ₹{{ $Payout->first_range_pay_duration }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    {{-- Duration Slab 2 --}}
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybyduration', 'both']))
                                            {{ $Payout->second_range_min_duration }} - {{ $Payout->second_range_max_duration }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybyduration', 'both']))
                                            ₹{{ $Payout->second_range_pay_duration }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    {{-- Duration Slab 1 --}}
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybyduration', 'both']))
                                            {{ $Payout->third_range_min_duration }} - {{ $Payout->third_range_max_duration }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybyduration', 'both']))
                                            ₹{{ $Payout->third_range_pay_duration }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    {{-- Duration Slab 1 --}}
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybyduration', 'both']))
                                            {{ $Payout->fourth_range_min_duration }} - {{ $Payout->fourth_range_max_duration }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($Payout->payout_type, ['paybyduration', 'both']))
                                            ₹{{ $Payout->fourth_range_pay_duration }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <a href="{{ route('payoutEdit', $Payout->id) }}" class="btn btn-sm btn-info">Edit</a>
                                        <form action="{{ route('payoutDestroy', $Payout->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center">No Payouts Found.</p>
            @endif
        </div>
    </div>
</div>

@endsection
