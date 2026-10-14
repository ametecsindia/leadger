@extends('dashboard.layouts.master')
@section('title', __('backend.usersPermissions'))

@section('content')
<style>
/* LeadGER Corporate Modern Single Container Add Staff Form Styling */
.lg-add-staff-wrapper {
    font-family: var(--lg-font-sans);
    color: #0F172A;
    background: #F8FAFC;
    padding: 24px;
    min-height: calc(100vh - 70px);
}

/* SINGLE MAIN CARD CONTAINER */
.lg-single-main-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    max-width: 1000px;
    margin: 0 auto;
}

/* Page Header inside single container */
.lg-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding-bottom: 24px;
    border-bottom: 1px solid #F1F5F9;
    margin-bottom: 28px;
}

.lg-header-title-box {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lg-header-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: rgba(20, 100, 244, 0.1);
    color: var(--lg-accent);
    border: 1px solid rgba(20, 100, 244, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.12);
}

.lg-header-h1 {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}

.lg-header-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 2px;
    font-weight: 400;
}

.lg-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #64748B;
}

.lg-breadcrumb a {
    color: var(--lg-accent);
    text-decoration: none;
    transition: color 0.15s ease;
}

.lg-breadcrumb a:hover {
    color: #0F52CC;
    text-decoration: underline;
}

/* Section Headings inside single container (No Card Boxes!) */
.lg-section-heading {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.lg-section-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #EFF6FF;
    color: var(--lg-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #BFDBFE;
}

.lg-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    letter-spacing: 0.01em;
    margin: 0;
}

/* Horizontal Section Divider */
.lg-section-divider {
    height: 1px;
    background: #F1F5F9;
    margin: 28px 0;
}

/* Two-Column Form Grid */
.lg-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px 24px;
}

@media (max-width: 768px) {
    .lg-form-grid {
        grid-template-columns: 1fr;
    }
}

.lg-field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.lg-field-group.full-width {
    grid-column: 1 / -1;
}

.lg-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0;
}

.lg-req {
    color: #EF4444;
    font-weight: 700;
}

/* Form Controls */
.lg-add-staff-wrapper input[type="text"],
.lg-add-staff-wrapper input[type="email"],
.lg-add-staff-wrapper input[type="tel"],
.lg-add-staff-wrapper input[type="password"],
.lg-add-staff-wrapper select,
.lg-add-staff-wrapper .form-control {
    width: 100% !important;
    height: 42px !important;
    padding: 0 14px !important;
    background: #FFFFFF !important;
    border: 1px solid #CBD5E1 !important;
    border-radius: 9px !important;
    font-size: 13.5px !important;
    color: #0F172A !important;
    font-family: inherit !important;
    box-shadow: none !important;
    transition: all 0.15s ease !important;
    box-sizing: border-box !important;
}

.lg-add-staff-wrapper select.form-control,
.lg-add-staff-wrapper select {
    appearance: none !important;
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center !important;
    padding-right: 34px !important;
    cursor: pointer !important;
}

.lg-add-staff-wrapper select[multiple] {
    height: auto !important;
    min-height: 80px !important;
    background-image: none !important;
    padding: 8px 12px !important;
}

.lg-add-staff-wrapper input[type="file"] {
    height: auto !important;
    padding: 8px 12px !important;
    background: #F8FAFC !important;
    border: 1px dashed #CBD5E1 !important;
    border-radius: 9px !important;
    cursor: pointer !important;
}

.lg-add-staff-wrapper input:focus,
.lg-add-staff-wrapper select:focus {
    border-color: var(--lg-accent) !important;
    background-color: #FFFFFF !important;
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12) !important;
}

.lg-help-text {
    font-size: 12px;
    color: #64748B;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 3px;
}

.telecaller-only {
    display: none;
}

/* Actions Row at Bottom of Single Card */
.lg-single-actions-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    margin-top: 28px;
    border-top: 1px solid #F1F5F9;
}

.lg-btn-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: var(--lg-accent) !important;
    color: #FFFFFF !important;
    font-size: 14px;
    font-weight: 600;
    padding: 10px 26px;
    border-radius: 10px;
    border: none !important;
    box-shadow: 0 2px 8px rgba(20, 100, 244, 0.25);
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-btn-save:hover {
    background: #0F52CC !important;
    color: #FFFFFF !important;
    box-shadow: 0 4px 14px rgba(20, 100, 244, 0.35);
}

.lg-btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: #FFFFFF !important;
    color: #475569 !important;
    font-size: 14px;
    font-weight: 600;
    padding: 9.5px 22px;
    border-radius: 10px;
    border: 1px solid #CBD5E1 !important;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
}

.lg-btn-cancel:hover {
    background: #F8FAFC !important;
    border-color: #94A3B8 !important;
    color: #0F172A !important;
}

/* Custom Single-Select Dropdown Styling (LeadGER Reusable Dropdown) */
.lg-custom-dropdown-wrap {
    position: relative;
    width: 100%;
}

.lg-custom-dropdown-wrap.open {
    z-index: 1060;
}

.lg-field-group.dropdown-open,
.lg-field-group:has(.lg-custom-dropdown-wrap.open) {
    position: relative;
    z-index: 1060;
}

.lg-custom-dropdown-trigger {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    background-color: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    outline: none;
    box-sizing: border-box;
}

.lg-custom-dropdown-trigger:hover,
.lg-custom-dropdown-trigger:focus,
.lg-custom-dropdown-wrap.open .lg-custom-dropdown-trigger {
    background-color: #FFFFFF;
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(20, 100, 244, 0.12);
}

.lg-custom-dropdown-arrow {
    transition: transform 0.2s ease;
    flex-shrink: 0;
    margin-left: 8px;
}

.lg-custom-dropdown-wrap.open .lg-custom-dropdown-arrow {
    transform: rotate(180deg);
    stroke: var(--lg-accent);
}

.lg-custom-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    width: 100%;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    z-index: 1070;
    max-height: 220px;
    overflow-y: auto;
    padding: 6px;
    box-sizing: border-box;
}

.lg-custom-dropdown-wrap.open .lg-custom-dropdown-menu {
    display: block;
}

.lg-custom-dropdown-option {
    padding: 9px 12px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0F172A;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lg-custom-dropdown-option:hover {
    background-color: #F8FAFC;
    color: var(--lg-accent);
}

.lg-custom-dropdown-option.selected {
    background-color: #EFF6FF;
    color: var(--lg-accent);
    font-weight: 600;
}
</style>

<div class="lg-add-staff-wrapper">

    <!-- ONE SINGLE MAIN CARD CONTAINER FOR THE WHOLE FORM -->
    <div class="lg-single-main-card">

        <!-- HEADER INSIDE SINGLE CONTAINER -->
        <div class="lg-header-row">
            <div class="lg-header-title-box">
                <div class="lg-header-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="16" y1="11" x2="22" y2="11"/>
                    </svg>
                </div>
                <div>
                    <h1 class="lg-header-h1">Add New Staff</h1>
                    <p class="lg-header-sub">Create a new staff member account and assign permissions</p>
                </div>
            </div>
            <div class="lg-breadcrumb">
                <a href="{{ route('adminHome') }}">Home</a>
                <span>/</span>
                <a href="{{ route('users') }}">Users</a>
                <span>/</span>
                <span>Add Staff</span>
            </div>
        </div>

        {{ Form::open(['route' => ['usersStore'], 'method' => 'POST', 'files' => true]) }}

        <!-- SECTION 1: STAFF INFORMATION -->
        <div class="lg-section-heading">
            <div class="lg-section-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <h2 class="lg-section-title">Staff Information</h2>
        </div>

        <div class="lg-form-grid">
            <div class="lg-field-group">
                <label for="name" class="lg-label">
                    <span>{!! __('backend.fullName') !!}</span> <span class="lg-req">*</span>
                </label>
                {!! Form::text('name', '', ['placeholder' => 'Enter full name', 'class' => 'form-control', 'id' => 'name', 'required' => '']) !!}
            </div>

            <div class="lg-field-group">
                <label for="email" class="lg-label">
                    <span>{!! __('backend.loginEmail') !!}</span> <span class="lg-req">*</span>
                </label>
                {!! Form::email('email', '', ['placeholder' => 'name@company.com', 'class' => 'form-control', 'id' => 'email', 'required' => '']) !!}
            </div>

            <div class="lg-field-group">
                <label for="mobile" class="lg-label">
                    <span>{!! __('Mobile Number') !!}</span> <span class="lg-req">*</span>
                </label>
                {!! Form::tel('mobile', '', ['placeholder' => 'e.g. 9876543210', 'class' => 'form-control', 'id' => 'mobile', 'required' => '']) !!}
            </div>

            <div class="lg-field-group">
                <label for="password" class="lg-label">
                    <span>{!! __('backend.loginPassword') !!}</span> <span class="lg-req">*</span>
                </label>
                {!! Form::text('password', '', ['placeholder' => 'Set login password', 'class' => 'form-control', 'id' => 'password', 'required' => '']) !!}
            </div>

            <div class="lg-field-group full-width">
                <label for="photo" class="lg-label">
                    <span>{!! __('backend.personalPhoto') !!}</span>
                </label>
                {!! Form::file('photo', ['class' => 'form-control', 'id' => 'photo', 'accept' => 'image/*']) !!}
                <div class="lg-help-text">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>{!! __('backend.imagesTypes') !!}</span>
                </div>
            </div>
        </div>

        <div class="lg-section-divider"></div>

        <!-- SECTION 2: ACCESS & PERMISSIONS -->
        <div class="lg-section-heading">
            <div class="lg-section-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h2 class="lg-section-title">Access & Permissions</h2>
        </div>

        <div class="lg-form-grid">
            <div class="lg-field-group">
                <label for="permissions_id" class="lg-label">
                    <span>Role</span> <span class="lg-req">*</span>
                </label>
                <div class="lg-custom-dropdown-wrap" data-select-id="permissions_id">
                    <input type="hidden" name="permissions_id" id="permissions_id" value="" required>
                    <div class="lg-custom-dropdown-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                        <span class="lg-custom-dropdown-label">Select Role</span>
                        <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="lg-custom-dropdown-menu" role="listbox">
                        <div class="lg-custom-dropdown-option selected" data-val="" role="option">
                            <span>Select Role</span>
                        </div>
                        @foreach ($Permissions as $Permission)
                            <div class="lg-custom-dropdown-option" data-val="{{ $Permission->id }}" role="option">
                                <span>{{ $Permission->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="lg-field-group telecaller-only">
                <label for="role_id" class="lg-label">
                    <span>Role</span>
                </label>
                <select name="role_id" id="role_id" class="form-control c-select">
                    <option value="">Select role</option>
                    @foreach ($Roles as $Role)
                        <option value="{{ $Role->id }}">{{ $Role->role_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg-field-group">
                <label for="team_id" class="lg-label">
                    <span>Teams</span> <span class="lg-req">*</span>
                </label>
                <select name="team_id[]" id="team_id" multiple required class="form-control c-select">
                    <option value="">- - Select Teams - -</option>
                    @foreach ($Teams as $Team)
                        <option value="{{ $Team->id }}">{{ $Team->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg-field-group">
                <label for="work_location_id" class="lg-label">
                    <span>Work Location</span> <span class="lg-req">*</span>
                </label>
                <select name="work_location_id" id="work_location_id" required class="form-control c-select">
                    @foreach ($WorkLocations as $WorkLocation)
                        <option value="{{ $WorkLocation->id }}">{{ $WorkLocation->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg-field-group">
                <label for="shift_id" class="lg-label">
                    <span>Shift</span> <span class="lg-req">*</span>
                </label>
                <div class="lg-custom-dropdown-wrap" data-select-id="shift_id">
                    <input type="hidden" name="shift_id" id="shift_id" value="1" required>
                    <div class="lg-custom-dropdown-trigger" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                        <span class="lg-custom-dropdown-label">Regular – 9:00 AM – 6:00 PM</span>
                        <svg class="lg-custom-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="lg-custom-dropdown-menu" role="listbox">
                        <div class="lg-custom-dropdown-option selected" data-val="1" role="option" aria-selected="true">
                            <span>Regular – 9:00 AM – 6:00 PM</span>
                            <svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: TELECALLER & BANKING DETAILS (Telecaller Only) -->
        <div class="telecaller-only">
            <div class="lg-section-divider"></div>
            <div class="lg-section-heading">
                <div class="lg-section-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </div>
                <h2 class="lg-section-title">Telecaller & Financial Details</h2>
            </div>

            <div class="lg-form-grid">
                <div class="lg-field-group">
                    <label for="languages_known" class="lg-label">
                        <span>Languages Known</span>
                    </label>
                    {!! Form::text('languages_known', '', ['placeholder' => 'Enter languages (e.g., English, Spanish)', 'class' => 'form-control', 'id' => 'languages_known']) !!}
                </div>

                <div class="lg-field-group">
                    <label for="account_number" class="lg-label">
                        <span>Account Number</span>
                    </label>
                    {!! Form::text('account_number', '', ['placeholder' => 'Enter Account Number', 'class' => 'form-control', 'id' => 'account_number']) !!}
                </div>

                <div class="lg-field-group">
                    <label for="ifsc_code" class="lg-label">
                        <span>IFSC Code</span>
                    </label>
                    {!! Form::text('ifsc_code', '', ['placeholder' => 'Enter IFSC Code', 'class' => 'form-control', 'id' => 'ifsc_code']) !!}
                </div>

                <div class="lg-field-group">
                    <label for="branch_name" class="lg-label">
                        <span>Branch Name</span>
                    </label>
                    {!! Form::text('branch_name', '', ['placeholder' => 'Branch Name', 'class' => 'form-control', 'id' => 'branch_name']) !!}
                </div>

                <div class="lg-field-group">
                    <label for="upi_id" class="lg-label">
                        <span>UPI ID</span>
                    </label>
                    {!! Form::text('upi_id', '', ['placeholder' => 'UPI ID', 'class' => 'form-control', 'id' => 'upi_id']) !!}
                </div>
            </div>
        </div>

        <div class="lg-section-divider"></div>

        <!-- SECTION 4: SYSTEM INTEGRATION -->
        <div class="lg-section-heading">
            <div class="lg-section-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            <h2 class="lg-section-title">System Integration</h2>
        </div>

        <div class="lg-form-grid">
            <div class="lg-field-group">
                <label for="sms_gateway_id" class="lg-label">
                    <span>SMS Gateway</span>
                </label>
                <select name="sms_gateway_id" id="sms_gateway_id" class="form-control c-select">
                    @foreach ($SmsGateways as $SmsGateway)
                        <option value="{{ $SmsGateway->id }}">{{ $SmsGateway->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg-field-group">
                <label for="email_setting_id" class="lg-label">
                    <span>Email Setting</span>
                </label>
                <select name="email_setting_id" id="email_setting_id" class="form-control c-select">
                    @foreach ($EmailSettings as $EmailSetting)
                        <option value="{{ $EmailSetting->id }}">{{ $EmailSetting->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- BOTTOM ACTION BUTTONS INSIDE SINGLE CONTAINER -->
        <div class="lg-single-actions-row">
            <a href="{{ route('users') }}" class="lg-btn-cancel">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <span>{!! __('backend.cancel') !!}</span>
            </a>
            <button type="submit" class="lg-btn-save">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Create Staff</span>
            </button>
        </div>

        {{ Form::close() }}

    </div>
</div>
@endsection

@php
    $telecallerPermission = $Permissions->where('name', 'Telecaller')->first();
    $telecallerPermissionId = $telecallerPermission ? $telecallerPermission->id : '';
@endphp

@push("after-scripts")
<script>
    $(document).ready(function () {
        const telecallerFields = document.querySelectorAll(".telecaller-only");
        const telecallerPermissionName = "7";

        function toggleFields(selectedPermissionName) {
            if (selectedPermissionName === telecallerPermissionName) {
                telecallerFields.forEach(field => field.style.display = "block");
            } else {
                telecallerFields.forEach(field => field.style.display = "none");
            }
        }

        toggleFields($("#permissions_id").val());

        $('#permissions_id').on('change', function () {
            const selectedPermissionName = $(this).val();
            toggleFields(selectedPermissionName);
        });

        // LeadGER Custom Dropdown Interactive Handling
        $(document).on('click', '.lg-custom-dropdown-trigger', function (e) {
            e.stopPropagation();
            const $wrap = $(this).closest('.lg-custom-dropdown-wrap');
            const isOpen = $wrap.hasClass('open');

            // Close any other open custom dropdowns
            $('.lg-custom-dropdown-wrap').not($wrap).removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
            $('.dropdown-open').removeClass('dropdown-open');

            if (isOpen) {
                $wrap.removeClass('open');
                $wrap.closest('.lg-field-group').removeClass('dropdown-open');
                $(this).attr('aria-expanded', 'false');
            } else {
                $wrap.addClass('open');
                $wrap.closest('.lg-field-group').addClass('dropdown-open');
                $(this).attr('aria-expanded', 'true');
            }
        });

        $(document).on('click', '.lg-custom-dropdown-option', function (e) {
            e.stopPropagation();
            const $option = $(this);
            const $wrap = $option.closest('.lg-custom-dropdown-wrap');
            const selectId = $wrap.data('select-id');
            const val = $option.data('val');
            const text = $.trim($option.find('span').text() || $option.text());

            // 1. Update native hidden select value for form submit
            $('#' + selectId).val(val).trigger('change');

            // 2. Update visual label
            $wrap.find('.lg-custom-dropdown-label').text(text);

            // 3. Highlight selected option with checkmark
            $wrap.find('.lg-custom-dropdown-option').removeClass('selected').find('.lg-option-check').remove();
            $option.addClass('selected');
            if (val && !$option.find('.lg-option-check').length) {
                $option.append('<svg class="lg-option-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
            }

            // 4. Close dropdown
            $wrap.removeClass('open');
            $wrap.closest('.lg-field-group').removeClass('dropdown-open');
            $wrap.find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function () {
            $('.lg-custom-dropdown-wrap').removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
            $('.dropdown-open').removeClass('dropdown-open');
        });

        // Keyboard support: Escape to close, Enter/Space/ArrowDown
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $('.lg-custom-dropdown-wrap').removeClass('open').find('.lg-custom-dropdown-trigger').attr('aria-expanded', 'false');
                $('.dropdown-open').removeClass('dropdown-open');
            }
        });

        $(document).on('keydown', '.lg-custom-dropdown-trigger', function (e) {
            const $trigger = $(this);
            const $wrap = $trigger.closest('.lg-custom-dropdown-wrap');
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $trigger.trigger('click');
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!$wrap.hasClass('open')) {
                    $trigger.trigger('click');
                }
            }
        });
    });
</script>
@endpush
