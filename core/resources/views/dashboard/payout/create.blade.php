@extends('dashboard.layouts.master')
@section('title', 'Create New Payout Range')
<style>
    .call-slab, .duration-slab {
        display: none;
    }
</style>



@section('content')
<div class="padding">
    <div class="box">
        <div class="box-header dker">
            <h3>Create New Payout Range</h3>
            <small>
                <a href="{{ route('adminHome') }}">Home</a> /
                <a href="#">Finance</a> /
                <a href="#">Payout Ranges</a>
            </small>
        </div>
        <div class="box-tool">
            <ul class="nav">
                <li class="nav-item inline">
                    <a class="nav-link" href="{{ route('payout.index') }}">
                        <i class="material-icons md-18">×</i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-box-shadow">
            {{ Form::open(['route' => 'payout.store', 'method' => 'POST']) }}

            <!-- Role Name -->
            <div class="form-group row">
                <label class="col-sm-2 form-control-label">Role Name</label>
                <div class="col-sm-10">
                    {{ Form::text('role_name', null, ['class' => 'form-control', 'placeholder' => 'Enter Role Name', 'required']) }}
                </div>
            </div>

            <!-- Payout Type -->
            <div class="form-group row">
                <label class="col-sm-2 form-control-label">Payout Type</label>
                <div class="col-sm-10">
                    <select name="payout_type" id="payout_type" class="form-control" required>
                        <option value="both">Both</option>
                        <option value="paybycall">Pay by Call</option>
                        <option value="paybyduration">Pay by Duration</option>
                    </select>

                </div>
            </div>

            {{-- Call Slabs --}}
        @php
        $labels = ['first', 'second', 'third', 'fourth'];
        @endphp

        @foreach($labels as $index => $label)
        <div class="form-group row call-slab">
            <label class="col-sm-2 form-control-label">Slab-{{ $index + 1 }} Calls</label>
            <div class="col-sm-10">
                <div class="row">
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_min_call", null, ['class' => 'form-control call-input', 'placeholder' => 'Lowest Calls']) }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_max_call", null, ['class' => 'form-control call-input', 'placeholder' => 'Highest Calls']) }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_pay_call", null, ['class' => 'form-control call-input', 'placeholder' => 'Rate (₹)', 'step' => '0.01']) }}
                    </div>
                </div>
            </div>
        </div>
        @endforeach


            {{-- Duration Slabs --}}
        @php
        $labels = ['first', 'second', 'third', 'fourth'];
        @endphp

        @foreach($labels as $index => $i)
            <div class="form-group row duration-slab">
                <label class="col-sm-2 form-control-label">Slab-{{ $i }} Duration</label>
                <div class="col-sm-10">
                    <div class="row">
                        <div class="col-sm-4">
                            {{ Form::number("{$i}_range_min_duration", null, ['class' => 'form-control duration-input', 'placeholder' => 'Lowest Duration']) }}
                        </div>
                        <div class="col-sm-4">
                            {{ Form::number("{$i}_range_max_duration", null, ['class' => 'form-control duration-input', 'placeholder' => 'Highest Duration']) }}
                        </div>
                        <div class="col-sm-4">
                            {{ Form::number("{$i}_range_pay_duration", null, ['class' => 'form-control duration-input', 'placeholder' => 'Rate (₹)', 'step' => '0.01']) }}
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            <!-- Submit -->
            <div class="form-group row m-t-md">
                <div class="offset-sm-2 col-sm-10">
                    <button type="submit" class="btn btn-primary m-t">
                        <i class="material-icons">&#xe31b;</i> Create
                    </button>
                    <a href="{{ route('payout.index') }}" class="btn btn-default m-t">
                        <i class="material-icons">&#xe5cd;</i> Cancel
                    </a>
                </div>
            </div>

            {{ Form::close() }}
        </div>
    </div>
</div>
@endsection

@push("after-scripts")
<script>
$(document).ready(function() {
    $('#payout_type').change(function() {
        const type = $(this).val();
        
        $('.call-slab').toggle(type === 'paybycall' || type === 'both');
        $('.duration-slab').toggle(type === 'paybyduration' || type === 'both');
        
        $('.call-input').prop('required', type === 'paybycall' );
        $('.duration-input').prop('required', type === 'paybyduration' || type === 'both');
    }).trigger('change'); // Initialize on load
});


    function onPayoutTypeChange(type) {
        //console.log('-----------');
        const callSlabs = document.querySelectorAll('.call-slab');
        const durationSlabs = document.querySelectorAll('.duration-slab');
        const callInputs = document.querySelectorAll('.call-input');
        const durationInputs = document.querySelectorAll('.duration-input');

        if (type === 'paybycall') {
            callSlabs.forEach(el => el.style.display = 'block');
            durationSlabs.forEach(el => el.style.display = 'none');

            callInputs.forEach(el => el.required = true);
            durationInputs.forEach(el => el.required = false);
        } else if (type === 'paybyduration') {
            callSlabs.forEach(el => el.style.display = 'none');
            durationSlabs.forEach(el => el.style.display = 'block');

            callInputs.forEach(el => el.required = false);
            durationInputs.forEach(el => el.required = true);
        } else {
            callSlabs.forEach(el => el.style.display = 'block');
            durationSlabs.forEach(el => el.style.display = 'block');

            callInputs.forEach(el => el.required =false);
            durationInputs.forEach(el => el.required = true);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const payoutType = document.getElementById('payout_type');

        // Call on load
        onPayoutTypeChange(payoutType.value);

        // Call when selection changes
        payoutType.addEventListener('change', function () {
            onPayoutTypeChange(this.value);
        });
    });


</script>


@endpush
