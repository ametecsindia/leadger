@extends('dashboard.layouts.master')
@section('title', 'Create Campaign')

@push("after-styles")
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Dashboard Canvas */
.lg-campaign-canvas {
    padding: 24px;
    background-color: #F8FAFD;
    min-height: calc(100vh - 60px);
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Page Header Card */
.lg-header-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
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
    background: linear-gradient(135deg, var(--lg-accent) 0%, var(--lg-accent-hover) 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    flex-shrink: 0;
}
.lg-header-title {
    margin: 0;
    font-weight: 700;
    color: #0F172A;
    font-size: 22px;
    line-height: 1.2;
}
.lg-header-sub {
    font-size: 13px;
    color: #64748B;
    margin-top: 3px;
    font-weight: 500;
}
.lg-breadcrumb {
    font-size: 13px;
    color: #64748B;
}
.lg-breadcrumb a {
    color: var(--lg-accent);
    text-decoration: none;
    font-weight: 600;
}
.lg-breadcrumb a:hover {
    text-decoration: underline;
}
.lg-breadcrumb .sep {
    margin: 0 6px;
    color: #CBD5E1;
}

/* Main Form Card */
.lg-form-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    margin-bottom: 30px;
}

/* Clean Responsive Grid Layouts */
.lg-form-grid-3col {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 992px) {
    .lg-form-grid-3col {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 640px) {
    .lg-form-grid-3col {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}

.lg-row-campaign-name {
    margin-bottom: 20px;
}

.lg-row-dates-team {
    margin-bottom: 24px;
}

.lg-field-group {
    display: flex;
    flex-direction: column;
    gap: 7px; /* 6-8px gap between label and its field */
}
.lg-label {
    font-size: 13px;
    font-weight: 600;
    color: #1E293B;
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    line-height: 1.2;
}
.lg-label .req {
    color: #EF4444;
}

/* Uniform Input & Select Wrappers with Clean Centered Outline Icons */
.lg-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.lg-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748B; /* muted blue-grey */
    font-size: 15px;
    line-height: 1;
    pointer-events: none;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}
.lg-input-icon svg,
svg.lg-input-icon {
    width: 18px !important;
    height: 18px !important;
    stroke: #64748B !important;
    stroke-width: 1.8 !important;
    flex-shrink: 0;
}
.lg-input, .lg-select {
    width: 100%;
    height: 44px;
    line-height: 44px;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0F172A;
    background-color: #FFFFFF;
    outline: none;
    transition: all 0.15s ease;
    font-family: inherit;
    box-sizing: border-box;
    text-indent: 0;
}
.lg-input {
    padding: 0 16px 0 46px !important; /* 14px left + 18px icon + 14px clear gap = 46px */
}
.lg-textarea {
    width: 100%;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0F172A;
    background-color: #FFFFFF;
    outline: none;
    transition: all 0.15s ease;
    font-family: inherit;
    box-sizing: border-box;
}
.lg-input::placeholder, .lg-textarea::placeholder {
    color: #94A3B8;
    opacity: 1;
}
.lg-input:focus, .lg-select:focus, .lg-textarea:focus {
    border-color: var(--lg-accent);
    box-shadow: 0 0 0 3px rgba(23, 105, 255, 0.12);
    background-color: #FFFFFF;
}

/* app.js turns the dropdowns into Select2: make them fill their column like the text inputs */
.lg-input-wrap .select2-container { width: 100% !important; }

/* Select Arrow Alignment */
.lg-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-left: 14px !important; /* dropdowns have no icon */
    padding-right: 42px !important; /* 42px right padding gives clear margin before chevron */
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: calc(100% - 14px) center;
    background-size: 14px;
    cursor: pointer;
}

/* Custom Dropzone / Upload Area */
.lg-file-box {
    border: 2px dashed #CBD5E1;
    border-radius: 12px;
    padding: 24px 20px;
    background: #F8FAFC;
    text-align: center;
    transition: all 0.15s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    box-sizing: border-box;
    cursor: pointer;
    position: relative;
}
.lg-file-box:hover {
    border-color: var(--lg-accent);
    background: #F0F7FF;
}
.lg-file-box label {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    cursor: pointer;
    margin: 0;
    gap: 6px;
}
.lg-file-box .upload-icon {
    font-size: 32px;
    color: var(--lg-accent);
    margin-bottom: 6px;
    display: block;
}
.lg-file-box .upload-main-text {
    font-size: 13.5px;
    font-weight: 600;
    color: #1E293B;
    display: block;
    text-align: center;
}
.lg-file-box .upload-sub-text {
    font-size: 12px;
    color: #64748B;
    margin-top: 4px;
    display: block;
    text-align: center;
}
.lg-sample-download-link {
    color: var(--lg-accent) !important;
    font-weight: 600;
    text-decoration: underline !important;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-block;
}
.lg-sample-download-link:hover {
    color: #0F52CC !important;
    text-decoration: underline !important;
}
.lg-file-selected-box {
    display: none;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 12px 16px;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-radius: 10px;
    box-sizing: border-box;
}
.lg-file-selected-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.lg-file-selected-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--lg-accent);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.lg-file-selected-name {
    font-size: 13.5px;
    font-weight: 600;
    color: #0F172A;
}
.lg-file-selected-size {
    font-size: 11.5px;
    color: #64748B;
}

/* Telecallers Section */
.lg-section-header {
    font-size: 14px;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 14px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lg-section-header svg,
.lg-section-header i {
    color: var(--lg-accent);
}

.lg-telecaller-count-badge {
    font-size: 12px;
    font-weight: 600;
    color: var(--lg-accent);
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    padding: 2px 8px;
    border-radius: 12px;
    margin-left: 6px;
}

.lg-telecaller-select-all {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px 0;
    font-size: 13px;
    font-weight: 600;
    color: #0F172A;
    cursor: pointer;
    user-select: none;
}
.lg-telecaller-select-all.is-hidden { display: none !important; }   /* labels get display !important from the theme */
.lg-telecaller-select-all input[type="checkbox"] {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: #1464F4;
    cursor: pointer;
}
.lg-telecaller-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}
.lg-telecaller-chip {
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 10px 14px;
    background: #FFFFFF;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
    margin: 0;
}
.lg-telecaller-chip:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
}
.lg-telecaller-chip:has(input:checked),
.lg-telecaller-chip.selected {
    border-color: var(--lg-accent) !important;
    background: #EFF6FF !important;
    box-shadow: 0 0 0 1px var(--lg-accent) !important;
}
.lg-telecaller-chip input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--lg-accent);
    cursor: pointer;
    margin: 0;
    flex-shrink: 0;
}
.lg-telecaller-name {
    font-size: 13px;
    font-weight: 600;
    color: #1E293B;
}

.lg-telecaller-notice {
    padding: 16px 20px;
    background: #F8FAFC;
    border: 1px dashed #CBD5E1;
    border-radius: 10px;
    color: #64748B;
    font-size: 13.5px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    box-sizing: border-box;
    margin-bottom: 24px;
}
.lg-telecaller-notice svg,
.lg-telecaller-notice i {
    font-size: 16px;
    color: #94A3B8;
    flex-shrink: 0;
}

/* Textarea Box */
.lg-textarea-wrap {
    position: relative;
    width: 100%;
}
.lg-textarea {
    height: 100px;
    padding: 12px 14px !important;
    resize: vertical;
}
.lg-char-counter {
    position: absolute;
    bottom: 10px;
    right: 14px;
    font-size: 11px;
    color: #94A3B8;
    background: #FFFFFF;
    padding: 2px 6px;
    border-radius: 4px;
}

/* Switches & Checkboxes */
.lg-switch-group {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    margin: 16px 0 24px 0;
    padding: 14px 18px;
    background: #F8FAFC;
    border-radius: 10px;
    border: 1px solid #F1F5F9;
}
.lg-checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    margin: 0;
    user-select: none;
}
.lg-checkbox-label input[type="checkbox"] {
    width: 17px;
    height: 17px;
    accent-color: var(--lg-accent);
    cursor: pointer;
    margin: 0;
}

/* Action Footer Buttons */
.lg-action-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #F1F5F9;
}
.lg-btn {
    height: 42px;
    padding: 0 22px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
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

.alert-danger-box {
    background: #FEF2F2;
    border: 1px solid #FCA5A5;
    border-radius: 10px;
    padding: 12px 16px;
    color: #991B1B;
    font-size: 13px;
    margin-bottom: 20px;
}
</style>
@endpush

@section('content')
<div class="lg-campaign-canvas">

    <!-- Page Header Card -->
    <div class="lg-header-card">
        <div class="lg-header-left">
            <div class="lg-header-icon">
                <i class="fa fa-bullhorn"></i>
            </div>
            <div>
                <h1 class="lg-header-title">Create Campaign</h1>
                <div class="lg-header-sub">Set up a campaign and choose who calls it</div>
            </div>
        </div>
        <div class="lg-breadcrumb">
            <a href="{{ route('campaigns') }}">Campaigns</a>
            <span class="sep">/</span>
            <span class="current" style="color: #64748B; font-weight: 500;">New</span>
        </div>
    </div>

    @if(session('errorMessage'))
    <div class="alert-danger-box">
        <i class="fa fa-exclamation-triangle me-1"></i> {{ session('errorMessage') }}
    </div>
    @endif

    <!-- Main Form Card -->
    <div class="lg-form-card">
        @php
            $Products = $Products ?? \App\Models\ProductService::where('status', 1)->orderby('id','asc')->get();
            $teamMembersMap = [];
            if (isset($Teams)) {
                foreach ($Teams as $t) {
                    $teamMembersMap[$t->id] = $t->users()->where('status', 1)->select('users.id', 'users.name')->get()->map(function($u) {
                        return ['id' => $u->id, 'name' => $u->name];
                    })->values()->toArray();
                }
            }
            $oldTelecallers = old('telecallers', []);
        @endphp

        {{ Form::open(['route' => ['campaignsStore'], 'method' => 'POST', 'files' => true, 'id' => 'createCampaignForm']) }}

        <!-- 1. Campaign Name (Full Row) -->
        <div class="lg-field-group lg-row-campaign-name">
            <label for="name" class="lg-label">Campaign name <span class="req">*</span></label>
            <div class="lg-input-wrap">
                <svg class="lg-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <line x1="10" y1="9" x2="8" y2="9"></line>
                </svg>
                {!! Form::text('name', '', [
                    'placeholder' => 'Enter campaign name / For example: October Home Loan',
                    'class' => 'lg-input',
                    'id' => 'name',
                    'required' => '',
                    'maxlength' => 50,
                    'autocomplete' => 'off'
                ]) !!}
            </div>
        </div>

        <!-- 2. Independent Dropdowns Row: Products, Source, Segments (equal widths, 20px gap) -->
        <div class="lg-form-grid-3col">

            <!-- Products Dropdown -->
            <div class="lg-field-group">
                <label for="product_id" class="lg-label">Products <span class="req">*</span></label>
                <div class="lg-input-wrap">
                    <select name="product_id" id="product_id" class="lg-select">
                        <option value="">- - Select Product - -</option>
                        @foreach($Products as $Product)
                        <option value="{!! $Product->id !!}">{!! $Product->name !!}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Source Dropdown -->
            <div class="lg-field-group">
                <label for="source_id" class="lg-label">Source <span class="req">*</span></label>
                <div class="lg-input-wrap">
                    <select name="source_id" id="source_id" required class="lg-select">
                        <option value="">- - Select Source - -</option>
                        @foreach($Sources as $Source)
                        <option value="{!! $Source->id !!}">{!! $Source->name !!}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Segments Dropdown -->
            <div class="lg-field-group">
                <label for="segment_id" class="lg-label">Segments <span class="req">*</span></label>
                <div class="lg-input-wrap">
                    <select name="segment_id" id="segment_id" required class="lg-select">
                        <option value="">- - Select Segment - -</option>
                        @foreach($Segments as $Segment)
                        <option value="{!! $Segment->id !!}">{!! $Segment->name !!}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>

        <!-- 3. Dates & Team Row: Start Date, End Date, Team (equal widths, 20px gap, 24px bottom gap) -->
        <div class="lg-form-grid-3col lg-row-dates-team">
            
            <!-- Start Date -->
            <div class="lg-field-group">
                <label for="start_date" class="lg-label">Start date <span class="req">*</span></label>
                <div class="lg-input-wrap">
                    <svg class="lg-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <input type="text" id="start_date" name="start_date" class="lg-input" value="{{ date('d-m-Y') }}" placeholder="DD-MM-YYYY">
                </div>
            </div>

            <!-- End Date (Expiry) -->
            <div class="lg-field-group">
                <label for="date" class="lg-label">End date (Expiry) <span class="req">*</span></label>
                <div class="lg-input-wrap">
                    <svg class="lg-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <polyline points="9 16 11 18 15 14"></polyline>
                    </svg>
                    {!! Form::text('date', date("Y-m-t"), [
                        'placeholder' => 'YYYY-MM-DD',
                        'class' => 'lg-input',
                        'id' => 'date',
                        'required' => ''
                    ]) !!}
                </div>
            </div>

            <!-- Team -->
            <div class="lg-field-group">
                <label for="team_id" class="lg-label">Team <span class="req">*</span></label>
                <div class="lg-input-wrap">
                    <select name="team_id" id="team_id" required class="lg-select">
                        <option value="">- - Select Team - -</option>
                        @foreach($Teams as $Team)
                        <option value="{!! $Team->id !!}">{!! $Team->name !!}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>

        <!-- Telecallers Selection Section (24px gap above) -->
        <div class="lg-section-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1464F4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            <span>Telecallers</span>
            <span class="lg-telecaller-count-badge" id="telecallerCountBadge" style="display: none;"></span>
        </div>

        <!-- Telecaller Message / Notice State -->
        <div id="telecallerMessageContainer"></div>

        <!-- Select All (shown when the selected team has telecallers) -->
        <label class="lg-telecaller-select-all is-hidden" id="telecallerSelectAllWrap">
            <input type="checkbox" id="telecallerSelectAll" onchange="toggleAllTelecallers(this.checked)">
            <span>Select All</span>
        </label>

        <!-- Telecallers Grid -->
        <div class="lg-telecaller-grid" id="telecallerGrid"></div>

        <!-- Lead CSV File Upload Section (Clean Custom Upload UI) -->
        <div class="lg-field-group mb-4">
            <label class="lg-label">Upload Lead List (CSV)</label>
            <div class="lg-file-box" id="fileDropZone">
                <input type="file" name="file" id="file" accept=".csv" onchange="updateFileName(this)" style="display: none !important; visibility: hidden !important;">
                
                <div id="fileUploadPrompt" style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
                    <label for="file" style="margin: 0; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
                        <i class="fa fa-cloud-upload upload-icon"></i>
                        <span class="upload-main-text">Click or drag CSV file here to upload</span>
                    </label>
                    <div class="upload-sub-text" style="margin-top: 6px;">
                        Extension: .csv &nbsp;|&nbsp;
                        <a href="{{ route('campaignsSampleFile') }}" download="sample_leads.csv" class="lg-sample-download-link" onclick="downloadSampleLeadCsv(event)">Download Sample CSV File</a>
                    </div>
                </div>

                <div id="selectedFileContainer" class="lg-file-selected-box">
                    <div class="lg-file-selected-info">
                        <div class="lg-file-selected-icon">
                            <i class="fa fa-file-text-o"></i>
                        </div>
                        <div>
                            <div class="lg-file-selected-name" id="selectedFileName">file.csv</div>
                            <div class="lg-file-selected-size" id="selectedFileSize">0 KB</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSelectedFile(event)" style="border-radius: 6px; font-size: 12px; font-weight: 600;">
                        <i class="fa fa-trash me-1"></i> Remove File
                    </button>
                </div>

            </div>
        </div>

        <!-- Switches & Options -->
        <div class="lg-switch-group">
            <label class="lg-checkbox-label">
                {!! Form::checkbox('is_public', '1', false, ['id' => 'is_public']) !!}
                <span>Public Leads</span>
            </label>

            <label class="lg-checkbox-label">
                {!! Form::checkbox('lead_info_edit', '1', false, ['id' => 'lead_info_edit']) !!}
                <span>Lead Info Edit</span>
            </label>

            <label class="lg-checkbox-label">
                {!! Form::checkbox('number_masking', '1', false, ['id' => 'number_masking']) !!}
                <span>Number Masking</span>
            </label>
        </div>

        <!-- Description / FAQ Section -->
        <div class="lg-field-group mb-4">
            <label for="faq" class="lg-label">
                <i class="fa fa-file-text-o text-primary"></i> Description
            </label>
            <div class="lg-textarea-wrap">
                {!! Form::textarea('faq', '', [
                    'placeholder' => 'Enter campaign description (optional)...',
                    'class' => 'lg-textarea',
                    'id' => 'faq',
                    'rows' => 3,
                    'maxlength' => 500,
                    'onkeyup' => 'updateCharCount(this)'
                ]) !!}
                <span class="lg-char-counter"><span id="charCount">0</span>/500</span>
            </div>
        </div>

        <!-- Action Section Buttons -->
        <div class="lg-action-footer">
            <a href="{{ route('campaigns') }}" class="lg-btn lg-btn-secondary">
                <i class="fa fa-times"></i> Cancel
            </a>
            <button type="submit" class="lg-btn lg-btn-primary" id="saveCampaignBtn">
                <i class="fa fa-save"></i> <span>Save Campaign</span>
            </button>
        </div>

        {{ Form::close() }}
    </div>

</div>
@endsection

@push("after-scripts")
<script type="text/javascript">
const teamMembersMap = {!! json_encode($teamMembersMap) !!};

function toggleChip(element) {
    if (!element) return;
    const chk = element.querySelector('input[type="checkbox"]');
    if (chk) {
        if (chk.checked) {
            element.classList.add('selected');
        } else {
            element.classList.remove('selected');
        }
    }
    updateTelecallerBadge();
}

function updateTelecallerBadge() {
    const checkedCount = $('#telecallerGrid input[name="telecallers[]"]:checked').length;
    const totalCount = $('#telecallerGrid input[name="telecallers[]"]').length;
    const $badge = $('#telecallerCountBadge');
    if (totalCount > 0) {
        $badge.text(checkedCount + ' of ' + totalCount + ' selected').show();
    } else {
        $badge.hide();
    }
    // Select All follows the individual choices: all ticked = checked, some = indeterminate, none = unchecked
    const all = document.getElementById('telecallerSelectAll');
    $('#telecallerSelectAllWrap').toggleClass('is-hidden', totalCount === 0);
    if (all) {
        all.checked = totalCount > 0 && checkedCount === totalCount;
        all.indeterminate = checkedCount > 0 && checkedCount < totalCount;
    }
}

function toggleAllTelecallers(checked) {
    $('#telecallerGrid input[name="telecallers[]"]').each(function () {
        this.checked = checked;
        $(this).closest('label').toggleClass('selected', checked);
    });
    updateTelecallerBadge();
}

function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

function handleTeamChange() {
    const teamId = $('#team_id').val();
    
    // Remember currently ticked telecaller IDs so they are preserved IF they belong to the new team
    const preservedIds = new Set();
    $('#telecallerGrid input[name="telecallers[]"]:checked').each(function() {
        preservedIds.add(String($(this).val()));
    });
    
    renderTelecallers(teamId, preservedIds);
}

function renderTelecallers(teamId, preservedIds = new Set()) {
    const $grid = $('#telecallerGrid');
    const $msg = $('#telecallerMessageContainer');
    
    if (!teamId) {
        $grid.hide().empty();
        $msg.show().html(`
            <div class="lg-telecaller-notice">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Please select a team above to view telecallers</span>
            </div>
        `);
        updateTelecallerBadge();
        return;
    }
    
    const members = teamMembersMap[teamId] || [];
    
    if (members.length === 0) {
        $grid.hide().empty();
        $msg.show().html(`
            <div class="lg-telecaller-notice">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>No telecallers in this team</span>
            </div>
        `);
        updateTelecallerBadge();
        return;
    }
    
    $msg.hide().empty();
    $grid.empty().show();
    
    members.forEach(function(member) {
        const isChecked = preservedIds.has(String(member.id));
        const chipHtml = `
            <label class="lg-telecaller-chip ${isChecked ? 'selected' : ''}" onclick="toggleChip(this)">
                <input type="checkbox" name="telecallers[]" value="${member.id}" ${isChecked ? 'checked' : ''} onchange="toggleChip(this.closest('label'))">
                <span class="lg-telecaller-name">${escapeHtml(member.name)}</span>
            </label>
        `;
        $grid.append(chipHtml);
    });
    
    updateTelecallerBadge();
}

function updateFileName(input) {
    let container = document.getElementById('selectedFileContainer');
    let prompt = document.getElementById('fileUploadPrompt');
    if (input.files && input.files[0]) {
        let file = input.files[0];
        document.getElementById('selectedFileName').innerText = file.name;
        document.getElementById('selectedFileSize').innerText = (file.size / 1024).toFixed(1) + ' KB';
        prompt.style.display = 'none';
        container.style.display = 'flex';
    }
}

function removeSelectedFile(event) {
    if (event) event.stopPropagation();
    let input = document.getElementById('file');
    input.value = '';
    document.getElementById('selectedFileContainer').style.display = 'none';
    document.getElementById('fileUploadPrompt').style.display = 'flex';
}

function updateCharCount(textarea) {
    document.getElementById('charCount').innerText = textarea.value.length;
}

/**
 * Downloads the sample lead CSV matching the exact structure and example data
 * used by Campaign Hub / Upload Leads submenu.
 */
function downloadSampleLeadCsv(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Exactly matches Upload Leads submenu (leads/create.blade.php) structure & data
    const sampleCsvData = "NAME,DESIGNATION,PHONE-NUMBER,PHONE-NUMBER2,COMPANY,DESCRIPTION,COUNTRY,PIN CODE,CITY,STATE,ADDRESS,EMAIL,WEBSITE,LEAD VALUE,POSITION,TAGS,USER_ID\n" +
        "Rajesh,,9154936351,9154936351,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\n" +
        "Sai kiran,,9154118359,9154118359,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\n" +
        "Madhu,,9154936355,9154936355,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,xyz.com,,,,\n" +
        "Vinitha ,,9121018749,9121018749,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\n" +
        "Ashish,,9154167650,9154167650,Ametecs,,India,500014,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\n" +
        "Sravani,,7386566623,,Ametecs,,India,500015,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\n" +
        "Akshitha,,7386566634,,Ametecs,,India,500016,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,\n" +
        "Feroz,,7386566637,,Ametecs,,India,500017,Hyderabad,Telangana,Hyderabad,xyz@gmail.com,,,,,";

    const blob = new Blob([sampleCsvData], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.setAttribute('download', 'sample_leads.csv');
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
    setTimeout(function() {
        URL.revokeObjectURL(downloadLink.href);
    }, 1500);

    if (typeof toastr !== 'undefined') {
        toastr.success('Sample file downloaded successfully!');
    }
}

$(document).ready(function () {
    // Listen for Team dropdown changes to immediately filter telecallers
    $('#team_id').on('change', handleTeamChange);

    // Initial render on page load
    const initialTeamId = $('#team_id').val();
    const initialPreserved = new Set({!! json_encode(array_map('strval', $oldTelecallers ?? [])) !!});
    renderTelecallers(initialTeamId, initialPreserved);

    const dropBox = document.getElementById('fileDropZone');
    if (dropBox) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropBox.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
                dropBox.style.borderColor = '#1464F4';
                dropBox.style.backgroundColor = '#F0F7FF';
            });
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropBox.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
                dropBox.style.borderColor = '#CBD5E1';
                dropBox.style.backgroundColor = '#F8FAFC';
            });
        });
        dropBox.addEventListener('drop', e => {
            e.preventDefault();
            e.stopPropagation();
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                const fileInput = document.getElementById('file');
                fileInput.files = e.dataTransfer.files;
                updateFileName(fileInput);
            }
        });
    }

    $('#createCampaignForm').on('submit', function() {
        let $btn = $('#saveCampaignBtn');
        $btn.prop('disabled', true);
        $btn.find('span').text('Saving Campaign...');
        $btn.find('i').removeClass('fa-save').addClass('fa-spinner fa-spin');
    });
});
</script>
@endpush
