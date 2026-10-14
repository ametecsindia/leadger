@extends('dashboard.layouts.master')
@section('title', 'Call Setup & Settings - LeadGER')

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.lg-settings-wrapper {
    background-color: #F8FAFC;
    min-height: calc(100vh - 72px);
    padding: 24px 30px;
    font-family: var(--lg-font-sans);
    color: #0F172A;
    box-sizing: border-box;
}

.lg-settings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-settings-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lg-settings-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.08);
    flex-shrink: 0;
}

.lg-settings-title-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lg-settings-breadcrumb {
    font-size: 13px;
    font-weight: 500;
    color: var(--lg-accent);
}

.lg-settings-title {
    font-size: 22px;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
    line-height: 1.2;
}

.lg-settings-subtitle {
    font-size: 13px;
    color: #64748B;
    margin-top: 2px;
}

.lg-settings-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    padding: 28px;
    margin-bottom: 24px;
}

.lg-settings-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    gap: 10px;
}

.lg-settings-section-title i {
    color: var(--lg-accent);
}

.lg-form-group {
    margin-bottom: 20px;
}

.lg-form-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
    display: block;
}

.lg-form-sublabel {
    font-size: 12px;
    color: #64748B;
    margin-top: 4px;
    display: block;
}

.lg-form-control, .lg-form-select {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13.5px;
    color: #0F172A;
    background: #FFFFFF;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.lg-form-control:focus, .lg-form-select:focus {
    outline: none;
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-btn-save {
    height: 42px;
    padding: 0 24px;
    background: var(--lg-accent);
    color: #FFFFFF !important;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(20, 100, 244, 0.2);
}

.lg-btn-save:hover {
    background: #0F52CC;
    box-shadow: 0 4px 12px rgba(20, 100, 244, 0.3);
}

.lg-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #A7F3D0;
}

.lg-status-pill .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10B981;
}

.lg-input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.lg-input-with-icon .lg-input-icon {
    position: absolute;
    left: 14px;
    color: #64748B;
    font-size: 15px;
    pointer-events: none;
    z-index: 2;
}

.lg-form-control.lg-input-padded {
    padding-left: 38px;
}
</style>
@endpush

@section('content')
<div class="lg-settings-wrapper">
    <!-- Header -->
    <div class="lg-settings-header">
        <div class="lg-settings-header-left">
            <div class="lg-settings-icon-box">
                <i class="fa fa-phone-volume"></i>
            </div>
            <div class="lg-settings-title-group">
                <h1 class="lg-settings-title">Call Setup & Settings</h1>
                <div class="lg-settings-subtitle">Manage calling channels, trunk lines, Click2Call defaults, and auto-dialer behavior</div>
            </div>
        </div>
    </div>

    @if(session('doneMessage'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px; background: #ECFDF5; border: 1px solid #A7F3D0; color: #047857;">
            <i class="fa fa-check-circle me-2"></i> {{ session('doneMessage') }}
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px; background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;">
            <div class="d-flex align-items-center mb-1">
                <i class="fa fa-circle-exclamation me-2"></i>
                <strong>Please check the errors below:</strong>
            </div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('callingSettingsUpdate') }}" method="POST">
        @csrf
        
        <!-- 1. Calling Channels & Telephony -->
        <div class="lg-settings-card">
            <div class="lg-settings-section-title">
                <i class="fa fa-headset"></i> Calling Channels & Telephony Setup
            </div>

            <input type="hidden" name="default_dialer_type" value="{{ old('default_dialer_type', $settings->active_call_channel ?? 'click2call') }}">

            <!-- Row 2: Calling hours from & Calling hours to -->
            <div class="row">
                <div class="col-md-6 lg-form-group">
                    <label class="lg-form-label" for="calling_hours_from">Calling hours from</label>
                    <div class="lg-input-with-icon">
                        <i class="fa-regular fa-clock lg-input-icon"></i>
                        <input type="time" id="calling_hours_from" name="calling_hours_from" class="lg-form-control lg-input-padded {{ $errors->has('calling_hours_from') ? 'is-invalid' : '' }}" value="{{ old('calling_hours_from', $calling_hours_from ?? '09:30') }}" required>
                    </div>
                    @error('calling_hours_from')
                        <span class="text-danger d-block mt-1" style="font-size: 12.5px; font-weight: 500;">{{ $message }}</span>
                    @enderror
                    <span class="lg-form-sublabel">Permitted start time for outbound sales calls (24-hour format: HH:mm)</span>
                </div>

                <div class="col-md-6 lg-form-group">
                    <label class="lg-form-label" for="calling_hours_to">Calling hours to</label>
                    <div class="lg-input-with-icon">
                        <i class="fa-regular fa-clock lg-input-icon"></i>
                        <input type="time" id="calling_hours_to" name="calling_hours_to" class="lg-form-control lg-input-padded {{ $errors->has('calling_hours_to') ? 'is-invalid' : '' }}" value="{{ old('calling_hours_to', $calling_hours_to ?? '19:30') }}" required>
                    </div>
                    @error('calling_hours_to')
                        <span class="text-danger d-block mt-1" style="font-size: 12.5px; font-weight: 500;">{{ $message }}</span>
                    @enderror
                    <span class="lg-form-sublabel">Permitted end time for outbound sales calls (24-hour format: HH:mm)</span>
                </div>
            </div>

            <!-- Row 3: Daily Limit -->
            <div class="row">
                <div class="col-md-6 lg-form-group">
                    <label class="lg-form-label" for="daily_call_limit">Maximum retry of a particular mobile number in a day</label>
                    <input type="number" id="daily_call_limit" name="daily_call_limit" class="lg-form-control {{ $errors->has('daily_call_limit') ? 'is-invalid' : '' }}" value="{{ old('daily_call_limit', $settings->daily_call_limit ?? 100) }}" min="1" max="1000" placeholder="100" required>
                    @error('daily_call_limit')
                        <span class="text-danger d-block mt-1" style="font-size: 12.5px; font-weight: 500;">{{ $message }}</span>
                    @enderror
                    <span class="lg-form-sublabel">Maximum number of outbound call attempts allowed to the same mobile number in a day.</span>
                </div>
                <input type="hidden" name="auto_recording" value="{{ old('auto_recording', $settings->auto_recording ?? 1) }}">
            </div>
        </div>

        <!-- Hidden inputs for dialer_delay and wrapup_time -->
        <input type="hidden" name="dialer_delay" value="{{ old('dialer_delay', $settings->dialer_delay ?? 5) }}">
        <input type="hidden" name="wrapup_time" value="{{ old('wrapup_time', $settings->wrapup_time ?? 30) }}">

        <!-- Submit Button -->
        <div class="d-flex align-items-center justify-content-end gap-3">
            <button type="submit" class="lg-btn-save">
                <i class="fa fa-save"></i> Save Calling Settings
            </button>
        </div>
    </form>
</div>
@endsection
