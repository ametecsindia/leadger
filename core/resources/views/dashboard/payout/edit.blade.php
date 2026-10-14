@extends('dashboard.layouts.master')
@section('title', 'Edit Payout')
<style>
    .call-slab, .duration-slab {
        display: none;
    }
</style>
@section('content')

<div class="padding">
    <div class="box">
        <div class="box-header dker">
            <h3>Edit Payout</h3>
            <small>
                <a href="{{ route('adminHome') }}">Home</a> /
                <a href="#">Finance</a> /
                <a href="#">Edit Payout</a>
            </small>
        </div>

        <div class="card-box-shadow">
            {{ Form::model($Payout, ['route' => ['payout.update', $Payout->id], 'method' => 'PUT']) }}

            <div class="form-group row">
                <label class="col-sm-2 form-control-label">Role Name</label>
                <div class="col-sm-10">
                    {{ Form::text('role_name', null, ['class' => 'form-control', 'required']) }}
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-2 form-control-label">Pay Type</label>
                <div class="col-sm-10">
                    <select name="payout_type" id="payout_type" class="form-control" required>
                        <option value="both" {{ $Payout->payout_type == 'both' ? 'selected' : '' }}>Both</option>
                        <option value="paybycall" {{ $Payout->payout_type == 'paybycall' ? 'selected' : '' }}>Pay by Call</option>
                        <option value="paybyduration" {{ $Payout->payout_type == 'paybyduration' ? 'selected' : '' }}>Pay by Duration</option>
                    </select>
                </div>
            </div>

            @php
                $labels = ['first', 'second', 'third', 'fourth'];
            @endphp

            {{-- Call Slabs --}}
            @foreach($labels as $label)
            <div class="form-group row call-slab">
                <label class="col-sm-2 form-control-label">{{ ucfirst($label) }} Call Slab</label>
                <div class="col-sm-10 row">
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_min_call", $Payout["{$label}_range_min_call"], ['class' => 'form-control call-input', 'placeholder' => 'Min Calls']) }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_max_call", $Payout["{$label}_range_max_call"], ['class' => 'form-control call-input', 'placeholder' => 'Max Calls']) }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_pay_call", $Payout["{$label}_range_pay_call"], ['class' => 'form-control call-input', 'placeholder' => '₹/Call', 'step' => '0.01']) }}
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Duration Slabs --}}
            @foreach($labels as $label)
            <div class="form-group row duration-slab">
                <label class="col-sm-2 form-control-label">{{ ucfirst($label) }} Duration Slab</label>
                <div class="col-sm-10 row">
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_min_duration", $Payout["{$label}_range_min_duration"], ['class' => 'form-control duration-input', 'placeholder' => 'Min Duration']) }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_max_duration", $Payout["{$label}_range_max_duration"], ['class' => 'form-control duration-input', 'placeholder' => 'Max Duration']) }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::number("{$label}_range_pay_duration", $Payout["{$label}_range_pay_duration"], ['class' => 'form-control duration-input', 'placeholder' => '₹/Duration', 'step' => '0.01']) }}
                    </div>
                </div>
            </div>
            @endforeach

            <div class="form-group row m-t-md">
                <div class="offset-sm-2 col-sm-10">
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons">&#xe31b;</i> Update
                    </button>
                    <a href="{{ route('payout.index') }}" class="btn btn-default">
                        <i class="material-icons">&#xe5cd;</i> Cancel
                    </a>
                </div>
            </div>

            {{ Form::close() }}
        </div>
    </div>
</div>

@endsection

@push('after-scripts')
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
            callInputs.forEach(el => el.required = false);
            durationInputs.forEach(el => el.required = true);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const payoutType = document.getElementById('payout_type');
        onPayoutTypeChange(payoutType.value);
        payoutType.addEventListener('change', function () {
            onPayoutTypeChange(this.value);
        });
    });
</script>
@endpush
