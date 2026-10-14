@extends('dashboard.layouts.master')
@section('title', 'Duplicate Campaign – LeadGER')

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

@push('after-styles')
<style>
/* ── Canvas & Wrapper ─────────────────────────────────────── */
.lg-campaign-canvas {
    padding: 24px 28px 40px;
    background-color: #F8FAFD;
    min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #0F172A;
    box-sizing: border-box;
}

.lg-campaign-container {
    max-width: 960px;
    margin: 0 auto;
}

/* ── Page Header Card ─────────────────────────────────────── */
.lg-header-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.lg-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lg-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1E3A5F 0%, var(--lg-accent) 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    flex-shrink: 0;
}

.lg-header-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 20px;
    line-height: 1.25;
    letter-spacing: -0.01em;
}

.lg-header-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 500;
}

.lg-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ── Main Form Card ───────────────────────────────────────── */
.lg-form-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 28px 32px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
    margin-bottom: 24px;
}

.lg-section-header {
    margin-bottom: 22px;
    padding-bottom: 14px;
    border-bottom: 1px solid #F1F5F9;
}

.lg-section-title {
    font-size: 15px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    letter-spacing: -0.01em;
}

/* ── 2-Column Responsive Form Grid ────────────────────────── */
.lg-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px 24px;
    margin-bottom: 24px;
}

.lg-col-full {
    grid-column: 1 / -1;
}

@media (max-width: 768px) {
    .lg-campaign-canvas {
        padding: 16px 16px 32px;
    }
    .lg-form-card {
        padding: 20px;
    }
    .lg-form-grid {
        grid-template-columns: 1fr;
        gap: 18px;
    }
    .lg-col-full {
        grid-column: auto;
    }
    .lg-header-card {
        padding: 16px 18px;
    }
}

/* ── Field Group & Labels ─────────────────────────────────── */
.lg-field-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.lg-label {
    font-size: 13.5px;
    font-weight: 600;
    color: #1E293B;
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 0;
    line-height: 1.2;
}

.lg-label .req {
    color: #EF4444;
    font-weight: 700;
}

/* ── Input & Select Styles ────────────────────────────────── */
.lg-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

.lg-input,
.lg-select {
    width: 100%;
    height: 42px;
    line-height: 42px;
    padding: 0 14px;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0F172A;
    background-color: #FFFFFF;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    font-family: inherit;
    box-sizing: border-box;
}

.lg-input::placeholder {
    color: #94A3B8;
    opacity: 1;
}

.lg-input:focus,
.lg-select:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12);
    background-color: #FFFFFF;
}

.lg-input.has-error,
.lg-select.has-error {
    border-color: #EF4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important;
}

/* Clean Custom Select with Properly Placed Chevron */
.lg-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 38px !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: calc(100% - 14px) center;
    background-size: 14px;
    cursor: pointer;
}

/* Select2 Custom Theme Alignment (if active) */
.select2-container--default .select2-selection--single {
    height: 42px !important;
    border: 1px solid #CBD5E1 !important;
    border-radius: 10px !important;
    background-color: #FFFFFF !important;
    padding: 0 14px !important;
    display: flex !important;
    align-items: center !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
}

.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--lg-accent) !important;
    box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12) !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px !important;
    padding-left: 0 !important;
    padding-right: 24px !important;
    color: #0F172A !important;
    font-size: 13.5px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 12px !important;
    top: 0 !important;
}

.select2-dropdown {
    border: 1px solid #CBD5E1 !important;
    border-radius: 10px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
    overflow: hidden !important;
}

.select2-search--dropdown {
    padding: 8px 10px !important;
}

.select2-search--dropdown .select2-search__field {
    border: 1px solid #CBD5E1 !important;
    border-radius: 8px !important;
    padding: 6px 10px !important;
    height: 36px !important;
    outline: none !important;
}

.select2-results__option {
    padding: 8px 14px !important;
    font-size: 13.5px !important;
}

.select2-results__option--highlighted[aria-selected] {
    background-color: var(--lg-accent) !important;
}

/* Date Input with Inside-Right Calendar Icon */
.lg-date-input {
    padding-right: 40px !important;
}

.lg-input-icon-right {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    cursor: pointer;
    pointer-events: auto;
    z-index: 2;
    transition: color 0.15s ease;
}

.lg-input-icon-right:hover {
    color: var(--lg-accent);
}

/* Inline Field Errors */
.lg-field-error {
    font-size: 12px;
    font-weight: 500;
    color: #EF4444;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ── Light-Blue Info Strip ────────────────────────────────── */
.lg-info-box {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-radius: 10px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--lg-accent-hover);
    font-size: 13px;
    font-weight: 500;
    margin-top: 4px;
    margin-bottom: 24px;
    line-height: 1.4;
}

.lg-info-icon {
    color: var(--lg-accent);
    flex-shrink: 0;
}

/* ── Action Footer & Buttons ──────────────────────────────── */
.lg-action-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #F1F5F9;
}

.lg-btn {
    height: 42px;
    padding: 0 20px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    font-family: inherit;
    box-sizing: border-box;
    white-space: nowrap;
}

.lg-btn-primary {
    background: var(--lg-accent);
    color: #FFFFFF;
    border: 1px solid var(--lg-accent);
    box-shadow: 0 2px 6px rgba(23, 105, 255, 0.25);
}

.lg-btn-primary:hover {
    background: #0F52CC;
    border-color: #0F52CC;
    color: #FFFFFF;
}

.lg-btn-secondary {
    background: #FFFFFF;
    color: #475569;
    border: 1px solid #CBD5E1;
}

.lg-btn-secondary:hover {
    background: #F1F5F9;
    color: #0F172A;
    border-color: #94A3B8;
}

.lg-btn-primary:disabled {
    opacity: 0.75;
    cursor: not-allowed;
    background: #3B82F6;
    border-color: #3B82F6;
}

@keyframes lg-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.lg-spin {
    animation: lg-spin 0.8s linear infinite;
}

.alert-danger-box {
    background: #FEF2F2;
    border: 1px solid #FCA5A5;
    border-radius: 10px;
    padding: 12px 16px;
    color: #991B1B;
    font-size: 13px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>
@endpush

@section('content')
<div class="lg-campaign-canvas">
    <div class="lg-campaign-container">

        <!-- 1. Page Header Card -->
        <div class="lg-header-card">
            <div class="lg-header-left">
                <div class="lg-header-icon">
                    <!-- Copy / Duplicate Icon -->
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="lg-header-title">Duplicate Campaign</h1>
                    <div class="lg-header-sub">Create a new campaign from an existing one.</div>
                </div>
            </div>
            <div class="lg-header-right">
                <a href="{{ route('campaigns') }}" class="lg-btn lg-btn-secondary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Back to Campaign List</span>
                </a>
            </div>
        </div>

        @if(session('errorMessage'))
        <div class="alert-danger-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <span>{{ session('errorMessage') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="alert-danger-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <span>Please check the form below for required fields.</span>
        </div>
        @endif

        <!-- 2. Form Card -->
        <div class="lg-form-card">
            <div class="lg-section-header">
                <h2 class="lg-section-title">Campaign details</h2>
            </div>

            {{ Form::open(['route' => ['campaignsDupStore'], 'method' => 'POST', 'id' => 'duplicateCampaignForm', 'novalidate' => 'novalidate']) }}

            <div class="lg-form-grid">
                <!-- Field 1: Campaign Dropdown (Full Width) -->
                <div class="lg-field-group lg-col-full">
                    <label for="campaign_id" class="lg-label">Campaign <span class="req">*</span></label>
                    <select name="campaign_id" id="campaign_id" class="lg-select" required>
                        <option value="">Select campaign</option>
                        @foreach($Campaigns as $Campaign)
                        <option value="{{ $Campaign->id }}" {{ old('campaign_id') == $Campaign->id ? 'selected' : '' }}>
                            {{ $Campaign->name }} ({{ number_format($Campaign->lead_count) }} {{ $Campaign->lead_count == 1 ? 'lead' : 'leads' }})
                        </option>
                        @endforeach
                    </select>
                    @error('campaign_id')
                    <div class="lg-field-error" id="err_campaign_id">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Field 2: New Campaign Name (Left Column) -->
                <div class="lg-field-group">
                    <label for="name" class="lg-label">New campaign name <span class="req">*</span></label>
                    <div class="lg-input-wrap">
                        {!! Form::text('name', old('name'), [
                            'placeholder' => 'Enter the new campaign name',
                            'class' => 'lg-input',
                            'id' => 'name',
                            'required' => '',
                            'maxlength' => 50,
                            'autocomplete' => 'off'
                        ]) !!}
                    </div>
                    @error('name')
                    <div class="lg-field-error" id="err_name">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Field 3: Expiry Date (Right Column) -->
                <div class="lg-field-group">
                    <label for="date" class="lg-label">Expiry date <span class="req">*</span></label>
                    <div class="lg-input-wrap">
                        {!! Form::text('date', old('date', date('Y-m-t')), [
                            'placeholder' => 'YYYY-MM-DD',
                            'class' => 'lg-input lg-date-input',
                            'id' => 'date',
                            'required' => '',
                            'autocomplete' => 'off'
                        ]) !!}
                        <span class="lg-input-icon-right" id="calendarIconBtn" title="Choose date">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </span>
                    </div>
                    @error('date')
                    <div class="lg-field-error" id="err_date">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- 3. Info Box -->
            <div class="lg-info-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lg-info-icon">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span>The new campaign copies the settings of the selected campaign. Leads are not copied.</span>
            </div>

            <!-- 4. Buttons Footer Row -->
            <div class="lg-action-footer">
                <a href="{{ route('campaigns') }}" class="lg-btn lg-btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="lg-btn lg-btn-primary" id="submitDuplicateBtn">
                    <svg class="lg-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span class="lg-btn-text">Create Campaign</span>
                </button>
            </div>

            {{ Form::close() }}
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
$(document).ready(function() {
    // 1. Searchable dropdown initialization if list has many options
    if (typeof $.fn.select2 !== 'undefined' && $('#campaign_id option').length > 6) {
        $('#campaign_id').select2({
            placeholder: 'Select campaign',
            allowClear: false,
            width: '100%'
        }).on('change', function() {
            if ($(this).val()) clearError('campaign_id');
        });
    }

    // 2. Datepicker initialization
    if (typeof $.fn.datetimepicker !== 'undefined') {
        $('#date').datetimepicker({
            format: 'YYYY-MM-DD',
            icons: {
                time: 'fa fa-clock-o',
                date: 'fa fa-calendar',
                up: 'fa fa-chevron-up',
                down: 'fa fa-chevron-down',
                previous: 'fa fa-chevron-left',
                next: 'fa fa-chevron-right',
                today: 'fa fa-screenshot',
                clear: 'fa fa-trash',
                close: 'fa fa-remove'
            }
        });

        $('#calendarIconBtn').on('click', function(e) {
            e.preventDefault();
            $('#date').focus();
            if ($('#date').data('DateTimePicker')) {
                $('#date').data('DateTimePicker').show();
            }
        });
    } else {
        // Fallback for native interaction
        $('#calendarIconBtn').on('click', function() {
            $('#date').focus();
        });
    }

    // 3. Inline validation helpers
    function showError(fieldId, msg) {
        $('#' + fieldId).addClass('has-error');
        if (fieldId === 'campaign_id' && $('#campaign_id').next('.select2-container').length) {
            $('#campaign_id').next('.select2-container').find('.select2-selection').css('border-color', '#EF4444');
        }
        var $err = $('#err_' + fieldId);
        if (!$err.length) {
            $err = $('<div class="lg-field-error" id="err_' + fieldId + '"></div>');
            $('#' + fieldId).closest('.lg-field-group').append($err);
        }
        $err.text(msg).show();
    }

    function clearError(fieldId) {
        $('#' + fieldId).removeClass('has-error');
        if (fieldId === 'campaign_id' && $('#campaign_id').next('.select2-container').length) {
            $('#campaign_id').next('.select2-container').find('.select2-selection').css('border-color', '');
        }
        $('#err_' + fieldId).hide().text('');
    }

    $('#campaign_id').on('change', function() {
        if ($(this).val()) clearError('campaign_id');
    });

    $('#name').on('input', function() {
        if ($.trim($(this).val())) clearError('name');
    });

    $('#date').on('change input', function() {
        if ($.trim($(this).val())) clearError('date');
    });

    // 4. Form Submit Handler with Loading State and Double-Submit Prevention
    $('#duplicateCampaignForm').on('submit', function(e) {
        var isValid = true;

        var campaignId = $('#campaign_id').val();
        if (!campaignId) {
            showError('campaign_id', 'Select a campaign');
            isValid = false;
        } else {
            clearError('campaign_id');
        }

        var name = $.trim($('#name').val());
        if (!name) {
            showError('name', 'Enter a name');
            isValid = false;
        } else {
            clearError('name');
        }

        var dateVal = $.trim($('#date').val());
        if (!dateVal) {
            showError('date', 'Select an expiry date');
            isValid = false;
        } else {
            clearError('date');
        }

        if (!isValid) {
            e.preventDefault();
            return false;
        }

        // Lock button briefly and show loading state
        var $btn = $('#submitDuplicateBtn');
        $btn.prop('disabled', true);
        $btn.find('.lg-btn-text').text('Creating Campaign...');
        $btn.find('.lg-btn-icon').replaceWith(
            '<svg class="lg-btn-icon lg-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg>'
        );
    });
});
</script>
@endpush
